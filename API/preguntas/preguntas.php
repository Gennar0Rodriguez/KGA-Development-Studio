<?php
require_once __DIR__ . '/../../backend/models/Pregunta.php';

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';
$preguntaModelo = new Pregunta();

switch ($accion) {
    case 'obtenerPorEncuesta':
        $idEncuesta = $_GET['id_encuesta'] ?? '';
        if (!empty($idEncuesta)) {
            $preguntas = $preguntaModelo->obtenerPorEncuesta($idEncuesta);
            echo json_encode($preguntas);
        } else {
            echo json_encode([]);
        }
        break;

    case 'guardarLote':
        $datos = json_decode(file_get_contents('php://input'), true);
        if ($datos && isset($datos['id_encuesta'], $datos['preguntas'])) {
            $idEncuesta = $datos['id_encuesta'];
            $preguntasNuevas = $datos['preguntas'];

            // Eliminamos las preguntas anteriores de esta encuesta para sincronizar el lote actual
            // (O puedes implementar una lógica de actualización individual según prefieras)
            $exitoGlobal = true;

            foreach ($preguntasNuevas as $p) {
                // Verificamos si ya existe o la creamos
                $existente = $preguntaModelo->obtenerPorId($p['id_pregunta'], $idEncuesta);
                if ($existente) {
                    $res = $preguntaModelo->editarPregunta($p['id_pregunta'], $idEncuesta, $p['nombre_pregunta'], $p['tipo']);
                } else {
                    $res = $preguntaModelo->crear($p['id_pregunta'], $idEncuesta, $p['nombre_pregunta'], $p['tipo']);
                }
                if (!$res) {
                    $exitoGlobal = false;
                }
            }

            echo json_encode(['exito' => $exitoGlobal]);
        } else {
            echo json_encode(['exito' => false]);
        }
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}