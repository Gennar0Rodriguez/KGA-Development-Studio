<?php

require_once __DIR__ . '/../config/database.php';

class Elemento
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
                    el.id_transportable,
                    el.nombre_elemento,
                    el.descripcion_elemento,
                    el.tipo_elemento,
                    t.ci_enfermero,
                    CONCAT(e.nombre_enfermero, ' ', e.apellido_enfermero)
                        AS nombre_enfermero,
                    t.habitacion_retiro,
                    t.piso_retiro
                FROM elemento el
                INNER JOIN transportable t
                    ON el.id_transportable = t.id_transportable
                INNER JOIN enfermero e
                    ON t.ci_enfermero = e.ci_enfermero
                ORDER BY el.nombre_elemento";

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

            $sqlElemento = "INSERT INTO elemento
                (id_transportable, nombre_elemento, descripcion_elemento, tipo_elemento)
                VALUES (:id, :nombre, :descripcion, :tipo)";

            $consultaElemento = $this->conexion->prepare($sqlElemento);

            $consultaElemento->execute([
                ':id' => $idTransportable,
                ':nombre' => $datos['nombre_elemento'],
                ':descripcion' => $datos['descripcion_elemento'],
                ':tipo' => $datos['tipo_elemento']
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

            // Comprobamos que el enfermero exista.
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

            // Actualizamos los datos generales del transportable.
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

            // Actualizamos los datos propios del elemento.
            $sqlElemento = "UPDATE elemento
                SET nombre_elemento = :nombre,
                    descripcion_elemento = :descripcion,
                    tipo_elemento = :tipo
                WHERE id_transportable = :id";

            $consultaElemento = $this->conexion->prepare($sqlElemento);

            $consultaElemento->execute([
                ':id' => $datos['id_transportable'],
                ':nombre' => $datos['nombre_elemento'],
                ':descripcion' => $datos['descripcion_elemento'],
                ':tipo' => $datos['tipo_elemento']
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
        // La FK de transporta no permite borrar un transportable
        // que forme parte de un traslado.
        $sql = "DELETE FROM transportable WHERE id_transportable = :id";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id' => $id]);

        return $consulta->rowCount() > 0;
    }
}