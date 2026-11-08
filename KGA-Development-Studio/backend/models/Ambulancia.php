<?php

require_once __DIR__ . '/../config/database.php';

class Ambulancia
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodas(): array
    {
        $sql = "SELECT matricula, modelo, capacidad, tipo_vehiculo, camilla FROM vehiculo ORDER BY matricula";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(
        string $matricula,
        string $modelo,
        string $capacidad,
        string $tipo,
        bool $camilla
    ): bool {
        $sql = "INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla) VALUES (:mat, :mod, :cap, :tipo, :cam)";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':mat' => $matricula,
            ':mod' => $modelo,
            ':cap' => $capacidad,
            ':tipo' => $tipo,
            ':cam' => $camilla ? 1 : 0
        ]);

        return $consulta->rowCount() > 0;
    }

    public function actualizar(
        string $matricula,
        string $modelo,
        string $capacidad,
        string $tipo,
        bool $camilla
    ): bool {
        $sql = "UPDATE vehiculo SET modelo = :mod, capacidad = :cap, tipo_vehiculo = :tipo, camilla = :cam WHERE matricula = :mat";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':mat' => $matricula,
            ':mod' => $modelo,
            ':cap' => $capacidad,
            ':tipo' => $tipo,
            ':cam' => $camilla ? 1 : 0
        ]);

        return $consulta->rowCount() > 0;
    }

    public function eliminar(string $matricula): bool
    {
        $sql = "DELETE FROM vehiculo WHERE matricula = :mat";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':mat' => $matricula]);

        return $consulta->rowCount() > 0;
    }
}