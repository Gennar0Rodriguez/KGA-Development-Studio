<?php
session_start();

// Desactivar impresión de errores HTML en la salida para evitar romper el JSON.
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Comprobar que exista una sesión iniciada.
$usuario = $_SESSION['usuario'] ?? null;

if (!$usuario || empty($usuario['ci_admin']) || empty($usuario['cargo'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Debe iniciar sesión']);
    exit;
}

$ciUsuario = $usuario['ci_admin'];

$esAdmin = strtolower(trim($usuario['cargo'])) === 'administrador';

try {

    $rutaModelo = __DIR__ . '/../../backend/models/Traslado.php';

    if (!file_exists($rutaModelo)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se encontró el archivo del modelo Traslado.php']);
        exit;
    }

    require_once $rutaModelo;

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $traslado = new Traslado();

    // ======================================================
    // RESPONDER ERRORES DE DISPONIBILIDAD
    // ======================================================

    function responderErrorDisponibilidad(Traslado $traslado): void
    {
        $error = $traslado->obtenerErrorDisponibilidad();

        http_response_code(409);

        if ($error !== null) {
            echo json_encode($error);
            return;
        }

        // Mantener un mensaje general para errores que no sean
        // conflictos de disponibilidad.
        echo json_encode([
            'codigo' => 'OPERACION_NO_REALIZADA',
            'error' => 'No se pudo realizar la operación. Compruebe los datos e inténtelo nuevamente.'
        ]);
    }

    switch ($metodo) {

        // ======================================================
        // OBTENER TRASLADOS
        // ======================================================

        case 'GET':

            if (isset($_GET['action']) && $_GET['action'] === 'auxiliares') {

                echo json_encode($traslado->obtenerListasAuxiliares());

            } else {

                // El administrador ve todos los traslados.
                // El empleado solamente los que creó.
                echo json_encode(
                    $traslado->obtenerTodos($esAdmin ? null : $ciUsuario)
                );

            }

            exit;


        // ======================================================
        // CREAR O RESOLVER SOLICITUD
        // ======================================================

        case 'POST':

            // Acción exclusiva del administrador.

            // Cambiar la etapa operativa de un traslado.
            if (isset($_GET['action']) && $_GET['action'] === 'estado') {
                if (!$esAdmin) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tiene permisos para realizar esta acción']);
                    exit;
                }

                $datos = json_decode(file_get_contents('php://input'), true);

                if (
                    empty($datos['id_traslado']) ||
                    !in_array($datos['estado_traslado'] ?? '', ['en proceso', 'finalizado'], true)
                ) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos de estado inválidos']);
                    exit;
                }

                if ($traslado->actualizarEstadoOperativo(
                    $datos['id_traslado'],
                    $datos['estado_traslado'],
                    $datos['hora_llegada'] ?? null,
                    $datos['fecha_llegada'] ?? null
                )) {
                    echo json_encode(['mensaje' => 'Estado del traslado actualizado correctamente']);
                } else {

                    if ($traslado->obtenerErrorDisponibilidad() !== null) {

                        responderErrorDisponibilidad($traslado);

                    } else {

                        http_response_code(409);

                        echo json_encode([
                            'codigo' => 'ERROR_ESTADO_TRASLADO',
                            'error' => 'No se pudo realizar el cambio de estado. Compruebe la etapa actual y las horas registradas.'
                        ]);

                    }
                }

                exit;
            }
            if (isset($_GET['action']) && $_GET['action'] === 'resolver') {

                if (!$esAdmin) {
                    http_response_code(403);
                    echo json_encode(['error' => 'No tiene permisos para realizar esta acción']);
                    exit;
                }

                $datos = json_decode(file_get_contents('php://input'), true);

                if (
                    empty($datos['id_traslado']) ||
                    !in_array($datos['estado_solicitud'] ?? '', ['aprobado', 'rechazado'], true)
                ) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datos de solicitud inválidos']);
                    exit;
                }
                if ($traslado->resolverSolicitud(
                    $datos['id_traslado'],
                    $datos['estado_solicitud'],
                    $ciUsuario,
                    $datos['hora_salida'] ?? null
                )) {

                    echo json_encode(['mensaje' => 'Solicitud actualizada correctamente']);

                } else {

                    responderErrorDisponibilidad($traslado);

                }

                exit;
            }

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos de formulario vacíos o inválidos']);
                exit;
            }

            // La CI del solicitante se obtiene de la sesión.
            if ($traslado->crear($datos, $ciUsuario, $esAdmin)) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Solicitud de traslado registrada correctamente']);

            } else {

                if ($traslado->obtenerErrorDisponibilidad() !== null) {

                    responderErrorDisponibilidad($traslado);

                } else {

                    http_response_code(500);

                    echo json_encode([
                        'codigo' => 'ERROR_CREAR_TRASLADO',
                        'error' => 'No se pudo guardar el traslado. Compruebe los datos ingresados.'
                    ]);

                }

            }

            exit;


        // ======================================================
        // ACTUALIZAR TRASLADO
        // ======================================================

        case 'PUT':

            // Solo los administradores pueden editar traslados.
            if (!$esAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'No tiene permisos para realizar esta acción']);
                exit;
            }

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos || empty($datos['id_traslado']) || empty($datos['id_transportable'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requieren los datos del traslado a editar']);
                exit;
            }

            if ($traslado->actualizar($datos)) {

                echo json_encode(['mensaje' => 'Traslado actualizado correctamente']);

            } else {

                if ($traslado->obtenerErrorDisponibilidad() !== null) {

                    responderErrorDisponibilidad($traslado);

                } else {

                    http_response_code(500);

                    echo json_encode([
                        'codigo' => 'ERROR_ACTUALIZAR_TRASLADO',
                        'error' => 'No se pudo actualizar el traslado. Compruebe los datos ingresados.'
                    ]);

                }

            }
            exit;


        // ======================================================
        // ELIMINAR TRASLADO
        // ======================================================

        case 'DELETE':

            // Solo los administradores pueden eliminar traslados.
            if (!$esAdmin) {
                http_response_code(403);
                echo json_encode(['error' => 'No tiene permisos para realizar esta acción']);
                exit;
            }

            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere el ID del traslado']);
                exit;
            }

            if ($traslado->eliminar($id)) {

                echo json_encode(['mensaje' => 'Traslado eliminado correctamente']);

            } else {

                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar el traslado']);

            }

            exit;
    }

} catch (PDOException $e) {

    http_response_code(500);
    echo json_encode(['error' => 'Error de Base de Datos: ' . $e->getMessage()]);
    exit;

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}
