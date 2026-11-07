
<?php

require_once __DIR__ . '/../config/database.php';

class Paciente
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Genera un identificador único para transportable.
    private function generarIdTransportable(): string
    {
        do {
            $id = 'TRP' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*) FROM transportable
                    WHERE id_transportable = :id";

            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([':id' => $id]);

            $existe = (int) $consulta->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }

    // Crea el transportable y el paciente dentro de una transacción.
    public function crear(array $datos): string
    {
        try {

            $this->conexion->beginTransaction();

            // Comprobamos que el enfermero seleccionado exista.
            $sqlEnfermero = "SELECT COUNT(*)
                             FROM enfermero
                             WHERE ci_enfermero = :ci";

            $consultaEnfermero = $this->conexion->prepare($sqlEnfermero);

            $consultaEnfermero->execute([
                ':ci' => $datos['ci_enfermero']
            ]);

            if ((int) $consultaEnfermero->fetchColumn() === 0) {
                throw new InvalidArgumentException(
                    'El enfermero seleccionado no existe.'
                );
            }

            $idTransportable = $this->generarIdTransportable();

            // Primero se registra el transportable.
            $sqlTransportable = "INSERT INTO transportable
                (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro)
                VALUES
                (:id, :enfermero, :habitacion, :piso)";

            $consultaTransportable = $this->conexion->prepare($sqlTransportable);

            $consultaTransportable->execute([
                ':id' => $idTransportable,
                ':enfermero' => $datos['ci_enfermero'],
                ':habitacion' => $datos['habitacion_retiro'],
                ':piso' => $datos['piso_retiro']
            ]);

            // Después se registra el paciente asociado al transportable.
            $sqlPaciente = "INSERT INTO paciente
                (id_transportable, ci_paciente, nombre_paciente,
                 accesorio, oxigeno, aislamiento)
                VALUES
                (:id, :ci, :nombre, :accesorio, :oxigeno, :aislamiento)";

            $consultaPaciente = $this->conexion->prepare($sqlPaciente);

            $consultaPaciente->execute([
                ':id' => $idTransportable,
                ':ci' => $datos['ci_paciente'],
                ':nombre' => $datos['nombre_paciente'],
                ':accesorio' => $datos['accesorio'],
                ':oxigeno' => $datos['oxigeno'],
                ':aislamiento' => $datos['aislamiento']
            ]);

            $this->conexion->commit();

            return $idTransportable;

        } catch (Throwable $error) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }
}
