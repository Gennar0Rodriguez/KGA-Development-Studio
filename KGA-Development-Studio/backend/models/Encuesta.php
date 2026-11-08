
<?php
require_once __DIR__ . '/../config/database.php';

class Encuesta
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Crear una encuesta asociada a una categoría.
    public function crear(
        string $tituloEncuesta,
        string $idCategoria,
        ?string $descripcion
    ): bool {

        $sql = "INSERT INTO encuesta (
                    titulo_enc,
                    id_categoria,
                    descripcion_enc
                )
                VALUES (
                    :titulo_enc,
                    :id_categoria,
                    :descripcion_enc
                )";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':titulo_enc' => $tituloEncuesta,
            ':id_categoria' => $idCategoria,
            ':descripcion_enc' => $descripcion
        ]);
    }


    // Obtener todas las encuestas de tipo encuestas.
    // Se utiliza desde el panel administrativo.
    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    e.titulo_enc,
                    e.id_categoria,
                    c.nombre_cat,
                    e.descripcion_enc,
                    c.estado_cat

                FROM encuesta e

                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria

                WHERE c.tipo_cat = 'encuestas'

                ORDER BY
                    c.nombre_cat ASC,
                    e.titulo_enc ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // Obtener solamente las encuestas de categorías activas.
    // Se utiliza desde el panel público de pacientes.
    public function obtenerActivas(): array
    {
        $sql = "SELECT
                    e.titulo_enc,
                    e.id_categoria,
                    c.nombre_cat,
                    e.descripcion_enc

                FROM encuesta e

                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria

                WHERE c.tipo_cat = 'encuestas'
                AND c.estado_cat = 'activa'

                ORDER BY
                    c.nombre_cat ASC,
                    e.titulo_enc ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // Buscar una encuesta por su título y categoría.
    public function obtenerPorId(
        string $tituloEncuesta,
        string $idCategoria
    ): ?array {

        $sql = "SELECT
                    e.titulo_enc,
                    e.id_categoria,
                    e.descripcion_enc,
                    c.nombre_cat

                FROM encuesta e

                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria

                WHERE e.titulo_enc = :titulo_enc
                AND e.id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':titulo_enc' => $tituloEncuesta,
            ':id_categoria' => $idCategoria
        ]);

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // Obtener todas las encuestas de una categoría.
    public function obtenerPorCategoria(string $idCategoria): array
    {
        $sql = "SELECT
                    e.titulo_enc,
                    e.id_categoria,
                    e.descripcion_enc,
                    c.nombre_cat

                FROM encuesta e

                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria

                WHERE e.id_categoria = :id_categoria

                ORDER BY e.titulo_enc ASC";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':id_categoria' => $idCategoria
        ]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // Buscar encuestas por su título.
    public function buscarPorTitulo(string $titulo): array
    {
        $sql = "SELECT
                    e.titulo_enc,
                    e.id_categoria,
                    c.nombre_cat,
                    e.descripcion_enc

                FROM encuesta e

                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria

                WHERE c.tipo_cat = 'encuestas'
                AND c.estado_cat = 'activa'
                AND e.titulo_enc LIKE :titulo

                ORDER BY e.titulo_enc ASC";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':titulo' => '%' . $titulo . '%'
        ]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // Eliminar una encuesta.
    // Primero se eliminan sus respuestas y preguntas.
    public function eliminar(
        string $tituloEncuesta,
        string $idCategoria
    ): bool {

        $this->conexion->beginTransaction();

        try {

            // Primero eliminamos las respuestas asociadas.
            $sqlRespuestas = "DELETE FROM respuesta
                              WHERE titulo_enc = :titulo_enc
                              AND id_categoria = :id_categoria";

            $sentenciaRespuestas = $this->conexion->prepare($sqlRespuestas);

            $sentenciaRespuestas->execute([
                ':titulo_enc' => $tituloEncuesta,
                ':id_categoria' => $idCategoria
            ]);


            // Después eliminamos las preguntas.
            $sqlPreguntas = "DELETE FROM pregunta
                             WHERE titulo_enc = :titulo_enc
                             AND id_categoria = :id_categoria";

            $sentenciaPreguntas = $this->conexion->prepare($sqlPreguntas);

            $sentenciaPreguntas->execute([
                ':titulo_enc' => $tituloEncuesta,
                ':id_categoria' => $idCategoria
            ]);


            // Finalmente eliminamos la encuesta.
            $sqlEncuesta = "DELETE FROM encuesta
                            WHERE titulo_enc = :titulo_enc
                            AND id_categoria = :id_categoria";

            $sentenciaEncuesta = $this->conexion->prepare($sqlEncuesta);

            $sentenciaEncuesta->execute([
                ':titulo_enc' => $tituloEncuesta,
                ':id_categoria' => $idCategoria
            ]);

            $eliminada = $sentenciaEncuesta->rowCount() > 0;

            $this->conexion->commit();

            return $eliminada;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }
}
