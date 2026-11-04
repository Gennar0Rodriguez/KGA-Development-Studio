<?php
require_once __DIR__ . '/../config/Database.php';

class Respuesta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function crear(string $idRespuesta, string $idPregunta, string $idEncuesta, ?string $informacion, string $tipo): bool {
        $sql = 'INSERT INTO respuesta (id_respuesta, id_pregunta, id_encuesta, informacion, tipo) VALUES (:id_respuesta, :id_pregunta, :id_encuesta, :informacion, :tipo)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_respuesta', $idRespuesta);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':informacion', $informacion);
        $sentencia->bindParam(':tipo', $tipo);
        return $sentencia->execute();
    }

    public function obtenerTodos(): array {
        $sql = 'SELECT * FROM respuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(string $idRespuesta, string $idPregunta, string $idEncuesta): ?array {
        $sql = 'SELECT * FROM respuesta WHERE id_respuesta = :id_respuesta AND id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_respuesta', $idRespuesta);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->execute();
        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    public function obtenerPorPregunta(string $idPregunta, string $idEncuesta): array {
        $sql = 'SELECT * FROM respuesta WHERE id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function editarRespuesta(string $idRespuesta, string $idPregunta, string $idEncuesta, ?string $informacion, string $tipo): bool {
        $sql = 'UPDATE respuesta 
                SET informacion = :informacion, 
                    tipo = :tipo 
                WHERE id_respuesta = :id_respuesta AND id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_respuesta', $idRespuesta);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        $sentencia->bindParam(':informacion', $informacion);
        $sentencia->bindParam(':tipo', $tipo);

        return $sentencia->execute();
    }

    public function eliminar(string $idRespuesta, string $idPregunta, string $idEncuesta): bool {
        $sql = 'DELETE FROM respuesta WHERE id_respuesta = :id_respuesta AND id_pregunta = :id_pregunta AND id_encuesta = :id_encuesta';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id_respuesta', $idRespuesta);
        $sentencia->bindParam(':id_pregunta', $idPregunta);
        $sentencia->bindParam(':id_encuesta', $idEncuesta);
        return $sentencia->execute();
    }




    public function obtenerEstadisticasPorEncuesta(string $idEncuesta): array {

        // Calculamos el promedio general y contamos las respuestas válidas.
        // Solo consideramos valores numéricos entre 1 y 5.
        $sqlGeneral = "SELECT
                            AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio_general,
                            COUNT(*) AS total_respuestas
                    FROM respuesta
                    WHERE id_encuesta = :id_encuesta
                    AND tipo = 'opcion multiple'
                    AND informacion REGEXP '^[1-5]$'";

        $stmtGeneral = $this->conexion->prepare($sqlGeneral);
        $stmtGeneral->bindParam(':id_encuesta', $idEncuesta);
        $stmtGeneral->execute();

        $datosGenerales = $stmtGeneral->fetch(PDO::FETCH_ASSOC);


        // Calculamos el promedio de cada pregunta por separado.
        $sqlPorPregunta = "SELECT
                                id_pregunta,
                                AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio
                        FROM respuesta
                        WHERE id_encuesta = :id_encuesta
                        AND tipo = 'opcion multiple'
                        AND informacion REGEXP '^[1-5]$'
                        GROUP BY id_pregunta";

        $stmtPregunta = $this->conexion->prepare($sqlPorPregunta);
        $stmtPregunta->bindParam(':id_encuesta', $idEncuesta);
        $stmtPregunta->execute();

        $promediosPregunta = $stmtPregunta->fetchAll(PDO::FETCH_ASSOC);


        // Organizamos los resultados con los nombres que espera JavaScript.
        return [
            'promedioGeneral' => round((float)($datosGenerales['promedio_general'] ?? 0), 1),
            'totalRespuestas' => (int)($datosGenerales['total_respuestas'] ?? 0),

            'promediosPregunta' => array_map(function($pregunta) {
                return [
                    'id_pregunta' => $pregunta['id_pregunta'],
                    'promedio' => round((float)$pregunta['promedio'], 1)
                ];
            }, $promediosPregunta)
        ];
    }

}