
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
        string $tituloEncuesta,
        string $idCategoria,
        string $nombreCategoria,
        ?string $informacion,
        string $tipo
    ): bool {

        $sql = "INSERT INTO respuesta (
                    id_respuesta,
                    id_pregunta,
                    titulo_enc,
                    id_categoria,
                    nombre_cat,
                    informacion,
                    tipo
                )
                VALUES (
                    :id_respuesta,
                    :id_pregunta,
                    :titulo_enc,
                    :id_categoria,
                    :nombre_cat,
                    :informacion,
                    :tipo
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);
        $sentencia->bindValue(':nombre_cat', $nombreCategoria);
        $sentencia->bindValue(':informacion', $informacion);
        $sentencia->bindValue(':tipo', $tipo);

        return $sentencia->execute();
    }


    // ======================================================
    // OBTENER TODAS LAS RESPUESTAS
    // ======================================================

    public function obtenerTodos(): array
    {
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
        string $tituloEncuesta,
        string $idCategoria
    ): ?array {

        $sql = "SELECT *
                FROM respuesta
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        $sentencia->execute();

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ======================================================
    // OBTENER RESPUESTAS DE UNA PREGUNTA
    // ======================================================

    public function obtenerPorPregunta(
        string $idPregunta,
        string $tituloEncuesta,
        string $idCategoria
    ): array {

        $sql = "SELECT *
                FROM respuesta
                WHERE id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
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
        string $tituloEncuesta,
        string $idCategoria,
        ?string $informacion,
        string $tipo
    ): bool {

        $sql = "UPDATE respuesta
                SET informacion = :informacion,
                    tipo = :tipo
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
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
        string $tituloEncuesta,
        string $idCategoria
    ): bool {

        $sql = "DELETE FROM respuesta
                WHERE id_respuesta = :id_respuesta
                AND id_pregunta = :id_pregunta
                AND titulo_enc = :titulo_enc
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_respuesta', $idRespuesta);
        $sentencia->bindValue(':id_pregunta', $idPregunta);
        $sentencia->bindValue(':titulo_enc', $tituloEncuesta);
        $sentencia->bindValue(':id_categoria', $idCategoria);

        return $sentencia->execute();
    }


    // ======================================================
    // OBTENER ESTADÍSTICAS DE UNA ENCUESTA
    // ======================================================

    public function obtenerEstadisticasPorEncuesta(
        string $tituloEncuesta,
        string $idCategoria
    ): array {

        // --------------------------------------------------
        // PROMEDIO GENERAL
        // --------------------------------------------------

        $sqlGeneral = "SELECT
                            AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio_general,
                            COUNT(*) AS total_respuestas

                       FROM respuesta

                       WHERE titulo_enc = :titulo_enc
                       AND id_categoria = :id_categoria
                       AND tipo = 'opcion multiple'
                       AND informacion REGEXP '^[1-5]$'";

        $stmtGeneral = $this->conexion->prepare($sqlGeneral);

        $stmtGeneral->bindValue(':titulo_enc', $tituloEncuesta);
        $stmtGeneral->bindValue(':id_categoria', $idCategoria);

        $stmtGeneral->execute();

        $datosGenerales = $stmtGeneral->fetch(PDO::FETCH_ASSOC);


        // --------------------------------------------------
        // PROMEDIO INDIVIDUAL DE CADA PREGUNTA
        // --------------------------------------------------

        $sqlPorPregunta = "SELECT
                                p.id_pregunta,
                                p.nombre_pregunta,

                                AVG(
                                    CAST(r.informacion AS DECIMAL(3,1))
                                ) AS promedio,

                                COUNT(r.id_respuesta) AS cantidad_respuestas

                           FROM pregunta p

                           LEFT JOIN respuesta r
                                ON p.id_pregunta = r.id_pregunta
                                AND p.titulo_enc = r.titulo_enc
                                AND p.id_categoria = r.id_categoria
                                AND r.tipo = 'opcion multiple'
                                AND r.informacion REGEXP '^[1-5]$'

                           WHERE p.titulo_enc = :titulo_enc
                           AND p.id_categoria = :id_categoria

                           GROUP BY
                                p.id_pregunta,
                                p.nombre_pregunta

                           ORDER BY p.id_pregunta";

        $stmtPregunta = $this->conexion->prepare($sqlPorPregunta);

        $stmtPregunta->bindValue(':titulo_enc', $tituloEncuesta);
        $stmtPregunta->bindValue(':id_categoria', $idCategoria);

        $stmtPregunta->execute();

        $promediosPregunta = $stmtPregunta->fetchAll(PDO::FETCH_ASSOC);


        // --------------------------------------------------
        // DEVOLVER LOS RESULTADOS
        // --------------------------------------------------

        return [

            'promedioGeneral' => round(
                (float) ($datosGenerales['promedio_general'] ?? 0),
                1
            ),

            'totalRespuestas' => (int) (
                $datosGenerales['total_respuestas'] ?? 0
            ),

            'promediosPregunta' => array_map(
                function ($pregunta) {

                    return [

                        'id_pregunta' => $pregunta['id_pregunta'],

                        'nombre_pregunta' => $pregunta['nombre_pregunta'],

                        'promedio' => $pregunta['promedio'] !== null
                            ? round((float) $pregunta['promedio'], 1)
                            : null,

                        'cantidad_respuestas' => (int) (
                            $pregunta['cantidad_respuestas']
                        )

                    ];
                },
                $promediosPregunta
            )
        ];
    }
}
