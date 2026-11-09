<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {

    require_once __DIR__ . '/../../backend/models/Ambulancia.php';

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $ambulancia = new Ambulancia();

    switch ($metodo) {

        case 'GET':

            echo json_encode($ambulancia->obtenerTodas(), JSON_UNESCAPED_UNICODE);
            exit;

        case 'POST':
        case 'PUT':

            $datos = json_decode(file_get_contents('php://input'), true);

            if (!is_array($datos)) {
                http_response_code(400);
                echo json_encode(['error' => 'Los datos enviados no tienen un formato válido.']);
                exit;
            }

            $matricula = trim($datos['matricula'] ?? '');
            $modelo = trim($datos['modelo'] ?? '');
            $capacidad = trim((string)($datos['capacidad'] ?? ''));
            $tipo = trim($datos['tipo_vehiculo'] ?? '');
            $camilla = $datos['camilla'] ?? null;

            // Toda ambulancia debe disponer obligatoriamente de camilla.
            if ($tipo === 'ambulancia') {
                $camilla = '1';
            }

            if (
                $matricula === '' ||
                $modelo === '' ||
                $capacidad === '' ||
                $tipo === '' ||
                $camilla === null ||
                $camilla === ''
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Todos los campos son obligatorios.']);
                exit;
            }

            if (strlen($matricula) > 15 || strlen($modelo) > 50) {
                http_response_code(400);
                echo json_encode(['error' => 'La matrícula admite hasta 15 caracteres y el modelo hasta 50.']);
                exit;
            }

            if (!ctype_digit($capacidad) || (int)$capacidad < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'La capacidad debe ser un número entero mayor que cero.']);
                exit;
            }

            if (!in_array($tipo, ['ambulancia', 'auto', 'otro'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'El tipo de vehículo seleccionado no es válido.']);
                exit;
            }

            if (!in_array((string)$camilla, ['0', '1'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Debe indicar si el vehículo dispone de camilla.']);
                exit;
            }

            if ($metodo === 'POST') {

                if ($ambulancia->crear($matricula, $modelo, $capacidad, $tipo, (bool)(int)$camilla)) {
                    http_response_code(201);
                    echo json_encode(['mensaje' => 'Vehículo registrado correctamente.']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'No se pudo guardar el vehículo.']);
                }

                exit;
            }

            // PUT: la matrícula es la clave principal y no se modifica.
            if ($ambulancia->actualizar($matricula, $modelo, $capacidad, $tipo, (bool)(int)$camilla)) {
                echo json_encode(['mensaje' => 'Vehículo actualizado correctamente.']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'No se encontró el vehículo indicado o no hubo cambios.']);
            }

            exit;

        case 'DELETE':

            $matricula = trim($_GET['matricula'] ?? '');

            if ($matricula === '') {
                http_response_code(400);
                echo json_encode(['error' => 'La matrícula es obligatoria.']);
                exit;
            }

            if ($ambulancia->eliminar($matricula)) {
                echo json_encode(['mensaje' => 'Vehículo eliminado correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar el vehículo.']);
            }

            exit;

        default:

            http_response_code(405);
            header('Allow: GET, POST, PUT, DELETE, OPTIONS');
            echo json_encode(['error' => 'Método HTTP no permitido.']);
            exit;
    }

} catch (Throwable $error) {

    error_log('Error en API/ambulancias/ambulancias.php: ' . $error->getMessage());

    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error interno al procesar el vehículo.']);
    exit;
}
