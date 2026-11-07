
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Destino.php';

function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function verificarAdministrador(): void
{
    $cargo = $_SESSION['usuario']['cargo'] ?? '';

    if (strtolower(trim($cargo)) !== 'administrador') {
        responderJson([
            'mensaje' => 'No tiene permisos para realizar esta operación.'
        ], 403);
    }
}

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

// GET: devuelve todos los destinos registrados.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        $destino = new Destino();

        responderJson($destino->obtenerTodos());

    } catch (Throwable $error) {

        error_log($error->getMessage());

        responderJson([
            'mensaje' => 'Ocurrió un error al consultar los destinos.'
        ], 500);

    }
}

// POST: conserva la lógica actual de registro.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['mensaje' => 'Método no permitido.'], 405);
}


$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    responderJson(['mensaje' => 'Los datos enviados no son válidos.'], 400);
}

$campos = [
    'nombre_destino' => 50,
    'direccion' => 50,
    'ruta' => 50
];

foreach ($campos as $campo => $maximo) {
    if (!campoValido($datos, $campo, $maximo)) {
        responderJson([
            'mensaje' => "El campo {$campo} es obligatorio o no es válido."
        ], 400);
    }
}

try {

    $destino = new Destino();

    $idGenerado = $destino->crear([
        'nombre_destino' => trim($datos['nombre_destino']),
        'direccion' => trim($datos['direccion']),
        'ruta' => trim($datos['ruta'])
    ]);

    responderJson([
        'mensaje' => 'Destino registrado correctamente.',
        'id_destino' => $idGenerado
    ], 201);

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson([
            'mensaje' => 'No se pudo registrar el destino por un conflicto con los datos.'
        ], 409);
    }

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el destino.'
    ], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el destino.'
    ], 500);
}
