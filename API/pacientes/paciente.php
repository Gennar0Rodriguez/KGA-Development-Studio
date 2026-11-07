
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Paciente.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['mensaje' => 'Método no permitido.'], 405);
}

$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {
    responderJson(['mensaje' => 'Los datos enviados no son válidos.'], 400);
}

// Validamos todos los campos obligatorios.
$campos = [
    'ci_paciente' => 8,
    'nombre_paciente' => 50,
    'accesorio' => 10,
    'habitacion_retiro' => 50,
    'piso_retiro' => 50,
    'ci_enfermero' => 8
];

foreach ($campos as $campo => $maximo) {
    if (!campoValido($datos, $campo, $maximo)) {
        responderJson([
            'mensaje' => "El campo {$campo} es obligatorio o no es válido."
        ], 400);
    }
}

// La cédula debe contener exactamente 8 dígitos.
if (
    !preg_match('/^[0-9]{8}$/', $datos['ci_paciente']) ||
    !preg_match('/^[0-9]{8}$/', $datos['ci_enfermero'])
) {
    responderJson([
        'mensaje' => 'Las cédulas deben contener exactamente 8 números.'
    ], 400);
}

// El accesorio debe coincidir con los valores permitidos por el MER.
if (!in_array($datos['accesorio'], ['baston', 'silla', 'otro'], true)) {
    responderJson([
        'mensaje' => 'El accesorio seleccionado no es válido.'
    ], 400);
}

// Estos valores llegan como "1" o "0" desde los select.
if (
    !in_array($datos['oxigeno'] ?? null, ['0', '1'], true) ||
    !in_array($datos['aislamiento'] ?? null, ['0', '1'], true)
) {
    responderJson([
        'mensaje' => 'Las opciones de oxígeno y aislamiento no son válidas.'
    ], 400);
}

try {

    $paciente = new Paciente();

    $idTransportable = $paciente->crear([
        'ci_paciente' => trim($datos['ci_paciente']),
        'nombre_paciente' => trim($datos['nombre_paciente']),
        'accesorio' => $datos['accesorio'],
        'oxigeno' => (int) $datos['oxigeno'],
        'aislamiento' => (int) $datos['aislamiento'],
        'ci_enfermero' => trim($datos['ci_enfermero']),
        'habitacion_retiro' => trim($datos['habitacion_retiro']),
        'piso_retiro' => trim($datos['piso_retiro'])
    ]);

    responderJson([
        'mensaje' => 'Paciente registrado correctamente.',
        'id_transportable' => $idTransportable
    ], 201);

} catch (InvalidArgumentException $error) {

    responderJson([
        'mensaje' => $error->getMessage()
    ], 400);

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson([
            'mensaje' => 'No se pudo registrar el paciente. Verifique que la cédula no esté registrada y que el enfermero exista.'
        ], 409);
    }

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el paciente.'
    ], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el paciente.'
    ], 500);
}
