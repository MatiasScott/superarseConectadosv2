<?php
require_once __DIR__ . '/Database.php';

class PoaModel extends Database
{
    protected $table_name = "poa";

    public function obtenerEstrategiasCatalogo()
    {
        $db = $this->getConnection();
        $sql = "SELECT
                    es.id,
                    es.codigo,
                    es.nombre,
                    obj.codigo AS objetivo_codigo,
                    obj.nombre AS objetivo_nombre,
                    ej.nombre AS eje_nombre
                FROM estrategias es
                INNER JOIN objetivos_estrategicos obj ON obj.id = es.objetivo_estrategico_id
                INNER JOIN ejes_estrategicos ej ON ej.id = obj.eje_id
                WHERE es.estado = 1
                ORDER BY ej.nombre ASC, obj.codigo ASC, es.codigo ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerSedes()
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT id, nombre FROM sedes WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerProcesos()
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT id, nombre FROM procesos WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos()
    {
        $db = $this->getConnection();

        $query = "SELECT
                    p.*,
                    es.codigo AS estrategia_codigo,
                    es.nombre AS estrategia_nombre,
                    s.nombre AS sede_nombre,
                    COALESCE(pa.presupuesto_asignado, 0) AS presupuesto_asignado,
                    (COALESCE(p.presupuesto_total_aprobado, 0) - COALESCE(pa.presupuesto_asignado, 0)) AS presupuesto_disponible,
                    COALESCE(pr.procesos_nombres, '') AS procesos_nombres
                FROM poa p
                INNER JOIN estrategias es ON es.id = p.estrategia_id
                INNER JOIN sedes s ON s.id = p.sede_id
                LEFT JOIN (
                    SELECT poa_id, COALESCE(SUM(presupuesto_asignado), 0) AS presupuesto_asignado
                    FROM poa_actividades
                    GROUP BY poa_id
                ) pa ON pa.poa_id = p.id
                LEFT JOIN (
                    SELECT
                        pp.poa_id,
                        GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR ', ') AS procesos_nombres
                    FROM poa_procesos pp
                    INNER JOIN procesos pr ON pr.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) pr ON pr.poa_id = p.id
                ORDER BY p.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT
                    p.*,
                    es.codigo AS estrategia_codigo,
                    es.nombre AS estrategia_nombre,
                    s.nombre AS sede_nombre
                FROM " . $this->table_name . " p
                INNER JOIN estrategias es ON es.id = p.estrategia_id
                INNER JOIN sedes s ON s.id = p.sede_id
                WHERE p.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $row['procesos_ids'] = $this->obtenerProcesosIdsPorPoa((int) $row['id']);
        return $row;
    }

    public function obtenerProcesosIdsPorPoa($poaId)
    {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT proceso_id FROM poa_procesos WHERE poa_id = ? ORDER BY proceso_id ASC");
        $stmt->execute([(int) $poaId]);
        return array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'proceso_id'));
    }

    public function sincronizarProcesosPorPoa($poaId, array $procesosIds)
    {
        $db = $this->getConnection();
        $db->beginTransaction();
        try {
            $deleteStmt = $db->prepare("DELETE FROM poa_procesos WHERE poa_id = ?");
            $deleteStmt->execute([(int) $poaId]);

            if (!empty($procesosIds)) {
                $insertStmt = $db->prepare("INSERT INTO poa_procesos (poa_id, proceso_id) VALUES (?, ?)");
                foreach ($procesosIds as $procesoId) {
                    $insertStmt->execute([(int) $poaId, (int) $procesoId]);
                }
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            error_log("Error sincronizar procesos POA: " . $e->getMessage());
            return false;
        }
    }

    public function crear($data)
    {
        $db = $this->getConnection();
        $db->beginTransaction();
        try {
            $sql = "INSERT INTO poa
                (estrategia_id, sede_id, anio_planificacion, presupuesto_total_aprobado, estado_aprobacion, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                (int) $data['estrategia_id'],
                (int) $data['sede_id'],
                (int) $data['anio_planificacion'],
                (float) $data['presupuesto_total_aprobado'],
                (string) $data['estado_aprobacion'],
                !empty($data['estado']) ? 1 : 0,
                (string) ($data['observaciones'] ?? ''),
            ]);

            $poaId = (int) $db->lastInsertId();
            if (!empty($data['procesos_ids']) && is_array($data['procesos_ids'])) {
                $insertProceso = $db->prepare("INSERT INTO poa_procesos (poa_id, proceso_id) VALUES (?, ?)");
                foreach ($data['procesos_ids'] as $procesoId) {
                    $insertProceso->execute([$poaId, (int) $procesoId]);
                }
            }

            $db->commit();
            return $poaId;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error crear POA: " . $e->getMessage());
            return 0;
        }
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();
        $db->beginTransaction();
        try {
            $query = "UPDATE " . $this->table_name . "
                SET estrategia_id = ?,
                    sede_id = ?,
                    anio_planificacion = ?,
                    presupuesto_total_aprobado = ?,
                    estado_aprobacion = ?,
                    observaciones = ?,
                    estado = ?
                WHERE id = ?";

            $stmt = $db->prepare($query);
            $stmt->execute([
                (int) $data['estrategia_id'],
                (int) $data['sede_id'],
                (int) $data['anio_planificacion'],
                (float) $data['presupuesto_total_aprobado'],
                (string) $data['estado_aprobacion'],
                (string) ($data['observaciones'] ?? ''),
                !empty($data['estado']) ? 1 : 0,
                (int) $id,
            ]);

            $deleteStmt = $db->prepare("DELETE FROM poa_procesos WHERE poa_id = ?");
            $deleteStmt->execute([(int) $id]);
            if (!empty($data['procesos_ids']) && is_array($data['procesos_ids'])) {
                $insertStmt = $db->prepare("INSERT INTO poa_procesos (poa_id, proceso_id) VALUES (?, ?)");
                foreach ($data['procesos_ids'] as $procesoId) {
                    $insertStmt->execute([(int) $id, (int) $procesoId]);
                }
            }

            $db->commit();
            return true;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Error actualizar POA: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminar POA: " . $e->getMessage());
            return false;
        }
    }
}
