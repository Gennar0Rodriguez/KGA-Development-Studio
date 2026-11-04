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

    /**
     * Calcula el promedio general, el total de respuestas y los promedios por pregunta
     * para alimentar el panel de estadísticas lateral en la interfaz.
     */
    public function obtenerEstadisticasPorEncuesta(string $idEncuesta): array {
        // Promedio global y total de respuestas de opción múltiple
        $sqlGeneral = 'SELECT AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio_general, COUNT(DISTINCT id_respuesta) AS total_respuestas 
                       FROM respuesta 
                       WHERE id_encuesta = :id_encuesta AND tipo = "opcion multiple"';
        
        $stmtGeneral = $this->conexion->prepare($sqlGeneral);
        $stmtGeneral->bindParam(':id_encuesta', $idEncuesta);
        $stmtGeneral->execute();
        $datosGenerales = $stmtGeneral->fetch(PDO::FETCH_ASSOC);

        // Promedio por cada pregunta específica
        $sqlPorPregunta = 'SELECT id_pregunta, AVG(CAST(informacion AS DECIMAL(3,1))) AS promedio 
                           FROM respuesta 
                           WHERE id_encuesta = :id_encuesta AND tipo = "opcion multiple" 
                           GROUP BY id_pregunta';
        
        $stmtPregunta = $this->conexion->prepare($sqlPorPregunta);
        $stmtPregunta->bindParam(':id_encuesta', $idEncuesta);
        $stmtPregunta->execute();
        $promediosPregunta = $stmtPregunta->fetchAll(PDO::FETCH_ASSOC);

        return [
            'promedioGeneral' => round((float)($datosGenerales['promedio_general'] ?? 0), 1),
            'totalRespuestas' => (int)($datosGenerales['total_respuestas'] ?? 0),
            'promediosPregunta' => array_map(function($p) {
                return [
                    'id_pregunta' => $p['id_pregunta'],
                    'promedio' => round((float)$p['promedio'], 1)
                ];
            }, $promediosPregunta)
        ];
    }
}