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

            $idEncuesta = $_GET['id_encuesta'] ?? '';
            $idCategoria = $_GET['id_categoria'] ?? '';

            if ($idEncuesta === '' || $idCategoria === '') {
                http_response_code(400);

                echo json_encode([
                    'error' => 'Falta el identificador de la encuesta o categoría'
                ]);

                exit;
            }

            $estadisticas = $respuestaModelo->obtenerEstadisticasPorEncuesta(
                $idEncuesta,
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
                empty($datos['id_encuesta']) ||
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

            $idEncuesta = $datos['id_encuesta'];
            $idCategoria = $datos['id_categoria'];
            $respuestas = $datos['respuestas'];

            if (count($respuestas) === 0) {
                http_response_code(400);

                echo json_encode([
                    'error' => 'Debe responder al menos una pregunta'
                ]);

                exit;
            }

            // Comprobar que la encuesta exista.
            $sqlEncuesta = "SELECT id_encuesta
                            FROM encuesta
                            WHERE id_encuesta = :id_encuesta
                            AND id_categoria = :id_categoria";

            $stmtEncuesta = $conexion->prepare($sqlEncuesta);

            $stmtEncuesta->execute([
                ':id_encuesta' => $idEncuesta,
                ':id_categoria' => $idCategoria
            ]);

            if (!$stmtEncuesta->fetch()) {
                http_response_code(404);

                echo json_encode([
                    'error' => 'La encuesta seleccionada no existe'
                ]);

                exit;
            }

            // Obtener las preguntas reales de la encuesta.
            $preguntas = $preguntaModelo->obtenerPorEncuesta(
                $idEncuesta,
                $idCategoria
            );

            $idsValidos = array_column($preguntas, 'id_pregunta');

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
                        $idEncuesta,
                        $idCategoria,
                        (string)$respuesta['valor'],
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