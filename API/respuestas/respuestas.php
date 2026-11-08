
<?php
require_once __DIR__ . '/../../backend/models/Respuesta.php';
require_once __DIR__ . '/../../backend/models/Pregunta.php';
require_once __DIR__ . '/../../backend/config/database.php';

header('Content-Type: application/json; charset=utf-8');


try {

    $accion = $_GET['accion'] ?? '';

    $respuestaModelo = new Respuesta();
    $preguntaModelo = new Pregunta();

    $conexion = Database::getInstancia()->getConexion();


    switch ($accion) {


        // ==========================================
        // OBTENER ESTADÍSTICAS
        // ==========================================

        case 'obtenerEstadisticas':

            $tituloEncuesta = $_GET['titulo_enc'] ?? '';
            $idCategoria = $_GET['id_categoria'] ?? '';

            if ($tituloEncuesta === '' || $idCategoria === '') {

                http_response_code(400);

                echo json_encode([
                    'error' => 'Falta el título de la encuesta o su categoría'
                ]);

                exit;
            }


            $estadisticas = $respuestaModelo->obtenerEstadisticasPorEncuesta(
                $tituloEncuesta,
                $idCategoria
            );

            echo json_encode($estadisticas);

            exit;


        // ==========================================
        // REGISTRAR RESPUESTAS DE LOS USUARIOS
        // ==========================================

        case 'guardar':

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
                !isset($datos['respuestas']) ||
                !is_array($datos['respuestas'])
            ) {

                http_response_code(400);

                echo json_encode([
                    'error' => 'Los datos enviados no son válidos'
                ]);

                exit;
            }


            $tituloEncuesta = trim($datos['titulo_enc']);
            $idCategoria = trim($datos['id_categoria']);
            $respuestas = $datos['respuestas'];


            if (count($respuestas) === 0) {

                http_response_code(400);

                echo json_encode([
                    'error' => 'Debe responder al menos una pregunta'
                ]);

                exit;
            }


            // Comprobar que la encuesta exista y esté activa.
            $sqlEncuesta = "SELECT
                                e.titulo_enc,
                                e.id_categoria,
                                e.nombre_cat

                            FROM encuesta e

                            INNER JOIN categoria c
                                ON e.id_categoria = c.id_categoria
                                AND e.nombre_cat = c.nombre_cat

                            WHERE e.titulo_enc = :titulo_enc
                            AND e.id_categoria = :id_categoria
                            AND c.tipo_cat = 'encuestas'
                            AND c.estado_cat = 'activa'";

            $stmtEncuesta = $conexion->prepare($sqlEncuesta);

            $stmtEncuesta->execute([
                ':titulo_enc' => $tituloEncuesta,
                ':id_categoria' => $idCategoria
            ]);

            $encuesta = $stmtEncuesta->fetch(PDO::FETCH_ASSOC);


            if (!$encuesta) {

                http_response_code(404);

                echo json_encode([
                    'error' => 'La encuesta no existe o ya no está habilitada'
                ]);

                exit;
            }


            // Obtener las preguntas reales de la encuesta.
            $preguntas = $preguntaModelo->obtenerPorEncuesta(
                $tituloEncuesta,
                $idCategoria
            );

            $idsValidos = array_column(
                $preguntas,
                'id_pregunta'
            );

            $idsRecibidos = [];


            // Validar que cada respuesta corresponda a una pregunta real.
            foreach ($respuestas as $respuesta) {

                $idPregunta = $respuesta['id_pregunta'] ?? '';

                $valor = filter_var(
                    $respuesta['valor'] ?? null,
                    FILTER_VALIDATE_INT
                );


                if (
                    $idPregunta === '' ||
                    $valor === false ||
                    $valor < 1 ||
                    $valor > 5 ||
                    !in_array($idPregunta, $idsValidos, true) ||
                    in_array($idPregunta, $idsRecibidos, true)
                ) {

                    http_response_code(400);

                    echo json_encode([
                        'error' => 'Una o más respuestas no son válidas'
                    ]);

                    exit;
                }

                $idsRecibidos[] = $idPregunta;
            }


            // Guardar todas las respuestas en una única transacción.
            $conexion->beginTransaction();

            try {

                foreach ($respuestas as $respuesta) {

                    $idRespuesta = 'RES' . bin2hex(random_bytes(6));

                    $respuestaModelo->crear(
                        $idRespuesta,
                        $respuesta['id_pregunta'],
                        $tituloEncuesta,
                        $idCategoria,
                        $encuesta['nombre_cat'],
                        (string) $respuesta['valor'],
                        'opcion multiple'
                    );
                }


                $conexion->commit();


                echo json_encode([
                    'exito' => true,
                    'mensaje' => 'La encuesta fue enviada correctamente'
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

    error_log('Error en respuestas.php: ' . $e->getMessage());

    http_response_code(500);

    echo json_encode([
        'error' => 'Ocurrió un error al procesar las respuestas'
    ]);
}
