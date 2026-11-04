<?php
require_once __DIR__ . '/../config/Database.php';

class Pregunta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function crear(string $idPregunta, string $idEncuesta, string $nombrePregunta, string $tipo): bool {
        $sql = 'INSERT INTO pregunta (id_pregunta, id_encuesta, nombre_pregunta, tipo) VALUES (:id_pregunta, :id_encuesta, :nombre_pregunta, :tipo)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':nombre_pregunta', $nombrePregunta);
        $sentencia->bindParam(':tipo', $tipo);
        return $sentencia->execute();
    }

    public function obtenerTodos(): array {
        $sql = 'SELECT * FROM pregunta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(string $idPregunta, string $idEncuesta): ?array {
        $sql = 'SELECT * FROM pregunta WHERE id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->execute();
        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    public function obtenerPorEncuesta(string $idEncuesta): array {
        $sql = 'SELECT * FROM pregunta WHERE id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function editarPregunta(string $idPregunta, string $idEncuesta, string $nombrePregunta, string $tipo): bool {
        $sql = 'UPDATE pregunta 
                SET nombre_pregunta = :nombre_pregunta, 
                    tipo = :tipo 
                WHERE id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':nombre_pregunta', $nombrePregunta);
        $sentencia->bindParam(':tipo', $tipo);

        return $sentencia->execute();
    }

    public function eliminar(string $idPregunta, string $idEncuesta): bool {
        $sql = 'DELETE FROM pregunta WHERE id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        return $sentencia->execute();
    }
}