
<?php

require_once __DIR__ . '/../config/database.php';

class Destino
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // Devuelve los destinos registrados.
    public function obtenerTodos(): array
    {
        $sql = "SELECT id_destino, nombre_destino, direccion, ruta
                FROM destino
                ORDER BY nombre_destino";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    // Genera un identificador único para el destino.
    private function generarId(): string
    {
        do {
            $id = 'DES' . strtoupper(bin2hex(random_bytes(4)));

            $sql = "SELECT COUNT(*) FROM destino WHERE id_destino = :id";
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([':id' => $id]);

            $existe = (int) $consulta->fetchColumn() > 0;

        } while ($existe);

        return $id;
    }

    // Registra un nuevo destino.
    public function crear(array $datos): string
    {
        $id = $this->generarId();

        $sql = "INSERT INTO destino
                (id_destino, nombre_destino, direccion, ruta)
                VALUES
                (:id, :nombre, :direccion, :ruta)";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre_destino'],
            ':direccion' => $datos['direccion'],
            ':ruta' => $datos['ruta']
        ]);

        return $id;
    }
}
