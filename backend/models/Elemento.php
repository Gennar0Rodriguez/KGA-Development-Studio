
<?php

require_once __DIR__ . '/../config/database.php';

class Elemento
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Genera un identificador único para la tabla transportable.
    private function generarIdTransportable(): string
    {
        do {

            $id = 'TRP' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*)
                    FROM transportable
                    WHERE id_transportable = :id";

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                ':id' => $id
            ]);

            $existe = (int) $consulta->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }

    // Registra el transportable y el elemento en una sola transacción.
    public function crear(array $datos): string
    {
        try {

            $this->conexion->beginTransaction();

            // Primero verificamos que el enfermero exista.
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

            // Generamos el identificador compartido por ambas tablas.
            $idTransportable = $this->generarIdTransportable();

            // PRIMERA INSERCIÓN:
            // Registramos los datos generales del transportable.
            $sqlTransportable = "INSERT INTO transportable
                (
                    id_transportable,
                    ci_enfermero,
                    habitacion_retiro,
                    piso_retiro
                )
                VALUES
                (
                    :id,
                    :enfermero,
                    :habitacion,
                    :piso
                )";

            $consultaTransportable = $this->conexion->prepare(
                $sqlTransportable
            );

            $consultaTransportable->execute([
                ':id' => $idTransportable,
                ':enfermero' => $datos['ci_enfermero'],
                ':habitacion' => $datos['habitacion_retiro'],
                ':piso' => $datos['piso_retiro']
            ]);

            // SEGUNDA INSERCIÓN:
            // Registramos el elemento utilizando el mismo identificador.
            $sqlElemento = "INSERT INTO elemento
                (
                    id_transportable,
                    nombre_elemento,
                    descripcion_elemento,
                    tipo_elemento
                )
                VALUES
                (
                    :id,
                    :nombre,
                    :descripcion,
                    :tipo
                )";

            $consultaElemento = $this->conexion->prepare($sqlElemento);

            $consultaElemento->execute([
                ':id' => $idTransportable,
                ':nombre' => $datos['nombre_elemento'],
                ':descripcion' => $datos['descripcion_elemento'],
                ':tipo' => $datos['tipo_elemento']
            ]);

            // Si ambas inserciones funcionaron, confirmamos los cambios.
            $this->conexion->commit();

            return $idTransportable;

        } catch (Throwable $error) {

            // Si alguna operación falla, deshacemos toda la transacción.
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $error;
        }
    }
}
