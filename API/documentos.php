<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");

require_once __DIR__ . '/../backend/models/Documento.php';

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$documento = new Documento();

try {
    switch ($metodo) {

        case 'GET':
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode($documento->obtenerTodos());
            exit;

        case 'POST':
            header("Content-Type: application/json; charset=UTF-8");

            $ciAdmin = $_SESSION['usuario']['ci_admin'] 
                ?? $_SESSION['ci_admin'] 
                ?? $_SESSION['ci'] 
                ?? null;

            if (!$ciAdmin) {
                http_response_code(401);
                echo json_encode(['error' => 'Sesión no válida o no autenticada']);
                exit;
            }

            $titulo = $_POST['documentTitle'] ?? null;
            $descripcion = $_POST['documentDescription'] ?? '';
            $tipo = $_POST['documentTipe'] ?? 'C1';

            if (!$titulo || !isset($_FILES['documentFile'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Complete el título y seleccione un archivo']);
                exit;
            }

            // Mapeo simple a las categorías que existen en tu base de datos (C1, C2, C3, C4, C5)
            $idCategoria = 'C1'; 
            if ($tipo === 'Emergencia') $idCategoria = 'C2';

            $idDoc = 'DOC' . rand(100, 999);

            $file = $_FILES['documentFile'];
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $nombreArchivo = $idDoc . '.' . $extension;

            $dirDestino = __DIR__ . '/../uploads/documentos/';
            if (!file_exists($dirDestino)) {
                mkdir($dirDestino, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dirDestino . $nombreArchivo)) {
                if ($documento->crear($idDoc, $idCategoria, $titulo, $descripcion, $ciAdmin)) {
                    http_response_code(201);
                    echo json_encode(['mensaje' => 'Documento subido correctamente']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Error al guardar en la base de datos']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al guardar el archivo localmente']);
            }
            exit;

        case 'DELETE':
            header("Content-Type: application/json; charset=UTF-8");
            $id = $_GET['id'] ?? null;

            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de documento obligatorio']);
                exit;
            }

            if ($documento->eliminar($id)) {
                echo json_encode(['mensaje' => 'Documento eliminado correctamente']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'No se pudo eliminar el documento']);
            }
            exit;
    }
} catch (Exception $e) {
    header("Content-Type: application/json; charset=UTF-8");
    http_response_code(500);
    echo json_encode(['error' => 'Error en el servidor: ' . $e->getMessage()]);
    exit;
}