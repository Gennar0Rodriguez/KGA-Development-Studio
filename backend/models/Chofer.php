
<?php

require_once __DIR__ . '/../config/database.php';

class Chofer
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Devuelve los choferes registrados.
    public function obtenerTodos(): array
    {
        $sql = "SELECT ci_chofer, nombre_chofer, apellido_chofer, telefono
                FROM chofer
                ORDER BY apellido_chofer, nombre_chofer";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // Registra un nuevo chofer.
    public function crear(array $datos): void
    {
        $sql = "INSERT INTO chofer
                (ci_chofer, nombre_chofer, apellido_chofer, telefono)
                VALUES
                (:ci, :nombre, :apellido, :telefono)";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ci' => $datos['ci_chofer'],
            ':nombre' => $datos['nombre_chofer'],
            ':apellido' => $datos['apellido_chofer'],
            ':telefono' => $datos['telefono']
        ]);
    }
}
