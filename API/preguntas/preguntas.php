<?php
require_once __DIR__ . '/../../backend/models/Pregunta.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $accion = $_GET['accion'] ?? '';
    $preguntaModelo = new Pregunta();

    switch ($accion) {

        // ==============================================
        // OBTENER PREGUNTAS DE UNA ENCUESTA
        // ==============================================

        case 'obtenerPorEncuesta':

            $idEncuesta = $_GET['id_encuesta'] ?? '';
            $idCategoria = $_GET['id_categoria'] ?? '';

            if (empty($idEncuesta) || empty($idCategoria)) {
                http_response_code(400);
                echo json_encode(['error' => 'Falta el ID de la encuesta o categoría']);
                exit;
            }

            $preguntas = $preguntaModelo->obtenerPorEncuesta(
                $idEncuesta,
                $idCategoria
            );

            echo json_encode($preguntas);

            exit;


        // ==============================================
        // GUARDAR PREGUNTAS
        // ==============================================

        case 'guardarLote':

            $datos = json_decode(file_get_contents('php://input'), true);

            if (
                !$datos ||
                empty($datos['id_encuesta']) ||
                empty($datos['id_categoria']) ||
                !isset($datos['preguntas']) ||
                !is_array($datos['preguntas'])
            ) {
                http_response_code(400);
                echo json_encode(['exito' => false, 'mensaje' => 'Datos inválidos']);
                exit;
            }

            $idEncuesta = $datos['id_encuesta'];
            $idCategoria = $datos['id_categoria'];

            $exitoGlobal = true;

            foreach ($datos['preguntas'] as $pregunta) {

                if (
                    empty($pregunta['id_pregunta']) ||
                    empty($pregunta['nombre_pregunta']) ||
                    !in_array(
                        $pregunta['tipo'] ?? '',
                        ['opcion multiple', 'texto'],
                        true
                    )
                ) {
                    $exitoGlobal = false;
                    continue;
                }

                // Comprobamos si la pregunta ya existe.
                $existente = $preguntaModelo->obtenerPorId(
                    $pregunta['id_pregunta'],
                    $idEncuesta,
                    $idCategoria
                );

                if ($existente) {

                    $resultado = $preguntaModelo->editarPregunta(
                        $pregunta['id_pregunta'],
                        $idEncuesta,
                        $idCategoria,
                        $pregunta['nombre_pregunta'],
                        $pregunta['tipo']
                    );

                } else {

                    $resultado = $preguntaModelo->crear(
                        $pregunta['id_pregunta'],
                        $idEncuesta,
                        $idCategoria,
                        $pregunta['nombre_pregunta'],
                        $pregunta['tipo']
                    );
                }

                if (!$resultado) {
                    $exitoGlobal = false;
                }
            }

            echo json_encode(['exito' => $exitoGlobal]);

            exit;


        default:

            http_response_code(400);
            echo json_encode(['error' => 'Acción no válida']);

            exit;
    }

} catch (Throwable $e) {

    error_log('Error en preguntas.php: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);

    exit;
}