<?php

header('Content-Type: application/json charset=utf-8');
header('Access-Ctonrol-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];

// Obtener método y URL
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Dividir URL en segmentos
$segments = explode('/', trim($uri, '/'));

// Remover "api" del inicio si existe
// La idea es remover todo hasta quedarme con el recurso
if ($segments[0] === 'kgade') {
    array_shift($segments);
}
if ($segments[0] === 'KGA-Development-Studio') {
    array_shift($segments);
}
if ($segments[0] === 'API') {
    array_shift($segments);
}


$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// Obtener datos del body para POST/PUT copia y pegue tmb del profe 
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($resource) {
    case 'login':
        require_once __DIR__ . '/login.php';
        exit;

    case 'logout':
        require_once __DIR__ . '/logout.php';
        exit;

    case 'sesion':
        require_once __DIR__ . '/sesion.php';
        exit;
    
    case 'usuarios':
        require_once __DIR__ . '/usuario/usuarios.php';
        exit;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Endpoint no encontrado']);
}
