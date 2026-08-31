<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

require_once __DIR__ . '/../../backend/models/Usuario.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id = $_GET['id'] ?? $_GET['ci'] ?? null;

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

            $ci = $datos['ci'] ?? null;
            $nombre = $datos['nombre'] ?? null;
            $apellido = $datos['apellido'] ?? null;
            $password = $datos['pass'] ?? null;
            $rol = $datos['rol'] ?? null;

            $resultado = $usuario->crear($ci, $nombre, $apellido, $password, $rol);

            if ($resultado) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Usuario creado correctamente']);
            } else { 
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo crear el usuario']);
            }
            exit;

        case 'PUT':
            // Recibir y actualizar los datos del usuario
            $datos = json_decode(file_get_contents('php://input'), true);

            $ci = $datos['ci'] ?? null;
            $nombre = $datos['nombre'] ?? null;
            $apellido = $datos['apellido'] ?? null;
            $password = $datos['pass'] ?? null;
            $rol = $datos['rol'] ?? null;

            if (!$ci) {
                http_response_code(400);
                echo json_encode(['error' => 'La CI es obligatoria']);
                exit;
            }

            $resultado = $usuario->editarUsuario($ci, $nombre, $apellido, $password, $rol);

            if ($resultado) {
                http_response_code(200);
                echo json_encode(['mensaje' => 'Usuario actualizado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo actualizar el usuario']);
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
                    echo json_encode(['error' => 'No se pudo eliminar el usuario']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Se requiere la CI del usuario']);
            }
            exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
}