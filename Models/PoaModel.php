<?php
require_once __DIR__ . '/Database.php';

class PoaModel extends Database
{
    protected $table_name = "poa";

    public function obtenerTodos()
    {
        $db = $this->getConnection();

        $actividadModel = new PoaActividadModel();
        $actividadModel->actualizarEstadosCaducados();

        $query = "SELECT pa.*,
                         COALESCE(e.nombre, e_alt.nombre, pa.eje) AS eje,
                         COALESCE(oe.nombre, pa.objetivo_estrategico) AS objetivo_estrategico,
                         COALESCE(es.nombre, pa.objetivo_estrategia) AS objetivo_estrategia,
                         a.nombre AS nombre_area, p.presupuesto_anual,
                         s.nombre AS nombre_sede,
                 COALESCE(NULLIF(TRIM(pa.meta), ''), CAST(ml.porcentaje_esperado AS CHAR), pm.meta_texto) AS meta_pedi,
                 COALESCE(ml.porcentaje_esperado, pm.porcentaje) AS meta_pedi_pct,
                 COALESCE(ml.anio, YEAR(pa.fecha_inicio), YEAR(CURDATE())) AS anio_meta_pedi,
                 COALESCE(es.nombre, pa.objetivo_estrategia) AS estrategia_meta_pedi
                FROM poa_actividades pa
                LEFT JOIN poa p ON pa.id_poa = p.id
                LEFT JOIN area a ON pa.area_id = a.id
                LEFT JOIN sede s ON pa.sede_id = s.id
                LEFT JOIN eje_estrategico e ON pa.eje_id = e.id
                LEFT JOIN eje_estrategico e_alt ON e_alt.nombre = pa.eje
                LEFT JOIN objetivo_estrategico oe ON pa.objetivo_id = oe.id
                LEFT JOIN estrategia es ON pa.estrategia_id = es.id
                LEFT JOIN (
                    SELECT estrategia_id, MAX(id) AS linea_base_id
                    FROM lineas_base
                    GROUP BY estrategia_id
                ) lb ON lb.estrategia_id = pa.estrategia_id
                LEFT JOIN (
                    SELECT linea_base_id, anio, MAX(porcentaje_esperado) AS porcentaje_esperado
                    FROM metas_linea_base
                    GROUP BY linea_base_id, anio
                ) ml ON ml.linea_base_id = lb.linea_base_id
                     AND ml.anio = COALESCE(YEAR(pa.fecha_inicio), YEAR(CURDATE()))
                LEFT JOIN (
                    SELECT
                        t.eje_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(t.meta_texto ORDER BY t.anio DESC SEPARATOR '||'), '||', 1) AS meta_texto,
                        CAST(SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(t.porcentaje, 0) ORDER BY t.anio DESC SEPARATOR ','), ',', 1) AS DECIMAL(10,2)) AS porcentaje
                    FROM (
                        SELECT pm.eje_id, pm.anio, pm.meta_texto, pm.porcentaje
                        FROM pedi_metas pm
                        WHERE pm.eje_id IS NOT NULL
                        UNION
                        SELECT e.id AS eje_id, pm.anio, pm.meta_texto, pm.porcentaje
                        FROM pedi_metas pm
                        JOIN pedi p ON pm.pedi_id = p.id_pedi
                        JOIN eje_estrategico e ON p.eje = e.nombre
                        WHERE pm.eje_id IS NULL
                    ) t
                    GROUP BY eje_id
                ) pm ON pm.eje_id = COALESCE(pa.eje_id, e.id, e_alt.id)
                ORDER BY pa.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosPoa()
    {
        $db = $this->getConnection();

        $query = "SELECT p.id AS id_poa, p.id_pedi, p.nombre, a.nombre AS nombre_area,
                         p.presupuesto_anual, p.estado_actividad, p.observaciones, p.estado,
                    COALESCE(pa.presupuesto_asignado, 0) AS presupuesto_asignado,
                    (COALESCE(p.presupuesto_anual, 0) - COALESCE(pa.presupuesto_asignado, 0)) AS presupuesto_disponible
                FROM poa p
                LEFT JOIN area a ON p.area_id = a.id
                LEFT JOIN (
                    SELECT id_poa, COALESCE(SUM(presupuesto_planificado), 0) AS presupuesto_asignado
                    FROM poa_actividades
                    GROUP BY id_poa
                ) pa ON pa.id_poa = p.id
                WHERE p.estado = 'activo'
                ORDER BY p.id DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT p.id AS id_poa, p.id_pedi, p.nombre, a.nombre AS nombre_area,
                         p.presupuesto_anual, p.estado_actividad, p.observaciones, p.estado
                FROM " . $this->table_name . " p
                LEFT JOIN area a ON p.area_id = a.id
                WHERE p.id = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO poa
            (id_pedi, nombre, area_id, presupuesto_anual, estado_actividad, observaciones, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['id_pedi'] ?? null,
            $data['nombre_area'] ?? ($data['nombre'] ?? ''),
            $data['area_id'] ?? null,
            $data['presupuesto_anual'] ?? 0,
            $data['estado_actividad'] ?? 'no ejecutada',
            $data['observaciones'] ?? '',
            $data['estado'] ?? 'activo'
        ]);
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $query = "UPDATE " . $this->table_name . "
            SET id_pedi = ?,
                nombre = ?,
                area_id = ?,
                presupuesto_anual = ?,
                estado_actividad = ?,
                observaciones = ?,
                estado = ?
            WHERE id = ?";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['id_pedi'] ?? null,
                $data['nombre_area'] ?? ($data['nombre'] ?? ''),
                $data['area_id'] ?? null,
                $data['presupuesto_anual'] ?? 0,
                $data['estado_actividad'] ?? 'no ejecutada',
                $data['observaciones'] ?? '',
                $data['estado'] ?? 'activo',
                $id
            ]);
        } catch (PDOException $e) {
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
