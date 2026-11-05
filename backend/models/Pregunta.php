<?php
require_once __DIR__ . '/../config/Database.php';

class Pregunta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // ======================================================
    // CREAR UNA PREGUNTA
    // ======================================================

    public function crear(
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria,
        string $nombrePregunta,
        string $tipo
    ): bool {

        $sql = "INSERT INTO pregunta (
                    id_pregunta,
                    id_encuesta,
                    id_categoria,
                    nombre_pregunta,
                    tipo
                )
                VALUES (
                    :id_pregunta,
                    :id_encuesta,
                    :id_categoria,
                    :nombre,
                    :tipo
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':nombre', $nombrePregunta);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }


    // ======================================================
    // OBTENER TODAS LAS PREGUNTAS
    // ======================================================

    public function obtenerTodos(): array {

        $sql = "SELECT * FROM pregunta";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // BUSCAR UNA PREGUNTA POR SUS IDENTIFICADORES
    // ======================================================

    public function obtenerPorId(
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria
    ): ?array {

        $sql = "SELECT *
                FROM pregunta
                WHERE id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ======================================================
    // OBTENER PREGUNTAS DE UNA ENCUESTA
    // ======================================================

    public function obtenerPorEncuesta(
        string $idEncuesta,
        string $idCategoria
    ): array {

        $sql = "SELECT *
                FROM pregunta
                WHERE id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // EDITAR UNA PREGUNTA
    // ======================================================

    public function editarPregunta(
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria,
        string $nombrePregunta,
        string $tipo
    ): bool {

        $sql = "UPDATE pregunta
                SET nombre_pregunta = :nombre,
                    tipo = :tipo
                WHERE id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':nombre', $nombrePregunta);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }


    // ======================================================
    // ELIMINAR UNA PREGUNTA
    // ======================================================

    public function eliminar(
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria
    ): bool {

        $sql = "DELETE FROM pregunta
                WHERE id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        return $sentencia->execute();
    }
}