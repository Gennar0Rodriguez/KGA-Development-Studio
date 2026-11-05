<?php
require_once __DIR__ . '/../config/Database.php';

class Respuesta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // ======================================================
    // CREAR UNA RESPUESTA
    // ======================================================

    public function crear(
        string $idRespuesta,
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria,
        ?string $informacion,
        string $tipo
    ): bool {

        $sql = "INSERT INTO respuesta (
                    id_respuesta,
                    id_pregunta,
                    id_encuesta,
                    id_categoria,
                    informacion,
                    tipo
                )
                VALUES (
                    :id_respuesta,
                    :id_pregunta,
                    :id_encuesta,
                    :id_categoria,
                    :informacion,
                    :tipo
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':informacion', $informacion);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }


    // ======================================================
    // OBTENER TODAS LAS RESPUESTAS
    // ======================================================

    public function obtenerTodos(): array {

        $sql = "SELECT * FROM respuesta";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // BUSCAR UNA RESPUESTA POR SUS IDENTIFICADORES
    // ======================================================

    public function obtenerPorId(
        string $idRespuesta,
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria
    ): ?array {

        $sql = "SELECT *
                FROM respuesta
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ======================================================
    // OBTENER RESPUESTAS DE UNA PREGUNTA
    // ======================================================

    public function obtenerPorPregunta(
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria
    ): array {

        $sql = "SELECT *
                FROM respuesta
                WHERE id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // EDITAR UNA RESPUESTA
    // ======================================================

    public function editarRespuesta(
        string $idRespuesta,
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria,
        ?string $informacion,
        string $tipo
    ): bool {

        $sql = "UPDATE respuesta
                SET informacion = :informacion,
                    tipo = :tipo
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':informacion', $informacion);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }


    // ======================================================
    // ELIMINAR UNA RESPUESTA
    // ======================================================

    public function eliminar(
        string $idRespuesta,
        string $idPregunta,
        string $idEncuesta,
        string $idCategoria
    ): bool {

        $sql = "DELETE FROM respuesta
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':id_encuesta', $idEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        return $sentencia->execute();
    }


    // ======================================================
    // OBTENER ESTADÍSTICAS DE UNA ENCUESTA
    // ======================================================

    public function obtenerEstadisticasPorEncuesta(
        string $idEncuesta,
        string $idCategoria
    ): array {

        // Promedio general de respuestas numéricas entre 1 y 5.
        $sqlGeneral = "SELECT
                            AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio_general,
                            COUNT(*) AS total_respuestas
                       FROM respuesta
                       WHERE id_encuesta = :id_encuesta
                       AND id_categoria = :id_categoria
                       AND tipo = 'opcion multiple'
                       AND informacion REGEXP '^[1-5]$'";

        $stmtGeneral = $this->conexion->prepare($sqlGeneral);

        $stmtGeneral->bindValue(':id_encuesta', $idEncuesta);
        $stmtGeneral->bindValue(':id_categoria', $idCategoria);

        $stmtGeneral->execute();

        $datosGenerales = $stmtGeneral->fetch(PDO::FETCH_ASSOC);


        // Promedio individual de cada pregunta.
        $sqlPorPregunta = "SELECT
                                id_pregunta,
                                AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio
                           FROM respuesta
                           WHERE id_encuesta = :id_encuesta
                           AND id_categoria = :id_categoria
                           AND tipo = 'opcion multiple'
                           AND informacion REGEXP '^[1-5]$'
                           GROUP BY id_pregunta";

        $stmtPregunta = $this->conexion->prepare($sqlPorPregunta);

        $stmtPregunta->bindValue(':id_encuesta', $idEncuesta);
        $stmtPregunta->bindValue(':id_categoria', $idCategoria);

        $stmtPregunta->execute();

        $promediosPregunta = $stmtPregunta->fetchAll(PDO::FETCH_ASSOC);


        // Devolvemos los nombres que ya utiliza el JavaScript.
        return [
            'promedioGeneral' => round(
                (float)($datosGenerales['promedio_general'] ?? 0),
                1
            ),

            'totalRespuestas' => (int)($datosGenerales['total_respuestas'] ?? 0),

            'promediosPregunta' => array_map(function ($pregunta) {

                return [
                    'id_pregunta' => $pregunta['id_pregunta'],
                    'promedio' => round((float)$pregunta['promedio'], 1)
                ];

            }, $promediosPregunta)
        ];
    }
}