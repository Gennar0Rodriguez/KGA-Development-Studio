<?php
require_once __DIR__ . '/../config/Database.php';

class Ambulancia
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodas(): array
    {
        $sql = "SELECT matricula, modelo, capacidad, tipo_vehiculo, camilla FROM vehiculo";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $matricula, string $modelo, string $capacidad, string $tipo, bool $camilla): bool
    {
        $sql = "INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla) 
                VALUES (:mat, :mod, :cap, :tipo, :cam)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':mat' => $matricula,
            ':mod' => $modelo,
            ':cap' => $capacidad,
            ':tipo' => $tipo,
            ':cam' => $camilla ? 1 : 0
        ]);
    }

    public function eliminar(string $matricula): bool
    {
        $sql = "DELETE FROM vehiculo WHERE matricula = :mat";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':mat' => $matricula]);
    }
}