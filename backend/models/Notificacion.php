<?php
require_once __DIR__ . '/../config/Database.php';

class Notificacion
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // ======================================================
    // OBTENER NOTIFICACIONES DE UN USUARIO
    // ======================================================
    public function obtenerPorUsuario(string $ciReceptor): array {

        $sql = "SELECT
                    id_traslado,
                    ci_chofer,
                    matricula,
                    id_destino,
                    ci_admin,
                    ci_emisor,
                    ci_receptor,
                    titulo_not AS titulo,
                    descripcion_not AS descripcion,
                    fecha_envio,
                    leido
                FROM notifica
                WHERE ci_receptor = :ci
                ORDER BY fecha_envio DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':ci', $ciReceptor);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // ======================================================
    // CREAR UNA NOTIFICACIÓN
    // ======================================================
    public function crear(
        string $idTraslado,
        string $ciChofer,
        string $matricula,
        string $idDestino,
        string $ciEmisor,
        string $ciReceptor,
        string $titulo,
        string $descripcion
    ): bool {

        // Limitar los textos al tamaño definido en la base de datos.
        $titulo = mb_substr($titulo, 0, 100);
        $descripcion = mb_substr($descripcion, 0, 255);

        /*
         * ci_admin representa la relación con administrativo.
         * Utilizamos la CI del receptor, ya que tanto empleados
         * como administradores pertenecen a esa entidad.
         */

        $sql = "INSERT INTO notifica (
                    id_traslado,
                    ci_chofer,
                    matricula,
                    id_destino,
                    ci_admin,
                    ci_emisor,
                    fecha_envio,
                    leido,
                    titulo_not,
                    descripcion_not,
                    ci_receptor
                )
                VALUES (
                    :id_traslado,
                    :ci_chofer,
                    :matricula,
                    :id_destino,
                    :ci_admin,
                    :ci_emisor,
                    CURDATE(),
                    FALSE,
                    :titulo,
                    :descripcion,
                    :ci_receptor
                )";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_traslado', $idTraslado);
        $sentencia->bindValue(':ci_chofer', $ciChofer);
        $sentencia->bindValue(':matricula', $matricula);
        $sentencia->bindValue(':id_destino', $idDestino);

        // La relación administrativa corresponde al receptor.
        $sentencia->bindValue(':ci_admin', $ciReceptor);

        $sentencia->bindValue(':ci_emisor', $ciEmisor);
        $sentencia->bindValue(':ci_receptor', $ciReceptor);

        $sentencia->bindValue(':titulo', $titulo);
        $sentencia->bindValue(':descripcion', $descripcion);

        return $sentencia->execute();
    }

    // ======================================================
    // NOTIFICAR A TODOS LOS ADMINISTRADORES
    // ======================================================
    public function notificarATodos(
        string $idTraslado,
        string $ciChofer,
        string $matricula,
        string $idDestino,
        string $ciEmisor,
        string $titulo,
        string $descripcion
    ): bool {

        try {

            // Las nuevas solicitudes se notifican a los administradores.
            $sql = "SELECT ci_admin
                    FROM administrativo
                    WHERE cargo = 'administrador'";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->execute();

            $administradores = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            if (empty($administradores)) {
                return false;
            }

            $resultado = true;

            foreach ($administradores as $admin) {

                $creada = $this->crear(
                    $idTraslado,
                    $ciChofer,
                    $matricula,
                    $idDestino,
                    $ciEmisor,
                    $admin['ci_admin'],
                    $titulo,
                    $descripcion
                );

                if (!$creada) {
                    $resultado = false;
                }
            }

            return $resultado;

        } catch (Throwable $e) {

            error_log("Error al notificar administradores: " . $e->getMessage());

            return false;
        }
    }

    // ======================================================
    // MARCAR UNA NOTIFICACIÓN COMO LEÍDA
    // ======================================================
    public function marcarComoLeida(array $datos, string $ciReceptor): bool {

        $sql = "UPDATE notifica
                SET leido = TRUE
                WHERE id_traslado = :id_traslado
                AND ci_chofer = :ci_chofer
                AND matricula = :matricula
                AND id_destino = :id_destino
                AND ci_admin = :ci_admin
                AND ci_receptor = :ci_receptor";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_traslado', $datos['id_traslado']);
        $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
        $sentencia->bindValue(':matricula', $datos['matricula']);
        $sentencia->bindValue(':id_destino', $datos['id_destino']);

        // El usuario solamente puede modificar sus propias notificaciones.
        $sentencia->bindValue(':ci_admin', $datos['ci_admin']);
        $sentencia->bindValue(':ci_receptor', $ciReceptor);

        return $sentencia->execute();
    }

    // ======================================================
    // ELIMINAR UNA NOTIFICACIÓN
    // ======================================================
    public function eliminar(array $datos, string $ciReceptor): bool {

        $sql = "DELETE FROM notifica
                WHERE id_traslado = :id_traslado
                AND ci_chofer = :ci_chofer
                AND matricula = :matricula
                AND id_destino = :id_destino
                AND ci_admin = :ci_admin
                AND ci_receptor = :ci_receptor";

        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindValue(':id_traslado', $datos['id_traslado']);
        $sentencia->bindValue(':ci_chofer', $datos['ci_chofer']);
        $sentencia->bindValue(':matricula', $datos['matricula']);
        $sentencia->bindValue(':id_destino', $datos['id_destino']);
        $sentencia->bindValue(':ci_admin', $ciReceptor);
        $sentencia->bindValue(':ci_receptor', $ciReceptor);

        return $sentencia->execute();
    }
}