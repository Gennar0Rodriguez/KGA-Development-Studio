
<?php

require_once __DIR__ . '/../config/database.php';

class Enfermero
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Devuelve los enfermeros registrados.
    // Se utiliza también para el selector del formulario de pacientes.
    public function obtenerTodos(): array
    {
        $sql = "SELECT ci_enfermero, nombre_enfermero, apellido_enfermero
                FROM enfermero
                ORDER BY apellido_enfermero, nombre_enfermero";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // Registra un nuevo enfermero.
    public function crear(array $datos): void
    {
        $sql = "INSERT INTO enfermero
                (ci_enfermero, nombre_enfermero, apellido_enfermero)
                VALUES
                (:ci, :nombre, :apellido)";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':ci' => $datos['ci_enfermero'],
            ':nombre' => $datos['nombre_enfermero'],
            ':apellido' => $datos['apellido_enfermero']
        ]);
    }
}
