<?php
require_once __DIR__ . '/../config/Database.php';

class Documento
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    public function obtenerTodos(): array {
        $sql = "SELECT d.id_documentos, d.titulo_docs, d.descripcion_docs, d.id_categoria,
                       c.fecha, c.hora, c.ci_admin, cat.nombre_cat
                FROM documentos d
                LEFT JOIN carga c ON d.id_documentos = c.id_documentos
                LEFT JOIN categoria cat ON d.id_categoria = cat.id_categoria
                ORDER BY c.fecha DESC, c.hora DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();
        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $idDoc, string $idCat, string $titulo, string $descripcion, string $ciAdmin): bool {
        try {
            $this->conexion->beginTransaction();

            $sqlDoc = "INSERT INTO documentos (id_documentos, id_categoria, titulo_docs, descripcion_docs)
                       VALUES (:id, :cat, :titulo, :desc)";

            $sentencia = $this->conexion->prepare($sqlDoc);
            $sentencia->bindParam(':id', $idDoc);
            $sentencia->bindParam(':cat', $idCat);
            $sentencia->bindParam(':titulo', $titulo);
            $sentencia->bindParam(':desc', $descripcion);
            $sentencia->execute();

            $sqlCarga = "INSERT INTO carga (ci_admin, id_documentos, hora, fecha)
                         VALUES (:ci, :idDoc, CURTIME(), CURDATE())";

            $sentencia = $this->conexion->prepare($sqlCarga);
            $sentencia->bindParam(':ci', $ciAdmin);
            $sentencia->bindParam(':idDoc', $idDoc);
            $sentencia->execute();

            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return false;
        }
    }

    public function eliminar(string $idDoc): bool {
        try {
            $this->conexion->beginTransaction();

            // Eliminar primero de la tabla carga
            $sqlCarga = "DELETE FROM carga WHERE id_documentos = :id";
            $sentencia = $this->conexion->prepare($sqlCarga);
            $sentencia->bindParam(':id', $idDoc);
            $sentencia->execute();

            // Despues eliminar de la tabla documentos
            $sqlDoc = "DELETE FROM documentos WHERE id_documentos = :id";
            $sentencia = $this->conexion->prepare($sqlDoc);
            $sentencia->bindParam(':id', $idDoc);
            $sentencia->execute();

            $this->conexion->commit();
            return true;

        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return false;
        }
    }
}