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

            $sql = "SELECT COUNT(*) FROM traslado WHERE id_traslado = :id";

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

            $sql = "SELECT COUNT(*) FROM transportable WHERE id_transportable = :id";

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
    // VALIDAR FECHA DEL TRASLADO
    // ======================================================

    private function validarFechaTraslado(string $fecha): bool
    {
        // Utilizar la zona horaria de Uruguay.
        $zonaHoraria = new DateTimeZone('America/Montevideo');

        // Comprobar que la fecha tenga el formato correcto.
        $fechaIngresada = DateTime::createFromFormat(
            '!Y-m-d',
            $fecha,
            $zonaHoraria
        );

        // Rechazar fechas inexistentes o con formato incorrecto.
        if (
            !$fechaIngresada ||
            $fechaIngresada->format('Y-m-d') !== $fecha
        ) {
            return false;
        }

        // Obtener la fecha actual de Uruguay.
        $fechaActual = new DateTime('today', $zonaHoraria);

        // La fecha del traslado debe ser hoy o posterior.
        return $fechaIngresada >= $fechaActual;
    }

    // ======================================================
    // COMPROBAR SI EL CHOFER PUEDE UTILIZAR EL VEHÍCULO
    // ======================================================

    private function validarConduce(
        string $ciChofer,
        string $matricula
    ): bool {

        $sql = "SELECT COUNT(*) FROM conduce
                WHERE ci_chofer = :ci
                AND matricula = :matricula";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':ci', $ciChofer);
        $sentencia->bindValue(':matricula', $matricula);

        $sentencia->execute();

        return $sentencia->fetchColumn() > 0;
    }

    // ======================================================
    // BLOQUEAR LOS RECURSOS DEL TRASLADO
    // ======================================================

    private function bloquearRecursos(
        string $ciChofer,
        string $ciEnfermero,
        string $matricula
    ): bool {

        /*
         * Bloqueamos las filas de los tres recursos mientras
         * se realiza la operación.
         *
         * Esto evita que dos administradores aprueben al mismo
         * tiempo traslados que utilizan los mismos recursos.
         *
         * Siempre se bloquean en el mismo orden:
         * 1. Chofer.
         * 2. Enfermero.
         * 3. Vehículo.
         *
         * Este orden ayuda a reducir los bloqueos cruzados.
         */

        $sqlChofer = "SELECT ci_chofer
                      FROM chofer
                      WHERE ci_chofer = :ci
                      FOR UPDATE";

        $sentencia = $this->conexion->prepare($sqlChofer);
        $sentencia->bindValue(':ci', $ciChofer);
        $sentencia->execute();

        if (!$sentencia->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sqlEnfermero = "SELECT ci_enfermero
                         FROM enfermero
                         WHERE ci_enfermero = :ci
                         FOR UPDATE";

        $sentencia = $this->conexion->prepare($sqlEnfermero);
        $sentencia->bindValue(':ci', $ciEnfermero);
        $sentencia->execute();

        if (!$sentencia->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $sqlVehiculo = "SELECT matricula
                        FROM vehiculo
                        WHERE matricula = :matricula
                        FOR UPDATE";

        $sentencia = $this->conexion->prepare($sqlVehiculo);
        $sentencia->bindValue(':matricula', $matricula);
        $sentencia->execute();

        if (!$sentencia->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        return true;
    }

    // ======================================================
    // COMPROBAR DISPONIBILIDAD DE LOS RECURSOS
    // ======================================================

    private function recursosDisponibles(
        string $ciChofer,
        string $ciEnfermero,
        string $matricula,
        ?string $idExcluir = null
    ): bool {

        /*
         * Un recurso está ocupado cuando aparece en otro
         * traslado aprobado que todavía está aprobado
         * o en proceso.
         *
         * Los traslados pendientes no ocupan recursos.
         * Los finalizados y denegados tampoco.
         *
         * No se consideran fechas ni horarios: mientras
         * el traslado siga activo, sus recursos están ocupados.
         */

        $sql = "SELECT COUNT(DISTINCT t.id_traslado)
                FROM traslado t
                INNER JOIN transporta tr
                    ON tr.id_traslado = t.id_traslado
                INNER JOIN transportable tb
                    ON tb.id_transportable = tr.id_transportable
                WHERE t.estado_solicitud = 'aprobado'
                AND t.estado_traslado IN ('aprobado', 'en proceso')
                AND (
                    t.ci_chofer = :ci_chofer
                    OR tb.ci_enfermero = :ci_enfermero
                    OR t.matricula = :matricula
                )";

        // Si estamos modificando un traslado, no debemos
        // compararlo contra sí mismo.
        if ($idExcluir !== null) {
            $sql .= " AND t.id_traslado <> :id_excluir";
        }

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':ci_chofer', $ciChofer);
        $sentencia->bindValue(':ci_enfermero', $ciEnfermero);
        $sentencia->bindValue(':matricula', $matricula);

        if ($idExcluir !== null) {
            $sentencia->bindValue(':id_excluir', $idExcluir);
        }

        $sentencia->execute();

        // Si el resultado es cero, los tres recursos están libres.
        return (int) $sentencia->fetchColumn() === 0;
    }

        // ======================================================
    // OBTENER TODOS LOS TRASLADOS
    // ======================================================

    public function obtenerTodos(?string $ciSolicitante = null): array
    {
        $sql = "SELECT
                    t.id_traslado,
                    t.matricula,
                    t.fecha_traslado,
                    t.fecha_llegada,
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
                    CONCAT(ch.nombre_chofer, ' ', ch.apellido_chofer) AS chofer,
                    CONCAT(enf.nombre_enfermero, ' ', enf.apellido_enfermero) AS enfermero,
                    enf.ci_enfermero,
                    tr.id_transportable,
                    CASE
                        WHEN p.id_transportable IS NOT NULL THEN 'paciente'
                        WHEN el.id_transportable IS NOT NULL THEN 'organo'
                        ELSE 'desconocido'
                    END AS tipo_transportable,
                    p.nombre_paciente,
                    p.ci_paciente,
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
                    ON tr_able.id_transportable = el.id_transportable ";

        if ($ciSolicitante !== null) {
            $sql .= " WHERE t.ci_solicitante = :ci_solicitante";
        }

        $sql .= " ORDER BY t.fecha_traslado DESC, t.hora_salida DESC";

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
        // Obtener vehículos registrados.
        $sql = "SELECT matricula, modelo FROM vehiculo";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $vehiculos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Obtener conductores registrados.
        $sql = "SELECT ci_chofer, nombre_chofer, apellido_chofer FROM chofer";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $choferes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Obtener enfermeros registrados.
        $sql = "SELECT ci_enfermero, nombre_enfermero, apellido_enfermero FROM enfermero";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $enfermeros = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Obtener destinos disponibles.
        $sql = "SELECT id_destino, nombre_destino FROM destino";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $destinos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Obtener pacientes registrados.
        $sql = "SELECT DISTINCT ci_paciente, nombre_paciente
                FROM paciente
                ORDER BY nombre_paciente";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        $pacientes = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        // Devolver todas las listas que utiliza el formulario.
        return [
            'vehiculos' => $vehiculos ?: [],
            'choferes' => $choferes ?: [],
            'enfermeros' => $enfermeros ?: [],
            'destinos' => $destinos ?: [],
            'pacientes' => $pacientes ?: []
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

    // Registrar en qué etapa se encuentra la creación.
    $etapa = 'inicio';

    try {

        // Determinar qué tipo de elemento se va a transportar.
        $tipoTransportable = $datos['tipo_transportable'] ?? 'paciente';

        if (!in_array($tipoTransportable, ['paciente', 'organo'], true)) {
            $etapa = 'validacion_tipo';
            error_log("DIAGNOSTICO TRASLADO: tipo de transportable inválido.");
            return false;
        }

        // Comprobar los datos específicos del tipo seleccionado.
        if ($tipoTransportable === 'paciente') {

            $ciPaciente = trim($datos['ci_paciente'] ?? '');
            $nombrePaciente = trim($datos['paciente'] ?? '');

            if (!preg_match('/^[0-9]{8}$/', $ciPaciente)) {
                $etapa = 'validacion_ci_paciente';
                error_log("DIAGNOSTICO TRASLADO: CI del paciente inválida.");
                return false;
            }

            if ($nombrePaciente === '') {
                $etapa = 'validacion_nombre_paciente';
                error_log("DIAGNOSTICO TRASLADO: nombre del paciente vacío.");
                return false;
            }

            // Comprobar que la combinación CI-nombre exista.
            $sqlValidarPaciente = "SELECT COUNT(*)
                                   FROM paciente
                                   WHERE ci_paciente = :ci
                                   AND nombre_paciente = :nombre";

            $sentenciaValidar = $this->conexion->prepare($sqlValidarPaciente);
            $sentenciaValidar->bindValue(':ci', $ciPaciente);
            $sentenciaValidar->bindValue(':nombre', $nombrePaciente);
            $sentenciaValidar->execute();

            if ($sentenciaValidar->fetchColumn() < 1) {
                $etapa = 'paciente_no_encontrado';
                error_log("DIAGNOSTICO TRASLADO: paciente no encontrado.");
                return false;
            }

        } else {

            if (
                empty(trim($datos['nombre_elemento'] ?? '')) ||
                empty(trim($datos['descripcion_elemento'] ?? ''))
            ) {
                $etapa = 'validacion_elemento';
                error_log("DIAGNOSTICO TRASLADO: datos del órgano incompletos.");
                return false;
            }
        }

        // El enfermero es obligatorio para ambos tipos.
        if (empty($datos['ci_enfermero'])) {
            $etapa = 'validacion_enfermero';
            error_log("DIAGNOSTICO TRASLADO: falta el enfermero.");
            return false;
        }

        // Comprobar los recursos necesarios para ambos tipos.
        foreach (['ci_chofer', 'matricula', 'id_destino'] as $campo) {
            if (empty($datos[$campo])) {
                $etapa = 'validacion_' . $campo;
                error_log("DIAGNOSTICO TRASLADO: falta el campo " . $campo);
                return false;
            }
        }

        // La fecha del traslado no puede ser anterior a hoy.
        if (
            empty($datos['fecha']) ||
            !$this->validarFechaTraslado($datos['fecha'])
        ) {
            $etapa = 'validacion_fecha';
            error_log("DIAGNOSTICO TRASLADO: fecha inválida.");
            return false;
        }

        // Iniciar la transacción.
        $etapa = 'inicio_transaccion';
        $this->conexion->beginTransaction();

        /*
         * Los administradores crean traslados aprobados.
         * Por eso deben comprobar que los recursos estén libres.
         */
        if ($esAdmin) {

            // Bloquear chofer, enfermero y vehículo.
            $etapa = 'bloqueo_recursos';

            if (
                !$this->bloquearRecursos(
                    $datos['ci_chofer'],
                    $datos['ci_enfermero'],
                    $datos['matricula']
                )
            ) {
                error_log(
                    "DIAGNOSTICO TRASLADO: bloquearRecursos devolvió false. " .
                    "Chofer: {$datos['ci_chofer']}, " .
                    "Enfermero: {$datos['ci_enfermero']}, " .
                    "Vehículo: {$datos['matricula']}"
                );

                $this->conexion->rollBack();
                return false;
            }

            // Comprobar que ninguno esté ocupado.
            $etapa = 'comprobacion_disponibilidad';

            if (
                !$this->recursosDisponibles(
                    $datos['ci_chofer'],
                    $datos['ci_enfermero'],
                    $datos['matricula']
                )
            ) {
                error_log(
                    "DIAGNOSTICO TRASLADO: recursosDisponibles devolvió false. " .
                    "Chofer: {$datos['ci_chofer']}, " .
                    "Enfermero: {$datos['ci_enfermero']}, " .
                    "Vehículo: {$datos['matricula']}"
                );

                $this->conexion->rollBack();
                return false;
            }
        }

        // Generar identificadores únicos.
        $etapa = 'generacion_identificadores';

        $idTraslado = $this->generarIdTraslado();
        $idTransportable = $this->generarIdTransportable();

        // Definir los estados según el tipo de usuario.
        $estadoSolicitud = $esAdmin ? 'aprobado' : 'pendiente';
        $estadoTraslado = $esAdmin ? 'aprobado' : 'pendiente';

        // ----------------------------------------------
        // INSERTAR TRASLADO
        // ----------------------------------------------

        $etapa = 'insertar_traslado';

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
                        ) VALUES (
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

        $hora = !empty($datos['hora']) ? $datos['hora'] : null;

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

        $etapa = 'insertar_transportable';

        $sqlTransportable = "INSERT INTO transportable (
                                id_transportable,
                                ci_enfermero,
                                habitacion_retiro,
                                piso_retiro
                            ) VALUES (
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

            $etapa = 'insertar_paciente';

            $sqlPaciente = "INSERT INTO paciente (
                                id_transportable,
                                ci_paciente,
                                nombre_paciente,
                                accesorio,
                                oxigeno,
                                aislamiento
                            ) VALUES (
                                :id,
                                :ci_paciente,
                                :nombre,
                                'silla',
                                :oxigeno,
                                FALSE
                            )";

            $sentencia = $this->conexion->prepare($sqlPaciente);

            $oxigeno = (
                ($datos['oxigeno'] ?? '') === 'Si' ||
                ($datos['oxigeno'] ?? '') === '1'
            ) ? 1 : 0;

            $sentencia->bindValue(':id', $idTransportable);
            $sentencia->bindValue(':ci_paciente', $ciPaciente);
            $sentencia->bindValue(':nombre', $nombrePaciente);

            $sentencia->bindValue(
                ':oxigeno',
                $oxigeno,
                PDO::PARAM_INT
            );

            $sentencia->execute();

        } else {

            $etapa = 'insertar_elemento';

            $sqlElemento = "INSERT INTO elemento (
                                id_transportable,
                                nombre_elemento,
                                descripcion_elemento,
                                tipo_elemento
                            ) VALUES (
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

        $etapa = 'insertar_transporta';

        $sqlTransporta = "INSERT INTO transporta (
                            id_transportable,
                            id_traslado,
                            ci_chofer,
                            matricula,
                            id_destino
                        ) VALUES (
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

            $etapa = 'notificaciones';

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

            if (!$resultadoNotificacion) {
                error_log(
                    "No se pudieron enviar todas las notificaciones de la solicitud."
                );
            }
        }

        // Confirmar todos los cambios.
        $etapa = 'confirmar_transaccion';

        $this->conexion->commit();

        error_log(
            "DIAGNOSTICO TRASLADO: creación completada. ID: " . $idTraslado
        );

        return true;

    } catch (Throwable $e) {

        if ($this->conexion->inTransaction()) {
            $this->conexion->rollBack();
        }

        error_log(
            "ERROR CREAR TRASLADO | Etapa: " . $etapa .
            " | Mensaje: " . $e->getMessage() .
            " | Archivo: " . $e->getFile() .
            " | Línea: " . $e->getLine()
        );

        return false;
    }
}

        // ======================================================
    // APROBAR O RECHAZAR UNA SOLICITUD
    // ======================================================

    public function resolverSolicitud(
        string $idTraslado,
        string $estadoSolicitud,
        string $ciAdmin,
        ?string $horaSalida = null
    ): bool {

        try {

            $this->conexion->beginTransaction();

            // Recuperar la solicitud y bloquearla durante la operación.
            $sql = "SELECT
                        t.ci_solicitante,
                        t.ci_chofer,
                        t.matricula,
                        t.id_destino,
                        tb.ci_enfermero
                    FROM traslado t
                    INNER JOIN transporta tr
                        ON tr.id_traslado = t.id_traslado
                    INNER JOIN transportable tb
                        ON tb.id_transportable = tr.id_transportable
                    WHERE t.id_traslado = :id
                    AND t.estado_solicitud IN ('pendiente', 'rechazado')
                    FOR UPDATE";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();

            $traslado = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (!$traslado) {
                $this->conexion->rollBack();
                return false;
            }

            // Determinar el estado operativo y las horas.
            if ($estadoSolicitud === 'aprobado') {

                // Para aprobar es obligatorio registrar la hora de salida.
                if (
                    empty($horaSalida) ||
                    !preg_match(
                        '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/',
                        $horaSalida
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                $sqlFecha = "SELECT fecha_traslado
                             FROM traslado
                             WHERE id_traslado = :id";

                $consultaFecha = $this->conexion->prepare($sqlFecha);
                $consultaFecha->bindValue(':id', $idTraslado);
                $consultaFecha->execute();

                $fechaTraslado = $consultaFecha->fetchColumn();

                if (
                    !$fechaTraslado ||
                    !$this->validarFechaTraslado($fechaTraslado)
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                /*
                 * Antes de aprobar, bloquear los tres recursos.
                 * Así evitamos que otro traslado los ocupe
                 * simultáneamente.
                 */
                if (
                    !$this->bloquearRecursos(
                        $traslado['ci_chofer'],
                        $traslado['ci_enfermero'],
                        $traslado['matricula']
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                // Comprobar que ningún otro traslado los esté utilizando.
            if (!$this->recursosDisponibles(
                $datos['ci_chofer'],
                $datos['ci_enfermero'],
                $datos['matricula']
            )) {
                error_log("DIAGNOSTICO TRASLADO: recursos ocupados");
                $this->conexion->rollBack();
                return false;
            }

                $estadoTraslado = 'aprobado';

                // Al aprobar se registra la salida y se limpia la llegada.
                $horaLlegada = null;

            } elseif ($estadoSolicitud === 'rechazado') {

                $estadoTraslado = 'denegado';

                // Una solicitud rechazada no conserva ninguna hora.
                $horaSalida = null;
                $horaLlegada = null;

            } else {

                $this->conexion->rollBack();
                return false;

            }

            // Actualizar los estados y las horas del traslado.
            $sql = "UPDATE traslado
                    SET estado_solicitud = :estado_solicitud,
                        estado_traslado = :estado_traslado,
                        hora_salida = :hora_salida,
                        hora_llegada = :hora_llegada,
                        fecha_llegada = NULL
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);

            $sentencia->bindValue(':estado_solicitud', $estadoSolicitud);
            $sentencia->bindValue(':estado_traslado', $estadoTraslado);

            $sentencia->bindValue(
                ':hora_salida',
                $horaSalida,
                $horaSalida === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );

            $sentencia->bindValue(
                ':hora_llegada',
                $horaLlegada,
                PDO::PARAM_NULL
            );

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
    // ACTUALIZAR ESTADO OPERATIVO
    // ======================================================

    public function actualizarEstadoOperativo(
        string $idTraslado,
        string $nuevoEstado,
        ?string $horaLlegada = null,
        ?string $fechaLlegada = null
    ): bool {

        try {

            $this->conexion->beginTransaction();

            // Recuperar los datos necesarios y bloquear el traslado.
            $sql = "SELECT
                        estado_traslado,
                        estado_solicitud,
                        fecha_traslado,
                        hora_salida
                    FROM traslado
                    WHERE id_traslado = :id
                    FOR UPDATE";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idTraslado);
            $sentencia->execute();

            $traslado = $sentencia->fetch(PDO::FETCH_ASSOC);

            if (
                !$traslado ||
                $traslado['estado_solicitud'] !== 'aprobado'
            ) {
                $this->conexion->rollBack();
                return false;
            }

            $estadoActual = $traslado['estado_traslado'];

            // Solo se puede iniciar un traslado aprobado
            // o devolver uno finalizado a proceso.
            if (
                $nuevoEstado === 'en proceso' &&
                !in_array($estadoActual, ['aprobado', 'finalizado'], true)
            ) {
                $this->conexion->rollBack();
                return false;
            }

            // Solo se puede finalizar un traslado en proceso.
            if (
                $nuevoEstado === 'finalizado' &&
                $estadoActual !== 'en proceso'
            ) {
                $this->conexion->rollBack();
                return false;
            }

            if (!in_array($nuevoEstado, ['en proceso', 'finalizado'], true)) {
                $this->conexion->rollBack();
                return false;
            }

            // Todo traslado debe tener fecha y hora de salida.
            if (
                empty($traslado['fecha_traslado']) ||
                empty($traslado['hora_salida'])
            ) {
                $this->conexion->rollBack();
                return false;
            }

            /*
             * Al iniciar o reactivar un traslado, comprobar
             * que sus recursos continúen disponibles.
             *
             * Se excluye el propio traslado de la búsqueda.
             */
            if ($nuevoEstado === 'en proceso') {

                // Recuperar los recursos asociados al traslado.
                $sqlRecursos = "SELECT
                                    t.ci_chofer,
                                    t.matricula,
                                    tb.ci_enfermero
                                FROM traslado t
                                INNER JOIN transporta tr
                                    ON tr.id_traslado = t.id_traslado
                                INNER JOIN transportable tb
                                    ON tb.id_transportable = tr.id_transportable
                                WHERE t.id_traslado = :id";

                $consultaRecursos = $this->conexion->prepare($sqlRecursos);
                $consultaRecursos->bindValue(':id', $idTraslado);
                $consultaRecursos->execute();

                $recursos = $consultaRecursos->fetch(PDO::FETCH_ASSOC);

                if (!$recursos) {
                    $this->conexion->rollBack();
                    return false;
                }

                if (
                    !$this->bloquearRecursos(
                        $recursos['ci_chofer'],
                        $recursos['ci_enfermero'],
                        $recursos['matricula']
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                if (
                    !$this->recursosDisponibles(
                        $recursos['ci_chofer'],
                        $recursos['ci_enfermero'],
                        $recursos['matricula'],
                        $idTraslado
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }
            }

            if ($nuevoEstado === 'finalizado') {

                // La fecha y la hora de llegada son obligatorias.
                if (
                    empty($fechaLlegada) ||
                    empty($horaLlegada)
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                // Comprobar el formato de la hora.
                $horaLlegada = substr($horaLlegada, 0, 5);

                if (
                    !preg_match(
                        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                        $horaLlegada
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                // Comprobar que la fecha de llegada sea válida.
                $zonaHoraria = new DateTimeZone('America/Montevideo');

                $fechaLlegadaValidada = DateTime::createFromFormat(
                    '!Y-m-d',
                    $fechaLlegada,
                    $zonaHoraria
                );

                if (
                    !$fechaLlegadaValidada ||
                    $fechaLlegadaValidada->format('Y-m-d') !== $fechaLlegada
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                // La llegada no puede ser anterior al día del traslado
                // ni estar registrada en una fecha futura.
                $fechaSalida = new DateTime(
                    $traslado['fecha_traslado'],
                    $zonaHoraria
                );

                $fechaActual = new DateTime('today', $zonaHoraria);

                if (
                    $fechaLlegadaValidada < $fechaSalida ||
                    $fechaLlegadaValidada > $fechaActual
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                // Combinar las fechas y horas para comparar
                // los momentos reales de salida y llegada.
                $salida = new DateTime(
                    $traslado['fecha_traslado'] . ' ' . $traslado['hora_salida'],
                    $zonaHoraria
                );

                $llegada = new DateTime(
                    $fechaLlegada . ' ' . $horaLlegada,
                    $zonaHoraria
                );

                // La llegada debe ocurrir después de la salida.
                if ($llegada <= $salida) {
                    $this->conexion->rollBack();
                    return false;
                }

            } else {

                // Al volver a en proceso se eliminan los datos de llegada.
                $horaLlegada = null;
                $fechaLlegada = null;
            }

            // Actualizar el estado y los datos de llegada.
            $sql = "UPDATE traslado
                    SET estado_traslado = :estado,
                        hora_llegada = :hora_llegada,
                        fecha_llegada = :fecha_llegada
                    WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sql);

            $sentencia->bindValue(':estado', $nuevoEstado);

            $sentencia->bindValue(
                ':hora_llegada',
                $horaLlegada,
                $horaLlegada === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );

            $sentencia->bindValue(
                ':fecha_llegada',
                $fechaLlegada,
                $fechaLlegada === null ? PDO::PARAM_NULL : PDO::PARAM_STR
            );

            $sentencia->bindValue(':id', $idTraslado);

            $sentencia->execute();

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            error_log(
                "Error al actualizar estado operativo: " . $e->getMessage()
            );

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

                $ciPaciente = trim($datos['ci_paciente'] ?? '');
                $nombrePaciente = trim($datos['paciente'] ?? '');

                if (!preg_match('/^[0-9]{8}$/', $ciPaciente)) {
                    return false;
                }

                if ($nombrePaciente === '') {
                    return false;
                }

                // La combinación debe existir antes de actualizar.
                $sqlValidarPaciente = "SELECT COUNT(*)
                                       FROM paciente
                                       WHERE ci_paciente = :ci
                                       AND nombre_paciente = :nombre";

                $sentenciaValidar = $this->conexion->prepare($sqlValidarPaciente);

                $sentenciaValidar->bindValue(':ci', $ciPaciente);
                $sentenciaValidar->bindValue(':nombre', $nombrePaciente);

                $sentenciaValidar->execute();

                if ($sentenciaValidar->fetchColumn() < 1) {
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

            if (
                empty($datos['ci_enfermero']) ||
                empty($datos['ci_chofer']) ||
                empty($datos['matricula']) ||
                empty($datos['id_traslado']) ||
                empty($datos['id_transportable'])
            ) {
                return false;
            }

            $this->conexion->beginTransaction();

            // La fecha debe ser válida.
            if (
                empty($datos['fecha']) ||
                !$this->validarFechaTraslado($datos['fecha'])
            ) {
                $this->conexion->rollBack();
                return false;
            }

            // ----------------------------------------------
            // RECUPERAR Y BLOQUEAR EL ESTADO ACTUAL
            // ----------------------------------------------

            $sqlEstadoActual = "SELECT
                                    estado_solicitud,
                                    estado_traslado
                                FROM traslado
                                WHERE id_traslado = :id
                                FOR UPDATE";

            $consultaEstado = $this->conexion->prepare($sqlEstadoActual);
            $consultaEstado->bindValue(':id', $datos['id_traslado']);
            $consultaEstado->execute();

            $trasladoActual = $consultaEstado->fetch(PDO::FETCH_ASSOC);

            if (!$trasladoActual) {
                $this->conexion->rollBack();
                return false;
            }

            $estadoSolicitudActual = $trasladoActual['estado_solicitud'];
            $estadoTrasladoActual = $trasladoActual['estado_traslado'];

            /*
             * Si el traslado está aprobado y todavía no finalizó,
             * los recursos nuevos deben estar disponibles.
             *
             * Los pendientes, rechazados y finalizados no ocupan
             * recursos y por eso no necesitan esta comprobación.
             */
            if (
                $estadoSolicitudActual === 'aprobado' &&
                in_array(
                    $estadoTrasladoActual,
                    ['aprobado', 'en proceso'],
                    true
                )
            ) {

                if (
                    !$this->bloquearRecursos(
                        $datos['ci_chofer'],
                        $datos['ci_enfermero'],
                        $datos['matricula']
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }

                if (
                    !$this->recursosDisponibles(
                        $datos['ci_chofer'],
                        $datos['ci_enfermero'],
                        $datos['matricula'],
                        $datos['id_traslado']
                    )
                ) {
                    $this->conexion->rollBack();
                    return false;
                }
            }

            // ----------------------------------------------
            // ACTUALIZAR TRASLADO
            // ----------------------------------------------

            $sqlTraslado = "UPDATE traslado
                            SET ci_chofer = :ci_chofer,
                                matricula = :matricula,
                                id_destino = :id_destino,
                                fecha_traslado = :fecha,
                                hora_salida = :hora,
                                observaciones = :observaciones
                            WHERE id_traslado = :id";

            $sentencia = $this->conexion->prepare($sqlTraslado);

            $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
            $sentencia->bindValue(':matricula', $datos['matricula']);
            $sentencia->bindValue(':id_destino', $datos['id_destino']);
            $sentencia->bindValue(':fecha', $datos['fecha']);

            $hora = $estadoSolicitudActual === 'rechazado'
                ? null
                : (!empty($datos['hora']) ? $datos['hora'] : null);

            // Los traslados aprobados deben conservar una hora válida.
            if (
                $estadoSolicitudActual === 'aprobado' &&
                (
                    !$hora ||
                    !preg_match(
                        '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                        substr($hora, 0, 5)
                    )
                )
            ) {
                $this->conexion->rollBack();
                return false;
            }

            $observaciones = $datos['observaciones'] ?? '';

            $sentencia->bindValue(':observaciones', $observaciones);
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
                                ) VALUES (
                                    :id,
                                    :ci_paciente,
                                    :nombre,
                                    'silla',
                                    :oxigeno,
                                    FALSE
                                )";

                $sentencia = $this->conexion->prepare($sqlPaciente);

                $ciPaciente = trim($datos['ci_paciente']);

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
                                ) VALUES (
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