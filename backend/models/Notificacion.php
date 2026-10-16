<?php
require_once __DIR__ . '/../config/Database.php';

class Notificacion
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Obtener las notificaciones de un usuario específico
    public function obtenerPorUsuario(string $ciReceptor): array
    {
        $sql = "SELECT id_notificacion, titulo, descripcion, fecha_envio, ci_emisor, leido 
                FROM notificacion 
                WHERE ci_receptor = :ci 
                ORDER BY fecha_envio DESC";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':ci' => $ciReceptor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una notificación individual
    public function crear(string $titulo, string $descripcion, string $ciReceptor, ?string $ciEmisor = null): bool
    {
        // Si el emisor es vacío o no viene, lo forzamos a NULL
        $emisorLimpio = (!empty($ciEmisor) && trim($ciEmisor) !== '') ? $ciEmisor : null;

        $sql = "INSERT INTO notificacion (titulo, descripcion, ci_emisor, ci_receptor) 
                VALUES (:titulo, :desc, :emisor, :receptor)";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([
            ':titulo'   => $titulo,
            ':desc'     => $descripcion,
            ':emisor'   => $emisorLimpio,
            ':receptor' => $ciReceptor
        ]);
    }

    // Notificar a TODOS los administrativos
    public function notificarATodos(string $titulo, string $descripcion, ?string $ciEmisor = null): bool
    {
        try {
            $stmtAdmins = $this->conexion->query("SELECT ci_admin FROM administrativo");
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

            if (empty($admins)) {
                // Fallback si no hay registros en administrativo aún
                return $this->crear($titulo, $descripcion, '11111111', null);
            }

            foreach ($admins as $admin) {
                $this->crear($titulo, $descripcion, $admin['ci_admin'], $ciEmisor);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function eliminar(int $idNotificacion): bool
    {
        $sql = "DELETE FROM notificacion WHERE id_notificacion = :id";
        $stmt = $this->conexion->prepare($sql);
        return $stmt->execute([':id' => $idNotificacion]);
    }
}