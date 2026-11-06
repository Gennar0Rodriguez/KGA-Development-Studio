
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
        string $idEncuesta,
        string $idCategoria,
        string $titulo,
        ?string $descripcion
    ): bool {
        $sql = "INSERT INTO encuesta
                    (id_encuesta, id_categoria, titulo_enc, descripcion_enc)
                VALUES
                    (:id_encuesta, :id_categoria, :titulo_enc, :descripcion_enc)";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_encuesta' => $idEncuesta,
            ':id_categoria' => $idCategoria,
            ':titulo_enc' => $titulo,
            ':descripcion_enc' => $descripcion
        ]);
    }

    // Obtener todas las encuestas de categorías de tipo encuestas.
    // Se utiliza desde el panel administrativo.
    public function obtenerTodos(): array
    {
        $sql = "SELECT e.*, c.nombre_cat, c.estado_cat
                FROM encuesta e
                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria
                WHERE c.tipo_cat = 'encuestas'
                ORDER BY c.nombre_cat ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener solamente las encuestas de categorías activas.
    // Se utiliza desde el panel público de pacientes.
    public function obtenerActivas(): array
    {
        $sql = "SELECT e.*, c.nombre_cat
                FROM encuesta e
                INNER JOIN categoria c
                    ON e.id_categoria = c.id_categoria
                WHERE c.tipo_cat = 'encuestas'
                AND c.estado_cat = 'activa'
                ORDER BY c.nombre_cat ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Buscar una encuesta por sus identificadores.
    public function obtenerPorId(
        string $idEncuesta,
        string $idCategoria
    ): ?array {
        $sql = "SELECT *
                FROM encuesta
                WHERE id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':id_encuesta' => $idEncuesta,
            ':id_categoria' => $idCategoria
        ]);

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // Obtener las encuestas de una categoría.
    public function obtenerPorCategoria(string $idCategoria): array
    {
        $sql = "SELECT *
                FROM encuesta
                WHERE id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->execute([
            ':id_categoria' => $idCategoria
        ]);

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Modificar el título y la descripción de una encuesta.
    public function editarEncuesta(
        string $idEncuesta,
        string $idCategoria,
        string $titulo,
        ?string $descripcion
    ): bool {
        $sql = "UPDATE encuesta
                SET titulo_enc = :titulo_enc,
                    descripcion_enc = :descripcion_enc
                WHERE id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_encuesta' => $idEncuesta,
            ':id_categoria' => $idCategoria,
            ':titulo_enc' => $titulo,
            ':descripcion_enc' => $descripcion
        ]);
    }

    // Eliminar una encuesta.
    public function eliminar(
        string $idEncuesta,
        string $idCategoria
    ): bool {
        $sql = "DELETE FROM encuesta
                WHERE id_encuesta = :id_encuesta
                AND id_categoria = :id_categoria";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_encuesta' => $idEncuesta,
            ':id_categoria' => $idCategoria
        ]);
    }
}
