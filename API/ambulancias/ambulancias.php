<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

require_once __DIR__ . '/../../backend/models/Ambulancia.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$ambulancia = new Ambulancia();

try {
    switch ($metodo) {
        case 'GET':
            $resultado = $ambulancia->obtenerTodas();
            echo json_encode($resultado);
            exit;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos || empty($datos['matricula'])) {
                http_response_code(400);
                echo json_encode(['error' => 'La matrícula es obligatoria']);
                exit;
            }

            $modelo = $datos['modelo'] ?? '';
            $capacidad = $datos['capacidad'] ?? '2';
            $tipo = $datos['tipo_vehiculo'] ?? 'ambulancia';
            $camilla = $datos['camilla'] ?? true;

            if ($ambulancia->crear($datos['matricula'], $modelo, $capacidad, $tipo, $camilla)) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Ambulancia registrada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo guardar la ambulancia']);
            }
            exit;

        case 'DELETE':
            $matricula = $_GET['matricula'] ?? null;

            if (!$matricula) {
                http_response_code(400);
                echo json_encode(['error' => 'Matrícula requerida']);
                exit;
            }

            if ($ambulancia->eliminar($matricula)) {
                echo json_encode(['mensaje' => 'Ambulancia eliminada correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar la ambulancia']);
            }
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}