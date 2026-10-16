<?php
session_start();

// Desactivar impresión de errores HTML en la salida para evitar romper el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

try {
    $rutaModelo = __DIR__ . '/../../backend/models/Traslado.php';
    if (!file_exists($rutaModelo)) {
        // Intenta la ruta alternativa si difiere la estructura de carpetas
        $rutaModelo = __DIR__ . '/../backend/models/Traslado.php';
    }

    if (!file_exists($rutaModelo)) {
        http_response_code(500);
        echo json_encode(['error' => 'No se encontró el archivo del modelo Traslado.php']);
        exit;
    }

    require_once $rutaModelo;

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $traslado = new Traslado();

    switch ($metodo) {
        case 'GET':
            if (isset($_GET['action']) && $_GET['action'] === 'auxiliares') {
                echo json_encode($traslado->obtenerListasAuxiliares());
            } else {
                echo json_encode($traslado->obtenerTodos());
            }
            exit;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);

            if (!$datos) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos de formulario vacíos o inválidos']);
                exit;
            }

            if ($traslado->crear($datos)) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Traslado registrado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo guardar el traslado en la base de datos']);
            }
            exit;

        case 'DELETE':
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