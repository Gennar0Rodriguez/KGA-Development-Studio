<?php
require_once __DIR__ . '/../config/Database.php';

class Encuesta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function crear(string $idEncuesta, string $idCategoria, string $titulo, ?string $descripcion): bool {
        $sql = 'INSERT INTO encuesta (id_encuesta, id_categoria, titulo_enc, descripcion_enc) VALUES (:id_encuesta, :id_categoria, :titulo_enc, :descripcion_enc)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':id_categoria', $idCategoria);
        $sentencia->bindParam(':titulo_enc', $titulo);
        $sentencia->bindParam(':descripcion_enc', $descripcion);
        return $sentencia->execute();
    }

    public function obtenerTodos(): array {
        $sql = 'SELECT * FROM encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(string $idEncuesta, string $idCategoria): ?array {
        $sql = 'SELECT * FROM encuesta WHERE id_encuesta = :id_encuesta AND id_categoria = :id_categoria';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':id_categoria', $idCategoria);
        $sentencia->execute();
        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    public function obtenerPorCategoria(string $idCategoria): array {
        $sql = 'SELECT * FROM encuesta WHERE id_categoria = :id_categoria';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_categoria', $idCategoria);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function editarEncuesta(string $idEncuesta, string $idCategoria, string $titulo, ?string $descripcion): bool {
        $sql = 'UPDATE encuesta 
                SET titulo_enc = :titulo_enc, 
                    descripcion_enc = :descripcion_enc 
                WHERE id_encuesta = :id_encuesta AND id_categoria = :id_categoria';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':id_categoria', $idCategoria);
        $sentencia->bindParam(':titulo_enc', $titulo);
        $sentencia->bindParam(':descripcion_enc', $descripcion);

        return $sentencia->execute();
    }

    public function eliminar(string $idEncuesta, string $idCategoria): bool {
        $sql = 'DELETE FROM encuesta WHERE id_encuesta = :id_encuesta AND id_categoria = :id_categoria';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':id_categoria', $idCategoria);
        return $sentencia->execute();
    }
}