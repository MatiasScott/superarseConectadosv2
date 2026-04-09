<?php
require_once __DIR__ . '/Database.php';

class PoaModel extends Database
{
    protected $table_name = "poa";

    public function obtenerTodos()
    {
        $db = $this->getConnection();

        $query = "SELECT p.*, pe.objetivo_estrategico, pe.objetivo_estrategia
                FROM poa p
                LEFT JOIN pedi pe ON p.id_pedi = pe.id_pedi
                ORDER BY p.id_poa DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT * FROM " . $this->table_name . " WHERE id_poa = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO poa
            (id_pedi, nombre_area, presupuesto_anual, estado_actividad, observaciones, estado)
            VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['id_pedi'],
            $data['nombre_area'],
            $data['presupuesto_anual'],
            $data['estado_actividad'],
            $data['observaciones'],
            $data['estado']
        ]);
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $query = "UPDATE " . $this->table_name . "
            SET id_pedi = ?,
                nombre_area = ?,
                presupuesto_anual = ?,
                estado_actividad = ?,
                observaciones = ?,
                estado = ?
            WHERE id_poa = ?";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['id_pedi'],
                $data['nombre_area'],
                $data['presupuesto_anual'],
                $data['estado_actividad'],
                $data['observaciones'],
                $data['estado'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizar POA: " . $e->getMessage());
            return false;
        }
    }
}
