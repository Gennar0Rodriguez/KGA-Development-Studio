
<?php
require_once __DIR__ . '/../config/database.php';

class Categoria
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Obtener todas las categorías pertenecientes a encuestas.
    public function obtenerCategoriasEncuestas(): array
    {
        $sql = "SELECT id_categoria, nombre_cat, estado_cat
                FROM categoria
                WHERE tipo_cat = 'encuestas'
                ORDER BY nombre_cat ASC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Contar cuántas categorías están activas.
    public function contarActivas(): int
    {
        $sql = "SELECT COUNT(*)
                FROM categoria
                WHERE tipo_cat = 'encuestas'
                AND estado_cat = 'activa'";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return (int) $sentencia->fetchColumn();
    }

    // Crear una categoría de encuestas.
    public function crear(
        string $idCategoria,
        string $nombre,
        string $estado
    ): bool {
        $sql = "INSERT INTO categoria
                    (id_categoria, nombre_cat, tipo_cat, estado_cat)
                VALUES
                    (:id_categoria, :nombre_cat, 'encuestas', :estado_cat)";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':id_categoria' => $idCategoria,
            ':nombre_cat' => $nombre,
            ':estado_cat' => $estado
        ]);
    }

    // Modificar el estado de una categoría.
    public function cambiarEstado(
        string $idCategoria,
        string $estado
    ): bool {
        $sql = "UPDATE categoria
                SET estado_cat = :estado_cat
                WHERE id_categoria = :id_categoria
                AND tipo_cat = 'encuestas'";

        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            ':estado_cat' => $estado,
            ':id_categoria' => $idCategoria
        ]);
    }
}
