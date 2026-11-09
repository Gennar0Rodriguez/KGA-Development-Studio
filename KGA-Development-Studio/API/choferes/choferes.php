<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Chofer.php';

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
        responderJson(['mensaje' => 'No tiene permisos para realizar esta operación.'], 403);
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

try {

    $chofer = new Chofer();
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        responderJson($chofer->obtenerTodos());
    }

    // DELETE: elimina un chofer.
if ($metodo === 'DELETE') {

    $ci = trim($_GET['ci_chofer'] ?? '');

    if ($ci === '') {
        responderJson(['mensaje' => 'La cédula del chofer es obligatoria.'], 400);
    }

    try {

        if (!$chofer->eliminar($ci)) {
            responderJson(['mensaje' => 'No se encontró el chofer indicado.'], 404);
        }

        responderJson(['mensaje' => 'Chofer eliminado correctamente.']);

    } catch (PDOException $error) {

        if ($error->getCode() === '23000') {
            responderJson(['mensaje' => 'No se puede eliminar el chofer porque tiene registros relacionados.'], 409);
        }

        throw $error;
    }
}

    if (!in_array($metodo, ['POST', 'PUT'], true)) {
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
            responderJson(['mensaje' => "El campo {$campo} es obligatorio o no es válido."], 400);
        }
    }

    if (!preg_match('/^[0-9]{8}$/', $datos['ci_chofer'])) {
        responderJson(['mensaje' => 'La cédula debe contener exactamente 8 números.'], 400);
    }

    $datosValidados = [
        'ci_chofer' => trim($datos['ci_chofer']),
        'nombre_chofer' => trim($datos['nombre_chofer']),
        'apellido_chofer' => trim($datos['apellido_chofer']),
        'telefono' => trim($datos['telefono'])
    ];

    if ($metodo === 'POST') {

        $chofer->crear($datosValidados);

        responderJson(['mensaje' => 'Chofer registrado correctamente.'], 201);
    }

    if (!$chofer->actualizar($datosValidados)) {
        responderJson(['mensaje' => 'No se encontró el chofer indicado.'], 404);
    }

    responderJson(['mensaje' => 'Chofer actualizado correctamente.']);

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson(['mensaje' => 'Ya existe un chofer con esa cédula.'], 409);
    }

    responderJson(['mensaje' => 'Ocurrió un error al procesar los choferes.'], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson(['mensaje' => 'Ocurrió un error al procesar los choferes.'], 500);
}
