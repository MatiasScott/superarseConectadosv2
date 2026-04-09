<?php
require_once __DIR__ . '/Database.php';

class Ponencia extends Database
{
    protected $table_name = "ponencias";

    public function obtenerTodas()
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name} ORDER BY fecha_realizacion DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();

        $sql = "DELETE FROM {$this->table_name} WHERE id_ponencia = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO {$this->table_name} (nombre_ponencia, autor, nro_acta, fecha_realizacion, nombre_organizador, periodo_academico)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_ponencia'],
            $data['autor'],
            $data['nro_acta'],
            $data['fecha_realizacion'],
            $data['nombre_organizador'],
            $data['periodo_academico']
        ]);
    }

        public function obtenerPorId($id)
        {
            $db = $this->getConnection();
    
            $sql = "SELECT * FROM {$this->table_name} WHERE id_ponencia = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$id]);
    
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $sql = "UPDATE {$this->table_name} SET
                nombre_ponencia = ?,
                autor = ?,
                fecha_realizacion = ?,
                nombre_organizador = ?,
                periodo_academico = ?
                WHERE id_ponencia = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_ponencia'],
            $data['autor'],
            $data['fecha_realizacion'],
            $data['nombre_organizador'],
            $data['periodo_academico'],
            $id
        ]);
    }
}