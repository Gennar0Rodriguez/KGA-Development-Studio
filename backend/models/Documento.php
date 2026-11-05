<?php
require_once __DIR__ . '/../config/Database.php';

class Documento
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Database::getInstancia()->getConexion();
    }

    // ======================================================
    // OBTENER TODOS LOS DOCUMENTOS
    // ======================================================

    public function obtenerTodos(): array {

        $sql = "SELECT
                    d.id_documentos,
                    d.titulo_docs,
                    d.descripcion_docs,
                    d.id_categoria,
                    d.fecha_carga AS fecha,
                    d.hora_carga AS hora,
                    d.ci_admin,
                    cat.nombre_cat
                FROM documentos d
                LEFT JOIN categoria cat
                    ON d.id_categoria = cat.id_categoria
                ORDER BY d.fecha_carga DESC, d.hora_carga DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }


    // ======================================================
    // BUSCAR UN DOCUMENTO POR ID
    // ======================================================

    public function buscarPorId(string $idDoc): ?array {

        $sql = "SELECT
                    id_documentos,
                    id_categoria,
                    titulo_docs,
                    descripcion_docs,
                    nombre_archivo,
                    ruta_archivo
                FROM documentos
                WHERE id_documentos = :id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->bindValue(':id', $idDoc);
        $sentencia->execute();

        return $sentencia->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    // ======================================================
    // CREAR UN DOCUMENTO
    // ======================================================

    public function crear(
        string $idDoc,
        string $idCat,
        string $titulo,
        string $descripcion,
        string $nombreArchivo,
        string $rutaArchivo,
        string $ciAdmin
    ): bool {

        try {

            // Ya no necesitamos insertar en una segunda tabla.
            // La fecha, hora y CI se guardan directamente en documentos.

            $sql = "INSERT INTO documentos (
                        id_documentos,
                        ci_admin,
                        id_categoria,
                        hora_carga,
                        fecha_carga,
                        titulo_docs,
                        descripcion_docs,
                        nombre_archivo,
                        ruta_archivo
                    )
                    VALUES (
                        :id,
                        :ci,
                        :cat,
                        CURTIME(),
                        CURDATE(),
                        :titulo,
                        :descripcion,
                        :nombre,
                        :ruta
                    )";

            $sentencia = $this->conexion->prepare($sql);

            $sentencia->bindValue(':id', $idDoc);
            $sentencia->bindValue(':ci', $ciAdmin);
            $sentencia->bindValue(':cat', $idCat);
            $sentencia->bindValue(':titulo', $titulo);
            $sentencia->bindValue(':descripcion', $descripcion);
            $sentencia->bindValue(':nombre', $nombreArchivo);
            $sentencia->bindValue(':ruta', $rutaArchivo);

            return $sentencia->execute();

        } catch (Throwable $e) {

            error_log("Error al crear documento: " . $e->getMessage());

            return false;
        }
    }


    // ======================================================
    // ELIMINAR UN DOCUMENTO
    // ======================================================

    public function eliminar(string $idDoc): bool {

        try {

            // Ya no existe la tabla carga.
            // Eliminamos directamente el registro del documento.

            $sql = "DELETE FROM documentos
                    WHERE id_documentos = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idDoc);

            return $sentencia->execute();

        } catch (Throwable $e) {

            error_log("Error al eliminar documento: " . $e->getMessage());

            return false;
        }
    }
}