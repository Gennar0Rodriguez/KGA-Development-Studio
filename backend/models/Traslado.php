<?php

require_once __DIR__ . '/../config/Database.php';

class Traslado
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }


    // ======================================================
    // GENERAR IDENTIFICADORES
    // ======================================================

    private function generarIdTraslado(): string
    {
        do {

            $id = 'TRAS' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*)
                    FROM traslado
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $id);
            $sentencia->execute();

            $existe = $sentencia->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }


    private function generarIdTransportable(): string
    {
        do {

            $id = 'TRAN' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*)
                    FROM transportable
                    WHERE id_transportable = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $id);
            $sentencia->execute();

            $existe = $sentencia->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }


    // ======================================================
    // NORMALIZAR ESTADO
    // ======================================================

    private function normalizarEstado(string $estado): string
    {
        $estado = strtolower(trim($estado));

        if ($estado === 'en curso') {
            return 'en proceso';
        }

        if ($estado === 'programado') {
            return 'pendiente';
        }

        $estadosValidos = [
            'finalizado',
            'aprobado',
            'denegado',
            'en proceso',
            'pendiente'
        ];

        if (in_array($estado, $estadosValidos, true)) {
            return $estado;
        }

        return 'en proceso';
    }


    // ======================================================
    // COMPROBAR SI EL CHOFER PUEDE UTILIZAR EL VEHÍCULO
    // ======================================================

    private function validarConduce(
        string $ciChofer,
        string $matricula
    ): bool {

        $sql = "SELECT COUNT(*)
                FROM conduce
                WHERE ci_chofer = :ci
                AND matricula = :matricula";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':ci', $ciChofer);
        $sentencia->bindValue(':matricula', $matricula);

        $sentencia->execute();

        return $sentencia->fetchColumn() > 0;
    }


    // ======================================================
    // OBTENER TODOS LOS TRASLADOS
    // ======================================================

    public function obtenerTodos(?string $ciSolicitante = null): array
    {
        /*
         * Un traslado puede transportar un paciente o un órgano.
         * Por eso consultamos ambas tablas mediante LEFT JOIN.
         *
         * El tipo_transportable permite que JavaScript sepa
         * qué información debe mostrar en cada tarjeta.
         */

        $sql = "SELECT
                    t.id_traslado,
                    t.matricula,
                    t.fecha_traslado,
                    t.hora_salida,
                    t.hora_llegada,
                    t.lugar_origen,
                    t.observaciones,
                    t.estado_traslado,
                    t.estado_solicitud,
                    t.ci_solicitante,
                    t.id_destino,
                    t.ci_chofer,

                    d.nombre_destino,

                    CONCAT(
                        ch.nombre_chofer,
                        ' ',
                        ch.apellido_chofer
                    ) AS chofer,

                    CONCAT(
                        enf.nombre_enfermero,
                        ' ',
                        enf.apellido_enfermero
                    ) AS enfermero,

                    enf.ci_enfermero,

                    tr.id_transportable,

                    CASE
                        WHEN p.id_transportable IS NOT NULL
                            THEN 'paciente'
                        WHEN el.id_transportable IS NOT NULL
                            THEN 'organo'
                        ELSE 'desconocido'
                    END AS tipo_transportable,

                    p.nombre_paciente,
                    p.oxigeno,

                    el.nombre_elemento,
                    el.descripcion_elemento,
                    el.tipo_elemento

                FROM traslado t

                LEFT JOIN destino d
                    ON t.id_destino = d.id_destino

                LEFT JOIN chofer ch
                    ON t.ci_chofer = ch.ci_chofer

                LEFT JOIN transporta tr
                    ON t.id_traslado = tr.id_traslado

                LEFT JOIN transportable tr_able
                    ON tr.id_transportable = tr_able.id_transportable

                LEFT JOIN enfermero enf
                    ON tr_able.ci_enfermero = enf.ci_enfermero

                LEFT JOIN paciente p
                    ON tr_able.id_transportable = p.id_transportable

                LEFT JOIN elemento el
                    ON tr_able.id_transportable = el.id_transportable

                " . ($ciSolicitante !== null
                    ? "WHERE t.ci_solicitante = :ci_solicitante"
                    : "") . "

                ORDER BY
                    t.fecha_traslado DESC,
                    t.hora_salida DESC";

        $sentencia = $this->conexion->prepare($sql);

        if ($ciSolicitante !== null) {
            $sentencia->bindValue(':ci_solicitante', $ciSolicitante);
        }

        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // OBTENER OPCIONES PARA EL FORMULARIO
    // ======================================================

    public function obtenerListasAuxiliares(): array
    {
        // Obtener vehículos.
        $sql = "SELECT matricula, modelo
                FROM vehiculo";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $vehiculos = $sentencia->fetchAll(PDO::FETCH_ASSOC);


        // Obtener conductores.
        $sql = "SELECT
                    ci_chofer,
                    nombre_chofer,
                    apellido_chofer
                FROM chofer";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $choferes = $sentencia->fetchAll(PDO::FETCH_ASSOC);


        // Obtener enfermeros.
        $sql = "SELECT
                    ci_enfermero,
                    nombre_enfermero,
                    apellido_enfermero
                FROM enfermero";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        $enfermeros = $sentencia->fetchAll(PDO::FETCH_ASSOC);


        // Obtener destinos.
        $sql = "SELECT id_destino, nombre_destino
                FROM destino";

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


    // ======================================================
    // CREAR UN TRASLADO
    // ======================================================

        public function crear(
            array $datos,
            string $ciSolicitante,
            bool $esAdmin = false
        ): bool {

        try {

            // Determinar qué tipo de elemento se va a transportar.
            $tipoTransportable = $datos['tipo_transportable'] ?? 'paciente';

            if (!in_array($tipoTransportable, ['paciente', 'organo'], true)) {
                return false;
            }

            // Comprobar los datos específicos del tipo seleccionado.
            if ($tipoTransportable === 'paciente') {

                if (empty(trim($datos['paciente'] ?? ''))) {
                    return false;
                }

            } else {

                if (
                    empty(trim($datos['nombre_elemento'] ?? '')) ||
                    empty(trim($datos['descripcion_elemento'] ?? ''))
                ) {
                    return false;
                }

            }

            // El enfermero es obligatorio para ambos tipos.
            if (empty($datos['ci_enfermero'])) {
                return false;
            }


            $this->conexion->beginTransaction();

            $idTraslado = $this->generarIdTraslado();

            $idTransportable = $this->generarIdTransportable();

            // Toda solicitud comienza pendiente.
            $estadoSolicitud = $esAdmin ? 'aprobado' : 'pendiente';

            $estadoTraslado = $esAdmin ? 'aprobado' : 'pendiente';


            // ----------------------------------------------
            // INSERTAR TRASLADO
            // ----------------------------------------------

            $sqlTraslado = "INSERT INTO traslado (
                                id_traslado,
                                ci_chofer,
                                matricula,
                                id_destino,
                                fecha_traslado,
                                hora_salida,
                                lugar_origen,
                                estado_traslado,
                                ci_solicitante,
                                estado_solicitud,
                                observaciones
                            )
                            VALUES (
                                :id,
                                :ci_chofer,
                                :matricula,
                                :id_destino,
                                :fecha,
                                :hora,
                                :origen,
                                :estado,
                                :ci_solicitante,
                                :estado_solicitud,
                                :observaciones
                            )";

            $sentencia = $this->conexion->prepare($sqlTraslado);

            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);
            $sentencia->bindValue(':fecha', $datos['fecha']);

            $hora = !empty($datos['hora'])
                ? $datos['hora']
                : null;

            $sentencia->bindValue(
                ':hora',
                $hora,
                $hora === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );

            $origen = 'Hospital de Clínicas';

            $sentencia->bindValue(':origen', $origen);
            $sentencia->bindValue(':estado', $estadoTraslado);
            $sentencia->bindValue(':ci_solicitante', $ciSolicitante);
            $sentencia->bindValue(':estado_solicitud', $estadoSolicitud);

            $observaciones = $datos['observaciones'] ?? '';

            $sentencia->bindValue(':observaciones', $observaciones);

            $sentencia->execute();


            // ----------------------------------------------
            // INSERTAR TRANSPORTABLE
            // ----------------------------------------------

            /*
             * Esta tabla se utiliza tanto para pacientes como
             * para órganos. El enfermero queda relacionado
             * con el transportable en ambos casos.
             */

            $sqlTransportable = "INSERT INTO transportable (
                                    id_transportable,
                                    ci_enfermero,
                                    habitacion_retiro,
                                    piso_retiro
                                 )
                                 VALUES (
                                    :id,
                                    :ci_enfermero,
                                    '101',
                                    '1'
                                 )";

            $sentencia = $this->conexion->prepare($sqlTransportable);

            $sentencia->bindValue(':id', $idTransportable);
            $sentencia->bindValue(':ci_enfermero', $datos['ci_enfermero']);

            $sentencia->execute();


            // ----------------------------------------------
            // INSERTAR PACIENTE U ÓRGANO
            // ----------------------------------------------

            if ($tipoTransportable === 'paciente') {

                // Guardar los datos en la tabla paciente.

                $sqlPaciente = "INSERT INTO paciente (
                                    id_transportable,
                                    ci_paciente,
                                    nombre_paciente,
                                    accesorio,
                                    oxigeno,
                                    aislamiento
                                )
                                VALUES (
                                    :id,
                                    :ci_paciente,
                                    :nombre,
                                    'silla',
                                    :oxigeno,
                                    FALSE
                                )";

                $sentencia = $this->conexion->prepare($sqlPaciente);

                // La CI se mantiene generada como en la implementación anterior.
                $ciPaciente = (string) rand(10000000, 99999999);

                $oxigeno = (
                    ($datos['oxigeno'] ?? '') === 'Si' ||
                    ($datos['oxigeno'] ?? '') === '1'
                ) ? 1 : 0;

                $sentencia->bindValue(':id', $idTransportable);
                $sentencia->bindValue(':ci_paciente', $ciPaciente);
                $sentencia->bindValue(
                    ':nombre',
                    trim($datos['paciente'])
                );

                $sentencia->bindValue(
                    ':oxigeno',
                    $oxigeno,
                    PDO::PARAM_INT
                );

                $sentencia->execute();

            } else {

                // Guardar los datos en la tabla elemento.
                // El tipo se establece como 'organo' según el MER.

                $sqlElemento = "INSERT INTO elemento (
                                    id_transportable,
                                    nombre_elemento,
                                    descripcion_elemento,
                                    tipo_elemento
                                )
                                VALUES (
                                    :id,
                                    :nombre,
                                    :descripcion,
                                    'organo'
                                )";

                $sentencia = $this->conexion->prepare($sqlElemento);

                $sentencia->bindValue(':id', $idTransportable);

                $sentencia->bindValue(
                    ':nombre',
                    trim($datos['nombre_elemento'])
                );

                $sentencia->bindValue(
                    ':descripcion',
                    trim($datos['descripcion_elemento'])
                );

                $sentencia->execute();

            }


            // ----------------------------------------------
            // RELACIONAR TRASLADO Y TRANSPORTABLE
            // ----------------------------------------------

            $sqlTransporta = "INSERT INTO transporta (
                                id_transportable,
                                id_traslado,
                                ci_chofer,
                                matricula,
                                id_destino
                              )
                              VALUES (
                                :id_transportable,
                                :id_traslado,
                                :ci_chofer,
                                :matricula,
                                :id_destino
                              )";

            $sentencia = $this->conexion->prepare($sqlTransporta);

            $sentencia->bindValue(':id_transportable', $idTransportable);
            $sentencia->bindValue(':id_traslado', $idTraslado);
            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);

            $sentencia->execute();


            // ----------------------------------------------
            // NOTIFICAR A LOS ADMINISTRADORES
            // ----------------------------------------------

            if (!$esAdmin) {

                require_once __DIR__ . '/Notificacion.php';

                $notificacion = new Notificacion();

                $resultadoNotificacion = $notificacion->notificarATodos(
                    $idTraslado,
                    $datos['ci_chofer'],
                    $datos['matricula'],
                    $datos['id_destino'],
                    $ciSolicitante,
                    'Nueva solicitud de traslado',
                    'Se registró una nueva solicitud para el vehículo ' .
                        $datos['matricula']
                );

                // El traslado se guarda aunque falle una notificación.
                if (!$resultadoNotificacion) {
                    error_log(
                        "No se pudieron enviar todas las notificaciones de la solicitud."
                    );
                }

            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log("Error en crear traslado: " . $e->getMessage());

            return false;
        }
    }


    // ======================================================
    // APROBAR O RECHAZAR UNA SOLICITUD
    // ======================================================

    public function resolverSolicitud(
        string $idTraslado,
        string $estadoSolicitud,
        string $ciAdmin
    ): bool {

        try {

            $this->conexion->beginTransaction();

            // Recuperar los datos necesarios para resolver y notificar.
            $sql = "SELECT
                        ci_solicitante,
                        ci_chofer,
                        matricula,
                        id_destino
                    FROM traslado
                    WHERE id_traslado = :id
                    AND estado_solicitud = 'pendiente'
                    FOR UPDATE";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();

            $traslado = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$traslado) {

                $this->conexion->rollBack();

                return false;
            }

            $estadoTraslado = (
                $estadoSolicitud === 'aprobado'
            ) ? 'aprobado' : 'denegado';


            // Actualizar los dos estados.
            $sql = "UPDATE traslado
                    SET estado_solicitud = :estado_solicitud,
                        estado_traslado = :estado_traslado
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);

            $sentencia->bindValue(':estado_solicitud', $estadoSolicitud);
            $sentencia->bindValue(':estado_traslado', $estadoTraslado);
            $sentencia->bindValue(':id', $idTraslado);

            $sentencia->execute();


            // ----------------------------------------------
            // NOTIFICAR AL EMPLEADO SOLICITANTE
            // ----------------------------------------------

            require_once __DIR__ . '/Notificacion.php';

            $notificacion = new Notificacion();

            $aprobada = $estadoSolicitud === 'aprobado';

            $titulo = $aprobada
                ? 'Traslado aprobado'
                : 'Traslado rechazado';

            $mensaje = $aprobada
                ? 'Tu solicitud de traslado fue aprobada.'
                : 'Tu solicitud de traslado fue rechazada.';

            $resultadoNotificacion = $notificacion->crear(
                $idTraslado,
                $traslado['ci_chofer'],
                $traslado['matricula'],
                $traslado['id_destino'],
                $ciAdmin,
                $traslado['ci_solicitante'],
                $titulo,
                $mensaje
            );

            if (!$resultadoNotificacion) {

                $this->conexion->rollBack();

                return false;
            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log("Error al resolver solicitud: " . $e->getMessage());

            return false;
        }
    }


    // ======================================================
    // ACTUALIZAR UN TRASLADO
    // ======================================================

    public function actualizar(array $datos): bool
    {
        try {

            $tipoTransportable = $datos['tipo_transportable'] ?? 'paciente';

            if (!in_array($tipoTransportable, ['paciente', 'organo'], true)) {
                return false;
            }

            if ($tipoTransportable === 'paciente') {

                if (empty(trim($datos['paciente'] ?? ''))) {
                    return false;
                }

            } else {

                if (
                    empty(trim($datos['nombre_elemento'] ?? '')) ||
                    empty(trim($datos['descripcion_elemento'] ?? ''))
                ) {
                    return false;
                }

            }

            if (empty($datos['ci_enfermero'])) {
                return false;
            }


            $this->conexion->beginTransaction();

            $estadoTraslado = $this->normalizarEstado(
                $datos['estado'] ?? ''
            );


            // ----------------------------------------------
            // ACTUALIZAR TRASLADO
            // ----------------------------------------------

            $sqlTraslado = "UPDATE traslado
                            SET ci_chofer = :ci_chofer,
                                matricula = :matricula,
                                id_destino = :id_destino,
                                fecha_traslado = :fecha,
                                hora_salida = :hora,
                                observaciones = :observaciones,
                                estado_traslado = :estado
                            WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sqlTraslado);

            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);
            $sentencia->bindValue(':fecha', $datos['fecha']);

            $hora = !empty($datos['hora'])
                ? $datos['hora']
                : null;

            $sentencia->bindValue(
                ':hora',
                $hora,
                $hora === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );

            $observaciones = $datos['observaciones'] ?? '';

            $sentencia->bindValue(':observaciones', $observaciones);
            $sentencia->bindValue(':estado', $estadoTraslado);
            $sentencia->bindValue(':id', $datos['id_traslado']);

            $sentencia->execute();


            // ----------------------------------------------
            // ACTUALIZAR RELACIÓN TRANSPORTA
            // ----------------------------------------------

            $sqlTransporta = "UPDATE transporta
                              SET ci_chofer = :ci_chofer,
                                  matricula = :matricula,
                                  id_destino = :id_destino
                              WHERE id_traslado = :id_traslado
                              AND id_transportable = :id_transportable";

            $sentencia = $this->conexion->prepare($sqlTransporta);

            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);
            $sentencia->bindValue(':id_traslado', $datos['id_traslado']);
            $sentencia->bindValue(
                ':id_transportable',
                $datos['id_transportable']
            );

            $sentencia->execute();


            // ----------------------------------------------
            // ACTUALIZAR NOTIFICACIONES
            // ----------------------------------------------

            $sqlNotificaciones = "UPDATE notifica
                                  SET ci_chofer = :ci_chofer,
                                      matricula = :matricula,
                                      id_destino = :id_destino
                                  WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sqlNotificaciones);

            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);
            $sentencia->bindValue(':id', $datos['id_traslado']);

            $sentencia->execute();


            // ----------------------------------------------
            // ACTUALIZAR ENFERMERO
            // ----------------------------------------------

            $sqlEnfermero = "UPDATE transportable
                             SET ci_enfermero = :ci_enfermero
                             WHERE id_transportable = :id";

            $sentencia = $this->conexion->prepare($sqlEnfermero);

            $sentencia->bindValue(':ci_enfermero', $datos['ci_enfermero']);
            $sentencia->bindValue(':id', $datos['id_transportable']);

            $sentencia->execute();


            // ----------------------------------------------
            // ACTUALIZAR PACIENTE U ÓRGANO
            // ----------------------------------------------

            /*
             * Eliminamos los datos anteriores de ambas tablas.
             * Luego insertamos solamente el tipo seleccionado.
             *
             * Así también se permite cambiar un traslado que antes
             * transportaba un paciente para que ahora transporte
             * un órgano, o viceversa.
             */

            $sqlEliminarPaciente = "DELETE FROM paciente
                                    WHERE id_transportable = :id";

            $sentencia = $this->conexion->prepare($sqlEliminarPaciente);
            $sentencia->bindValue(':id', $datos['id_transportable']);
            $sentencia->execute();


            $sqlEliminarElemento = "DELETE FROM elemento
                                    WHERE id_transportable = :id";

            $sentencia = $this->conexion->prepare($sqlEliminarElemento);
            $sentencia->bindValue(':id', $datos['id_transportable']);
            $sentencia->execute();


            if ($tipoTransportable === 'paciente') {

                $sqlPaciente = "INSERT INTO paciente (
                                    id_transportable,
                                    ci_paciente,
                                    nombre_paciente,
                                    accesorio,
                                    oxigeno,
                                    aislamiento
                                )
                                VALUES (
                                    :id,
                                    :ci_paciente,
                                    :nombre,
                                    'silla',
                                    :oxigeno,
                                    FALSE
                                )";

                $sentencia = $this->conexion->prepare($sqlPaciente);

                // Si ya existía un paciente, conservamos su CI.
                // Si el traslado antes era un órgano, generamos una nueva.
                $sqlCI = "SELECT ci_paciente
                          FROM paciente
                          WHERE id_transportable = :id";

                /*
                 * La fila anterior ya fue eliminada, por lo que
                 * en este caso se genera una CI nueva.
                 */
                $ciPaciente = (string) rand(10000000, 99999999);

                $oxigeno = (
                    ($datos['oxigeno'] ?? '') === 'Si' ||
                    ($datos['oxigeno'] ?? '') === '1'
                ) ? 1 : 0;

                $sentencia->bindValue(
                    ':id',
                    $datos['id_transportable']
                );

                $sentencia->bindValue(':ci_paciente', $ciPaciente);

                $sentencia->bindValue(
                    ':nombre',
                    trim($datos['paciente'])
                );

                $sentencia->bindValue(
                    ':oxigeno',
                    $oxigeno,
                    PDO::PARAM_INT
                );

                $sentencia->execute();

            } else {

                $sqlElemento = "INSERT INTO elemento (
                                    id_transportable,
                                    nombre_elemento,
                                    descripcion_elemento,
                                    tipo_elemento
                                )
                                VALUES (
                                    :id,
                                    :nombre,
                                    :descripcion,
                                    'organo'
                                )";

                $sentencia = $this->conexion->prepare($sqlElemento);

                $sentencia->bindValue(
                    ':id',
                    $datos['id_transportable']
                );

                $sentencia->bindValue(
                    ':nombre',
                    trim($datos['nombre_elemento'])
                );

                $sentencia->bindValue(
                    ':descripcion',
                    trim($datos['descripcion_elemento'])
                );

                $sentencia->execute();

            }


            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log("Error en actualizar traslado: " . $e->getMessage());

            return false;
        }
    }


    // ======================================================
    // ELIMINAR UN TRASLADO
    // ======================================================

    public function eliminar(string $idTraslado): bool
    {
        try {

            $this->conexion->beginTransaction();

            // Recuperar los transportables relacionados.
            $sql = "SELECT id_transportable
                    FROM transporta
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();

            $transportables = $sentencia->fetchAll(PDO::FETCH_COLUMN);


            // Eliminar notificaciones relacionadas.
            $sql = "DELETE FROM notifica
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();


            // Eliminar relaciones.
            $sql = "DELETE FROM transporta
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();


            // Eliminar traslado.
            $sql = "DELETE FROM traslado
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();


            // Eliminar transportables que ya no estén asociados
            // a ningún otro traslado.
            foreach ($transportables as $idTransportable) {

                $sql = "SELECT COUNT(*)
                        FROM transporta
                        WHERE id_transportable = :id";

                $sentencia = $this->conexion->prepare($sql);
                $sentencia->bindValue(':id', $idTransportable);
                $sentencia->execute();

                $sigueUtilizado = $sentencia->fetchColumn() > 0;

                if (!$sigueUtilizado) {

                    // Eliminar paciente si existe.
                    $sql = "DELETE FROM paciente
                            WHERE id_transportable = :id";

                    $sentencia = $this->conexion->prepare($sql);
                    $sentencia->bindValue(':id', $idTransportable);
                    $sentencia->execute();


                    // Eliminar elemento si existe.
                    $sql = "DELETE FROM elemento
                            WHERE id_transportable = :id";

                    $sentencia = $this->conexion->prepare($sql);
                    $sentencia->bindValue(':id', $idTransportable);
                    $sentencia->execute();


                    // Eliminar transportable.
                    $sql = "DELETE FROM transportable
                            WHERE id_transportable = :id";

                    $sentencia = $this->conexion->prepare($sql);
                    $sentencia->bindValue(':id', $idTransportable);
                    $sentencia->execute();

                }

            }

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log("Error al eliminar traslado: " . $e->getMessage());

            return false;
        }
    }

}