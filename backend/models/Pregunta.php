
<?php
require_once __DIR__ . '/../config/database.php';

class Pregunta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Obtener todas las preguntas de una encuesta.
    public function obtenerPorEncuesta(
        string $tituloEncuesta,
        string $idCategoria
    ): array {

        $sql = "SELECT *
                FROM pregunta
                WHERE titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria
                ORDER BY id_pregunta";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // Buscar una pregunta por sus identificadores.
    public function obtenerPorId(
        string $idPregunta,
        string $tituloEncuesta,
        string $idCategoria
    ): ?array {

        $sql = "SELECT *
                FROM pregunta
                WHERE id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ======================================================
    // CREAR UNA RESPUESTA
    // ======================================================

    public function crear(
        string $idRespuesta,
        string $idPregunta,
        string $tituloEncuesta,
        string $idCategoria,
        ?string $informacion,
        string $tipo
    ): bool {

        $sql = "INSERT INTO respuesta (
                    id_respuesta,
                    id_pregunta,
                    titulo_enc,
                    id_categoria,
                    informacion,
                    tipo
                )
                VALUES (
                    :id_respuesta,
                    :id_pregunta,
                    :titulo_enc,
                    :id_categoria,
                    :informacion,
                    :tipo
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':informacion', $informacion);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }

    // Modificar el texto de una pregunta existente.
    public function editarPregunta(
        string $idPregunta,
        string $tituloEncuesta,
        string $idCategoria,
        string $nombrePregunta
    ): bool {

        $sql = "UPDATE pregunta
                SET nombre_pregunta = :nombre_pregunta
                WHERE id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':nombre_pregunta', $nombrePregunta);

        return $sentencia->execute();
    }


    // Eliminar primero las respuestas y después la pregunta.
    public function eliminar(
        string $idPregunta,
        string $tituloEncuesta,
        string $idCategoria
    ): bool {

        // Primero eliminamos las respuestas asociadas.
        $sqlRespuestas = "DELETE FROM respuesta
                          WHERE id_pregunta = :id_pregunta
                          AND titulo_enc = :titulo_enc
                          AND id_categoria = :id_categoria";

        $sentenciaRespuestas = $this->conexion->prepare($sqlRespuestas);

        $sentenciaRespuestas->execute([
            ':id_pregunta' => $idPregunta,
            ':titulo_enc' => $tituloEncuesta,
            ':id_categoria' => $idCategoria
        ]);


        // Después eliminamos la pregunta.
        $sqlPregunta = "DELETE FROM pregunta
                        WHERE id_pregunta = :id_pregunta
                        AND titulo_enc = :titulo_enc
                        AND id_categoria = :id_categoria";

        $sentenciaPregunta = $this->conexion->prepare($sqlPregunta);

        $sentenciaPregunta->execute([
            ':id_pregunta' => $idPregunta,
            ':titulo_enc' => $tituloEncuesta,
            ':id_categoria' => $idCategoria
        ]);

        return $sentenciaPregunta->rowCount() > 0;
    }
}
