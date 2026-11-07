
<?php

session_start();

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../backend/models/Elemento.php';

// Devuelve una respuesta JSON y finaliza la ejecución.
function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);

    echo json_encode($datos, JSON_UNESCAPED_UNICODE);

    exit;
}

// Solo un administrador puede registrar entidades desde este formulario.
function verificarAdministrador(): void
{
    $cargo = $_SESSION['usuario']['cargo'] ?? '';

    if (strtolower(trim($cargo)) !== 'administrador') {

        responderJson([
            'mensaje' => 'No tiene permisos para realizar esta operación.'
        ], 403);
    }
}

// Comprueba que un campo exista, no esté vacío y respete su longitud.
function campoValido(array $datos, string $campo, int $maximo): bool
{
    return isset($datos[$campo])
        && is_string($datos[$campo])
        && trim($datos[$campo]) !== ''
        && mb_strlen(trim($datos[$campo])) <= $maximo;
}

// Respondemos a las solicitudes OPTIONS.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

verificarAdministrador();


// GET: devuelve todos los elementos registrados.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        $elemento = new Elemento();

        responderJson($elemento->obtenerTodos());

    } catch (Throwable $error) {

        error_log($error->getMessage());

        responderJson([
            'mensaje' => 'Ocurrió un error al consultar los elementos.'
        ], 500);

    }
}


// Este controlador recibe únicamente solicitudes POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    responderJson([
        'mensaje' => 'Método no permitido.'
    ], 405);
}

// Obtenemos los datos enviados desde JavaScript.
$datos = json_decode(file_get_contents('php://input'), true);

if (!is_array($datos)) {

    responderJson([
        'mensaje' => 'Los datos enviados no son válidos.'
    ], 400);
}

// Validamos los campos según los atributos definidos en el MER.
$campos = [
    'nombre_elemento' => 50,
    'descripcion_elemento' => 50,
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

// La cédula debe contener exactamente ocho números.
if (!preg_match('/^[0-9]{8}$/', $datos['ci_enfermero'])) {

    responderJson([
        'mensaje' => 'La cédula del enfermero debe contener exactamente 8 números.'
    ], 400);
}

// El tipo debe coincidir con los valores permitidos por el ENUM del MER.
$tiposPermitidos = [
    'organo',
    'documentacion',
    'otro'
];

if (
    !isset($datos['tipo_elemento']) ||
    !in_array($datos['tipo_elemento'], $tiposPermitidos, true)
) {

    responderJson([
        'mensaje' => 'El tipo de elemento seleccionado no es válido.'
    ], 400);
}

try {

    $elemento = new Elemento();

    // Enviamos los datos validados al modelo.
    // El identificador de transportable se genera dentro del modelo.
    $idTransportable = $elemento->crear([

        'nombre_elemento' => trim($datos['nombre_elemento']),

        'descripcion_elemento' => trim($datos['descripcion_elemento']),

        'tipo_elemento' => $datos['tipo_elemento'],

        'ci_enfermero' => trim($datos['ci_enfermero']),

        'habitacion_retiro' => trim($datos['habitacion_retiro']),

        'piso_retiro' => trim($datos['piso_retiro'])

    ]);

    responderJson([
        'mensaje' => 'Elemento registrado correctamente.',
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
            'mensaje' => 'No se pudo registrar el elemento. Verifique que el enfermero exista y que los datos no estén duplicados.'
        ], 409);
    }

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el elemento.'
    ], 500);

} catch (Throwable $error) {

    error_log($error->getMessage());

    responderJson([
        'mensaje' => 'Ocurrió un error al registrar el elemento.'
    ], 500);
}
