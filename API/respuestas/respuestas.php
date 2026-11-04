<?php
// Permitir solicitudes JSON y CORS si es necesario
header('Content-Type: application/json; charset=UTF-8');

// Ajusta la ruta relativa según la ubicación exacta de tu archivo models/Respuesta.php
require_once __DIR__ . '/../../models/Respuesta.php';

$accion = $_GET['accion'] ?? '';
$idEncuesta = $_GET['id_encuesta'] ?? '';

$respuestaModelo = new Respuesta();

switch ($accion) {
    case 'obtenerEstadisticas':
        if (empty($idEncuesta)) {
            echo json_encode([
                'promedioGeneral' => 0,
                'totalRespuestas' => 0,
                'promediosPregunta' => []
            ]);
            exit;
        }

        try {
            // Llamamos al método que calcula las estadísticas en tu modelo
            $estadisticas = $respuestaModelo->obtenerEstadisticasPorEncuesta($idEncuesta);
            echo json_encode($estadisticas);
        } catch (Exception $e) {
            echo json_encode([
                'error' => $e->getMessage(),
                'promedioGeneral' => 0,
                'totalRespuestas' => 0,
                'promediosPregunta' => []
            ]);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no válida en respuestas']);
        break;
}