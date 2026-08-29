<?php
//aca fue la guerra pero anduvo
require_once __DIR__ . '/../backend/models/Usuario.php';

header('Content-Type: application/json; charset=utf-8');

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método no permitido'
    ]);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!isset($datos['ci_admin']) || !isset($datos['pass'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Debe ingresar usuario y contraseña'
    ]);
    exit;
}

$usuario = new Usuario();

$resultado = $usuario->login(
    $datos['ci_admin'],
    $datos['pass']
);

if ($resultado === null) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Usuario o contraseña incorrectos'
    ]);
    exit;
}

$_SESSION['usuario'] = $resultado;

echo json_encode([
    'mensaje' => 'Login correcto',
    'usuario' => $resultado
]);