<?php
session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

// Responde las solicitudes previas del navegador sin ejecutar consultas.
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
            // Devuelve todos los vehículos registrados.
            echo json_encode($ambulancia->obtenerTodas());
            exit;

        case 'POST':
            // Los datos llegan en formato JSON desde el formulario.
            $datos = json_decode(file_get_contents('php://input'), true);

            if (!is_array($datos)) {
                http_response_code(400);
                echo json_encode(['error' => 'Los datos enviados no tienen un formato válido.']);
                exit;
            }

            // Limpiamos los textos para evitar guardar espacios innecesarios.
            $matricula = trim($datos['matricula'] ?? '');
            $modelo = trim($datos['modelo'] ?? '');
            $capacidad = trim((string)($datos['capacidad'] ?? ''));
            $tipo = trim($datos['tipo_vehiculo'] ?? '');
            $camilla = $datos['camilla'] ?? null;

            // Comprobamos que ningún campo obligatorio quede vacío.
            if ($matricula === '' || $modelo === '' || $capacidad === '' || $tipo === '' || $camilla === null || $camilla === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Todos los campos son obligatorios.']);
                exit;
            }

            // Validamos longitudes según la definición de la tabla vehiculo.
            if (strlen($matricula) > 15 || strlen($modelo) > 50) {
                http_response_code(400);
                echo json_encode(['error' => 'La matrícula admite hasta 15 caracteres y el modelo hasta 50.']);
                exit;
            }

            // La capacidad debe ser un número entero mayor que cero.
            if (!ctype_digit($capacidad) || (int)$capacidad < 1) {
                http_response_code(400);
                echo json_encode(['error' => 'La capacidad debe ser un número entero mayor que cero.']);
                exit;
            }

            // Estos son los únicos tipos aceptados por el ENUM de MySQL.
            $tiposPermitidos = ['ambulancia', 'auto', 'otro'];
            if (!in_array($tipo, $tiposPermitidos, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'El tipo de vehículo seleccionado no es válido.']);
                exit;
            }

            // Camilla se almacena como BOOLEAN (0 o 1).
            if (!in_array((string)$camilla, ['0', '1'], true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Debe indicar si el vehículo dispone de camilla.']);
                exit;
            }

            if ($ambulancia->crear($matricula, $modelo, $capacidad, $tipo, (bool)(int)$camilla)) {
                http_response_code(201);
                echo json_encode(['mensaje' => 'Vehículo registrado correctamente.']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo guardar el vehículo.']);
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
            header('Allow: GET, POST, DELETE, OPTIONS');
            echo json_encode(['error' => 'Método HTTP no permitido.']);
            exit;
    }
} catch (Throwable $e) {
    // El detalle queda en el log del servidor, no se expone al navegador.
    error_log('Error en API/ambulancias/ambulancias.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Ocurrió un error interno al procesar el vehículo.']);
    exit;
}
