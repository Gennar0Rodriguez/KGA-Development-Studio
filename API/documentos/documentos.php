<?php
session_start();

header("X-Content-Type-Options: nosniff");

require_once __DIR__ . "/../../backend/models/Documento.php";

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$documento = new Documento();
$raizProyecto = dirname(__DIR__, 2); // La carpeta del proyecto (donde están API, backend, frontend y uploads)
$tamañoMaximo = 10 * 1024 * 1024; // 10 MB de tamaño máximo

// Se estandariza response con función
function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode($datos);

    exit;
}


function resolverRutaArchivo(string $rutaRelativa, string $raizProyecto): ?string {
    $raizUploads = realpath($raizProyecto . "/uploads/documentos");

    if ($raizUploads === false) {
        return null;
    }

    $rutaRelativa = str_replace(["\\", "/"],
        DIRECTORY_SEPARATOR,
        $rutaRelativa
    );

    $rutaFisica = realpath($raizProyecto . DIRECTORY_SEPARATOR . $rutaRelativa);

    if ($rutaFisica === false) {
        return null;
    }

    $prefijo = $raizUploads . DIRECTORY_SEPARATOR;

    if (strpos($rutaFisica, $prefijo) !== 0) {
        return null;
    }

    return $rutaFisica;
}


switch ($metodo) {
    case 'GET':
        responderJson($documento->obtenerTodos());

        exit;

    case 'POST':
        // El CI de quien sube el documento sale de la sesión
        $ciAdmin = $_SESSION['usuario']['ci_admin']
            ?? $_SESSION['ci_admin']
            ?? $_SESSION['ci']
            ?? null;

        if (!$ciAdmin) {
            responderJson([
                "error" => "Sesión no válida o no autenticada"
            ], 401);
        }

        $titulo = $_POST['documentTitle'] ?? null;
        $descripcion = $_POST['documentDescription'] ?? '';
        $tipo = $_POST['documentTipe'] ?? 'C1';

        // Se comprueba si en body.documentFile se recibe archivo
        if (!$titulo || !isset($_FILES["documentFile"])) {
            responderJson([
                "error" => "Complete el título y seleccione un archivo"
            ], 400);
        }

        $archivo = $_FILES["documentFile"];
            /*
                [
                    "name" => "contrato.pdf",
                    "type" => "application/pdf",
                    "tmp_name" => "/tmp/phpABC123",
                    "error" => 0,
                    "size" => 245760
                ]
                tmp_name: el archivo aún no está en uploads/documentos/. PHP lo almacena temporalmente mientras procesa la petición
            */


        if ($archivo["error"] !== UPLOAD_ERR_OK) {
            responderJson([
                "error" => "Se produjo un error al subir el archivo"
            ], 400);
        }

        // Se comprueba que el archivo temporal (recordar tmp_name) sea válido
        if (!is_uploaded_file($archivo["tmp_name"]) || $archivo["size"] <= 0) {
            responderJson([
                "error" => "El archivo recibido no es válido"
            ], 400);
        }

        // Se comprueba tamaño del archivo
        if ($archivo["size"] > $tamañoMaximo) {
            responderJson([
                "error" => "El archivo supera el límite de 10 MB"
            ], 400);
        }

        // Se comprueba que el archivo sea PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // MIME: application/pdf, image/png, image/jpeg o text/plain

        $tipoMime = finfo_file($finfo, $archivo["tmp_name"]);

        $handle = fopen($archivo["tmp_name"], "rb");
        $cabecera = fread($handle, 5); // Se leen los primeros caracteres de un PDF %PDF- (abrir un PDF con editor de texto)
        fclose($handle);

        // Recién acá se realiza la comprobación
        if ($tipoMime !== "application/pdf" || $cabecera !== "%PDF-") {
            responderJson([
                "error" => "El archivo debe ser un PDF válido"
            ], 400);
        }


        // Seleccionar la categoria del documento
        $idCategoria = 'C1';

        if ($tipo === 'Emergencia') {
            $idCategoria = 'C2';
        }

        // El ID del documento es también el nombre del PDF (el frontend lo busca como ID.pdf)
        $idDocumento = 'DOC' . bin2hex(random_bytes(4));


        // Se crea la carpeta uploads/documentos, si es que ya no existe
        $carpetaFisica = $raizProyecto . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "documentos";

        if (!is_dir($carpetaFisica) && !mkdir($carpetaFisica, 0777, true) && !is_dir($carpetaFisica)) {
            responderJson([
                "error" => "No se pudo crear la carpeta de destino"
            ], 500);
        }


        // El archivo queda en el srv en uploads/documentos/
        $rutaFisica = $carpetaFisica . DIRECTORY_SEPARATOR . $idDocumento . ".pdf";

        if (!move_uploaded_file($archivo["tmp_name"], $rutaFisica)) {
            responderJson([
                "error" => "No se pudo guardar el archivo en el servidor"
            ], 500);
        }

        // Se guarda el documento en la BD
        if (!$documento->crear($idDocumento, $idCategoria, $titulo, $descripcion, $ciAdmin)) {
            if (is_file($rutaFisica)) {
                // Si falla el guardado en la BD se borra el PDF
                unlink($rutaFisica);
            }

            responderJson([
                "error" => "No se pudo registrar el documento"
            ], 500);
        }


        responderJson([
            "mensaje" => "Documento subido correctamente",
            "id" => $idDocumento
        ], 201);

        break;

    case 'DELETE':
        $idDocumento = $_GET['id'] ?? null;

        if (!$idDocumento) {
            responderJson([
                "error" => "Debe indicar el ID del documento"
            ], 400);
        }

        $rutaFisica = resolverRutaArchivo(
            "uploads/documentos/" . $idDocumento . ".pdf",
            $raizProyecto
        );

        if ($rutaFisica !== null && is_file($rutaFisica) && !unlink($rutaFisica)) {
            responderJson([
                "error" => "No se pudo eliminar el archivo físico"
            ], 500);
        }

        if (!$documento->eliminar($idDocumento)) {
            responderJson([
                "error" => "No se pudo eliminar el registro"
            ], 500);
        }

        responderJson([
            "mensaje" => "Documento eliminado correctamente"
        ]);

        break;

    default:
        responderJson([
            "error" => "Método HTTP no permitido"
        ], 405);
}