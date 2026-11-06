<?php
require_once __DIR__ . '/../../backend/models/Encuesta.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $accion = $_GET['accion'] ?? '';

    $encuestaModelo = new Encuesta();

    switch ($accion) {

        case 'obtenerTodas':

            $encuestas = $encuestaModelo->obtenerTodos();

            echo json_encode($encuestas);
            break;

        default:

            http_response_code(400);

            echo json_encode([
                'error' => 'Acción no válida'
            ]);

            break;
    }

} catch (Throwable $e) {

    error_log('Error en encuestas.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'No se pudieron obtener las encuestas'
    ]);
}