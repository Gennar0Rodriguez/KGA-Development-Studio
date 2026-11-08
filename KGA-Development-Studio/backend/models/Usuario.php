<?php
require_once __DIR__ . '/../config/Database.php';

class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function crear(string $ci, string $nombre, string $apellido, string $pass, string $rol): bool {
        $sql = 'INSERT INTO administrativo(ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES (:ci, :nombre, :apellido, :password, :rol)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':ci', $ci);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':apellido', $apellido);
        $sentencia->bindParam(':password', $pass);
        $sentencia->bindParam(':rol', $rol);
        return $sentencia->execute();
    }

    public function obtenerTodos(): array {
        $sql = 'SELECT * FROM administrativo';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorCi(string $ci): ?array {
        $sql = 'SELECT * FROM administrativo WHERE ci_admin = :ci';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(":ci", $ci);
        $sentencia->execute();
        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    public function login(string $ci, string $pass): ?array {
        $sql = "SELECT ci_admin, nombre_admin, apellido_admin, cargo FROM administrativo WHERE ci_admin = :ci_admin AND contraseña_admin = :contrasena_admin";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(":ci_admin", $ci);
        $sentencia->bindParam(":contrasena_admin", $pass);
        $sentencia->execute();

        $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);
        return $usuario ?: null;
    }

    public function editarUsuario(string $ci, string $nombre, string $apellido, ?string $pass, string $rol): bool {
        if (!empty($pass)) {
            $sql = 'UPDATE administrativo 
                    SET nombre_admin = :nombre, 
                        apellido_admin = :apellido, 
                        contraseña_admin = :pass, 
                        cargo = :rol 
                    WHERE ci_admin = :ci';

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindParam(':pass', $pass);
        } else {
            $sql = 'UPDATE administrativo 
                    SET nombre_admin = :nombre, 
                        apellido_admin = :apellido, 
                        cargo = :rol 
                    WHERE ci_admin = :ci';

            $sentencia = $this->conexion->prepare($sql);
        }

        $sentencia->bindParam(':ci', $ci);
        $sentencia->bindParam(':nombre', $nombre);
        $sentencia->bindParam(':apellido', $apellido);
        $sentencia->bindParam(':rol', $rol);

        return $sentencia->execute();
    }

    public function eliminar(string $ci): bool {
        $sql = 'DELETE FROM administrativo WHERE ci_admin = :ci';
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(':ci', $ci);
        return $sentencia->execute();
    }
}