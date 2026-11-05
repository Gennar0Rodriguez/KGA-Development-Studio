<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

require_once __DIR__ . '/../../backend/models/Notificacion.php';

// No utilizamos una CI predeterminada.
// Cada usuario debe tener una sesión válida.
$ciUsuario = $_SESSION['usuario']['ci_admin'] ?? null;

if (!$ciUsuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Debe iniciar sesión']);
    exit;
}

try {

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $notificacion = new Notificacion();

    switch ($metodo) {

        // ==============================================
        // OBTENER NOTIFICACIONES
        // ==============================================

        case 'GET':

            $lista = $notificacion->obtenerPorUsuario($ciUsuario);

            $noLeidas = 0;

            foreach ($lista as $item) {
                if ((int)$item['leido'] === 0) {
                    $noLeidas++;
                }
            }

            echo json_encode([
                'notificaciones' => $lista,
                'no_leidas' => $noLeidas
            ]);

            exit;


        // ==============================================
        // ENVIAR UNA NOTIFICACIÓN
        // ==============================================

        case 'POST':

            $datos = json_decode(file_get_contents('php://input'), true);

            if (
                empty($datos['id_traslado']) ||
                empty($datos['ci_chofer']) ||
                empty($datos['matricula']) ||
                empty($datos['id_destino']) ||
                empty($datos['ci_receptor']) ||
                empty($datos['titulo']) ||
                empty($datos['descripcion'])
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan datos de la notificación']);
                exit;
            }

            $resultado = $notificacion->crear(
                $datos['id_traslado'],
                $datos['ci_chofer'],
                $datos['matricula'],
                $datos['id_destino'],
                $ciUsuario,
                $datos['ci_receptor'],
                $datos['titulo'],
                $datos['descripcion']
            );

            if ($resultado) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Notificación enviada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo enviar la notificación']);
            }

            exit;


        // ==============================================
        // MARCAR COMO LEÍDA
        // ==============================================

        case 'PUT':

            $datos = json_decode(file_get_contents('php://input'), true);

            if (
                empty($datos['id_traslado']) ||
                empty($datos['ci_chofer']) ||
                empty($datos['matricula']) ||
                empty($datos['id_destino'])
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan identificadores de la notificación']);
                exit;
            }

            if ($notificacion->marcarComoLeida($datos, $ciUsuario)) {
                echo json_encode(['mensaje' => 'Notificación marcada como leída']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo actualizar la notificación']);
            }

            exit;


        // ==============================================
        // ELIMINAR UNA NOTIFICACIÓN
        // ==============================================

        case 'DELETE':

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos) {
                http_response_code(400);
                echo json_encode(['error' => 'Faltan los datos de la notificación']);
                exit;
            }

            if ($notificacion->eliminar($datos, $ciUsuario)) {
                echo json_encode(['mensaje' => 'Notificación eliminada']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar la notificación']);
            }

            exit;


        default:

            http_response_code(405);
            echo json_encode(['error' => 'Método HTTP no permitido']);
            exit;
    }

} catch (Throwable $e) {

    error_log("Error en notificaciones.php: " . $e->getMessage());

    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
    exit;
}