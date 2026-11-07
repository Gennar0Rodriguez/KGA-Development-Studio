
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Chofer.php';

// Envía una respuesta JSON y finaliza la ejecución.
function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

// Comprueba que el usuario haya iniciado sesión como administrador.
function verificarAdministrador(): void
{
    $cargo = $_SESSION['usuario']['cargo'] ?? '';

    if (strtolower(trim($cargo)) !== 'administrador') {
        responderJson([
            'mensaje' => 'No tiene permisos para realizar esta operación.'
        ], 403);
    }
}

// Valida que un campo obligatorio exista y no esté vacío.
function campoValido(array $datos, string $campo, int $maximo): bool
{
    return isset($datos[$campo])
        && is_string($datos[$campo])
        && trim($datos[$campo]) !== ''
        && mb_strlen(trim($datos[$campo])) <= $maximo;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

verificarAdministrador();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['mensaje' => 'Método no permitido.'], 405);
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    responderJson(['mensaje' => 'Los datos enviados no son válidos.'], 400);
}

$campos = [
    'ci_chofer' => 8,
    'nombre_chofer' => 50,
    'apellido_chofer' => 50,
    'telefono' => 50
];

foreach ($campos as $campo => $maximo) {
    if (!campoValido($datos, $campo, $maximo)) {
        responderJson([
            'mensaje' => "El campo {$campo} es obligatorio o no es válido."
        ], 400);
    }
}

if (!preg_match('/^[0-9]{8}$/', $datos['ci_chofer'])) {
    responderJson([
        'mensaje' => 'La cédula debe contener exactamente 8 números.'
    ], 400);
}

try {

    $chofer = new Chofer();

    $chofer->crear([
        'ci_chofer' => trim($datos['ci_chofer']),
        'nombre_chofer' => trim($datos['nombre_chofer']),
        'apellido_chofer' => trim($datos['apellido_chofer']),
        'telefono' => trim($datos['telefono'])
    ]);

    responderJson([
        'mensaje' => 'Chofer registrado correctamente.'
    ], 201);

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson([
            'mensaje' => 'Ya existe un chofer con esa cédula.'
        ], 409);
    }

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el chofer.'
    ], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el chofer.'
    ], 500);
}
