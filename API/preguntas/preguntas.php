<?php
session_start();

require_once __DIR__ . '/../../backend/models/Pregunta.php';
require_once __DIR__ . '/../../backend/models/Encuesta.php';
require_once __DIR__ . '/../../backend/config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Comprobar que la persona tenga una sesión de administrador.
function verificarAdministrador(): bool
{
    $usuario = $_SESSION['usuario'] ?? [];

    return isset($usuario['cargo'])
        && strtolower(trim($usuario['cargo'])) === 'administrador';
}

try {

    $accion = $_GET['accion'] ?? '';

    $preguntaModelo = new Pregunta();
    $encuestaModelo = new Encuesta();

    switch ($accion) {

        case 'obtenerPorEncuesta':

            $idEncuesta = $_GET['id_encuesta'] ?? '';
            $idCategoria = $_GET['id_categoria'] ?? '';

            if ($idEncuesta === '' || $idCategoria === '') {
                http_response_code(400);

                echo json_encode([
                    'error' => 'Falta el identificador de la encuesta o categoría'
                ]);

                exit;
            }

            echo json_encode(
                $preguntaModelo->obtenerPorEncuesta(
                    $idEncuesta,
                    $idCategoria
                )
            );

            exit;


        case 'guardarLote':

            // Solo un administrador puede modificar las preguntas.
            if (!verificarAdministrador()) {
                http_response_code(403);

                echo json_encode([
                    'error' => 'No tiene permisos para modificar las encuestas'
                ]);

                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);

                echo json_encode([
                    'error' => 'Método no permitido'
                ]);

                exit;
            }

            $datos = json_decode(file_get_contents('php://input'), true);

            if (
                !is_array($datos) ||
                empty($datos['id_encuesta']) ||
                empty($datos['id_categoria']) ||
                !isset($datos['preguntas']) ||
                !is_array($datos['preguntas'])
            ) {
                http_response_code(400);

                echo json_encode([
                    'exito' => false,
                    'mensaje' => 'Los datos enviados no son válidos'
                ]);

                exit;
            }

            $idEncuesta = $datos['id_encuesta'];
            $idCategoria = $datos['id_categoria'];
            $preguntasNuevas = $datos['preguntas'];

            // Comprobar que la encuesta realmente exista.
            $encuesta = $encuestaModelo->obtenerPorId(
                $idEncuesta,
                $idCategoria
            );

            if (!$encuesta) {
                http_response_code(404);

                echo json_encode([
                    'exito' => false,
                    'mensaje' => 'La encuesta no existe'
                ]);

                exit;
            }

            // Obtener las preguntas que ya existen.
            $preguntasActuales = $preguntaModelo->obtenerPorEncuesta(
                $idEncuesta,
                $idCategoria
            );

            $idsActuales = array_column(
                $preguntasActuales,
                'id_pregunta'
            );

            $idsRecibidos = [];

            // Validar todos los textos antes de modificar la base.
            foreach ($preguntasNuevas as $pregunta) {

                $idPregunta = trim($pregunta['id_pregunta'] ?? '');
                $nombrePregunta = trim($pregunta['nombre_pregunta'] ?? '');

                if ($idPregunta === '' || $nombrePregunta === '') {
                    http_response_code(400);

                    echo json_encode([
                        'exito' => false,
                        'mensaje' => 'Todas las preguntas deben tener un texto'
                    ]);

                    exit;
                }

                if (mb_strlen($nombrePregunta) > 50) {
                    http_response_code(400);

                    echo json_encode([
                        'exito' => false,
                        'mensaje' => 'Una pregunta supera los 50 caracteres permitidos'
                    ]);

                    exit;
                }

                if (in_array($idPregunta, $idsRecibidos, true)) {
                    http_response_code(400);

                    echo json_encode([
                        'exito' => false,
                        'mensaje' => 'Hay preguntas repetidas'
                    ]);

                    exit;
                }

                $idsRecibidos[] = $idPregunta;
            }

            // Guardar todo dentro de una transacción.
            $conexion = Database::getInstancia()->getConexion();

            $conexion->beginTransaction();

            try {

                foreach ($preguntasNuevas as $pregunta) {

                    $idPregunta = $pregunta['id_pregunta'];
                    $nombrePregunta = trim($pregunta['nombre_pregunta']);

                    if (in_array($idPregunta, $idsActuales, true)) {

                        // Si existe, modificamos su texto.
                        $preguntaModelo->editarPregunta(
                            $idPregunta,
                            $idEncuesta,
                            $idCategoria,
                            $nombrePregunta
                        );

                    } else {

                        // Si no existe, creamos una pregunta nueva.
                        $preguntaModelo->crear(
                            $idPregunta,
                            $idEncuesta,
                            $idCategoria,
                            $nombrePregunta
                        );
                    }
                }

                // Eliminar las preguntas que el administrador quitó.
                foreach ($idsActuales as $idActual) {

                    if (!in_array($idActual, $idsRecibidos, true)) {

                        $preguntaModelo->eliminar(
                            $idActual,
                            $idEncuesta,
                            $idCategoria
                        );
                    }
                }

                $conexion->commit();

                echo json_encode([
                    'exito' => true,
                    'mensaje' => 'Las preguntas se guardaron correctamente'
                ]);

            } catch (Throwable $e) {

                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }

                throw $e;
            }

            exit;


        default:

            http_response_code(400);

            echo json_encode([
                'error' => 'Acción no válida'
            ]);

            exit;
    }

} catch (Throwable $e) {

    error_log('Error en preguntas.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'Ocurrió un error al procesar las preguntas'
    ]);
}