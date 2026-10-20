<?php
require_once __DIR__ . '/../config/Database.php';

class Ambulancia
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodas(): array {
        $sql = "SELECT matricula, modelo, capacidad, tipo_vehiculo, camilla FROM vehiculo";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $matricula, string $modelo, string $capacidad, string $tipo, bool $camilla): bool {
        $sql = 'INSERT INTO vehiculo (matricula, modelo, capacidad, tipo_vehiculo, camilla)
                VALUES (:mat, :mod, :cap, :tipo, :cam)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':mat', $matricula);
        $sentencia->bindParam(':mod', $modelo);
        $sentencia->bindParam(':cap', $capacidad);
        $sentencia->bindParam(':tipo', $tipo);

        $camilla = $camilla ? 1 : 0;
        $sentencia->bindParam(':cam', $camilla, PDO::PARAM_INT);

        return $sentencia->execute();
    }

    public function eliminar(string $matricula): bool {
        $sql = 'DELETE FROM vehiculo WHERE matricula = :mat';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':mat', $matricula);
        return $sentencia->execute();
    }
}