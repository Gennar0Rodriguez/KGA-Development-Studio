<?php

require_once __DIR__ . '/../config/database.php';

class Paciente
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    private function generarIdTransportable(): string
    {
        do {
            $id = 'TRP' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*)
                    FROM transportable
                    WHERE id_transportable = :id";

            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([':id' => $id]);

            $existe = (int) $consulta->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }

    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    p.id_transportable,
                    p.ci_paciente,
                    p.nombre_paciente,
                    p.accesorio,
                    p.oxigeno,
                    p.aislamiento,
                    t.ci_enfermero,
                    CONCAT(e.nombre_enfermero, ' ', e.apellido_enfermero)
                        AS nombre_enfermero,
                    t.habitacion_retiro,
                    t.piso_retiro
                FROM paciente p
                INNER JOIN transportable t
                    ON p.id_transportable = t.id_transportable
                INNER JOIN enfermero e
                    ON t.ci_enfermero = e.ci_enfermero
                ORDER BY p.nombre_paciente";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(array $datos): string
    {
        try {
            $this->conexion->beginTransaction();

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

            $sqlTransportable = "INSERT INTO transportable
                (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro)
                VALUES (:id, :enfermero, :habitacion, :piso)";

            $consultaTransportable = $this->conexion->prepare($sqlTransportable);

            $consultaTransportable->execute([
                ':id' => $idTransportable,
                ':enfermero' => $datos['ci_enfermero'],
                ':habitacion' => $datos['habitacion_retiro'],
                ':piso' => $datos['piso_retiro']
            ]);

            $sqlPaciente = "INSERT INTO paciente
                (id_transportable, ci_paciente, nombre_paciente,
                 accesorio, oxigeno, aislamiento)
                VALUES (:id, :ci, :nombre, :accesorio, :oxigeno, :aislamiento)";

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

    public function actualizar(array $datos): bool
    {
        try {
            $this->conexion->beginTransaction();

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

            $sqlTransportable = "UPDATE transportable
                SET ci_enfermero = :enfermero,
                    habitacion_retiro = :habitacion,
                    piso_retiro = :piso
                WHERE id_transportable = :id";

            $consultaTransportable = $this->conexion->prepare($sqlTransportable);

            $consultaTransportable->execute([
                ':id' => $datos['id_transportable'],
                ':enfermero' => $datos['ci_enfermero'],
                ':habitacion' => $datos['habitacion_retiro'],
                ':piso' => $datos['piso_retiro']
            ]);

            $sqlPaciente = "UPDATE paciente
                SET ci_paciente = :ci,
                    nombre_paciente = :nombre,
                    accesorio = :accesorio,
                    oxigeno = :oxigeno,
                    aislamiento = :aislamiento
                WHERE id_transportable = :id";

            $consultaPaciente = $this->conexion->prepare($sqlPaciente);

            $consultaPaciente->execute([
                ':id' => $datos['id_transportable'],
                ':ci' => $datos['ci_paciente'],
                ':nombre' => $datos['nombre_paciente'],
                ':accesorio' => $datos['accesorio'],
                ':oxigeno' => $datos['oxigeno'],
                ':aislamiento' => $datos['aislamiento']
            ]);

            $this->conexion->commit();

            return true;

        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }

    public function eliminar(string $id): bool
    {
        try {

            // Iniciamos una transacción para evitar eliminaciones parciales.
            $this->conexion->beginTransaction();

            // Primero eliminamos el registro de paciente.
            $sqlPaciente = "DELETE FROM paciente WHERE id_transportable = :id";

            $consultaPaciente = $this->conexion->prepare($sqlPaciente);
            $consultaPaciente->execute([':id' => $id]);

            // Si no encontramos el paciente, cancelamos la operación.
            if ($consultaPaciente->rowCount() === 0) {
                $this->conexion->rollBack();
                return false;
            }

            // Eliminamos el transportable solamente si no está
            // relacionado con elementos ni con traslados.
            $sqlTransportable = " DELETE FROM transportable WHERE id_transportable = :id AND NOT EXISTS 
            (SELECT 1 FROM elemento WHERE elemento.id_transportable = transportable.id_transportable)
                AND NOT EXISTS (SELECT 1 FROM transporta WHERE transporta.id_transportable = transportable.id_transportable)";

            $consultaTransportable = $this->conexion->prepare($sqlTransportable);
            $consultaTransportable->execute([':id' => $id]);

            // Confirmamos los cambios.
            $this->conexion->commit();

            return true;

        } catch (Throwable $error) {

            // Si algo falla, deshacemos todos los cambios.
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }
}