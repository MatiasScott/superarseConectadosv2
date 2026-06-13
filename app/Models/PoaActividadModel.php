<?php
require_once __DIR__ . '/Database.php';

class PoaActividadModel extends Database
{
    protected $table_name = "poa_actividades";

    private ?array $cacheColumnasActividad = null;

    private function obtenerColumnasActividad(PDO $db): array
    {
        if ($this->cacheColumnasActividad !== null) {
            return $this->cacheColumnasActividad;
        }

        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$this->table_name]);
        $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $this->cacheColumnasActividad = is_array($columnas) ? array_fill_keys($columnas, true) : [];
        return $this->cacheColumnasActividad;
    }

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

        $query = "SELECT
                    a.id, a.id AS id_actividad,
                    a.poa_id,
                    a.tipo_registro,
                    a.nombre, a.nombre AS nombre_actividad,
                    a.descripcion, a.descripcion AS observacion_actividad,
                    a.laboratorio,
                    a.meta,
                    COALESCE(NULLIF(TRIM(a.meta), ''), CAST(ml.porcentaje_esperado AS CHAR)) AS meta_pedi,
                    a.procesos_institucionales_id,
                    a.procesos_institucionales_id AS proceso_id,
                    a.gestion_id,
                    pr.nombre AS proceso_nombre,
                    ge.nombre AS gestion_nombre,
                    a.presupuesto_asignado, a.presupuesto_asignado AS presupuesto_planificado,
                    a.presupuesto_ejecutado,
                    a.avance_actividad, a.avance_actividad AS avance_ejecutado,
                    a.estado AS estado_tinyint,
                    a.observaciones,
                    CASE WHEN a.estado = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                    s.nombre AS nombre_sede,
                    s.nombre AS sede,
                    eje.nombre AS eje,
                    obj.nombre AS objetivo_estrategico,
                    est.nombre AS objetivo_estrategia,
                    COALESCE(NULLIF(TRIM(a.observaciones), ''), '') AS observaciones_avance,
                    ml.porcentaje_esperado AS meta_pedi_pct,
                    ptab.anio_planificacion AS anio_meta_pedi,
                    est.nombre AS estrategia_meta_pedi,
                    proc.nombre_area,
                    COALESCE(cr.ene_pct, 0) AS ene_pct,
                    COALESCE(cr.feb_pct, 0) AS feb_pct,
                    COALESCE(cr.mar_pct, 0) AS mar_pct,
                    COALESCE(cr.abr_pct, 0) AS abr_pct,
                    COALESCE(cr.may_pct, 0) AS may_pct,
                    COALESCE(cr.jun_pct, 0) AS jun_pct,
                    COALESCE(cr.jul_pct, 0) AS jul_pct,
                    COALESCE(cr.ago_pct, 0) AS ago_pct,
                    COALESCE(cr.sep_pct, 0) AS sep_pct,
                    COALESCE(cr.oct_pct, 0) AS oct_pct,
                    COALESCE(cr.nov_pct, 0) AS nov_pct,
                    COALESCE(cr.dic_pct, 0) AS dic_pct
                FROM poa_actividades a
                INNER JOIN poa ptab ON ptab.id = a.poa_id
                LEFT JOIN sedes s ON s.id = ptab.sede_id
                LEFT JOIN estrategias est ON est.id = ptab.estrategia_id
                LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                LEFT JOIN gestion ge ON ge.id = a.gestion_id
                LEFT JOIN (
                    SELECT estrategia_id, MAX(id) AS linea_base_id
                    FROM lineas_base
                    GROUP BY estrategia_id
                ) lb ON lb.estrategia_id = ptab.estrategia_id
                LEFT JOIN (
                    SELECT linea_base_id, anio, MAX(porcentaje_esperado) AS porcentaje_esperado
                    FROM metas_linea_base
                    GROUP BY linea_base_id, anio
                ) ml ON ml.linea_base_id = lb.linea_base_id AND ml.anio = ptab.anio_planificacion
                LEFT JOIN (
                    SELECT pp.poa_id, GROUP_CONCAT(p2.nombre SEPARATOR ', ') AS nombre_area
                    FROM poa_procesos pp
                    INNER JOIN procesos p2 ON p2.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) proc ON proc.poa_id = a.poa_id
                LEFT JOIN (
                    SELECT poa_actividad_id,
                        COALESCE(MAX(CASE WHEN mes = 1 THEN avance END), 0) AS ene_pct,
                        COALESCE(MAX(CASE WHEN mes = 2 THEN avance END), 0) AS feb_pct,
                        COALESCE(MAX(CASE WHEN mes = 3 THEN avance END), 0) AS mar_pct,
                        COALESCE(MAX(CASE WHEN mes = 4 THEN avance END), 0) AS abr_pct,
                        COALESCE(MAX(CASE WHEN mes = 5 THEN avance END), 0) AS may_pct,
                        COALESCE(MAX(CASE WHEN mes = 6 THEN avance END), 0) AS jun_pct,
                        COALESCE(MAX(CASE WHEN mes = 7 THEN avance END), 0) AS jul_pct,
                        COALESCE(MAX(CASE WHEN mes = 8 THEN avance END), 0) AS ago_pct,
                        COALESCE(MAX(CASE WHEN mes = 9 THEN avance END), 0) AS sep_pct,
                        COALESCE(MAX(CASE WHEN mes = 10 THEN avance END), 0) AS oct_pct,
                        COALESCE(MAX(CASE WHEN mes = 11 THEN avance END), 0) AS nov_pct,
                        COALESCE(MAX(CASE WHEN mes = 12 THEN avance END), 0) AS dic_pct
                    FROM cronogramas
                    GROUP BY poa_actividad_id
                ) cr ON cr.poa_actividad_id = a.id
                ORDER BY a.id DESC";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PoaActividadModel::obtenerTodos consulta extendida no disponible: ' . $e->getMessage());
        }

        return $this->obtenerFallbackSimplificado();
    }

    public function obtenerPorPoaId($poaId)
    {
        $db = $this->getConnection();

        $query = "SELECT
                    a.id, a.id AS id_actividad,
                    a.poa_id,
                    a.tipo_registro,
                    a.nombre, a.nombre AS nombre_actividad,
                    a.descripcion, a.descripcion AS observacion_actividad,
                    a.laboratorio,
                    a.meta,
                    COALESCE(NULLIF(TRIM(a.meta), ''), CAST(ml.porcentaje_esperado AS CHAR)) AS meta_pedi,
                    a.procesos_institucionales_id,
                    a.procesos_institucionales_id AS proceso_id,
                    a.gestion_id,
                    pr.nombre AS proceso_nombre,
                    ge.nombre AS gestion_nombre,
                    a.presupuesto_asignado, a.presupuesto_asignado AS presupuesto_planificado,
                    a.presupuesto_ejecutado,
                    a.avance_actividad, a.avance_actividad AS avance_ejecutado,
                    a.estado AS estado_tinyint,
                    a.observaciones,
                    CASE WHEN a.estado = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                    s.nombre AS nombre_sede,
                    s.nombre AS sede,
                    eje.nombre AS eje,
                    obj.nombre AS objetivo_estrategico,
                    est.nombre AS objetivo_estrategia,
                    COALESCE(NULLIF(TRIM(a.observaciones), ''), '') AS observaciones_avance,
                    ml.porcentaje_esperado AS meta_pedi_pct,
                    ptab.anio_planificacion AS anio_meta_pedi,
                    est.nombre AS estrategia_meta_pedi,
                    proc.nombre_area,
                    COALESCE(cr.ene_pct, 0) AS ene_pct,
                    COALESCE(cr.feb_pct, 0) AS feb_pct,
                    COALESCE(cr.mar_pct, 0) AS mar_pct,
                    COALESCE(cr.abr_pct, 0) AS abr_pct,
                    COALESCE(cr.may_pct, 0) AS may_pct,
                    COALESCE(cr.jun_pct, 0) AS jun_pct,
                    COALESCE(cr.jul_pct, 0) AS jul_pct,
                    COALESCE(cr.ago_pct, 0) AS ago_pct,
                    COALESCE(cr.sep_pct, 0) AS sep_pct,
                    COALESCE(cr.oct_pct, 0) AS oct_pct,
                    COALESCE(cr.nov_pct, 0) AS nov_pct,
                    COALESCE(cr.dic_pct, 0) AS dic_pct
                FROM poa_actividades a
                INNER JOIN poa ptab ON ptab.id = a.poa_id
                LEFT JOIN sedes s ON s.id = ptab.sede_id
                LEFT JOIN estrategias est ON est.id = ptab.estrategia_id
                LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                LEFT JOIN gestion ge ON ge.id = a.gestion_id
                LEFT JOIN (
                    SELECT estrategia_id, MAX(id) AS linea_base_id
                    FROM lineas_base
                    GROUP BY estrategia_id
                ) lb ON lb.estrategia_id = ptab.estrategia_id
                LEFT JOIN (
                    SELECT linea_base_id, anio, MAX(porcentaje_esperado) AS porcentaje_esperado
                    FROM metas_linea_base
                    GROUP BY linea_base_id, anio
                ) ml ON ml.linea_base_id = lb.linea_base_id AND ml.anio = ptab.anio_planificacion
                LEFT JOIN (
                    SELECT pp.poa_id, GROUP_CONCAT(p2.nombre SEPARATOR ', ') AS nombre_area
                    FROM poa_procesos pp
                    INNER JOIN procesos p2 ON p2.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) proc ON proc.poa_id = a.poa_id
                LEFT JOIN (
                    SELECT poa_actividad_id,
                        COALESCE(MAX(CASE WHEN mes = 1 THEN avance END), 0) AS ene_pct,
                        COALESCE(MAX(CASE WHEN mes = 2 THEN avance END), 0) AS feb_pct,
                        COALESCE(MAX(CASE WHEN mes = 3 THEN avance END), 0) AS mar_pct,
                        COALESCE(MAX(CASE WHEN mes = 4 THEN avance END), 0) AS abr_pct,
                        COALESCE(MAX(CASE WHEN mes = 5 THEN avance END), 0) AS may_pct,
                        COALESCE(MAX(CASE WHEN mes = 6 THEN avance END), 0) AS jun_pct,
                        COALESCE(MAX(CASE WHEN mes = 7 THEN avance END), 0) AS jul_pct,
                        COALESCE(MAX(CASE WHEN mes = 8 THEN avance END), 0) AS ago_pct,
                        COALESCE(MAX(CASE WHEN mes = 9 THEN avance END), 0) AS sep_pct,
                        COALESCE(MAX(CASE WHEN mes = 10 THEN avance END), 0) AS oct_pct,
                        COALESCE(MAX(CASE WHEN mes = 11 THEN avance END), 0) AS nov_pct,
                        COALESCE(MAX(CASE WHEN mes = 12 THEN avance END), 0) AS dic_pct
                    FROM cronogramas
                    GROUP BY poa_actividad_id
                ) cr ON cr.poa_actividad_id = a.id
                WHERE a.poa_id = ?
                ORDER BY a.id DESC";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute([(int) $poaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PoaActividadModel::obtenerPorPoaId consulta extendida no disponible: ' . $e->getMessage());
        }

        return $this->obtenerFallbackSimplificado((int) $poaId);
    }

    private function obtenerFallbackSimplificado($poaId = null)
    {
        $db = $this->getConnection();

        // Consulta con JOINs reales pero sin las tablas potencialmente ausentes
        // en producción (cronogramas, lineas_base, metas_linea_base, pedi_metas).
        $query = "SELECT
                    a.id,
                    a.id AS id_actividad,
                    a.poa_id,
                    COALESCE(a.tipo_registro, '') AS tipo_registro,
                    COALESCE(a.nombre, '') AS nombre,
                    COALESCE(a.nombre, '') AS nombre_actividad,
                    COALESCE(a.descripcion, '') AS descripcion,
                    COALESCE(a.descripcion, '') AS observacion_actividad,
                    COALESCE(a.laboratorio, '') AS laboratorio,
                    COALESCE(a.meta, '') AS meta,
                    COALESCE(a.meta, '') AS meta_pedi,
                    a.procesos_institucionales_id,
                    a.procesos_institucionales_id AS proceso_id,
                    a.gestion_id,
                    COALESCE(pr.nombre, '') AS proceso_nombre,
                    COALESCE(ge.nombre, '') AS gestion_nombre,
                    COALESCE(a.presupuesto_asignado, 0) AS presupuesto_asignado,
                    COALESCE(a.presupuesto_asignado, 0) AS presupuesto_planificado,
                    COALESCE(a.presupuesto_ejecutado, 0) AS presupuesto_ejecutado,
                    COALESCE(a.avance_actividad, 0) AS avance_actividad,
                    COALESCE(a.avance_actividad, 0) AS avance_ejecutado,
                    COALESCE(a.estado, 0) AS estado_tinyint,
                    COALESCE(a.observaciones, '') AS observaciones,
                    CASE WHEN COALESCE(a.estado, 0) = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                    COALESCE(s.nombre, '') AS nombre_sede,
                    COALESCE(s.nombre, '') AS sede,
                    COALESCE(eje.nombre, '') AS eje,
                    COALESCE(obj.nombre, '') AS objetivo_estrategico,
                    COALESCE(est.nombre, '') AS objetivo_estrategia,
                    COALESCE(a.observaciones, '') AS observaciones_avance,
                    NULL AS meta_pedi_pct,
                    ptab.anio_planificacion AS anio_meta_pedi,
                    COALESCE(est.nombre, '') AS estrategia_meta_pedi,
                    COALESCE(proc.nombre_area, '') AS nombre_area,
                    0 AS ene_pct,
                    0 AS feb_pct,
                    0 AS mar_pct,
                    0 AS abr_pct,
                    0 AS may_pct,
                    0 AS jun_pct,
                    0 AS jul_pct,
                    0 AS ago_pct,
                    0 AS sep_pct,
                    0 AS oct_pct,
                    0 AS nov_pct,
                    0 AS dic_pct
                FROM " . $this->table_name . " a
                INNER JOIN poa ptab ON ptab.id = a.poa_id
                LEFT JOIN sedes s ON s.id = ptab.sede_id
                LEFT JOIN estrategias est ON est.id = ptab.estrategia_id
                LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                LEFT JOIN gestion ge ON ge.id = a.gestion_id
                LEFT JOIN (
                    SELECT pp.poa_id, GROUP_CONCAT(p2.nombre SEPARATOR ', ') AS nombre_area
                    FROM poa_procesos pp
                    INNER JOIN procesos p2 ON p2.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) proc ON proc.poa_id = a.poa_id";

        $params = [];
        if ($poaId !== null) {
            $query .= " WHERE a.poa_id = ?";
            $params[] = (int) $poaId;
        }

        $query .= " ORDER BY a.id DESC";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('PoaActividadModel::obtenerFallbackSimplificado falló: ' . $e->getMessage());
        }

        // Último recurso: solo poa_actividades sin ningún join
        $minQuery = "SELECT
                    a.id,
                    a.id AS id_actividad,
                    a.poa_id,
                    '' AS tipo_registro,
                    COALESCE(a.nombre, '') AS nombre,
                    COALESCE(a.nombre, '') AS nombre_actividad,
                    COALESCE(a.descripcion, '') AS descripcion,
                    COALESCE(a.descripcion, '') AS observacion_actividad,
                    '' AS laboratorio,
                    COALESCE(a.meta, '') AS meta,
                    COALESCE(a.meta, '') AS meta_pedi,
                    COALESCE(a.presupuesto_asignado, 0) AS presupuesto_asignado,
                    COALESCE(a.presupuesto_asignado, 0) AS presupuesto_planificado,
                    0 AS presupuesto_ejecutado,
                    0 AS avance_actividad,
                    0 AS avance_ejecutado,
                    COALESCE(a.estado, 0) AS estado_tinyint,
                    COALESCE(a.observaciones, '') AS observaciones,
                    CASE WHEN COALESCE(a.estado, 0) = 1 THEN 'activo' ELSE 'inactivo' END AS estado,
                    '' AS nombre_sede, '' AS sede, '' AS eje,
                    '' AS objetivo_estrategico, '' AS objetivo_estrategia,
                    COALESCE(a.observaciones, '') AS observaciones_avance,
                    NULL AS meta_pedi_pct, NULL AS anio_meta_pedi, '' AS estrategia_meta_pedi,
                    '' AS nombre_area,
                    0 AS ene_pct, 0 AS feb_pct, 0 AS mar_pct, 0 AS abr_pct,
                    0 AS may_pct, 0 AS jun_pct, 0 AS jul_pct, 0 AS ago_pct,
                    0 AS sep_pct, 0 AS oct_pct, 0 AS nov_pct, 0 AS dic_pct
                FROM " . $this->table_name . " a";

        if ($poaId !== null) {
            $minQuery .= " WHERE a.poa_id = ?";
        }
        $minQuery .= " ORDER BY a.id DESC";

        $stmt = $db->prepare($minQuery);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, p.anio_planificacion, p.presupuesto_total_aprobado, p.sede_id, p.estrategia_id,
                         s.nombre AS sede_nombre,
                         eje.id AS eje_id, eje.nombre AS eje,
                         obj.id AS objetivo_id, obj.nombre AS objetivo_estrategico,
                 est.nombre AS objetivo_estrategia,
                         a.procesos_institucionales_id,
                         a.procesos_institucionales_id AS proceso_id,
                 a.gestion_id,
                 pr.nombre AS proceso_nombre,
                 ge.nombre AS gestion_nombre,
                         GROUP_CONCAT(DISTINCT p2.id ORDER BY p2.id SEPARATOR ',') AS proceso_ids
                FROM " . $this->table_name . " a
                INNER JOIN poa p ON p.id = a.poa_id
                INNER JOIN sedes s ON s.id = p.sede_id
                LEFT JOIN estrategias est ON est.id = p.estrategia_id
                LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
            LEFT JOIN gestion ge ON ge.id = a.gestion_id
                LEFT JOIN poa_procesos pp ON pp.poa_id = p.id
                LEFT JOIN procesos p2 ON p2.id = pp.proceso_id
                WHERE a.id = ?
                GROUP BY a.id";

        $stmt = $db->prepare($query);
        $stmt->execute([(int) $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $columnasActividad = $this->obtenerColumnasActividad($db);
        $usarFechaInicio = isset($columnasActividad['fecha_inicio']);
        $usarFechaFin = isset($columnasActividad['fecha_fin']);

        $columnas = [
            'poa_id',
            'tipo_registro',
            'nombre',
            'descripcion',
            'laboratorio',
            'meta',
            'procesos_institucionales_id',
            'gestion_id',
            'presupuesto_asignado',
            'presupuesto_ejecutado',
            'avance_actividad',
            'estado',
            'observaciones',
        ];

        $valores = [
            (int) $data['poa_id'],
            (string) $data['tipo_registro'],
            (string) $data['nombre'],
            (string) ($data['descripcion'] ?? ''),
            (string) ($data['laboratorio'] ?? ''),
            (string) $data['meta'],
            !empty($data['proceso_id']) ? (int) $data['proceso_id'] : null,
            !empty($data['gestion_id']) ? (int) $data['gestion_id'] : null,
            (float) $data['presupuesto_asignado'],
            (float) $data['presupuesto_ejecutado'],
            (float) ($data['avance_actividad'] ?? 0),
            !empty($data['estado']) ? 1 : 0,
            (string) ($data['observaciones'] ?? ''),
        ];

        if ($usarFechaInicio) {
            $columnas[] = 'fecha_inicio';
            $valores[] = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        }

        if ($usarFechaFin) {
            $columnas[] = 'fecha_fin';
            $valores[] = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
        }

        $placeholders = implode(', ', array_fill(0, count($columnas), '?'));
        $query = "INSERT INTO " . $this->table_name . " (" . implode(', ', $columnas) . ") VALUES (" . $placeholders . ")";
        $stmt = $db->prepare($query);

        try {
            $ok = $stmt->execute($valores);

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

        $columnasActividad = $this->obtenerColumnasActividad($db);
        $usarFechaInicio = isset($columnasActividad['fecha_inicio']);
        $usarFechaFin = isset($columnasActividad['fecha_fin']);

        $set = [
            'poa_id = ?',
            'tipo_registro = ?',
            'nombre = ?',
            'descripcion = ?',
            'laboratorio = ?',
            'meta = ?',
            'procesos_institucionales_id = ?',
            'gestion_id = ?',
            'presupuesto_asignado = ?',
            'presupuesto_ejecutado = ?',
            'avance_actividad = ?',
            'observaciones = ?',
        ];

        $valores = [
            (int) $data['poa_id'],
            (string) $data['tipo_registro'],
            (string) $data['nombre'],
            (string) ($data['descripcion'] ?? ''),
            (string) ($data['laboratorio'] ?? ''),
            (string) $data['meta'],
            !empty($data['proceso_id']) ? (int) $data['proceso_id'] : null,
            !empty($data['gestion_id']) ? (int) $data['gestion_id'] : null,
            (float) $data['presupuesto_asignado'],
            (float) $data['presupuesto_ejecutado'],
            (float) ($data['avance_actividad'] ?? 0),
            (string) ($data['observaciones'] ?? ''),
        ];

        if ($usarFechaInicio) {
            $set[] = 'fecha_inicio = ?';
            $valores[] = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        }

        if ($usarFechaFin) {
            $set[] = 'fecha_fin = ?';
            $valores[] = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
        }

        $set[] = 'estado = ?';
        $valores[] = !empty($data['estado']) ? 1 : 0;
        $valores[] = (int) $id;

        $query = "UPDATE " . $this->table_name . "\n                SET " . implode(",\n                    ", $set) . "\n                WHERE id = ?";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute($valores);
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
            // Reemplaza por completo el cronograma para evitar acumulación histórica
            // que provoca meses marcados incorrectamente al re-editar.
            $deleteStmt = $db->prepare("DELETE FROM cronogramas WHERE poa_actividad_id = ?");
            $deleteStmt->execute([(int) $actividadId]);

            $stmt = $db->prepare("INSERT INTO cronogramas (poa_actividad_id, mes, avance, estado_semaforo, estado, observaciones)
                VALUES (?, ?, ?, ?, 1, '')");

            for ($m = 1; $m <= 12; $m++) {
                $checked = !empty($cronogramaPorMes[$m]);
                $avance = $checked ? 100 : 0;
                $semaforo = $checked ? 'cumple_segun_planificado' : 'no_cumple';

                $stmt->execute([
                    (int) $actividadId,
                    $m,
                    $avance,
                    $semaforo,
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
