<?php
// Permitir peticiones JSON y acceso CORS si aplica
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . '/../../backend/models/Usuario.php';

// Capturar el método HTTP enviado por la petición (GET, POST, etc.)
$metodo = $_SERVER['REQUEST_METHOD'];

// Capturar el parametro id/ci si viene por URL (ej: ?id=12345678)
$id = $_GET['id'] ?? null;

$usuario = new Usuario();

try {
    switch ($metodo) {
        case 'GET':
            if ($id === null) {
                $usuarios = $usuario->obtenerTodos();
                echo json_encode($usuarios);
            } else {
                $usuarios = $usuario->obtenerPorCi($id);
                if ($usuarios === null) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario no encontrado']);
                    exit;
                }
                echo json_encode($usuarios);
            }
            exit;

        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);

            $ci = $datos['ci'];
            $nombre = $datos['nombre'];
            $apellido = $datos['apellido'];
            $user_name = $datos['user'];
            $password = $datos['pass'];
            $rol = $datos['rol'];

            $resultado = $usuario->crear($ci, $nombre, $apellido, $user_name, $password, $rol);

            if ($resultado) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Usuario creado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['Error' => 'No se pudo crear el usuario']);
            }
            exit;

        case 'DELETE':
            if ($id !== null) {
                $resultado = $usuario->eliminar($id);
                if ($resultado) {
                    http_response_code(200);
                    echo json_encode(['mensaje' => 'Usuario eliminado correctamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['Error' => 'No se pudo eliminar el usuario']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere la CI del usuario']);
            }
            exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos']);
}