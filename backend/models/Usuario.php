<?php
require_once __DIR__ . '/../config/Database.php';
//creamos la clase usuario que va a tener los metodos para crear, obtener y loguear usuarios
class Usuario
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }
    //creamos con la sentencia insert into un usuario con los datos que le pasamos por parametro, devuelve true si se creo correctamente y false si no
    public function crear(string $ci, string $nombre, string $apellido,  string $pass, string $rol): bool {
        $sql = 'INSERT INTO administrativo(ci_admin, nombre_admin, apellido_admin, contraseña_admin, cargo) VALUES (:ci, :nombre, :apellido, :password, :rol)';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia -> bindParam(':ci', $ci);
        $sentencia -> bindParam(':nombre', $nombre);
        $sentencia -> bindParam(':apellido', $apellido);
        $sentencia -> bindParam(':password', $pass);
        $sentencia -> bindParam(':rol', $rol);
        return $sentencia -> execute();
    }
    //creamos un metodo que nos devuelva todos los usuarios de la base de datos, devuelve un array con todos los usuarios
    public function obtenerTodos(): array {
       $sql = 'SELECT * FROM administrativo';
       $sentencia = $this->conexion->prepare($sql);

       $sentencia->execute();

       return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorCi(string $ci): ?array {

        $sql = 'SELECT * FROM administrativo WHERE ci_admin= :ci'; //ci es un parametro de la query
        
        $sentencia = $this->conexion->prepare($sql);

        $sentencia->bindParam(":ci", $ci);

        $sentencia->execute();

        return ($sentencia->fetch(PDO::FETCH_ASSOC)) ?: null;
    }

    // login basicon con la ci y la contraseña, le puse con la n pq si no no me acuerdo q me pasaba que no andaba
    public function login(string $ci, string $pass): ?array {
        $sql = "SELECT ci_admin, nombre_admin, apellido_admin, cargo FROM administrativo WHERE ci_admin = :ci_admin and contraseña_admin = :contrasena_admin";
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindParam(":ci_admin", $ci);
        $sentencia->bindParam(":contrasena_admin", $pass);
        $sentencia->execute();

        $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function editarUsuario(string $ci, string $nombre, string $apellido,  string $pass, string $rol): bool {
        $sql = 'UPDATE administrativo SET nombre_admin=:nombre, apellido_admin=:apellido, contraseña_admin=:pass, cargo WHERE ci= :ci_admin';

        $sentencia = $this->conexion->prepare($sql);

        $sentencia -> bindParam(':ci', $ci);
        $sentencia -> bindParam(':nombre', $nombre);
        $sentencia -> bindParam(':apellido', $apellido);
        $sentencia -> bindParam(':password', $pass);
        $sentencia -> bindParam(':rol', $rol);
        return $sentencia -> execute();
    }
}

