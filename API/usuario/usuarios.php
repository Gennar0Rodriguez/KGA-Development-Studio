<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . '/../../backend/models/Usuario.php';

// Obtener el método HTTP y el ID del usuario
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = $_GET['id'] ?? $_GET['ci'] ?? null;

$usuario = new Usuario();

try {
    switch ($metodo) {
        // GET /api/usuarios/
        // GET /api/usuarios/12345678
        case 'GET':
            if ($id === null) {
                $usuarios = $usuario->obtenerTodos();
                echo json_encode($usuarios);
            } else {
                $usuarios = $usuario->obtenerPorCi($id);
                if ($usuarios === null) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario No encontrado']);
                    exit;
                }
                echo json_encode($usuarios);
            } 
            exit;

        case 'POST':
            // POST /api/usuarios/
            $datos = json_decode(file_get_contents('php://input'), true);

            $ci = $datos['ci'] ?? null;
            $nombre = $datos['nombre'] ?? null;
            $apellido = $datos['apellido'] ?? null;
            $password = $datos['pass'] ?? null;
            $rol = $datos['rol'] ?? null;

            $resultado = $usuario->crear(
                $ci,
                $nombre,
                $apellido,
                $password,
                $rol
            );

            if ($resultado) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Usuario creado correctamente']);
            } else { 
                http_response_code(500);
                echo json_encode(['Error' => 'No se pudo crear el usuario']);
            }
            exit;

        case 'PUT':
            exit;

        case 'DELETE':
            // DELETE /api/usuarios/?id=12345678 aunque no anda
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
                echo json_encode(['Error' => 'Se requiere la CI del usuario']);
            }
            exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor']);
}