<?php
require_once __DIR__ . '/../config/Database.php';

class Notificacion
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Obtener las notificaciones de un usuario
    public function obtenerPorUsuario(string $ciReceptor): array {
        $sql = "SELECT id_notificacion, titulo, descripcion, fecha_envio, ci_emisor, leido
                FROM notificacion
                WHERE ci_receptor = :ci
                ORDER BY fecha_envio DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':ci', $ciReceptor);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una notificacion
    public function crear(string $titulo, string $descripcion, string $ciReceptor, ?string $ciEmisor = null): bool {
        $emisorLimpio = (!empty($ciEmisor) && trim($ciEmisor) !== '') ? $ciEmisor : null;

        $sql = "INSERT INTO notificacion (titulo, descripcion, ci_emisor, ci_receptor)
                VALUES (:titulo, :desc, :emisor, :receptor)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':titulo', $titulo);
        $sentencia->bindParam(':desc', $descripcion);
        $sentencia->bindParam(':emisor', $emisorLimpio);
        $sentencia->bindParam(':receptor', $ciReceptor);

        return $sentencia->execute();
    }

    // Notificar a todos los administrativos
    public function notificarATodos(string $titulo, string $descripcion, ?string $ciEmisor = null): bool {
        try {
            $sql = "SELECT ci_admin FROM administrativo";
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->execute();
            $administrativos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

            if (empty($administrativos)) {
                // Si no hay administrativos, se usa una CI predeterminada
                return $this->crear($titulo, $descripcion, '11111111', null);
            }

            foreach ($administrativos as $admin) {
                $this->crear($titulo, $descripcion, $admin['ci_admin'], $ciEmisor);
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function eliminar(int $idNotificacion): bool {
        $sql = "DELETE FROM notificacion WHERE id_notificacion = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id', $idNotificacion, PDO::PARAM_INT);
        return $sentencia->execute();
    }
}