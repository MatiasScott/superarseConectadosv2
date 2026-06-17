<?php

require_once __DIR__ . '/Database.php';

class AdminReportesModel extends Database
{
    private $db;
    private ?bool $practicaTieneObservacionColumnCache = null;

    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function practicaTieneObservacionColumn(): bool
    {
        if ($this->practicaTieneObservacionColumnCache !== null) {
            return $this->practicaTieneObservacionColumnCache;
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM practicas_estudiantes LIKE 'observacion'");
            $this->practicaTieneObservacionColumnCache = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->practicaTieneObservacionColumnCache = false;
        }

        return $this->practicaTieneObservacionColumnCache;
    }

    public function getTotalesPracticas(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_practicas,
                    COUNT(DISTINCT pe.user_id) AS total_estudiantes,
                    COUNT(DISTINCT pe.entidad_id) AS total_empresas,
                    COUNT(DISTINCT COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad')) AS total_modalidades,
                    SUM(CASE WHEN pe.estado_fase_uno_completado = 0 THEN 1 ELSE 0 END) AS fase_uno,
                    SUM(CASE WHEN pe.estado_fase_uno_completado = 1 THEN 1 ELSE 0 END) AS fase_dos
                FROM practicas_estudiantes pe";

        try {
            $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
            return [
                'total_practicas' => (int) ($row['total_practicas'] ?? 0),
                'total_estudiantes' => (int) ($row['total_estudiantes'] ?? 0),
                'total_empresas' => (int) ($row['total_empresas'] ?? 0),
                'total_modalidades' => (int) ($row['total_modalidades'] ?? 0),
                'fase_uno' => (int) ($row['fase_uno'] ?? 0),
                'fase_dos' => (int) ($row['fase_dos'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getTotalesPracticas -> ' . $e->getMessage());
            return [
                'total_practicas' => 0,
                'total_estudiantes' => 0,
                'total_empresas' => 0,
                'total_modalidades' => 0,
                'fase_uno' => 0,
                'fase_dos' => 0,
            ];
        }
    }

    public function getEmpresasConEstudiantes(): array
    {
        $selectObservacion = $this->practicaTieneObservacionColumn()
            ? "COALESCE(NULLIF(TRIM(pe.observacion), ''), 'N/A') AS observacion,"
            : "'N/A' AS observacion,";

        $sql = "SELECT
                    pe.id_practica,
                    CASE
                        WHEN UPPER(TRIM(COALESCE(pe.estado, 'ACTIVA'))) IN ('CANCELADA', 'NO FINALIZADO') THEN 'NO FINALIZADO'
                        ELSE UPPER(TRIM(COALESCE(pe.estado, 'ACTIVA')))
                    END AS estado_practica,
                    CASE
                        WHEN pe.estado_fase_uno_completado = 2 THEN 'Práctica Finalizada'
                        WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2'
                        ELSE 'Fase 1'
                    END AS fase,
                    pe.fecha_registro,
                    pe.fecha_fin,
                    {$selectObservacion}
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa,
                    COALESCE(NULLIF(TRIM(e.ruc), ''), 'N/A') AS ruc,
                    COALESCE(NULLIF(TRIM(e.razon_social), ''), 'N/A') AS razon_social,
                    COALESCE(NULLIF(TRIM(e.persona_contacto), ''), 'N/A') AS persona_contacto,
                    COALESCE(NULLIF(TRIM(e.telefono_contacto), ''), 'N/A') AS telefono_contacto,
                    COALESCE(NULLIF(TRIM(e.email_contacto), ''), 'N/A') AS email_contacto,
                    COALESCE(NULLIF(TRIM(e.direccion), ''), 'N/A') AS direccion,
                    u.id AS estudiante_id,
                    COALESCE(NULLIF(TRIM(u.codigo_matricula), ''), 'N/A') AS codigo_matricula,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera,
                    COALESCE(NULLIF(TRIM(doc.nombre_completo), ''), 'N/A') AS docente_asignado,
                    COALESCE(NULLIF(TRIM(tutemp.nombre_completo), ''), 'N/A') AS tutor_empresarial,
                    COALESCE(NULLIF(TRIM(tutemp.cedula), ''), 'N/A') AS tutor_empresarial_cedula,
                    COALESCE(NULLIF(TRIM(tutemp.funcion), ''), 'N/A') AS tutor_empresarial_funcion,
                    COALESCE(NULLIF(TRIM(tutemp.telefono), ''), 'N/A') AS tutor_empresarial_telefono,
                    COALESCE(NULLIF(TRIM(tutemp.email), ''), 'N/A') AS tutor_empresarial_email,
                    COALESCE(NULLIF(TRIM(tutemp.departamento), ''), 'N/A') AS tutor_empresarial_departamento
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id
                LEFT JOIN docentes doc ON doc.id_docente = pe.docente_asignado_id
                LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
                ORDER BY empresa ASC, estudiante ASC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getEmpresasConEstudiantes -> ' . $e->getMessage());
            return [];
        }
    }

    public function countEmpresasConEstudiantes(): int
    {
        $sql = "SELECT COUNT(*)
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id";

        try {
            return (int) ($this->db->query($sql)->fetchColumn() ?: 0);
        } catch (PDOException $e) {
            error_log('AdminReportesModel::countEmpresasConEstudiantes -> ' . $e->getMessage());
            return 0;
        }
    }

    public function getEmpresasConEstudiantesPaginated(int $limit, int $offset): array
    {
        $selectObservacion = $this->practicaTieneObservacionColumn()
            ? "COALESCE(NULLIF(TRIM(pe.observacion), ''), 'N/A') AS observacion,"
            : "'N/A' AS observacion,";

        $sql = "SELECT
                    pe.id_practica,
                    CASE
                        WHEN UPPER(TRIM(COALESCE(pe.estado, 'ACTIVA'))) IN ('CANCELADA', 'NO FINALIZADO') THEN 'NO FINALIZADO'
                        ELSE UPPER(TRIM(COALESCE(pe.estado, 'ACTIVA')))
                    END AS estado_practica,
                    CASE
                        WHEN pe.estado_fase_uno_completado = 2 THEN 'Práctica Finalizada'
                        WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2'
                        ELSE 'Fase 1'
                    END AS fase,
                    pe.fecha_registro,
                    pe.fecha_fin,
                    {$selectObservacion}
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa,
                    COALESCE(NULLIF(TRIM(e.ruc), ''), 'N/A') AS ruc,
                    COALESCE(NULLIF(TRIM(e.razon_social), ''), 'N/A') AS razon_social,
                    COALESCE(NULLIF(TRIM(e.persona_contacto), ''), 'N/A') AS persona_contacto,
                    COALESCE(NULLIF(TRIM(e.telefono_contacto), ''), 'N/A') AS telefono_contacto,
                    COALESCE(NULLIF(TRIM(e.email_contacto), ''), 'N/A') AS email_contacto,
                    COALESCE(NULLIF(TRIM(e.direccion), ''), 'N/A') AS direccion,
                    u.id AS estudiante_id,
                    COALESCE(NULLIF(TRIM(u.codigo_matricula), ''), 'N/A') AS codigo_matricula,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera,
                    COALESCE(NULLIF(TRIM(doc.nombre_completo), ''), 'N/A') AS docente_asignado,
                    COALESCE(NULLIF(TRIM(tutemp.nombre_completo), ''), 'N/A') AS tutor_empresarial,
                    COALESCE(NULLIF(TRIM(tutemp.cedula), ''), 'N/A') AS tutor_empresarial_cedula,
                    COALESCE(NULLIF(TRIM(tutemp.funcion), ''), 'N/A') AS tutor_empresarial_funcion,
                    COALESCE(NULLIF(TRIM(tutemp.telefono), ''), 'N/A') AS tutor_empresarial_telefono,
                    COALESCE(NULLIF(TRIM(tutemp.email), ''), 'N/A') AS tutor_empresarial_email,
                    COALESCE(NULLIF(TRIM(tutemp.departamento), ''), 'N/A') AS tutor_empresarial_departamento
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id
                LEFT JOIN docentes doc ON doc.id_docente = pe.docente_asignado_id
                LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
                ORDER BY empresa ASC, estudiante ASC
                LIMIT :lim OFFSET :off";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getEmpresasConEstudiantesPaginated -> ' . $e->getMessage());
            return [];
        }
    }

    public function getDistribucionModalidadPorCarrera(): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera,
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    COUNT(*) AS total
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                GROUP BY carrera, modalidad
                ORDER BY carrera ASC, total DESC, modalidad ASC";

        try {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getDistribucionModalidadPorCarrera -> ' . $e->getMessage());
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $carrera = (string) ($row['carrera'] ?? 'Sin carrera');
            if (!isset($grouped[$carrera])) {
                $grouped[$carrera] = [];
            }

            $grouped[$carrera][] = [
                'modalidad' => (string) ($row['modalidad'] ?? 'Sin modalidad'),
                'total' => (int) ($row['total'] ?? 0),
            ];
        }

        return $grouped;
    }

    public function getDistribucionModalidadPorCarreraDetallada(): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera,
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                ORDER BY carrera ASC, modalidad ASC, estudiante ASC";

        try {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getDistribucionModalidadPorCarreraDetallada -> ' . $e->getMessage());
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $carrera = (string) ($row['carrera'] ?? 'Sin carrera');
            if (!isset($grouped[$carrera])) {
                $grouped[$carrera] = [];
            }

            $grouped[$carrera][] = [
                'modalidad' => (string) ($row['modalidad'] ?? 'Sin modalidad'),
                'identificacion' => (string) ($row['identificacion'] ?? 'N/A'),
                'estudiante' => trim((string) ($row['estudiante'] ?? '')),
            ];
        }

        return $grouped;
    }

    public function getEstudiantesByFase(string $fase): array
    {
        $fase = strtolower(trim($fase));
        $estado = $fase === 'fase_dos' ? 1 : 0;

        $sql = "SELECT
                    pe.id_practica,
                    pe.fecha_registro,
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    CASE
                        WHEN pe.estado_fase_uno_completado = 2 THEN 'Práctica Finalizada'
                        WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2'
                        ELSE 'Fase 1'
                    END AS fase,
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa,
                    COALESCE(NULLIF(TRIM(e.ruc), ''), 'N/A') AS ruc,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera,
                    COALESCE(NULLIF(TRIM(u.email), ''), 'N/A') AS email
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id
                WHERE pe.estado_fase_uno_completado = :estado
                ORDER BY carrera ASC, estudiante ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':estado', $estado, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getEstudiantesByFase -> ' . $e->getMessage());
            return [];
        }
    }

    public function getDataForModuleExport(string $module, $areaId = null): array
    {
        $module = strtolower(trim($module));

        switch ($module) {
            case 'practicas':
                return [
                    'label' => 'Prácticas',
                    'rows' => $this->getEmpresasConEstudiantes(),
                ];

            case 'vinculacion':
                return [
                    'label' => 'Vinculación',
                    'rows' => $this->getProyectosByTipo('VINCULACION'),
                ];

            case 'vinculacion_proyectos':
                return [
                    'label' => 'Vinculación - Proyectos',
                    'rows' => $this->getProyectosByTipo('VINCULACION'),
                ];

            case 'vinculacion_proyectos_carrera':
                return [
                    'label' => 'Vinculación - Proyectos por Carrera',
                    'rows' => $this->getProyectoCarreraByTipo('VINCULACION'),
                ];

            case 'investigacion':
                return [
                    'label' => 'Investigación',
                    'rows' => $this->getProyectosByTipo('INVESTIGACION'),
                ];

            case 'investigacion_proyectos':
                return [
                    'label' => 'Investigación - Proyectos',
                    'rows' => $this->getProyectosByTipo('INVESTIGACION'),
                ];

            case 'investigacion_publicaciones':
                return [
                    'label' => 'Investigación - Publicaciones',
                    'rows' => $this->getPublicacionesRows(),
                ];

            case 'investigacion_ponencias':
                return [
                    'label' => 'Investigación - Ponencias',
                    'rows' => $this->getPonenciasRows(),
                ];

            case 'investigacion_proyectos_carrera':
                return [
                    'label' => 'Investigación - Proyectos por Carrera',
                    'rows' => $this->getProyectoCarreraByTipo('INVESTIGACION'),
                ];

            case 'planificacion':
                return [
                    'label' => 'Planificación',
                    'rows' => $this->getPlanificacionRows(),
                ];

            case 'planificacion_pedi':
                return [
                    'label' => 'Planificación - Plan Estratégico de Desarrollo Institucional',
                    'rows' => $this->getPediRows(),
                ];

            case 'planificacion_poa':
                return [
                    'label' => 'Planificación - Plan Operativo Anual',
                    'rows' => $this->getPoaRows(),
                ];

            case 'planificacion_poa_actividades':
                return [
                    'label' => 'Planificación - Actividades de Plan Operativo',
                    'rows' => $this->getPoaActividadesRows($areaId),
                ];

            case 'convenios':
                return [
                    'label' => 'Convenios',
                    'rows' => $this->getConveniosRows(),
                ];

            default:
                return [
                    'label' => 'Módulo',
                    'rows' => [],
                ];
        }
    }

    private function getProyectosByTipo(string $tipo): array
    {
        $sql = "SELECT
                    id_proyecto,
                    tipo_proyecto,
                    nombre_proyecto,
                    codigo_proyecto,
                    responsable,
                    correo_responsable,
                    fecha_inicio,
                    fecha_fin,
                    porcentaje_avance,
                    estado,
                    localizacion,
                    periodo_academico,
                    presupuesto,
                    beneficiarios
                FROM proyectos_administracion
                WHERE tipo_proyecto = :tipo
                ORDER BY created_at DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getProyectosByTipo -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPlanificacionRows(): array
    {
        $sql = "SELECT
                    'PEDI' AS submodulo,
                    es.id AS id_registro,
                    CONCAT(COALESCE(ej.nombre, ''), ' / ', COALESCE(obj.nombre, '')) AS nombre,
                    COALESCE(es.nombre, '') AS detalle,
                    CASE WHEN es.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado,
                    NULL AS fecha_inicio,
                    NULL AS fecha_fin
                FROM estrategias es
                LEFT JOIN objetivos_estrategicos obj ON obj.id = es.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos ej ON ej.id = obj.eje_id
                UNION ALL
                SELECT
                    'POA' AS submodulo,
                    po.id AS id_registro,
                    p2.nombre AS nombre,
                    po.observaciones AS detalle,
                    po.estado_aprobacion,
                    NULL AS proceso,
                    NULL AS gestion,
                    NULL AS proceso,
                    NULL AS gestion,
                    NULL AS fecha_inicio,
                    NULL AS fecha_fin
                FROM poa po
                LEFT JOIN (
                    SELECT pp.poa_id, GROUP_CONCAT(p2.nombre SEPARATOR ', ') AS nombre
                    FROM poa_procesos pp
                    INNER JOIN procesos p2 ON p2.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) p2 ON p2.poa_id = po.id
                UNION ALL
                SELECT
                    'POA_ACTIVIDAD' AS submodulo,
                    a.id AS id_registro,
                    a.nombre AS nombre,
                    a.descripcion AS detalle,
                    a.estado,
                    COALESCE(pr.nombre, '') AS proceso,
                    COALESCE(ge.nombre, '') AS gestion,
                    NULL AS fecha_inicio,
                    NULL AS fecha_fin
                FROM poa_actividades a
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                LEFT JOIN gestion ge ON ge.id = a.gestion_id
                ORDER BY submodulo ASC, id_registro DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPlanificacionRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getProyectoCarreraByTipo(string $tipo): array
    {
        $sql = "SELECT
                    pec.id,
                    pa.tipo_proyecto,
                    pa.nombre_proyecto,
                    pec.carrera,
                    pec.nro_estudiantes
                FROM proyecto_estudiantes_carrera pec
                INNER JOIN proyectos_administracion pa ON pa.id_proyecto = pec.id_proyecto
                WHERE pa.tipo_proyecto = :tipo
                ORDER BY pa.nombre_proyecto ASC, pec.carrera ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getProyectoCarreraByTipo -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPublicacionesRows(): array
    {
        $sql = "SELECT
                    id_publicacion,
                    nombre_publicacion,
                    anio,
                    tipo,
                    periodo_academico
                FROM publicaciones
                ORDER BY anio DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPublicacionesRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPonenciasRows(): array
    {
        $sql = "SELECT
                    id_ponencia,
                    nombre_ponencia,
                    autor,
                    nro_acta,
                    fecha_realizacion,
                    nombre_organizador,
                    periodo_academico
                FROM ponencias
                ORDER BY fecha_realizacion DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPonenciasRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPediRows(): array
    {
        $sql = "SELECT
                    COALESCE(ej.nombre, '') AS eje,
                    COALESCE(obj.nombre, '') AS objetivo_estrategico,
                    COALESCE(es.nombre, '') AS estrategia,
                    COALESCE(lb.porcentaje_partida, 0) AS linea_base,
                    MAX(CASE WHEN ml.anio = 2024 THEN ml.porcentaje_esperado END) AS y2024,
                    MAX(CASE WHEN ml.anio = 2025 THEN ml.porcentaje_esperado END) AS y2025,
                    MAX(CASE WHEN ml.anio = 2026 THEN ml.porcentaje_esperado END) AS y2026,
                    MAX(CASE WHEN ml.anio = 2027 THEN ml.porcentaje_esperado END) AS y2027,
                    MAX(CASE WHEN ml.anio = 2028 THEN ml.porcentaje_esperado END) AS y2028,
                    CASE WHEN es.estado = 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado
                FROM estrategias es
                LEFT JOIN objetivos_estrategicos obj ON obj.id = es.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos ej ON ej.id = obj.eje_id
                LEFT JOIN lineas_base lb ON lb.estrategia_id = es.id
                LEFT JOIN metas_linea_base ml ON ml.linea_base_id = lb.id
                GROUP BY es.id, ej.nombre, obj.nombre, es.nombre, lb.porcentaje_partida, es.estado
                ORDER BY ej.nombre ASC, obj.nombre ASC, es.codigo ASC";

        try {
            $stmt = $this->db->query($sql);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $formatNum = static function ($value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                if (!is_numeric($value)) {
                    return (string) $value;
                }
                $num = (float) $value;
                return rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
            };

            $rows = [];
            $index = 1;
            foreach ($raw as $r) {
                $rows[] = [
                    'N' => $index++,
                    'EJE' => (string) ($r['eje'] ?? ''),
                    'OBJETIVO ESTRATÉGICO' => (string) ($r['objetivo_estrategico'] ?? ''),
                    'ESTRATEGIA' => (string) ($r['estrategia'] ?? ''),
                    'LINEA BASE' => $formatNum($r['linea_base'] ?? null),
                    '2024' => $formatNum($r['y2024'] ?? null),
                    '2025' => $formatNum($r['y2025'] ?? null),
                    '2026' => $formatNum($r['y2026'] ?? null),
                    '2027' => $formatNum($r['y2027'] ?? null),
                    '2028' => $formatNum($r['y2028'] ?? null),
                    'ESTADO' => (string) ($r['estado'] ?? ''),
                ];
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPediRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaRows(): array
    {
        $sql = "SELECT
                    po.id,
                    COALESCE(s.nombre, '') AS sede,
                    po.anio_planificacion,
                    COALESCE(po.presupuesto_total_aprobado, 0) AS presupuesto_total_aprobado,
                    COALESCE(po.estado_aprobacion, '') AS estado_aprobacion,
                    COALESCE(po.observaciones, '') AS observaciones,
                    COALESCE(po.estado, 0) AS estado,
                    COALESCE(proc.procesos_nombres, '') AS procesos,
                    COALESCE(procinst.proceso_institucional, '') AS proceso_institucional,
                    COALESCE(gest.gestion, '') AS gestion
                FROM poa po
                LEFT JOIN sedes s ON s.id = po.sede_id
                LEFT JOIN (
                    SELECT pp.poa_id, GROUP_CONCAT(p2.nombre ORDER BY p2.nombre SEPARATOR ', ') AS procesos_nombres
                    FROM poa_procesos pp
                    INNER JOIN procesos p2 ON p2.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) proc ON proc.poa_id = po.id
                LEFT JOIN (
                    SELECT
                        a.poa_id,
                        GROUP_CONCAT(DISTINCT pi.nombre ORDER BY pi.nombre SEPARATOR ', ') AS proceso_institucional
                    FROM poa_actividades a
                    LEFT JOIN procesos_institucionales pi ON pi.id = a.procesos_institucionales_id
                    GROUP BY a.poa_id
                ) procinst ON procinst.poa_id = po.id
                LEFT JOIN (
                    SELECT
                        a.poa_id,
                        GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS gestion
                    FROM poa_actividades a
                    LEFT JOIN gestion g ON g.id = a.gestion_id
                    GROUP BY a.poa_id
                ) gest ON gest.poa_id = po.id
                ORDER BY po.id DESC";

        try {
            $stmt = $this->db->query($sql);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $rows = [];
            $index = 1;
            foreach ($raw as $r) {
                $rows[] = [
                    'N' => $index++,
                    'ID POA' => (string) ($r['id'] ?? ''),
                    'SEDE' => (string) ($r['sede'] ?? ''),
                    'AÑO PLANIFICACIÓN' => (string) ($r['anio_planificacion'] ?? ''),
                    'PRESUPUESTO TOTAL APROBADO' => '$' . number_format((float) ($r['presupuesto_total_aprobado'] ?? 0), 2, '.', ','),
                    'ESTADO APROBACIÓN' => (string) ($r['estado_aprobacion'] ?? ''),
                    'OBSERVACIONES' => (string) ($r['observaciones'] ?? ''),
                    'ESTADO' => ((int) ($r['estado'] ?? 0) === 1) ? 'ACTIVO' : 'INACTIVO',
                    'PROCESOS' => (string) ($r['procesos'] ?? ''),
                    'PROCESO INSTITUCIONAL' => (string) ($r['proceso_institucional'] ?? ''),
                    'GESTIÓN' => (string) ($r['gestion'] ?? ''),
                ];
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaRows consulta completa -> ' . $e->getMessage());
        }

        // Fallback: si faltan tablas/columnas auxiliares, al menos devuelve datos base del POA.
        $fallbackSql = "SELECT
                            po.id,
                            COALESCE(s.nombre, '') AS sede,
                            po.anio_planificacion,
                            COALESCE(po.presupuesto_total_aprobado, 0) AS presupuesto_total_aprobado,
                            COALESCE(po.estado_aprobacion, '') AS estado_aprobacion,
                            COALESCE(po.observaciones, '') AS observaciones,
                            COALESCE(po.estado, 0) AS estado
                        FROM poa po
                        LEFT JOIN sedes s ON s.id = po.sede_id
                        ORDER BY po.id DESC";

        try {
            $stmt = $this->db->query($fallbackSql);
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $rows = [];
            $index = 1;
            foreach ($raw as $r) {
                $rows[] = [
                    'N' => $index++,
                    'ID POA' => (string) ($r['id'] ?? ''),
                    'SEDE' => (string) ($r['sede'] ?? ''),
                    'AÑO PLANIFICACIÓN' => (string) ($r['anio_planificacion'] ?? ''),
                    'PRESUPUESTO TOTAL APROBADO' => '$' . number_format((float) ($r['presupuesto_total_aprobado'] ?? 0), 2, '.', ','),
                    'ESTADO APROBACIÓN' => (string) ($r['estado_aprobacion'] ?? ''),
                    'OBSERVACIONES' => (string) ($r['observaciones'] ?? ''),
                    'ESTADO' => ((int) ($r['estado'] ?? 0) === 1) ? 'ACTIVO' : 'INACTIVO',
                    'PROCESOS' => '',
                    'PROCESO INSTITUCIONAL' => '',
                    'GESTIÓN' => '',
                ];
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaRows fallback -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaActividadesRows($areaId = null): array
    {
        $sql = "SELECT
                    a.id,
                    COALESCE(eje.nombre, '') AS eje,
                    COALESCE(obj.nombre, '') AS objetivo_estrategico,
                    COALESCE(est.nombre, '') AS estrategia,
                    COALESCE(a.nombre, '') AS nombre_actividad,
                    COALESCE(a.descripcion, '') AS descripcion,
                    COALESCE(NULLIF(TRIM(a.meta), ''), CAST(ml.porcentaje_esperado AS CHAR), pm.meta_texto, '') AS meta_pedi,
                    COALESCE(pr.nombre, '') AS proceso,
                    COALESCE(ge.nombre, '') AS gestion,
                    s.nombre AS sede_nombre,
                    COALESCE(a.laboratorio, '') AS laboratorio,
                    COALESCE(a.presupuesto_asignado, 0) AS presupuesto_asignado,
                    COALESCE(a.presupuesto_ejecutado, 0) AS presupuesto_ejecutado,
                    COALESCE(proc.procesos_nombres, '') AS procesos
                    ,COALESCE(a.observaciones, '') AS observaciones
                    ,a.estado
                    ,COALESCE(cr.ene_pct, 0) AS ene_pct
                    ,COALESCE(cr.feb_pct, 0) AS feb_pct
                    ,COALESCE(cr.mar_pct, 0) AS mar_pct
                    ,COALESCE(cr.abr_pct, 0) AS abr_pct
                    ,COALESCE(cr.may_pct, 0) AS may_pct
                    ,COALESCE(cr.jun_pct, 0) AS jun_pct
                    ,COALESCE(cr.jul_pct, 0) AS jul_pct
                    ,COALESCE(cr.ago_pct, 0) AS ago_pct
                    ,COALESCE(cr.sep_pct, 0) AS sep_pct
                    ,COALESCE(cr.oct_pct, 0) AS oct_pct
                    ,COALESCE(cr.nov_pct, 0) AS nov_pct
                    ,COALESCE(cr.dic_pct, 0) AS dic_pct
                FROM poa_actividades a
                INNER JOIN poa p ON p.id = a.poa_id
                LEFT JOIN sedes s ON s.id = p.sede_id
                LEFT JOIN estrategias est ON est.id = p.estrategia_id
                LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                LEFT JOIN gestion ge ON ge.id = a.gestion_id
                LEFT JOIN (
                    SELECT estrategia_id, MAX(id) AS linea_base_id
                    FROM lineas_base
                    GROUP BY estrategia_id
                ) lb ON lb.estrategia_id = p.estrategia_id
                LEFT JOIN (
                    SELECT linea_base_id, anio, MAX(porcentaje_esperado) AS porcentaje_esperado
                    FROM metas_linea_base
                    GROUP BY linea_base_id, anio
                ) ml ON ml.linea_base_id = lb.linea_base_id AND ml.anio = p.anio_planificacion
                LEFT JOIN (
                    SELECT
                        m.eje_id,
                        SUBSTRING_INDEX(GROUP_CONCAT(m.meta_texto ORDER BY m.anio DESC SEPARATOR '||'), '||', 1) AS meta_texto
                    FROM (
                        SELECT pm.eje_id, pm.anio, pm.meta_texto
                        FROM pedi_metas pm
                        WHERE pm.eje_id IS NOT NULL

                        UNION ALL

                        SELECT e.id AS eje_id, pm.anio, pm.meta_texto
                        FROM pedi_metas pm
                        INNER JOIN pedi pdi ON pm.pedi_id = pdi.id_pedi
                        INNER JOIN ejes_estrategicos e ON e.nombre = pdi.eje
                        WHERE pm.eje_id IS NULL
                    ) m
                    GROUP BY m.eje_id
                ) pm ON pm.eje_id = eje.id
                LEFT JOIN (
                    SELECT
                        pp.poa_id,
                        GROUP_CONCAT(pr.nombre ORDER BY pr.nombre SEPARATOR ', ') AS procesos_nombres,
                        GROUP_CONCAT(pr.id ORDER BY pr.id SEPARATOR ',') AS procesos_ids
                    FROM poa_procesos pp
                    INNER JOIN procesos pr ON pr.id = pp.proceso_id
                    GROUP BY pp.poa_id
                ) proc ON proc.poa_id = p.id
                LEFT JOIN (
                    SELECT
                        poa_actividad_id,
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
                ) cr ON cr.poa_actividad_id = a.id";

        $params = [];
        if ($areaId !== null && $areaId !== '' && ctype_digit((string) $areaId)) {
            $sql .= " WHERE FIND_IN_SET(:area_id, proc.procesos_ids) > 0";
            $params[':area_id'] = (string) (int) $areaId;
        }

        $sql .= " ORDER BY a.id DESC";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $formatMoney = static function ($value): string {
                $num = is_numeric($value) ? (float) $value : 0.0;
                return '$' . number_format($num, 2, '.', ',');
            };

            $monthMark = static function ($value): string {
                return (is_numeric($value) && (float) $value > 0) ? 'V' : '—';
            };

            $rows = [];
            foreach ($raw as $r) {
                $plan = is_numeric($r['presupuesto_asignado'] ?? null) ? (float) $r['presupuesto_asignado'] : 0.0;
                $ejec = is_numeric($r['presupuesto_ejecutado'] ?? null) ? (float) $r['presupuesto_ejecutado'] : 0.0;
                $avance = $plan > 0 ? ($ejec / $plan) * 100 : 0.0;

                $meta = (string) ($r['meta_pedi'] ?? '');
                if (is_numeric($meta)) {
                    $meta = rtrim(rtrim(number_format((float) $meta, 2, '.', ''), '0'), '.');
                }

                $rows[] = [
                    'EJE ESTRATÉGICO (PEDI)' => (string) ($r['eje'] ?? ''),
                    'OBJETIVO ESTRATÉGICO (PEDI)' => (string) ($r['objetivo_estrategico'] ?? ''),
                    'ESTRATEGIA (PEDI)' => (string) ($r['estrategia'] ?? ''),
                    'NOMBRE DEL PROYECTO/ ACTIVIDAD' => (string) ($r['nombre_actividad'] ?? ''),
                    'DESCRIPCIÓN' => (string) ($r['descripcion'] ?? ''),
                    'META (PEDI)' => $meta,
                    'PROCESO INSTITUCIONAL' => (string) ($r['proceso'] ?? ''),
                    'GESTIÓN' => (string) ($r['gestion'] ?? ''),
                    'SEDE' => (string) ($r['sede_nombre'] ?? ''),
                    'LABORATORIO' => (string) ($r['laboratorio'] ?? ''),
                    'PRESUPUESTO PLANIFICADO' => $formatMoney($plan),
                    'PRESUPUESTO EJECUTADO' => $formatMoney($ejec),
                    'EJECUCIÓN PRESUPUESTARIA (%)' => number_format($avance, 2, '.', '') . '%',
                    'PROCESOS' => (string) ($r['procesos'] ?? ''),
                    'OBSERVACIONES' => (string) ($r['observaciones'] ?? ''),
                    'ESTADO' => ((int) ($r['estado'] ?? 0) === 1) ? 'ACTIVO' : 'CADUCADO',
                    'ENE' => $monthMark($r['ene_pct'] ?? 0),
                    'FEB' => $monthMark($r['feb_pct'] ?? 0),
                    'MAR' => $monthMark($r['mar_pct'] ?? 0),
                    'ABR' => $monthMark($r['abr_pct'] ?? 0),
                    'MAY' => $monthMark($r['may_pct'] ?? 0),
                    'JUN' => $monthMark($r['jun_pct'] ?? 0),
                    'JUL' => $monthMark($r['jul_pct'] ?? 0),
                    'AGO' => $monthMark($r['ago_pct'] ?? 0),
                    'SEP' => $monthMark($r['sep_pct'] ?? 0),
                    'OCT' => $monthMark($r['oct_pct'] ?? 0),
                    'NOV' => $monthMark($r['nov_pct'] ?? 0),
                    'DIC' => $monthMark($r['dic_pct'] ?? 0),
                ];
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaActividadesRows consulta completa -> ' . $e->getMessage());
        }

        // Fallback: evita reporte vacío si faltan tablas/columnas auxiliares.
        $fallbackSql = "SELECT
                            a.id,
                            COALESCE(eje.nombre, '') AS eje,
                            COALESCE(obj.nombre, '') AS objetivo_estrategico,
                            COALESCE(est.nombre, '') AS estrategia,
                            COALESCE(a.nombre, '') AS nombre_actividad,
                            COALESCE(a.descripcion, '') AS descripcion,
                            COALESCE(a.meta, '') AS meta_pedi,
                            COALESCE(pr.nombre, '') AS proceso,
                            COALESCE(ge.nombre, '') AS gestion,
                            COALESCE(s.nombre, '') AS sede_nombre,
                            COALESCE(a.laboratorio, '') AS laboratorio,
                            COALESCE(a.presupuesto_asignado, 0) AS presupuesto_asignado,
                            COALESCE(a.presupuesto_ejecutado, 0) AS presupuesto_ejecutado,
                            COALESCE(proc.procesos_nombres, '') AS procesos,
                            COALESCE(a.observaciones, '') AS observaciones,
                            COALESCE(a.estado, 0) AS estado,
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
                        FROM poa_actividades a
                        INNER JOIN poa p ON p.id = a.poa_id
                        LEFT JOIN sedes s ON s.id = p.sede_id
                        LEFT JOIN estrategias est ON est.id = p.estrategia_id
                        LEFT JOIN objetivos_estrategicos obj ON obj.id = est.objetivo_estrategico_id
                        LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                        LEFT JOIN procesos_institucionales pr ON pr.id = a.procesos_institucionales_id
                        LEFT JOIN gestion ge ON ge.id = a.gestion_id
                        LEFT JOIN (
                            SELECT
                                pp.poa_id,
                                GROUP_CONCAT(pr2.nombre ORDER BY pr2.nombre SEPARATOR ', ') AS procesos_nombres,
                                GROUP_CONCAT(pr2.id ORDER BY pr2.id SEPARATOR ',') AS procesos_ids
                            FROM poa_procesos pp
                            INNER JOIN procesos pr2 ON pr2.id = pp.proceso_id
                            GROUP BY pp.poa_id
                        ) proc ON proc.poa_id = p.id";

        $fallbackParams = [];
        if ($areaId !== null && $areaId !== '' && ctype_digit((string) $areaId)) {
            $fallbackSql .= " WHERE FIND_IN_SET(:area_id, proc.procesos_ids) > 0";
            $fallbackParams[':area_id'] = (string) (int) $areaId;
        }

        $fallbackSql .= " ORDER BY a.id DESC";

        try {
            $stmt = $this->db->prepare($fallbackSql);
            foreach ($fallbackParams as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $raw = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $formatMoney = static function ($value): string {
                $num = is_numeric($value) ? (float) $value : 0.0;
                return '$' . number_format($num, 2, '.', ',');
            };

            $monthMark = static function ($value): string {
                return (is_numeric($value) && (float) $value > 0) ? 'V' : '—';
            };

            $rows = [];
            foreach ($raw as $r) {
                $plan = is_numeric($r['presupuesto_asignado'] ?? null) ? (float) $r['presupuesto_asignado'] : 0.0;
                $ejec = is_numeric($r['presupuesto_ejecutado'] ?? null) ? (float) $r['presupuesto_ejecutado'] : 0.0;
                $avance = $plan > 0 ? ($ejec / $plan) * 100 : 0.0;

                $meta = (string) ($r['meta_pedi'] ?? '');
                if (is_numeric($meta)) {
                    $meta = rtrim(rtrim(number_format((float) $meta, 2, '.', ''), '0'), '.');
                }

                $rows[] = [
                    'EJE ESTRATÉGICO (PEDI)' => (string) ($r['eje'] ?? ''),
                    'OBJETIVO ESTRATÉGICO (PEDI)' => (string) ($r['objetivo_estrategico'] ?? ''),
                    'ESTRATEGIA (PEDI)' => (string) ($r['estrategia'] ?? ''),
                    'NOMBRE DEL PROYECTO/ ACTIVIDAD' => (string) ($r['nombre_actividad'] ?? ''),
                    'DESCRIPCIÓN' => (string) ($r['descripcion'] ?? ''),
                    'META (PEDI)' => $meta,
                    'PROCESO INSTITUCIONAL' => (string) ($r['proceso'] ?? ''),
                    'GESTIÓN' => (string) ($r['gestion'] ?? ''),
                    'SEDE' => (string) ($r['sede_nombre'] ?? ''),
                    'LABORATORIO' => (string) ($r['laboratorio'] ?? ''),
                    'PRESUPUESTO PLANIFICADO' => $formatMoney($plan),
                    'PRESUPUESTO EJECUTADO' => $formatMoney($ejec),
                    'EJECUCIÓN PRESUPUESTARIA (%)' => number_format($avance, 2, '.', '') . '%',
                    'PROCESOS' => (string) ($r['procesos'] ?? ''),
                    'OBSERVACIONES' => (string) ($r['observaciones'] ?? ''),
                    'ESTADO' => ((int) ($r['estado'] ?? 0) === 1) ? 'ACTIVO' : 'CADUCADO',
                    'ENE' => $monthMark($r['ene_pct'] ?? 0),
                    'FEB' => $monthMark($r['feb_pct'] ?? 0),
                    'MAR' => $monthMark($r['mar_pct'] ?? 0),
                    'ABR' => $monthMark($r['abr_pct'] ?? 0),
                    'MAY' => $monthMark($r['may_pct'] ?? 0),
                    'JUN' => $monthMark($r['jun_pct'] ?? 0),
                    'JUL' => $monthMark($r['jul_pct'] ?? 0),
                    'AGO' => $monthMark($r['ago_pct'] ?? 0),
                    'SEP' => $monthMark($r['sep_pct'] ?? 0),
                    'OCT' => $monthMark($r['oct_pct'] ?? 0),
                    'NOV' => $monthMark($r['nov_pct'] ?? 0),
                    'DIC' => $monthMark($r['dic_pct'] ?? 0),
                ];
            }

            return $rows;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaActividadesRows fallback -> ' . $e->getMessage());
            return [];
        }
    }

    private function getConveniosRows(): array
    {
        $sql = "SELECT
                    id_convenio,
                    nombre_empresa,
                    fecha_inicio,
                    fecha_fin,
                    estado_convenio,
                    tipo_convenio_acuerdo,
                    tipo_institucion,
                    tipo_convenio,
                    carrera,
                    localizacion,
                    ciudad,
                    estado
                FROM convenios
                ORDER BY fecha_inicio DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getConveniosRows -> ' . $e->getMessage());
            return [];
        }
    }
}
