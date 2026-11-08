
<?php

require_once __DIR__ . '/../config/database.php';

class Enfermero
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT ci_enfermero, nombre_enfermero, apellido_enfermero
                FROM enfermero
                ORDER BY apellido_enfermero, nombre_enfermero";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $datos): void
    {
        $sql = "INSERT INTO enfermero
                (ci_enfermero, nombre_enfermero, apellido_enfermero)
                VALUES (:ci, :nombre, :apellido)";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ci' => $datos['ci_enfermero'],
            ':nombre' => $datos['nombre_enfermero'],
            ':apellido' => $datos['apellido_enfermero']
        ]);
    }

    public function actualizar(array $datos): bool
    {
        $sql = "UPDATE enfermero
                SET nombre_enfermero = :nombre,
                    apellido_enfermero = :apellido
                WHERE ci_enfermero = :ci";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ci' => $datos['ci_enfermero'],
            ':nombre' => $datos['nombre_enfermero'],
            ':apellido' => $datos['apellido_enfermero']
        ]);

        // Si no hubo cambios, comprobamos que el enfermero exista.
        if ($consulta->rowCount() === 0) {
            $sql = "SELECT COUNT(*)
                    FROM enfermero
                    WHERE ci_enfermero = :ci";

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                ':ci' => $datos['ci_enfermero']
            ]);

            return (int) $consulta->fetchColumn() > 0;
        }

        return true;
    }

    public function eliminar(string $ci): bool
    {
        // La base de datos se encarga de colocar NULL
        // en ci_enfermero de los transportables relacionados.
        $sql = "DELETE FROM enfermero
                WHERE ci_enfermero = :ci";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ci' => $ci
        ]);

        return $consulta->rowCount() > 0;
    }
}
