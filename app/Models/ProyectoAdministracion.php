<?php
require_once __DIR__ . '/Database.php';

class ProyectoAdministracion extends Database
{
    protected $table_name = "proyectos_administracion";

    public function obtenerPorTipo($tipo)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name}
                WHERE tipo_proyecto = ?
                ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute([$tipo]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $db = $this->getConnection();

        $sql = "DELETE FROM {$this->table_name} WHERE id_proyecto = ?";
        $stmt = $db->prepare($sql);

        return $stmt->execute([$id]);
    }

    public function crearInvestigacion($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO {$this->table_name} (
                nombre_proyecto,
                codigo_proyecto,
                responsable,
                correo_responsable,
                objetivo,
                fecha_inicio,
                fecha_fin,
                porcentaje_avance,
                localizacion,
                convenio,
                linea_investigacion,
                alcance_proyecto,
                tipo_proyecto,
                presupuesto,
                beneficiarios,
                periodo_academico,
                estado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_proyecto'],
            $data['codigo_proyecto'],
            $data['responsable'],
            $data['correo_responsable'],
            $data['objetivo'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['porcentaje_avance'],
            $data['localizacion'],
            $data['convenio'],
            $data['linea_investigacion'],
            $data['alcance_proyecto'],
            'INVESTIGACION', // tipo_proyecto fijo
            $data['presupuesto'],
            $data['beneficiarios'],
            $data['periodo_academico'],
            $data['estado']
        ]);
    }

    public function crearVinculacion($data)
    {
        $db = $this->getConnection();

        $sql = "INSERT INTO {$this->table_name} (
                nombre_proyecto,
                codigo_proyecto,
                responsable,
                correo_responsable,
                objetivo,
                fecha_inicio,
                fecha_fin,
                porcentaje_avance,
                localizacion,
                convenio,
                linea_investigacion,
                alcance_proyecto,
                tipo_proyecto,
                presupuesto,
                beneficiarios,
                periodo_academico,
                estado
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_proyecto'],
            $data['codigo_proyecto'],
            $data['responsable'],
            $data['correo_responsable'],
            $data['objetivo'],
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['porcentaje_avance'],
            $data['localizacion'],
            $data['convenio'],
            $data['linea_investigacion'],
            $data['alcance_proyecto'],
            'VINCULACION', // tipo_proyecto fijo
            $data['presupuesto'],
            $data['beneficiarios'],
            $data['periodo_academico'],
            $data['estado']
        ]);
    }

    public function actualizarInvestigacion($id, $data)
    {
        $db = $this->getConnection();

        $sql = "UPDATE proyectos_administracion SET
        nombre_proyecto = ?,
        codigo_proyecto = ?,
        responsable = ?,
        correo_responsable = ?,
        objetivo = ?,
        fecha_inicio = ?,
        fecha_fin = ?,
        porcentaje_avance = ?,
        localizacion = ?,
        convenio = ?,
        linea_investigacion = ?,
        alcance_proyecto = ?,
        presupuesto = ?,
        beneficiarios = ?,
        periodo_academico = ?,
        estado = ?
        WHERE id_proyecto = ? AND tipo_proyecto = 'INVESTIGACION'";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_proyecto'],
            $data['codigo_proyecto'],
            $data['responsable'],
            $data['correo_responsable'] ?? null,
            $data['objetivo'] ?? null,
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $data['porcentaje_avance'] ?? 0,
            $data['localizacion'] ?? null,
            $data['convenio'] ?? null,
            $data['linea_investigacion'] ?? null,
            $data['alcance_proyecto'] ?? null,
            $data['presupuesto'] ?? 0,
            $data['beneficiarios'] ?? 0,
            $data['periodo_academico'] ?? null,
            $data['estado'] ?? 'ACTIVO',
            $id
        ]);
    }

    public function actualizarVinculacion($id, $data)
    {
        $db = $this->getConnection();

        $sql = "UPDATE proyectos_administracion SET
        nombre_proyecto = ?,
        codigo_proyecto = ?,
        responsable = ?,
        correo_responsable = ?,
        objetivo = ?,
        fecha_inicio = ?,
        fecha_fin = ?,
        porcentaje_avance = ?,
        localizacion = ?,
        convenio = ?,
        linea_investigacion = ?,
        alcance_proyecto = ?,
        presupuesto = ?,
        beneficiarios = ?,
        periodo_academico = ?,
        estado = ?
        WHERE id_proyecto = ? AND tipo_proyecto = 'VINCULACION'";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            $data['nombre_proyecto'],
            $data['codigo_proyecto'],
            $data['responsable'],
            $data['correo_responsable'] ?? null,
            $data['objetivo'] ?? null,
            $data['fecha_inicio'] ?? null,
            $data['fecha_fin'] ?? null,
            $data['porcentaje_avance'] ?? 0,
            $data['localizacion'] ?? null,
            $data['convenio'] ?? null,
            $data['linea_investigacion'] ?? null,
            $data['alcance_proyecto'] ?? null,
            $data['presupuesto'] ?? 0,
            $data['beneficiarios'] ?? 0,
            $data['periodo_academico'] ?? null,
            $data['estado'] ?? 'ACTIVO',
            $id
        ]);
    }

    public function obtenerPorId($id)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name}
                WHERE id_proyecto = ? AND tipo_proyecto = 'INVESTIGACION'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerVinculacionPorId($id)
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->table_name}
                WHERE id_proyecto = ? AND tipo_proyecto = 'VINCULACION'";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerActivosInvestigacion()
    {
        $db = $this->getConnection();

        $sql = "SELECT id_proyecto, nombre_proyecto 
            FROM proyectos_administracion
            WHERE tipo_proyecto = 'INVESTIGACION'
            AND estado = 'ACTIVO'
            ORDER BY nombre_proyecto ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivosVinculacion()
    {
        $db = $this->getConnection();

        $sql = "SELECT id_proyecto, nombre_proyecto 
            FROM proyectos_administracion
            WHERE tipo_proyecto = 'VINCULACION'
            AND estado = 'ACTIVO'
            ORDER BY nombre_proyecto ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarActivosPorTipo($tipo)
    {
        $db = $this->getConnection();

        $sql = "SELECT COUNT(*) as total FROM {$this->table_name}
                WHERE tipo_proyecto = ? AND estado = 'ACTIVO'";

        $stmt = $db->prepare($sql);
        $stmt->execute([$tipo]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
