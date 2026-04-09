<?php
require_once __DIR__ . '/Database.php';

class Publicacion extends Database
{
    protected $table_name = "publicaciones";

    public function obtenerTodas()
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name} ORDER BY anio DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();

        $sql = "DELETE FROM {$this->table_name} WHERE id_publicacion = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO {$this->table_name} (nombre_publicacion, anio, tipo, periodo_academico)
                VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_publicacion'],
            $data['anio'],
            $data['tipo'],
            $data['periodo_academico']
        ]);
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $sql = "UPDATE {$this->table_name}
                SET nombre_publicacion = ?, anio = ?, tipo = ?, periodo_academico = ?
                WHERE id_publicacion = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_publicacion'],
            $data['anio'],
            $data['tipo'],
            $data['periodo_academico'],
            $id
        ]);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name} WHERE id_publicacion = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
