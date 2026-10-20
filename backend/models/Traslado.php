<?php
require_once __DIR__ . '/../config/Database.php';

class Traslado
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodos(): array {
        $sql = "SELECT
                    t.id_traslado,
                    t.matricula,
                    t.fecha_traslado,
                    t.hora_salida,
                    t.hora_llegada,
                    t.lugar_origen,
                    t.estado,
                    d.nombre_destino,
                    CONCAT(ch.nombre_chofer, ' ', ch.apellido_chofer) AS chofer,
                    CONCAT(enf.nombre_enfermero, ' ', enf.apellido_enfermero) AS enfermero,
                    p.nombre_paciente,
                    p.oxigeno
                FROM traslado t
                LEFT JOIN destino d ON t.id_destino = d.id_destino
                LEFT JOIN chofer ch ON t.ci_chofer = ch.ci_chofer
                LEFT JOIN transporta tr ON t.id_traslado = tr.id_traslado
                LEFT JOIN transportable tr_able ON tr.id_transportable = tr_able.id_transportable
                LEFT JOIN enfermero enf ON tr_able.ci_enfermero = enf.ci_enfermero
                LEFT JOIN paciente p ON tr_able.id_transportable = p.id_transportable
                ORDER BY t.fecha_traslado DESC, t.hora_salida DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerListasAuxiliares(): array {
        $sql = "SELECT matricula, modelo FROM vehiculo";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $vehiculos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT ci_chofer, nombre_chofer, apellido_chofer FROM chofer";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $choferes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT ci_enfermero, nombre_enfermero, apellido_enfermero FROM enfermero";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $enfermeros = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT id_destino, nombre_destino FROM destino";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $destinos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        return [
            'vehiculos' => $vehiculos ?: [],
            'choferes' => $choferes ?: [],
            'enfermeros' => $enfermeros ?: [],
            'destinos' => $destinos ?: []
        ];
    }

    public function crear(array $datos): bool {
        try {
            $this->conexion->beginTransaction();

            $idTraslado = 'TRAS' . rand(100, 999);
            $idTransportable = 'TRAN' . rand(100, 999);

            $estadoEnum = 'en proceso';
            $estadoInput = strtolower($datos['estado'] ?? '');

            if ($estadoInput === 'programado' || $estadoInput === 'en curso') {
                $estadoEnum = 'en proceso';
            } else if ($estadoInput === 'finalizado' || $estadoInput === 'aprobado') {
                $estadoEnum = 'aprobado';
            }

            // Insertar en traslado
            $sqlTraslado = "INSERT INTO traslado (id_traslado, ci_chofer, matricula, id_destino, fecha_traslado, hora_salida, lugar_origen, estado)
                            VALUES (:id, :ci_chofer, :matricula, :id_destino, :fecha, :hora, :origen, :estado)";

            $sentencia = $this->conexion->prepare($sqlTraslado);
            $sentencia->bindParam(':id', $idTraslado);
            $sentencia->bindParam(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindParam(':matricula', $datos['matricula']);
            $sentencia->bindParam(':id_destino', $datos['id_destino']);
            $sentencia->bindParam(':fecha', $datos['fecha']);
            $sentencia->bindParam(':hora', $datos['hora']);

            $origen = 'Hospital de Clínicas';
            $sentencia->bindParam(':origen', $origen);
            $sentencia->bindParam(':estado', $estadoEnum);
            $sentencia->execute();

            // Insertar en transportable
            $sqlTrans = "INSERT INTO transportable (id_transportable, ci_enfermero, habitacion_retiro, piso_retiro)
                         VALUES (:id, :ci_enf, '101', '1')";

            $sentencia = $this->conexion->prepare($sqlTrans);
            $sentencia->bindParam(':id', $idTransportable);
            $sentencia->bindParam(':ci_enf', $datos['ci_enfermero']);
            $sentencia->execute();

            // Insertar en paciente
            $sqlPac = "INSERT INTO paciente (id_transportable, ci_paciente, nombre_paciente, accesorio, oxigeno, aislamiento)
                       VALUES (:id, :ci_pac, :nombre, 'silla', :oxigeno, FALSE)";

            $sentencia = $this->conexion->prepare($sqlPac);

            $ciPaciente = (string) rand(10000000, 99999999);
            $nombrePaciente = $datos['paciente'];
            $oxigeno = ($datos['oxigeno'] === 'Si' || $datos['oxigeno'] === '1') ? 1 : 0;

            $sentencia->bindParam(':id', $idTransportable);
            $sentencia->bindParam(':ci_pac', $ciPaciente);
            $sentencia->bindParam(':nombre', $nombrePaciente);
            $sentencia->bindParam(':oxigeno', $oxigeno, PDO::PARAM_INT);
            $sentencia->execute();

            // Vincular en transporta
            $sqlTransporta = "INSERT INTO transporta (id_transportable, id_traslado, ci_chofer, matricula, id_destino)
                              VALUES (:idTrans, :idTras, :ci_ch, :mat, :id_dest)";

            $sentencia = $this->conexion->prepare($sqlTransporta);
            $sentencia->bindParam(':idTrans', $idTransportable);
            $sentencia->bindParam(':idTras', $idTraslado);
            $sentencia->bindParam(':ci_ch', $datos['ci_chofer']);
            $sentencia->bindParam(':mat', $datos['matricula']);
            $sentencia->bindParam(':id_dest', $datos['id_destino']);
            $sentencia->execute();

            // Enviar notificacion
            try {
                $rutaNotif = __DIR__ . '/Notificacion.php';

                if (file_exists($rutaNotif)) {
                    require_once $rutaNotif;

                    $notifModel = new Notificacion();
                    $notifModel->notificarATodos(
                        "Nueva solicitud de traslado",
                        "Se registró una nueva solicitud para la ambulancia " . $datos['matricula']
                    );
                }
            } catch (Exception $eNotif) {
                error_log("Error al enviar notificacion: " . $eNotif->getMessage());
            }

            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log("Error en crear traslado: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar(string $idTraslado): bool {
        try {
            $this->conexion->beginTransaction();

            $sql = "DELETE FROM transporta WHERE id_traslado = :id";
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindParam(':id', $idTraslado);
            $sentencia->execute();

            $sql = "DELETE FROM traslado WHERE id_traslado = :id";
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindParam(':id', $idTraslado);
            $sentencia->execute();

            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            return false;
        }
    }
}