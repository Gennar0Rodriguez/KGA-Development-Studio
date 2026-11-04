<?php
require_once __DIR__ . '/../../backend/models/Encuesta.php'; // Ajusta los niveles si es necesario

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';
$encuestaModelo = new Encuesta();

switch ($accion) {
    case 'obtenerTodas':
        try {
            $encuestas = $encuestaModelo->obtenerTodos(); 
            echo json_encode($encuestas);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'crear':
        $datos = json_decode(file_get_contents('php://input'), true);
        if ($datos) {
            $resultado = $encuestaModelo->crear(
                $datos['id_encuesta'],
                $datos['id_categoria'],
                $datos['titulo_enc'],
                $datos['descripcion_enc']
            );
            echo json_encode(['exito' => $resultado]);
        } else {
            echo json_encode(['exito' => false]);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}