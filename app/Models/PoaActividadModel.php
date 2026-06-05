<?php
require_once __DIR__ . '/Database.php';

class PoaActividadModel extends Database
{
    protected $table_name = "poa_actividades";

    public function obtenerPresupuestoUsadoPorPoa($idPoa, $excluirActividadId = null)
    {
        $db = $this->getConnection();

        $query = "SELECT COALESCE(SUM(presupuesto_actividad), 0) AS total
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
                    COALESCE(SUM(COALESCE(a.presupuesto_actividad, 0)), 0) AS suma_presupuesto,
                    COALESCE(
                        SUM(COALESCE(a.avance, 0) * COALESCE(a.presupuesto_actividad, 0)),
                        0
                    ) AS suma_ponderada,
                    AVG(COALESCE(a.avance, 0)) AS promedio_simple
                FROM poa p
                LEFT JOIN " . $this->table_name . " a ON a.id_poa = p.id_poa
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

        $query = "SELECT a.*, p.nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id_poa
                ORDER BY a.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosVinculacion()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, p.nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id_poa
                where tipo_proyecto = 'VINCULACION'
                ORDER BY a.id_actividad DESC";

        $stmt = $db->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosInvestigacion()
    {
        $db = $this->getConnection();

        $query = "SELECT a.*, p.nombre_area
                FROM poa_actividades a
                LEFT JOIN poa p ON a.id_poa = p.id_poa
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

        $query = "INSERT INTO " . $this->table_name . "
                (id_poa, nombre_actividad, presupuesto_actividad, fecha_inicio, fecha_fin, avance, observacion_actividad, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['id_poa'],
                $data['nombre_actividad'],
                $data['presupuesto_actividad'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['avance'],
                $data['observacion_actividad'],
                $data['estado']
            ]);
        } catch (PDOException $e) {
            error_log("Error crear actividad: " . $e->getMessage());
            return false;
        }
    }

    public function actualizar($id, $data)
    {
        $db = $this->getConnection();

        $query = "UPDATE " . $this->table_name . "
                SET id_poa = ?,
                    nombre_actividad = ?,
                    presupuesto_actividad = ?,
                    fecha_inicio = ?,
                    fecha_fin = ?,
                    avance = ?,
                    observacion_actividad = ?,
                    estado = ?
                WHERE id_actividad = ?";

        $stmt = $db->prepare($query);

        try {
            return $stmt->execute([
                $data['id_poa'],
                $data['nombre_actividad'],
                $data['presupuesto_actividad'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['avance'],
                $data['observacion_actividad'],
                $data['estado'],
                $id
            ]);
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
}
