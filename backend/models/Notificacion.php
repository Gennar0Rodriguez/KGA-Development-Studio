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
        // titulo_not y descripcion_not se devuelven como titulo y descripcion
        // para que la API y el JS sigan funcionando igual
        $sql = "SELECT n.id_notificacion, n.titulo_not AS titulo, n.descripcion_not AS descripcion,
                       r.fecha_envio, r.ci_emisor, r.leido
                FROM notificacion n
                INNER JOIN recibe r ON n.id_notificacion = r.id_notificacion
                WHERE r.ci_admin = :ci
                ORDER BY r.fecha_envio DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':ci', $ciReceptor);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear una notificacion (se guarda en notificacion y en recibe)
    public function crear(string $titulo, string $descripcion, string $ciReceptor, ?string $ciEmisor = null): bool {
        $emisorLimpio = (!empty($ciEmisor) && trim($ciEmisor) !== '') ? $ciEmisor : null;

        // La tabla limita el largo: titulo 50 y descripcion 100
        $titulo = mb_substr($titulo, 0, 50);
        $descripcion = mb_substr($descripcion, 0, 100);

        $idNotificacion = $this->generarId();

        // 1. La notificacion
        $sql = "INSERT INTO notificacion (id_notificacion, titulo_not, descripcion_not)
                VALUES (:id, :titulo, :desc)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id', $idNotificacion);
        $sentencia->bindParam(':titulo', $titulo);
        $sentencia->bindParam(':desc', $descripcion);
        $sentencia->execute();

        // 2. Quien la recibe
        $sql = "INSERT INTO recibe (ci_admin, id_notificacion, ci_emisor, ci_receptor, fecha_envio, leido)
                VALUES (:ci_admin, :id, :emisor, :receptor, NOW(), 0)";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':ci_admin', $ciReceptor);
        $sentencia->bindParam(':id', $idNotificacion);
        $sentencia->bindParam(':emisor', $emisorLimpio);
        $sentencia->bindParam(':receptor', $ciReceptor);

        return $sentencia->execute();
    }

    // Genera un id de 8 caracteres (NOT + 5 numeros) que no exista todavia
    private function generarId(): string {
        do {
            $id = 'NOT' . rand(10000, 99999);

            $sql = "SELECT COUNT(*) FROM notificacion WHERE id_notificacion = :id";
            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindParam(':id', $id);
            $sentencia->execute();
            $existe = $sentencia->fetchColumn() > 0;
        } while ($existe);

        return $id;
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

    // Al borrar la notificacion, recibe se borra sola (ON DELETE CASCADE)
    public function eliminar(string $idNotificacion): bool {
        $sql = "DELETE FROM notificacion WHERE id_notificacion = :id";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':id', $idNotificacion);
        return $sentencia->execute();
    }
}