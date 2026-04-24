<?php
require_once __DIR__ . '/Database.php';

class PediModel extends Database
{
    protected $table_name = "pedi";

    public function obtenerTodos()
    {
        $db = $this->getConnection();
        $query = "SELECT *, YEAR(fecha_creacion) AS anio_creacion FROM " . $this->table_name . " ORDER BY id_pedi DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();
        $query = "SELECT *, YEAR(fecha_creacion) AS anio_creacion FROM " . $this->table_name . " WHERE id_pedi = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $query = "INSERT INTO " . $this->table_name . "
                (objetivo_estrategico, avance, objetivo_estrategia, avance_estrategia, estado)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['objetivo_estrategico'],
                $data['avance'],
                $data['objetivo_estrategia'],
                $data['avance_estrategia'],
                $data['estado']
            ]);
        } catch (PDOException $e) {
            error_log("Error crear PEDI: " . $e->getMessage());
            return false;
        }
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $query = "UPDATE " . $this->table_name . "
                SET objetivo_estrategico = ?,
                    avance = ?,
                    objetivo_estrategia = ?,
                    avance_estrategia = ?,
                    estado = ?
                WHERE id_pedi = ?";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['objetivo_estrategico'],
                $data['avance'],
                $data['objetivo_estrategia'],
                $data['avance_estrategia'],
                $data['estado'],
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizar PEDI: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();
        $query = "DELETE FROM " . $this->table_name . " WHERE id_pedi = ?";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminar PEDI: " . $e->getMessage());
            return false;
        }
    }
}
