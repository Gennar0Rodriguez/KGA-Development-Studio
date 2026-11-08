
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


        // ==========================================
        // OBTENER PREGUNTAS DE UNA ENCUESTA
        // ==========================================

        case 'obtenerPorEncuesta':

            $tituloEncuesta = $_GET['titulo_enc'] ?? '';
            $idCategoria = $_GET['id_categoria'] ?? '';

            if ($tituloEncuesta === '' || $idCategoria === '') {

                http_response_code(400);

                echo json_encode([
                    'error' => 'Falta el título de la encuesta o su categoría'
                ]);

                exit;
            }


            echo json_encode(
                $preguntaModelo->obtenerPorEncuesta(
                    $tituloEncuesta,
                    $idCategoria
                )
            );

            exit;


        // ==========================================
        // GUARDAR PREGUNTAS
        // ==========================================

        case 'guardarLote':

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
                empty($datos['titulo_enc']) ||
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


            $tituloEncuesta = trim($datos['titulo_enc']);
            $idCategoria = trim($datos['id_categoria']);
            $preguntasNuevas = $datos['preguntas'];


            // Comprobar que la encuesta exista.
            $encuesta = $encuestaModelo->obtenerPorId(
                $tituloEncuesta,
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
                $tituloEncuesta,
                $idCategoria
            );

            $idsActuales = array_column(
                $preguntasActuales,
                'id_pregunta'
            );

            $idsRecibidos = [];


            // Obtener la conexión a la base de datos.
            $conexion = Database::getInstancia()->getConexion();


            // Comprobar que los identificadores nuevos no existan
            // en otra encuesta, ya que el MER referencia id_pregunta individualmente.
            $sqlIdExistente = "SELECT COUNT(*)
                               FROM pregunta
                               WHERE id_pregunta = :id_pregunta";

            $sentenciaIdExistente = $conexion->prepare($sqlIdExistente);


            // Validar todos los textos e identificadores antes de modificar la base.
            foreach ($preguntasNuevas as $pregunta) {

                if (!is_array($pregunta)) {

                    http_response_code(400);

                    echo json_encode([
                        'exito' => false,
                        'mensaje' => 'El formato de una pregunta no es válido'
                    ]);

                    exit;
                }


                $idPregunta = trim($pregunta['id_pregunta'] ?? '');
                $nombrePregunta = trim($pregunta['nombre_pregunta'] ?? '');


                if ($idPregunta === '' || $nombrePregunta === '') {

                    http_response_code(400);

                    echo json_encode([
                        'exito' => false,
                        'mensaje' => 'Todas las preguntas deben tener un identificador y un texto'
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


                // Si no pertenece a las preguntas actuales,
                // comprobamos que el ID no esté utilizado en otra encuesta.
                if (!in_array($idPregunta, $idsActuales, true)) {

                    $sentenciaIdExistente->execute([
                        ':id_pregunta' => $idPregunta
                    ]);

                    if ((int) $sentenciaIdExistente->fetchColumn() > 0) {

                        http_response_code(409);

                        echo json_encode([
                            'exito' => false,
                            'mensaje' => 'El identificador de una pregunta ya está siendo utilizado'
                        ]);

                        exit;
                    }
                }


                $idsRecibidos[] = $idPregunta;
            }


            // Guardar todo dentro de una transacción.
            $conexion->beginTransaction();

            try {

                foreach ($preguntasNuevas as $pregunta) {

                    $idPregunta = trim($pregunta['id_pregunta']);
                    $nombrePregunta = trim($pregunta['nombre_pregunta']);


                    if (in_array($idPregunta, $idsActuales, true)) {

                        // Si existe, modificamos su texto.
                        $preguntaModelo->editarPregunta(
                            $idPregunta,
                            $tituloEncuesta,
                            $idCategoria,
                            $nombrePregunta
                        );

                    } else {

                    // Si no existe, creamos una pregunta nueva.
                    $preguntaModelo->crear(
                        $idPregunta,
                        $tituloEncuesta,
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
                            $tituloEncuesta,
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
