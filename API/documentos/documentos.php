<?php

session_start();

header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../backend/models/Documento.php';
require_once __DIR__ . '/../../backend/models/Categoria.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$documento = new Documento();
$categoria = new Categoria();

$raizProyecto = dirname(__DIR__, 2);
$tamanoMaximo = 10 * 1024 * 1024;

// ======================================================
// RESPUESTA JSON
// ======================================================

function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($datos);

    exit;
}

// ======================================================
// VERIFICAR ADMINISTRADOR
// ======================================================

function verificarAdministrador(): bool
{
    $usuario = $_SESSION['usuario'] ?? [];

    return isset($usuario['cargo'])
        && strtolower(trim($usuario['cargo'])) === 'administrador';
}

// ======================================================
// RESOLVER RUTA SEGURA DEL ARCHIVO
// ======================================================

function resolverRutaArchivo(
    string $rutaRelativa,
    string $raizProyecto
): ?string {
    $raizUploads = realpath(
        $raizProyecto . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos'
    );

    if ($raizUploads === false) {
        return null;
    }

    $rutaRelativa = str_replace(
        ['\\', '/'],
        DIRECTORY_SEPARATOR,
        $rutaRelativa
    );

    $rutaFisica = realpath(
        $raizProyecto . DIRECTORY_SEPARATOR . $rutaRelativa
    );

    if ($rutaFisica === false) {
        return null;
    }

    $prefijo = $raizUploads . DIRECTORY_SEPARATOR;

    if (strpos($rutaFisica, $prefijo) !== 0) {
        return null;
    }

    return $rutaFisica;
}

// ======================================================
// MÉTODOS HTTP
// ======================================================

switch ($metodo) {

    // ==================================================
    // GET: LISTAR O MOSTRAR UN DOCUMENTO
    // ==================================================

    case 'GET':

        $idDocumento = $_GET['id'] ?? null;

        if ($idDocumento !== null) {

            $datosDocumento = $documento->buscarPorId($idDocumento);

            if ($datosDocumento === null) {
                responderJson([
                    'error' => 'Documento no encontrado'
                ], 404);
            }

            $rutaFisica = resolverRutaArchivo(
                $datosDocumento['ruta_archivo'],
                $raizProyecto
            );

            if (
                $rutaFisica === null ||
                !is_file($rutaFisica) ||
                !is_readable($rutaFisica)
            ) {
                responderJson([
                    'error' => 'El archivo no existe o no se puede leer'
                ], 404);
            }

            $nombreArchivo = str_replace(
                ["\r", "\n", '"', "\\"],
                '_',
                basename($datosDocumento['nombre_archivo'])
            );

            header('Content-Type: application/pdf');

            header(
                'Content-Disposition: inline; filename="' . $nombreArchivo . '"'
            );

            header('Content-Length: ' . filesize($rutaFisica));

            readfile($rutaFisica);
            exit;
        }

        responderJson($documento->obtenerTodos());

        break;

    // ==================================================
    // POST: SUBIR UN DOCUMENTO
    // ==================================================

    case 'POST':

        if (!verificarAdministrador()) {
            responderJson([
                'error' => 'No tiene permisos para subir documentos'
            ], 403);
        }

        $ciAdmin = $_SESSION['usuario']['ci_admin']
            ?? $_SESSION['ci_admin']
            ?? $_SESSION['ci']
            ?? null;

        if (!$ciAdmin) {
            responderJson([
                'error' => 'No se pudo identificar al administrador'
            ], 401);
        }

        $titulo = trim($_POST['documentTitle'] ?? '');
        $descripcion = trim($_POST['documentDescription'] ?? '');
        $idCategoria = trim($_POST['id_categoria'] ?? '');

        if ($titulo === '' || $idCategoria === '') {
            responderJson([
                'error' => 'Complete el título y seleccione una categoría válida'
            ], 400);
        }

        if (mb_strlen($titulo, 'UTF-8') > 50) {
            responderJson([
                'error' => 'El título no puede superar los 50 caracteres'
            ], 400);
        }

        if (mb_strlen($descripcion, 'UTF-8') > 50) {
            responderJson([
                'error' => 'La descripción no puede superar los 50 caracteres'
            ], 400);
        }

        // La categoría debe existir, ser de documentos y estar activa.
        if (!$categoria->existeCategoriaDocumentoActiva($idCategoria)) {
            responderJson([
                'error' => 'La categoría seleccionada no existe o está deshabilitada'
            ], 400);
        }

        if (!isset($_FILES['documentFile'])) {
            responderJson([
                'error' => 'Debe seleccionar un archivo PDF'
            ], 400);
        }

        $archivo = $_FILES['documentFile'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            responderJson([
                'error' => 'Se produjo un error al subir el archivo'
            ], 400);
        }

        if (
            !is_uploaded_file($archivo['tmp_name']) ||
            $archivo['size'] <= 0
        ) {
            responderJson([
                'error' => 'El archivo recibido no es válido'
            ], 400);
        }

        if ($archivo['size'] > $tamanoMaximo) {
            responderJson([
                'error' => 'El archivo supera el límite de 10 MB'
            ], 400);
        }

        // Comprobar el tipo MIME real del archivo.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            responderJson([
                'error' => 'No se pudo comprobar el archivo'
            ], 500);
        }

        $tipoMime = finfo_file($finfo, $archivo['tmp_name']);

        finfo_close($finfo);

        // Comprobar también la cabecera característica de un PDF.
        $handle = fopen($archivo['tmp_name'], 'rb');

        if ($handle === false) {
            responderJson([
                'error' => 'No se pudo leer el archivo'
            ], 400);
        }

        $cabecera = fread($handle, 5);

        fclose($handle);

        if ($tipoMime !== 'application/pdf' || $cabecera !== '%PDF-') {
            responderJson([
                'error' => 'El archivo debe ser un PDF válido'
            ], 400);
        }

        // Limpiar el nombre original del archivo.
        $nombreOriginal = basename(
            str_replace('\\', '/', $archivo['name'])
        );

        $nombreOriginal = preg_replace(
            '/[\x00-\x1F\x7F]/',
            '',
            $nombreOriginal
        );

        if ($nombreOriginal === '') {
            $nombreOriginal = 'documento.pdf';
        }

        if (function_exists('mb_substr')) {
            $nombreOriginal = mb_substr(
                $nombreOriginal,
                0,
                255,
                'UTF-8'
            );
        } else {
            $nombreOriginal = substr($nombreOriginal, 0, 255);
        }

        // Generar un identificador único para el documento.
        $idDocumento = 'DOC' . bin2hex(random_bytes(4));

        $nombreServidor = $idDocumento . '.pdf';

        $carpetaRelativa = 'uploads/documentos';

        $carpetaFisica = $raizProyecto
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $carpetaRelativa);

        // Crear la carpeta si todavía no existe.
        if (
            !is_dir($carpetaFisica) &&
            !mkdir($carpetaFisica, 0777, true) &&
            !is_dir($carpetaFisica)
        ) {
            responderJson([
                'error' => 'No se pudo crear la carpeta de destino'
            ], 500);
        }

        $rutaRelativa = $carpetaRelativa . '/' . $nombreServidor;

        $rutaFisica = $carpetaFisica
            . DIRECTORY_SEPARATOR
            . $nombreServidor;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
            responderJson([
                'error' => 'No se pudo guardar el archivo en el servidor'
            ], 500);
        }

        // Registrar el documento con el ID real de la categoría.
        $guardado = $documento->crear(
            $idDocumento,
            $idCategoria,
            $titulo,
            $descripcion,
            $nombreOriginal,
            $rutaRelativa,
            $ciAdmin
        );

        // Si falla la BD, quitar el archivo para no dejarlo suelto.
        if (!$guardado) {

            if (is_file($rutaFisica)) {
                unlink($rutaFisica);
            }

            responderJson([
                'error' => 'No se pudo registrar el documento'
            ], 500);
        }

        responderJson([
            'mensaje' => 'Documento subido correctamente',
            'id' => $idDocumento
        ], 201);

        break;

    // ==================================================
    // DELETE: ELIMINAR UN DOCUMENTO
    // ==================================================

    case 'DELETE':

        if (!verificarAdministrador()) {
            responderJson([
                'error' => 'No tiene permisos para eliminar documentos'
            ], 403);
        }

        $idDocumento = $_GET['id'] ?? null;

        if (!$idDocumento) {
            responderJson([
                'error' => 'Debe indicar el ID del documento'
            ], 400);
        }

        // Recuperar el registro para comprobar que exista.
        $datosDocumento = $documento->buscarPorId($idDocumento);

        if ($datosDocumento === null) {
            responderJson([
                'error' => 'Documento no encontrado'
            ], 404);
        }

        $rutaFisica = resolverRutaArchivo(
            $datosDocumento['ruta_archivo'],
            $raizProyecto
        );

        if (
            $rutaFisica !== null &&
            is_file($rutaFisica) &&
            !unlink($rutaFisica)
        ) {
            responderJson([
                'error' => 'No se pudo eliminar el archivo físico'
            ], 500);
        }

        if (!$documento->eliminar($idDocumento)) {
            responderJson([
                'error' => 'No se pudo eliminar el registro'
            ], 500);
        }

        responderJson([
            'mensaje' => 'Documento eliminado correctamente'
        ]);

        break;

    default:

        responderJson([
            'error' => 'Método HTTP no permitido'
        ], 405);
}