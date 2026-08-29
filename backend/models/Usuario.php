<?php
require_once __DIR__ . '/../config/Database.php';

class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function crear(string $ci, string $nombre, string $apellido, string $user, string $pass, string $rol): bool {
        $sql = 'INSERT INTO administrativo(ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES (:ci, :nombre, :apellido, :password, :rol)';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia -> bindParam(':ci', $ci);
        $sentencia -> bindParam(':nombre', $nombre);
        $sentencia -> bindParam(':apellido', $apellido);
        $sentencia -> bindParam(':password', $pass);
        $sentencia -> bindParam(':rol', $rol);
        return $sentencia -> execute();
    }

    // Obtener todos los usuarios
    public function obtenerTodos(): array {
    // Escribimos como texto literal la consulta SQL    
       $sql = 'SELECT * FROM administrativo';
        //Preparamos la sentencia
       $sentencia = $this->conexion->prepare($sql);

       //Ejecutamos la consulta
       $sentencia->execute();

        //Retornamos un array asociativo (FETCH_ASSOC)
       return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener usuario por CI
    public function obtenerPorCi(string $ci): ?array {

        $sql = 'SELECT * FROM administrativo WHERE ci_admin= :ci'; //ci es un parametro de la query
        
        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindParam(":ci", $ci);

        $sentencia->execute();

        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    // LOGIN
    public function login(string $ci, string $pass): ?array {
        $sql = "SELECT ci_admin, nombre_admin, apellido_admin, cargo FROM administrativo WHERE ci_admin = :ci_admin and contraseña_admin = :contrasena_admin";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(":ci_admin", $ci);
        $sentencia->bindParam(":contrasena_admin", $pass);
        $sentencia->execute();

        $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }
}