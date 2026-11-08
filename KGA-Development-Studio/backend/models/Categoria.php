<?php

require_once __DIR__ . '/../config/database.php';

class Categoria
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // ======================================================
    // CATEGORÍAS DE ENCUESTAS
    // Estos métodos se conservan para no afectar ese módulo.
    // ======================================================

    public function obtenerCategoriasEncuestas(): array
    {
        $sql = "SELECT id_categoria, nombre_cat, estado_cat FROM categoria WHERE tipo_cat = 'encuestas' ORDER BY nombre_cat ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarActivas(): int
    {
        $sql = "SELECT COUNT(*) FROM categoria WHERE tipo_cat = 'encuestas' AND estado_cat = 'activa'";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return (int) $sentencia->fetchColumn();
    }

    public function crear(
        string $idCategoria,
        string $nombre,
        string $estado
    ): bool {
        $sql = "INSERT INTO categoria (id_categoria, nombre_cat, tipo_cat, estado_cat) VALUES (:id_categoria, :nombre_cat, 'encuestas', :estado_cat)";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_categoria' => $idCategoria,
            ':nombre_cat' => $nombre,
            ':estado_cat' => $estado
        ]);
    }

    public function cambiarEstado(
        string $idCategoria,
        string $estado
    ): bool {
        $sql = "UPDATE categoria SET estado_cat = :estado_cat WHERE id_categoria = :id_categoria AND tipo_cat = 'encuestas'";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':estado_cat' => $estado,
            ':id_categoria' => $idCategoria
        ]);
    }

    // ======================================================
    // CATEGORÍAS DE DOCUMENTOS
    // Estos métodos solamente trabajan con tipo_cat = docs.
    // ======================================================

    // Obtener categorías de documentos.
    // Si $soloActivas es true, excluye las deshabilitadas.
    public function obtenerCategoriasDocumentos(
        bool $soloActivas = false
    ): array {
        $sql = "SELECT id_categoria, nombre_cat, estado_cat FROM categoria WHERE tipo_cat = 'docs'";

        if ($soloActivas) {
            $sql .= " AND estado_cat = 'activa'";
        }

        $sql .= " ORDER BY nombre_cat ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Comprobar que una categoría exista, sea de documentos
    // y esté activa. Se utiliza antes de subir un PDF.
    public function existeCategoriaDocumentoActiva(
        string $idCategoria
    ): bool {
        $sql = "SELECT COUNT(*) FROM categoria WHERE id_categoria = :id_categoria AND tipo_cat = 'docs' AND estado_cat = 'activa'";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':id_categoria' => $idCategoria
        ]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    // Crear una categoría nueva para documentos.
    public function crearCategoriaDocumento(
        string $idCategoria,
        string $nombre
    ): bool {
        $sql = "INSERT INTO categoria ( id_categoria, nombre_cat, tipo_cat, estado_cat ) VALUES ( :id_categoria, :nombre_cat, 'docs', 'activa' )";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_categoria' => $idCategoria,
            ':nombre_cat' => $nombre
        ]);
    }

    // Cambiar el estado exclusivamente de categorías docs.
    public function cambiarEstadoDocumento(
        string $idCategoria,
        string $estado
    ): bool {
        $sql = "UPDATE categoria SET estado_cat = :estado_cat WHERE id_categoria = :id_categoria AND tipo_cat = 'docs'";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':estado_cat' => $estado,
            ':id_categoria' => $idCategoria
        ]);
    }

    // Comprobar si ya existe una categoría de documentos
    // con el mismo nombre, sin distinguir mayúsculas.
    public function existeNombreDocumento(string $nombre): bool
    {
        $sql = "SELECT COUNT(*) FROM categoria WHERE tipo_cat = 'docs' AND LOWER(nombre_cat) = LOWER(:nombre)";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':nombre' => $nombre
        ]);

        return (int) $sentencia->fetchColumn() > 0;
    }

    // Comprobar que una categoría exista dentro de documentos.
    // También permite encontrar categorías deshabilitadas.
    public function buscarCategoriaDocumento(
        string $idCategoria
    ): ?array {
        $sql = "SELECT id_categoria, nombre_cat, estado_cat FROM categoria WHERE id_categoria = :id_categoria AND tipo_cat = 'docs'";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':id_categoria' => $idCategoria
        ]);

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}