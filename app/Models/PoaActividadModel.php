<?php
require_once __DIR__ . '/Database.php';

class PoaActividadModel extends Database
{
    protected $table_name = "poa_actividades";
    private $avanceObservationColumn = null;

    public function obtenerPresupuestoUsadoPorPoa($idPoa, $excluirActividadId = null)
    {
        $db = $this->getConnection();

        $query = "SELECT COALESCE(SUM(presupuesto_planificado), 0) AS total
                FROM " . $this->table_name . "
                WHERE id_poa = ?";

        $params = [(int) $idPoa];

        if ($excluirActividadId !== null) {
            $query .= " AND id_actividad <> ?";
            $params[] = (int) $excluirActividadId;
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float) ($row['total'] ?? 0);
    }

    public function calcularAvanceEstrategiaPorPedi($idPedi)
    {
        $db = $this->getConnection();

        $query = "SELECT
                    COALESCE(SUM(COALESCE(a.presupuesto_planificado, 0)), 0) AS suma_presupuesto,
                    COALESCE(
                        SUM(COALESCE(a.avance, 0) * COALESCE(a.presupuesto_planificado, 0)),
                        0
                    ) AS suma_ponderada,
                    AVG(COALESCE(a.avance, 0)) AS promedio_simple
                FROM poa p
                LEFT JOIN " . $this->table_name . " a ON a.id_poa = p.id
                WHERE p.id_pedi = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([(int) $idPedi]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0.0;
        }

        $sumaPresupuesto = (float) ($row['suma_presupuesto'] ?? 0);
        $sumaPonderada = (float) ($row['suma_ponderada'] ?? 0);
        $promedioSimple = (float) ($row['promedio_simple'] ?? 0);

        if ($sumaPresupuesto > 0) {
            return round($sumaPonderada / $sumaPresupuesto, 2);
        }

        return round($promedioSimple, 2);
    }

    public function obtenerTodos()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, ar.nombre AS nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id
                LEFT JOIN area ar ON a.area_id = ar.id
                ORDER BY a.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosVinculacion()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, ar.nombre AS nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id
                LEFT JOIN area ar ON a.area_id = ar.id
                where tipo_proyecto = 'VINCULACION'
                ORDER BY a.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosInvestigacion()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, ar.nombre AS nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id
                LEFT JOIN area ar ON a.area_id = ar.id
                where tipo_proyecto = 'INVESTIGACION'
                ORDER BY a.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $query = "SELECT * FROM " . $this->table_name . " WHERE id_actividad = ?";

        $stmt = $db->prepare($query);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($data)
    {
        $db = $this->getConnection();

        $observationColumn = $this->resolveAvanceObservationColumn($db);

        $columns = [
            'eje_id',
            'objetivo_id',
            'estrategia_id',
            'eje',
            'objetivo_estrategico',
            'objetivo_estrategia',
            'nombre_actividad',
            'meta',
            'area_id',
            'sede_id',
            'laboratorio',
            'sede',
            'presupuesto_planificado',
            'presupuesto_ejecutado',
            'fecha_inicio',
            'fecha_fin',
            'avance',
            'observacion_actividad',
            'observaciones',
            'estado',
            'ene_pct',
            'feb_pct',
            'mar_pct',
            'abr_pct',
            'may_pct',
            'jun_pct',
            'jul_pct',
            'ago_pct',
            'sep_pct',
            'oct_pct',
            'nov_pct',
            'dic_pct',
            'avance_ejecutado',
        ];

        $values = [
            $data['eje_id'] ?? null,
            $data['objetivo_id'] ?? null,
            $data['estrategia_id'] ?? null,
            $data['eje'] ?? null,
            $data['objetivo_estrategico'] ?? null,
            $data['objetivo_estrategia'] ?? null,
            $data['nombre_actividad'],
            $data['meta'] ?? null,
            $data['area_id'] ?? null,
            $data['sede_id'] ?? null,
            $data['laboratorio'] ?? null,
            $data['sede'] ?? null,
            $data['presupuesto_planificado'],
            $data['presupuesto_ejecutado'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['avance'],
            $data['observacion_actividad'],
            $data['observaciones'] ?? null,
            $data['estado'],
            $data['ene_pct'] ?? null,
            $data['feb_pct'] ?? null,
            $data['mar_pct'] ?? null,
            $data['abr_pct'] ?? null,
            $data['may_pct'] ?? null,
            $data['jun_pct'] ?? null,
            $data['jul_pct'] ?? null,
            $data['ago_pct'] ?? null,
            $data['sep_pct'] ?? null,
            $data['oct_pct'] ?? null,
            $data['nov_pct'] ?? null,
            $data['dic_pct'] ?? null,
            $data['avance_ejecutado'] ?? 0,
        ];

        if ($observationColumn !== null) {
            $columns[] = $observationColumn;
            $values[] = $data['observaciones_avance'] ?? '';
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $query = "INSERT INTO " . $this->table_name . " (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error crear actividad: " . $e->getMessage());
            return false;
        }
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $observationColumn = $this->resolveAvanceObservationColumn($db);

        $setParts = [
            'eje_id = ?',
            'objetivo_id = ?',
            'estrategia_id = ?',
            'eje = ?',
            'objetivo_estrategico = ?',
            'objetivo_estrategia = ?',
            'nombre_actividad = ?',
            'meta = ?',
            'area_id = ?',
            'sede_id = ?',
            'laboratorio = ?',
            'sede = ?',
            'presupuesto_planificado = ?',
            'presupuesto_ejecutado = ?',
            'fecha_inicio = ?',
            'fecha_fin = ?',
            'avance = ?',
            'avance_ejecutado = ?',
            'observacion_actividad = ?',
            'observaciones = ?',
            'estado = ?',
            'ene_pct = ?',
            'feb_pct = ?',
            'mar_pct = ?',
            'abr_pct = ?',
            'may_pct = ?',
            'jun_pct = ?',
            'jul_pct = ?',
            'ago_pct = ?',
            'sep_pct = ?',
            'oct_pct = ?',
            'nov_pct = ?',
            'dic_pct = ?',
        ];

        $values = [
            $data['eje_id'] ?? null,
            $data['objetivo_id'] ?? null,
            $data['estrategia_id'] ?? null,
            $data['eje'] ?? null,
            $data['objetivo_estrategico'] ?? null,
            $data['objetivo_estrategia'] ?? null,
            $data['nombre_actividad'],
            $data['meta'] ?? null,
            $data['area_id'] ?? null,
            $data['sede_id'] ?? null,
            $data['laboratorio'] ?? null,
            $data['sede'] ?? null,
            $data['presupuesto_planificado'],
            $data['presupuesto_ejecutado'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['avance'],
            $data['avance_ejecutado'] ?? 0,
            $data['observacion_actividad'],
            $data['observaciones'] ?? null,
            $data['estado'],
            $data['ene_pct'] ?? null,
            $data['feb_pct'] ?? null,
            $data['mar_pct'] ?? null,
            $data['abr_pct'] ?? null,
            $data['may_pct'] ?? null,
            $data['jun_pct'] ?? null,
            $data['jul_pct'] ?? null,
            $data['ago_pct'] ?? null,
            $data['sep_pct'] ?? null,
            $data['oct_pct'] ?? null,
            $data['nov_pct'] ?? null,
            $data['dic_pct'] ?? null,
        ];

        if ($observationColumn !== null) {
            $setParts[] = $observationColumn . ' = ?';
            $values[] = $data['observaciones_avance'] ?? '';
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $setParts) . " WHERE id_actividad = ?";
        $stmt = $db->prepare($query);

        try {
            $values[] = $id;
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error actualizar actividad: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();
        $query = "DELETE FROM " . $this->table_name . " WHERE id_actividad = ?";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminar actividad POA: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarEstadosCaducados()
    {
        $db = $this->getConnection();
        $query = "UPDATE " . $this->table_name . "
                  SET estado = 'CADUCADO'
                  WHERE fecha_fin < CURDATE() AND estado NOT IN ('CADUCADO', 'INACTIVO')";
        $stmt = $db->prepare($query);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error actualizar estados caducados: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarAvanceEjecutado($idActividad, $avanceEjecutado, $observacionAvance = '')
    {
        $db = $this->getConnection();
        $idActividad = (int) $idActividad;
        $avanceEjecutado = max(0, min(100, (float) $avanceEjecutado));
        $observacionAvance = trim((string) $observacionAvance);

        if ($idActividad <= 0) {
            return false;
        }

        $observationColumn = $this->resolveAvanceObservationColumn($db);

        $query = "UPDATE " . $this->table_name . " SET avance_ejecutado = :avance_ejecutado";
        if ($observationColumn !== null) {
            $query .= ", " . $observationColumn . " = :observacion_avance";
        }
        $query .= " WHERE id_actividad = :id_actividad";

        $stmt = $db->prepare($query);

        try {
            $params = [
                ':avance_ejecutado' => $avanceEjecutado,
                ':id_actividad' => $idActividad,
            ];

            if ($observationColumn !== null) {
                $params[':observacion_avance'] = $observacionAvance;
            }

            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error actualizar avance ejecutado de actividad: " . $e->getMessage());
            return false;
        }
    }

    private function resolveAvanceObservationColumn(PDO $db)
    {
        if ($this->avanceObservationColumn !== null) {
            return $this->avanceObservationColumn;
        }

        $candidates = ['observaciones_avance', 'obeservaciones_avance'];

        foreach ($candidates as $column) {
            $stmt = $db->prepare("SHOW COLUMNS FROM " . $this->table_name . " LIKE ?");
            $stmt->execute([$column]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->avanceObservationColumn = $column;
                return $this->avanceObservationColumn;
            }
        }

        return null;
    }
}
