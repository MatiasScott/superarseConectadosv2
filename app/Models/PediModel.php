<?php
require_once __DIR__ . '/Database.php';

class PediModel extends Database
{
    protected $table_name = "pedi";

    public function recalcularAvanceObjetivoPorPediId($idPedi)
    {
        $db = $this->getConnection();

        $sqlMeta = "SELECT objetivo_estrategico, YEAR(fecha_creacion) AS anio_creacion
                    FROM " . $this->table_name . "
                    WHERE id_pedi = ?
                    LIMIT 1";
        $stmtMeta = $db->prepare($sqlMeta);
        $stmtMeta->execute([(int)$idPedi]);
        $meta = $stmtMeta->fetch(PDO::FETCH_ASSOC);

        if (!$meta) {
            return;
        }

        $objetivo = trim((string)($meta['objetivo_estrategico'] ?? ''));
        $anio = (int)($meta['anio_creacion'] ?? 0);

        if ($objetivo === '' || $anio <= 0) {
            return;
        }

        $sqlAvg = "SELECT AVG(COALESCE(avance_estrategia, 0)) AS promedio
                   FROM " . $this->table_name . "
                   WHERE objetivo_estrategico = ?
                     AND YEAR(fecha_creacion) = ?";
        $stmtAvg = $db->prepare($sqlAvg);
        $stmtAvg->execute([$objetivo, $anio]);
        $rowAvg = $stmtAvg->fetch(PDO::FETCH_ASSOC);

        $promedio = round((float)($rowAvg['promedio'] ?? 0), 2);

        $sqlUpd = "UPDATE " . $this->table_name . "
                   SET avance = ?
                   WHERE objetivo_estrategico = ?
                     AND YEAR(fecha_creacion) = ?";
        $stmtUpd = $db->prepare($sqlUpd);
        $stmtUpd->execute([$promedio, $objetivo, $anio]);
    }

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
