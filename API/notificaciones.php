<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

require_once __DIR__ . '/../backend/models/Notificacion.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$notificacion = new Notificacion();

$ciUsuario = $_SESSION['usuario']['ci_admin'] 
    ?? $_SESSION['ci_admin'] 
    ?? $_SESSION['ci'] 
    ?? '11111111'; // Fallback para pruebas locales si no hay sesión iniciada

try {
    switch ($metodo) {
    case 'GET':
        $lista = $notificacion->obtenerPorUsuario($ciUsuario);

        $noLeidas = 0;

        foreach ($lista as $item) {
            if ((int)($item['leido'] ?? 0) === 0) {
                $noLeidas++;
            }
        }

        echo json_encode([
            'notificaciones' => $lista,
            'no_leidas' => $noLeidas
        ]);

        exit;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);

            if (empty($datos['titulo']) || empty($datos['descripcion'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Título y descripción son obligatorios']);
                exit;
            }

            if (!empty($datos['ci_receptor'])) {
                $res = $notificacion->crear($datos['titulo'], $datos['descripcion'], $datos['ci_receptor'], $ciUsuario);
            } else {
                // Si no se especifica un receptor, se notifica a todos
                $res = $notificacion->notificarATodos($datos['titulo'], $datos['descripcion'], $ciUsuario);
            }

            if ($res) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Notificación enviada con éxito']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo enviar la notificación']);
            }
            exit;

        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de notificación requerido']);
                exit;
            }

            if ($notificacion->eliminar((int)$id)) {
                echo json_encode(['mensaje' => 'Notificación eliminada']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar la notificación']);
            }
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}