<?php
require_once __DIR__ . '/Database.php';

class PoaActividadModel extends Database
{
    protected $table_name = "poa_actividades";

    public function obtenerPresupuestoUsadoPorPoa($idPoa, $excluirActividadId = null)
    {
        $db = $this->getConnection();

        $query = "SELECT COALESCE(SUM(presupuesto_asignado), 0) AS total
                FROM " . $this->table_name . "
                WHERE poa_id = ?";

        $params = [(int) $idPoa];

        if ($excluirActividadId !== null) {
            $query .= " AND id <> ?";
            $params[] = (int) $excluirActividadId;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float) ($row['total'] ?? 0);
    }

    public function obtenerTodos()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, p.anio_planificacion, s.nombre AS sede_nombre
                FROM poa_actividades a
                INNER JOIN poa p ON p.id = a.poa_id
                INNER JOIN sedes s ON s.id = p.sede_id
                ORDER BY a.id DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorPoaId($poaId)
    {
        $db = $this->getConnection();

        $query = "SELECT a.*,
                    COALESCE(cr.promedio_avance, 0) AS avance_cronograma
                FROM poa_actividades a
                LEFT JOIN (
                    SELECT poa_actividad_id, AVG(avance) AS promedio_avance
                    FROM cronogramas
                    GROUP BY poa_actividad_id
                ) cr ON cr.poa_actividad_id = a.id
                WHERE a.poa_id = ?
                ORDER BY a.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([(int) $poaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, p.anio_planificacion, p.presupuesto_total_aprobado, s.nombre AS sede_nombre
                FROM " . $this->table_name . " a
                INNER JOIN poa p ON p.id = a.poa_id
                INNER JOIN sedes s ON s.id = p.sede_id
                WHERE a.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([(int) $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $query = "INSERT INTO " . $this->table_name . "
                (poa_id, tipo_registro, nombre, descripcion, laboratorio, meta, presupuesto_asignado, presupuesto_ejecutado, avance_actividad, estado, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        try {
            $ok = $stmt->execute([
                (int) $data['poa_id'],
                (string) $data['tipo_registro'],
                (string) $data['nombre'],
                (string) ($data['descripcion'] ?? ''),
                (string) ($data['laboratorio'] ?? ''),
                (string) $data['meta'],
                (float) $data['presupuesto_asignado'],
                (float) $data['presupuesto_ejecutado'],
                (float) ($data['avance_actividad'] ?? 0),
                !empty($data['estado']) ? 1 : 0,
                (string) ($data['observaciones'] ?? ''),
            ]);

            if (!$ok) {
                return 0;
            }

            return (int) $db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error crear actividad: " . $e->getMessage());
            return 0;
        }
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $query = "UPDATE " . $this->table_name . "
                SET poa_id = ?,
                    tipo_registro = ?,
                    nombre = ?,
                    descripcion = ?,
                    laboratorio = ?,
                    meta = ?,
                    presupuesto_asignado = ?,
                    presupuesto_ejecutado = ?,
                    avance_actividad = ?,
                    observaciones = ?,
                    estado = ?
                WHERE id = ?";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                (int) $data['poa_id'],
                (string) $data['tipo_registro'],
                (string) $data['nombre'],
                (string) ($data['descripcion'] ?? ''),
                (string) ($data['laboratorio'] ?? ''),
                (string) $data['meta'],
                (float) $data['presupuesto_asignado'],
                (float) $data['presupuesto_ejecutado'],
                (float) ($data['avance_actividad'] ?? 0),
                (string) ($data['observaciones'] ?? ''),
                !empty($data['estado']) ? 1 : 0,
                (int) $id,
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizar actividad: " . $e->getMessage());
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
            error_log("Error eliminar actividad POA: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerCronogramaPorActividad($actividadId)
    {
        $db = $this->getConnection();
        $sql = "SELECT mes, avance, estado_semaforo, observaciones
                FROM cronogramas
                WHERE poa_actividad_id = ?
                ORDER BY mes ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([(int) $actividadId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $indexed = [];
        foreach ($rows as $row) {
            $mes = (int) ($row['mes'] ?? 0);
            if ($mes >= 1 && $mes <= 12) {
                $indexed[$mes] = $row;
            }
        }

        return $indexed;
    }

    public function guardarCronogramaActividad($actividadId, array $cronogramaPorMes)
    {
        $db = $this->getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO cronogramas (poa_actividad_id, mes, avance, estado_semaforo, estado, observaciones)
                VALUES (?, ?, ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE
                    avance = VALUES(avance),
                    estado_semaforo = VALUES(estado_semaforo),
                    observaciones = VALUES(observaciones),
                    estado = 1,
                    fecha_actualizacion = CURRENT_TIMESTAMP");

            foreach ($cronogramaPorMes as $mes => $item) {
                $mesNumero = (int) $mes;
                if ($mesNumero < 1 || $mesNumero > 12) {
                    continue;
                }

                $avance = max(0, min(100, (float) ($item['avance'] ?? 0)));
                $semaforo = (string) ($item['estado_semaforo'] ?? 'no_cumple');
                if (!in_array($semaforo, ['no_cumple', 'cumple_parcialmente', 'cumple_segun_planificado'], true)) {
                    $semaforo = 'no_cumple';
                }

                $stmt->execute([
                    (int) $actividadId,
                    $mesNumero,
                    $avance,
                    $semaforo,
                    (string) ($item['observaciones'] ?? ''),
                ]);
            }

            $db->commit();
            return true;
        } catch (Throwable $e) {
            $db->rollBack();
            error_log('Error guardar cronograma actividad: ' . $e->getMessage());
            return false;
        }
    }
}
