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

    $destino = new Destino();
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        responderJson($destino->obtenerTodos());
    }

    // DELETE: elimina un destino.
if ($metodo === 'DELETE') {

    $id = trim($_GET['id_destino'] ?? '');

    if ($id === '') {
        responderJson(['mensaje' => 'El identificador del destino es obligatorio.'], 400);
    }

    try {

        if (!$destino->eliminar($id)) {
            responderJson(['mensaje' => 'No se encontró el destino indicado.'], 404);
        }

        responderJson(['mensaje' => 'Destino eliminado correctamente.']);

    } catch (PDOException $error) {

        if ($error->getCode() === '23000') {
            responderJson(['mensaje' => 'No se puede eliminar el destino porque tiene traslados relacionados.'], 409);
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
        'nombre_destino' => 50,
        'direccion' => 50,
        'ruta' => 50
    ];

    foreach ($campos as $campo => $maximo) {
        if (!campoValido($datos, $campo, $maximo)) {
            responderJson(['mensaje' => "El campo {$campo} es obligatorio o no es válido."], 400);
        }
    }

    $datosValidados = [
        'nombre_destino' => trim($datos['nombre_destino']),
        'direccion' => trim($datos['direccion']),
        'ruta' => trim($datos['ruta'])
    ];

    if ($metodo === 'POST') {

        $idGenerado = $destino->crear($datosValidados);

        responderJson([
            'mensaje' => 'Destino registrado correctamente.',
            'id_destino' => $idGenerado
        ], 201);
    }

    // El identificador original se conserva durante la edición.
    if (!isset($datos['id_destino']) || !is_string($datos['id_destino']) || trim($datos['id_destino']) === '') {
        responderJson(['mensaje' => 'El identificador del destino es obligatorio.'], 400);
    }

    $datosValidados['id_destino'] = trim($datos['id_destino']);

    if (!$destino->actualizar($datosValidados)) {
        responderJson(['mensaje' => 'No se encontró el destino indicado.'], 404);
    }

    responderJson(['mensaje' => 'Destino actualizado correctamente.']);

} catch (PDOException $error) {

    error_log($error->getMessage());

    responderJson(['mensaje' => 'Ocurrió un error al procesar los destinos.'], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson(['mensaje' => 'Ocurrió un error al procesar los destinos.'], 500);
}
