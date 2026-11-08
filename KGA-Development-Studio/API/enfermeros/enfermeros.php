
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Enfermero.php';

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

function validarDatos(array $datos): array
{
    $campos = [
        'ci_enfermero' => 8,
        'nombre_enfermero' => 50,
        'apellido_enfermero' => 50
    ];

    foreach ($campos as $campo => $maximo) {
        if (!campoValido($datos, $campo, $maximo)) {
            responderJson([
                'mensaje' => "El campo {$campo} es obligatorio o no es válido."
            ], 400);
        }
    }

    if (!preg_match('/^[0-9]{8}$/', $datos['ci_enfermero'])) {
        responderJson([
            'mensaje' => 'La cédula debe contener exactamente 8 números.'
        ], 400);
    }

    return [
        'ci_enfermero' => trim($datos['ci_enfermero']),
        'nombre_enfermero' => trim($datos['nombre_enfermero']),
        'apellido_enfermero' => trim($datos['apellido_enfermero'])
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

verificarAdministrador();

try {

    $enfermero = new Enfermero();
    $metodo = $_SERVER['REQUEST_METHOD'];

    // GET: devuelve todos los enfermeros.
    if ($metodo === 'GET') {
        responderJson($enfermero->obtenerTodos());
    }

    // DELETE: elimina un enfermero.
if ($metodo === 'DELETE') {

    $ci = trim($_GET['ci_enfermero'] ?? '');

    if ($ci === '') {
        responderJson(['mensaje' => 'La cédula del enfermero es obligatoria.'], 400);
    }

    try {

        if (!$enfermero->eliminar($ci)) {
            responderJson(['mensaje' => 'No se encontró el enfermero indicado.'], 404);
        }

        responderJson(['mensaje' => 'Enfermero eliminado correctamente.']);

    } catch (RuntimeException $error) {

        responderJson(['mensaje' => $error->getMessage()], 409);

    } catch (PDOException $error) {

        if ($error->getCode() === '23000') {
            responderJson(['mensaje' => 'No se puede eliminar el enfermero porque tiene registros relacionados.'], 409);
        }

        throw $error;
    }
}

    // Solo aceptamos POST y PUT para modificar los datos.
    if (!in_array($metodo, ['POST', 'PUT'], true)) {
        responderJson([
            'mensaje' => 'Método no permitido.'
        ], 405);
    }

    $datosRecibidos = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($datosRecibidos)) {
        responderJson([
            'mensaje' => 'Los datos enviados no son válidos.'
        ], 400);
    }

    $datos = validarDatos($datosRecibidos);

    // POST: registra un nuevo enfermero.
    if ($metodo === 'POST') {

        $enfermero->crear($datos);

        responderJson([
            'mensaje' => 'Enfermero registrado correctamente.'
        ], 201);
    }

    // PUT: actualiza un enfermero existente.
    if ($metodo === 'PUT') {

        $actualizado = $enfermero->actualizar($datos);

        if (!$actualizado) {
            responderJson([
                'mensaje' => 'No se encontró el enfermero indicado.'
            ], 404);
        }

        responderJson([
            'mensaje' => 'Enfermero actualizado correctamente.'
        ]);
    }

} catch (PDOException $error) {

    error_log($error->getMessage());

    if ($error->getCode() === '23000') {
        responderJson([
            'mensaje' => 'Ya existe un enfermero con esa cédula.'
        ], 409);
    }

    responderJson([
        'mensaje' => 'Ocurrió un error al procesar los enfermeros.'
    ], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson([
        'mensaje' => 'Ocurrió un error al procesar los enfermeros.'
    ], 500);
}
