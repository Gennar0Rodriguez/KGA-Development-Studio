
<?php
require_once __DIR__ . '/../../backend/models/Respuesta.php';

header('Content-Type: application/json; charset=utf-8');

// Obtenemos la acción solicitada desde la URL.
$accion = $_GET['accion'] ?? '';

try {
    $respuestaModelo = new Respuesta();

    switch ($accion) {

        case 'obtenerEstadisticas':

            // Comprobamos que se haya recibido el ID de la encuesta.
            $idEncuesta = $_GET['id_encuesta'] ?? '';

            if (empty($idEncuesta)) {
                http_response_code(400);
                echo json_encode([
                    'exito' => false,
                    'mensaje' => 'Falta el ID de la encuesta.'
                ]);
                break;
            }

            // Obtenemos las estadísticas calculadas por el modelo.
            $estadisticas = $respuestaModelo->obtenerEstadisticasPorEncuesta($idEncuesta);

            echo json_encode($estadisticas);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Acción no válida.'
            ]);
            break;
    }

} catch (Throwable $e) {

    // Registramos el error real en el servidor.
    error_log('Error en respuestas.php: ' . $e->getMessage());

    http_response_code(500);

    // Devolvemos JSON para que JavaScript pueda interpretar el error.
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Ocurrió un error al obtener las estadísticas.'
    ]);
}
