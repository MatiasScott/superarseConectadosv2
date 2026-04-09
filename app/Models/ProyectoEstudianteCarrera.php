<?php
require_once __DIR__ . '/Database.php';

class ProyectoEstudianteCarrera extends Database
{
    protected $table_name = "proyecto_estudiantes_carrera";

    public function obtenerTodas()
    {
        $db = $this->getConnection();

        $sql = "SELECT pec.*, pa.nombre_proyecto
            FROM proyecto_estudiantes_carrera pec
            INNER JOIN proyectos_administracion pa 
                ON pec.id_proyecto = pa.id_proyecto
                WHERE pa.tipo_proyecto = 'INVESTIGACION'
            ORDER BY pa.nombre_proyecto ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodasv()
    {
        $db = $this->getConnection();

        $sql = "SELECT pec.*, pa.nombre_proyecto
            FROM proyecto_estudiantes_carrera pec
            INNER JOIN proyectos_administracion pa 
                ON pec.id_proyecto = pa.id_proyecto
                WHERE pa.tipo_proyecto = 'VINCULACION'
            ORDER BY pa.nombre_proyecto ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();

        $sql = "DELETE FROM {$this->table_name} WHERE id = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function agregarCarrera($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO {$this->table_name} (id_proyecto, carrera, nro_estudiantes)
                VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['id_proyecto'],
            $data['carrera'],
            $data['nro_estudiantes']
        ]);
    }

    public function obtenerPorProyecto($id_proyecto)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name} WHERE id_proyecto = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id_proyecto]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actualizarCarrera($id, $data)
    {
        $db = $this->getConnection();

        $sql = "UPDATE {$this->table_name}
                SET carrera = ?, nro_estudiantes = ?
                WHERE id = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['carrera'],
            $data['nro_estudiantes'],
            $id
        ]);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name} WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerPorIdConProyecto($id)
    {
        $db = $this->getConnection();

        $sql = "SELECT pec.*, pa.nombre_proyecto
            FROM {$this->table_name} pec
            INNER JOIN proyectos_administracion pa 
                ON pec.id_proyecto = pa.id_proyecto
            WHERE pec.id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
