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

    public function obtenerTodos(): array
    {
        $sql = "SELECT
                    d.id_documentos,
                    d.titulo_docs,
                    d.descripcion_docs,
                    d.id_categoria,
                    d.fecha_carga AS fecha,
                    d.hora_carga AS hora,
                    d.ci_admin,
                    cat.nombre_cat,
                    cat.estado_cat
                FROM documentos d
                INNER JOIN categoria cat
                    ON d.id_categoria = cat.id_categoria
                WHERE cat.tipo_cat = 'docs'
                ORDER BY d.fecha_carga DESC, d.hora_carga DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute();

        return $sentencia->fetchAll(PDO::FETCH_ASSOC);
    }

    // ======================================================
    // BUSCAR UN DOCUMENTO POR ID
    // ======================================================

    public function buscarPorId(string $idDoc): ?array
    {
        $sql = "SELECT
                    d.id_documentos,
                    d.id_categoria,
                    d.titulo_docs,
                    d.descripcion_docs,
                    d.nombre_archivo,
                    d.ruta_archivo
                FROM documentos d
                INNER JOIN categoria cat
                    ON d.id_categoria = cat.id_categoria
                WHERE d.id_documentos = :id
                AND cat.tipo_cat = 'docs'";

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

            error_log('Error al crear documento: ' . $e->getMessage());

            return false;
        }
    }

    // ======================================================
    // ELIMINAR UN DOCUMENTO
    // ======================================================

    public function eliminar(string $idDoc): bool
    {
        try {

            $sql = "DELETE FROM documentos
                    WHERE id_documentos = :id";

            $sentencia = $this->conexion->prepare($sql);
            $sentencia->bindValue(':id', $idDoc);

            return $sentencia->execute();

        } catch (Throwable $e) {

            error_log('Error al eliminar documento: ' . $e->getMessage());

            return false;
        }
    }
}