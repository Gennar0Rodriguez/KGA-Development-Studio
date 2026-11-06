
<?php
session_start();

require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/models/Encuesta.php';
require_once __DIR__ . '/../../backend/models/Categoria.php';

header('Content-Type: application/json; charset=utf-8');

// Comprobar que el usuario tenga una sesión de administrador.
function verificarAdministrador(): bool
{
    $usuario = $_SESSION['usuario'] ?? [];

    return isset($usuario['cargo'])
        && strtolower(trim($usuario['cargo'])) === 'administrador';
}

// Responder un error en formato JSON.
function responderError(string $mensaje, int $codigo): void
{
    http_response_code($codigo);

    echo json_encode([
        'error' => $mensaje
    ]);

    exit;
}

try {

    $accion = $_GET['accion'] ?? '';

    $encuestaModelo = new Encuesta();
    $categoriaModelo = new Categoria();

    $conexion = Database::getInstancia()->getConexion();

    switch ($accion) {

        // ==========================================
        // OBTENER TODAS LAS ENCUESTAS (ADMIN)
        // ==========================================

        case 'obtenerTodas':

            if (!verificarAdministrador()) {
                responderError('No tiene permisos para consultar estas encuestas', 403);
            }

            echo json_encode($encuestaModelo->obtenerTodos());
            exit;


        // ==========================================
        // OBTENER ENCUESTAS ACTIVAS (PÚBLICO)
        // ==========================================

        case 'obtenerActivas':

            echo json_encode($encuestaModelo->obtenerActivas());
            exit;


        // ==========================================
        // OBTENER TODAS LAS CATEGORÍAS (ADMIN)
        // ==========================================

        case 'obtenerCategorias':

            if (!verificarAdministrador()) {
                responderError('No tiene permisos para consultar las categorías', 403);
            }

            echo json_encode(
                $categoriaModelo->obtenerCategoriasEncuestas()
            );

            exit;


        // ==========================================
        // CREAR CATEGORÍA Y SU ENCUESTA
        // ==========================================

        case 'crearCategoria':

            if (!verificarAdministrador()) {
                responderError('No tiene permisos para crear categorías', 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderError('Método no permitido', 405);
            }

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!is_array($datos)) {
                responderError('Los datos enviados no son válidos', 400);
            }

            $nombre = trim($datos['nombre_cat'] ?? '');

            if ($nombre === '') {
                responderError('Debe ingresar un nombre para la categoría', 400);
            }

            if (mb_strlen($nombre) > 50) {
                responderError('El nombre no puede superar los 50 caracteres', 400);
            }

            // Bloquear operaciones simultáneas sobre el límite de categorías.
            $bloqueo = $conexion->query(
                "SELECT GET_LOCK('sigsm_categorias_encuestas', 5)"
            )->fetchColumn();

            if ((int) $bloqueo !== 1) {
                responderError('No se pudo reservar la operación. Intente nuevamente', 503);
            }

            try {

                $conexion->beginTransaction();

                // Comprobar que no exista otra categoría con el mismo nombre.
                $sqlDuplicada = "SELECT COUNT(*)
                                 FROM categoria
                                 WHERE tipo_cat = 'encuestas'
                                 AND LOWER(nombre_cat) = LOWER(:nombre)";

                $sentenciaDuplicada = $conexion->prepare($sqlDuplicada);

                $sentenciaDuplicada->execute([
                    ':nombre' => $nombre
                ]);

                if ((int) $sentenciaDuplicada->fetchColumn() > 0) {
                    throw new DomainException(
                        'Ya existe una categoría con ese nombre'
                    );
                }

                // Determinar si la nueva categoría puede quedar activa.
                $cantidadActivas = $categoriaModelo->contarActivas();

                $estado = $cantidadActivas < 6
                    ? 'activa'
                    : 'deshabilitada';

                // Generar identificadores únicos.
                $idCategoria = 'CAT' . bin2hex(random_bytes(8));
                $idEncuesta = 'ENC' . bin2hex(random_bytes(8));

                // Crear la categoría.
                $categoriaModelo->crear(
                    $idCategoria,
                    $nombre,
                    $estado
                );

                // Crear automáticamente su encuesta.
                $encuestaModelo->crear(
                    $idEncuesta,
                    $idCategoria,
                    $nombre,
                    null
                );

                $conexion->commit();

                echo json_encode([
                    'exito' => true,
                    'mensaje' => $estado === 'activa'
                        ? 'La categoría y su encuesta fueron creadas correctamente'
                        : 'La categoría fue creada deshabilitada porque ya hay seis activas',
                    'estado_cat' => $estado,
                    'id_categoria' => $idCategoria,
                    'id_encuesta' => $idEncuesta
                ]);

            } catch (DomainException $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                http_response_code(409);

                echo json_encode([
                    'error' => $e->getMessage()
                ]);

            } catch (Throwable $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                throw $e;

            } finally {

                $conexion->query(
                    "SELECT RELEASE_LOCK('sigsm_categorias_encuestas')"
                );
            }

            exit;


        // ==========================================
        // HABILITAR O DESHABILITAR UNA CATEGORÍA
        // ==========================================

        case 'cambiarEstado':

            if (!verificarAdministrador()) {
                responderError('No tiene permisos para modificar categorías', 403);
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                responderError('Método no permitido', 405);
            }

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!is_array($datos)) {
                responderError('Los datos enviados no son válidos', 400);
            }

            $idCategoria = trim($datos['id_categoria'] ?? '');
            $nuevoEstado = $datos['estado_cat'] ?? '';

            if (
                $idCategoria === '' ||
                !in_array($nuevoEstado, ['activa', 'deshabilitada'], true)
            ) {
                responderError('Los datos enviados no son válidos', 400);
            }

            $bloqueo = $conexion->query(
                "SELECT GET_LOCK('sigsm_categorias_encuestas', 5)"
            )->fetchColumn();

            if ((int) $bloqueo !== 1) {
                responderError('No se pudo reservar la operación. Intente nuevamente', 503);
            }

            try {

                $conexion->beginTransaction();

                // Verificar que la categoría exista y sea de encuestas.
                $sqlCategoria = "SELECT estado_cat
                                 FROM categoria
                                 WHERE id_categoria = :id_categoria
                                 AND tipo_cat = 'encuestas'";

                $sentenciaCategoria = $conexion->prepare($sqlCategoria);

                $sentenciaCategoria->execute([
                    ':id_categoria' => $idCategoria
                ]);

                $categoria = $sentenciaCategoria->fetch(PDO::FETCH_ASSOC);

                if (!$categoria) {
                    throw new DomainException(
                        'La categoría seleccionada no existe'
                    );
                }

                // Comprobar el límite antes de habilitar.
                if (
                    $nuevoEstado === 'activa' &&
                    $categoria['estado_cat'] !== 'activa' &&
                    $categoriaModelo->contarActivas() >= 6
                ) {
                    throw new DomainException(
                        'No se puede habilitar: ya existen seis categorías activas'
                    );
                }

                $categoriaModelo->cambiarEstado(
                    $idCategoria,
                    $nuevoEstado
                );

                $conexion->commit();

                echo json_encode([
                    'exito' => true,
                    'mensaje' => 'El estado de la categoría fue actualizado',
                    'estado_cat' => $nuevoEstado
                ]);

            } catch (DomainException $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                http_response_code(409);

                echo json_encode([
                    'error' => $e->getMessage()
                ]);

            } catch (Throwable $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                throw $e;

            } finally {

                $conexion->query(
                    "SELECT RELEASE_LOCK('sigsm_categorias_encuestas')"
                );
            }

            exit;


        default:

            responderError('Acción no válida', 400);
    }

} catch (Throwable $e) {

    error_log('Error en encuestas.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'No se pudo procesar la solicitud'
    ]);
}
