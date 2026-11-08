
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Elemento.php';

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

    $elemento = new Elemento();
    $metodo = $_SERVER['REQUEST_METHOD'];

    if ($metodo === 'GET') {
        responderJson($elemento->obtenerTodos());
    }

    // DELETE: elimina un elemento.
if ($metodo === 'DELETE') {

    $id = trim($_GET['id_transportable'] ?? '');

    if ($id === '') {
        responderJson(['mensaje' => 'El identificador del elemento es obligatorio.'], 400);
    }

    try {

        if (!$elemento->eliminar($id)) {
            responderJson(['mensaje' => 'No se encontró el elemento indicado.'], 404);
        }

        responderJson(['mensaje' => 'Elemento eliminado correctamente.']);

    } catch (PDOException $error) {

        if ($error->getCode() === '23000') {
            responderJson(['mensaje' => 'No se puede eliminar el elemento porque forma parte de registros relacionados.'], 409);
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
        'nombre_elemento' => 50,
        'descripcion_elemento' => 50,
        'habitacion_retiro' => 50,
        'piso_retiro' => 50,
        'ci_enfermero' => 8
    ];

    foreach ($campos as $campo => $maximo) {
        if (!campoValido($datos, $campo, $maximo)) {
            responderJson(['mensaje' => "El campo {$campo} es obligatorio o no es válido."], 400);
        }
    }

    if (!preg_match('/^[0-9]{8}$/', $datos['ci_enfermero'])) {
        responderJson(['mensaje' => 'La cédula del enfermero debe contener exactamente 8 números.'], 400);
    }

    if (!in_array($datos['tipo_elemento'] ?? null, ['organo', 'documentacion', 'otro'], true)) {
        responderJson(['mensaje' => 'El tipo de elemento seleccionado no es válido.'], 400);
    }

    $datosValidados = [
        'nombre_elemento' => trim($datos['nombre_elemento']),
        'descripcion_elemento' => trim($datos['descripcion_elemento']),
        'tipo_elemento' => $datos['tipo_elemento'],
        'ci_enfermero' => trim($datos['ci_enfermero']),
        'habitacion_retiro' => trim($datos['habitacion_retiro']),
        'piso_retiro' => trim($datos['piso_retiro'])
    ];

    if ($metodo === 'POST') {

        $idTransportable = $elemento->crear($datosValidados);

        responderJson([
            'mensaje' => 'Elemento registrado correctamente.',
            'id_transportable' => $idTransportable
        ], 201);
    }

    if (!isset($datos['id_transportable']) || !is_string($datos['id_transportable']) || trim($datos['id_transportable']) === '') {
        responderJson(['mensaje' => 'El identificador del elemento es obligatorio.'], 400);
    }

    $datosValidados['id_transportable'] = trim($datos['id_transportable']);

    $elemento->actualizar($datosValidados);

    responderJson(['mensaje' => 'Elemento actualizado correctamente.']);

} catch (InvalidArgumentException $error) {

    responderJson(['mensaje' => $error->getMessage()], 400);

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson(['mensaje' => 'No se pudo procesar el elemento. Verifique los datos relacionados.'], 409);
    }

    responderJson(['mensaje' => 'Ocurrió un error al procesar los elementos.'], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson(['mensaje' => 'Ocurrió un error al procesar los elementos.'], 500);
}
