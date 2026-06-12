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
        $query = "SELECT
                         e.nombre AS eje,
                         oe.nombre AS objetivo_estrategico,
                         es.nombre AS objetivo_estrategia,
                         'activo' AS estado,
                         MAX(lb.porcentaje_partida) AS linea_base,
                         MAX(CASE WHEN ml.anio = 2024 THEN ml.porcentaje_esperado END) AS meta_2024,
                         MAX(CASE WHEN ml.anio = 2024 THEN ml.porcentaje_esperado END) AS meta_2024_pct,
                         MAX(CASE WHEN ml.anio = 2025 THEN ml.porcentaje_esperado END) AS meta_2025,
                         MAX(CASE WHEN ml.anio = 2025 THEN ml.porcentaje_esperado END) AS meta_2025_pct,
                         MAX(CASE WHEN ml.anio = 2026 THEN ml.porcentaje_esperado END) AS meta_2026,
                         MAX(CASE WHEN ml.anio = 2026 THEN ml.porcentaje_esperado END) AS meta_2026_pct,
                         MAX(CASE WHEN ml.anio = 2027 THEN ml.porcentaje_esperado END) AS meta_2027,
                         MAX(CASE WHEN ml.anio = 2027 THEN ml.porcentaje_esperado END) AS meta_2027_pct,
                         MAX(CASE WHEN ml.anio = 2028 THEN ml.porcentaje_esperado END) AS meta_2028,
                         MAX(CASE WHEN ml.anio = 2028 THEN ml.porcentaje_esperado END) AS meta_2028_pct
                FROM ejes_estrategicos e
                LEFT JOIN objetivos_estrategicos oe ON oe.eje_id = e.id
                LEFT JOIN estrategias es ON es.objetivo_estrategico_id = oe.id
                LEFT JOIN lineas_base lb ON lb.estrategia_id = es.id
                LEFT JOIN metas_linea_base ml ON ml.linea_base_id = lb.id
                WHERE e.estado = 1
                GROUP BY e.id, oe.id, es.id
                ORDER BY e.nombre, oe.nombre, es.nombre";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PediModel::obtenerTodos consulta extendida no disponible: ' . $e->getMessage());
        }

        $legacyQuery = "SELECT
                            eje,
                            objetivo_estrategico,
                            objetivo_estrategia,
                            COALESCE(estado, 'activo') AS estado,
                            COALESCE(linea_base, '') AS linea_base,
                            COALESCE(meta_2024, '') AS meta_2024,
                            NULL AS meta_2024_pct,
                            COALESCE(meta_2025, '') AS meta_2025,
                            NULL AS meta_2025_pct,
                            COALESCE(meta_2026, '') AS meta_2026,
                            NULL AS meta_2026_pct,
                            COALESCE(meta_2027, '') AS meta_2027,
                            NULL AS meta_2027_pct,
                            COALESCE(meta_2028, '') AS meta_2028,
                            NULL AS meta_2028_pct
                        FROM " . $this->table_name . "
                        ORDER BY id_pedi DESC";

        try {
            $stmt = $db->prepare($legacyQuery);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PediModel::obtenerTodos fallback legacy falló: ' . $e->getMessage());
        }

        $minimumQuery = "SELECT
                            '' AS eje,
                            '' AS objetivo_estrategico,
                            '' AS objetivo_estrategia,
                            'activo' AS estado,
                            '' AS linea_base,
                            '' AS meta_2024,
                            NULL AS meta_2024_pct,
                            '' AS meta_2025,
                            NULL AS meta_2025_pct,
                            '' AS meta_2026,
                            NULL AS meta_2026_pct,
                            '' AS meta_2027,
                            NULL AS meta_2027_pct,
                            '' AS meta_2028,
                            NULL AS meta_2028_pct
                        FROM " . $this->table_name . "
                        LIMIT 0";

        $stmt = $db->prepare($minimumQuery);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();
        $query = "SELECT p.*, YEAR(p.fecha_creacion) AS anio_creacion,
                         MAX(CASE WHEN pm.anio = 0 THEN pm.meta_texto END) AS linea_base,
                         MAX(CASE WHEN pm.anio = 2024 THEN pm.meta_texto END) AS meta_2024,
                         MAX(CASE WHEN pm.anio = 2024 THEN pm.porcentaje END) AS meta_2024_pct,
                         MAX(CASE WHEN pm.anio = 2025 THEN pm.meta_texto END) AS meta_2025,
                         MAX(CASE WHEN pm.anio = 2025 THEN pm.porcentaje END) AS meta_2025_pct,
                         MAX(CASE WHEN pm.anio = 2026 THEN pm.meta_texto END) AS meta_2026,
                         MAX(CASE WHEN pm.anio = 2026 THEN pm.porcentaje END) AS meta_2026_pct,
                         MAX(CASE WHEN pm.anio = 2027 THEN pm.meta_texto END) AS meta_2027,
                         MAX(CASE WHEN pm.anio = 2027 THEN pm.porcentaje END) AS meta_2027_pct,
                         MAX(CASE WHEN pm.anio = 2028 THEN pm.meta_texto END) AS meta_2028,
                         MAX(CASE WHEN pm.anio = 2028 THEN pm.porcentaje END) AS meta_2028_pct
                FROM " . $this->table_name . " p
                LEFT JOIN pedi_metas pm ON pm.pedi_id = p.id_pedi
                WHERE p.id_pedi = ?
                GROUP BY p.id_pedi";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $query = "INSERT INTO " . $this->table_name . "
                (objetivo_estrategico, eje, objetivo_estrategia, avance, avance_estrategia, estado)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        try {
            $result = $stmt->execute([
                $data['objetivo_estrategico'],
                $data['eje'] ?? null,
                $data['objetivo_estrategia'],
                $data['avance'] ?? 0,
                $data['avance_estrategia'] ?? 0,
                $data['estado'] ?? 'activo'
            ]);

            if ($result) {
                $pediId = (int)$db->lastInsertId();
                if (isset($data['linea_base']) && $data['linea_base'] !== '') {
                    $db->prepare("INSERT INTO pedi_metas (pedi_id, anio, meta_texto) VALUES (?, 0, ?)")->execute([$pediId, $data['linea_base']]);
                }
                for ($anio = 2024; $anio <= 2028; $anio++) {
                    $metaVal = $data['meta_' . $anio] ?? '';
                    if ($metaVal !== '') {
                        $db->prepare("INSERT INTO pedi_metas (pedi_id, anio, meta_texto) VALUES (?, ?, ?)")
                            ->execute([$pediId, $anio, $metaVal]);
                    }
                }
            }

            return $result;
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
                    eje = ?,
                    objetivo_estrategia = ?,
                    avance = ?,
                    avance_estrategia = ?,
                    estado = ?
                WHERE id_pedi = ?";

        $stmt = $db->prepare($query);

        try {
            $result = $stmt->execute([
                $data['objetivo_estrategico'],
                $data['eje'] ?? null,
                $data['objetivo_estrategia'],
                $data['avance'] ?? 0,
                $data['avance_estrategia'] ?? 0,
                $data['estado'] ?? 'activo',
                $id
            ]);

            if ($result) {
                if (isset($data['linea_base'])) {
                    $db->prepare("INSERT INTO pedi_metas (pedi_id, anio, meta_texto) VALUES (?, 0, ?)
                                   ON DUPLICATE KEY UPDATE meta_texto = VALUES(meta_texto)")
                        ->execute([$id, $data['linea_base']]);
                }
                for ($anio = 2024; $anio <= 2028; $anio++) {
                    $key = 'meta_' . $anio;
                    if (isset($data[$key])) {
                        $db->prepare("INSERT INTO pedi_metas (pedi_id, anio, meta_texto) VALUES (?, ?, ?)
                                       ON DUPLICATE KEY UPDATE meta_texto = VALUES(meta_texto)")
                            ->execute([$id, $anio, $data[$key]]);
                    }
                }
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error actualizar PEDI: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();
        $db->prepare("DELETE FROM pedi_metas WHERE pedi_id = ?")->execute([$id]);
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
