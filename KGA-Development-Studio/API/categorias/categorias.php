<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../../backend/models/Categoria.php';

// Responder siempre con JSON.
function responderJson(array $datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

// Comprobar que la sesión pertenezca a un administrador.
function verificarAdministrador(): bool
{
    $usuario = $_SESSION['usuario'] ?? [];

    return isset($usuario['cargo'])
        && strtolower(trim($usuario['cargo'])) === 'administrador';
}

try {

    $categoria = new Categoria();
    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // ======================================================
    // GET: CONSULTAR CATEGORÍAS
    // ======================================================

    if ($metodo === 'GET') {

        // El administrador puede consultar también las
        // categorías deshabilitadas para poder reactivarlas.
        $esAdmin = ($_GET['admin'] ?? '') === '1';

        if ($esAdmin && !verificarAdministrador()) {
            responderJson([
                'error' => 'No tiene permisos para consultar estas categorías'
            ], 403);
        }

        $categorias = $categoria->obtenerCategoriasDocumentos(!$esAdmin);

        responderJson($categorias);
    }

    // ======================================================
    // POST: CREAR O CAMBIAR ESTADO
    // ======================================================

    if ($metodo === 'POST') {

        if (!verificarAdministrador()) {
            responderJson([
                'error' => 'No tiene permisos para administrar categorías'
            ], 403);
        }

        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            responderJson([
                'error' => 'Los datos enviados no son válidos'
            ], 400);
        }

        $accion = $datos['accion'] ?? '';

        // --------------------------------------------------
        // CREAR CATEGORÍA
        // --------------------------------------------------

        if ($accion === 'crear') {

            $nombre = trim($datos['nombre_cat'] ?? '');

            if ($nombre === '') {
                responderJson([
                    'error' => 'Debe ingresar un nombre para la categoría'
                ], 400);
            }

            if (mb_strlen($nombre, 'UTF-8') > 50) {
                responderJson([
                    'error' => 'El nombre no puede superar los 50 caracteres'
                ], 400);
            }

            if ($categoria->existeNombreDocumento($nombre)) {
                responderJson([
                    'error' => 'Ya existe una categoría con ese nombre'
                ], 409);
            }

            $idCategoria = 'CAT' . bin2hex(random_bytes(8));

            $creada = $categoria->crearCategoriaDocumento(
                $idCategoria,
                $nombre
            );

            if (!$creada) {
                responderJson([
                    'error' => 'No se pudo crear la categoría'
                ], 500);
            }

            responderJson([
                'mensaje' => 'Categoría creada correctamente',
                'id_categoria' => $idCategoria,
                'nombre_cat' => $nombre,
                'estado_cat' => 'activa'
            ], 201);
        }

        // --------------------------------------------------
        // CAMBIAR ESTADO DE UNA CATEGORÍA
        // --------------------------------------------------

        if ($accion === 'cambiarEstado') {

            $idCategoria = trim($datos['id_categoria'] ?? '');
            $nuevoEstado = $datos['estado_cat'] ?? '';

            if (
                $idCategoria === '' ||
                !in_array(
                    $nuevoEstado,
                    ['activa', 'deshabilitada'],
                    true
                )
            ) {
                responderJson([
                    'error' => 'Los datos enviados no son válidos'
                ], 400);
            }

            $categoriaExistente = $categoria->buscarCategoriaDocumento(
                $idCategoria
            );

            if (!$categoriaExistente) {
                responderJson([
                    'error' => 'La categoría no existe'
                ], 404);
            }

            if (
                $categoriaExistente['estado_cat'] === $nuevoEstado
            ) {
                responderJson([
                    'mensaje' => 'La categoría ya tiene ese estado'
                ]);
            }

            $actualizada = $categoria->cambiarEstadoDocumento(
                $idCategoria,
                $nuevoEstado
            );

            if (!$actualizada) {
                responderJson([
                    'error' => 'No se pudo actualizar la categoría'
                ], 500);
            }

            responderJson([
                'mensaje' => 'Estado de la categoría actualizado correctamente',
                'estado_cat' => $nuevoEstado
            ]);
        }

        responderJson([
            'error' => 'La acción solicitada no es válida'
        ], 400);
    }

    responderJson([
        'error' => 'Método HTTP no permitido'
    ], 405);

} catch (Throwable $e) {

    error_log('Error en categorias.php: ' . $e->getMessage());

    responderJson([
        'error' => 'Ocurrió un error al procesar las categorías'
    ], 500);
}