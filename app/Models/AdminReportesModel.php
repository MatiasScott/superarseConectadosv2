<?php

require_once __DIR__ . '/Database.php';

class AdminReportesModel extends Database
{
    private $db;
    private ?bool $practicaTieneObservacionColumnCache = null;
    private ?string $poaObservacionAvanceColumnCache = null;

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
                    CASE WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2' ELSE 'Fase 1' END AS fase,
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
                    CASE WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2' ELSE 'Fase 1' END AS fase,
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
                    CASE WHEN pe.estado_fase_uno_completado = 1 THEN 'Fase 2' ELSE 'Fase 1' END AS fase,
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
                    'rows' => $this->getPoaRows($areaId),
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
                    p.id_pedi AS id_registro,
                    p.objetivo_estrategico AS nombre,
                    p.objetivo_estrategia AS detalle,
                    p.estado,
                    NULL AS fecha_inicio,
                    NULL AS fecha_fin
                FROM pedi p
                UNION ALL
                SELECT
                    'POA' AS submodulo,
                    po.id_poa AS id_registro,
                    po.nombre_area AS nombre,
                    po.observaciones AS detalle,
                    po.estado,
                    NULL AS fecha_inicio,
                    NULL AS fecha_fin
                FROM poa po
                UNION ALL
                SELECT
                    'POA_ACTIVIDAD' AS submodulo,
                    a.id_actividad AS id_registro,
                    a.nombre_actividad AS nombre,
                    a.observacion_actividad AS detalle,
                    a.estado,
                    a.fecha_inicio,
                    a.fecha_fin
                FROM poa_actividades a
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
                    COALESCE(e.nombre, p.eje) AS eje,
                    p.objetivo_estrategico,
                    p.objetivo_estrategia AS estrategia,
                    MAX(CASE WHEN pm.anio = 0 THEN pm.meta_texto END) AS linea_base,
                    MAX(CASE WHEN pm.anio = 2024 THEN pm.meta_texto END) AS meta_2024,
                    MAX(CASE WHEN pm.anio = 2025 THEN pm.meta_texto END) AS meta_2025,
                    MAX(CASE WHEN pm.anio = 2026 THEN pm.meta_texto END) AS meta_2026,
                    MAX(CASE WHEN pm.anio = 2027 THEN pm.meta_texto END) AS meta_2027,
                    MAX(CASE WHEN pm.anio = 2028 THEN pm.meta_texto END) AS meta_2028,
                    p.estado
                FROM pedi p
                LEFT JOIN eje_estrategico e ON TRIM(p.eje) = e.nombre
                LEFT JOIN pedi_metas pm ON pm.pedi_id = p.id_pedi
                GROUP BY p.id_pedi
                ORDER BY p.id_pedi DESC";

        try {
            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $result = [];
            $i = 1;
            foreach ($rows as $row) {
                $result[] = [
                    'N' => $i,
                    'EJE' => $row['eje'] ?? '',
                    'OBJETIVO ESTRATÉGICO' => $row['objetivo_estrategico'] ?? '',
                    'ESTRATEGIA' => $row['estrategia'] ?? '',
                    'LINEA BASE' => $row['linea_base'] ?? '',
                    '2024' => $row['meta_2024'] ?? '',
                    '2025' => $row['meta_2025'] ?? '',
                    '2026' => $row['meta_2026'] ?? '',
                    '2027' => $row['meta_2027'] ?? '',
                    '2028' => $row['meta_2028'] ?? '',
                    'ESTADO' => $row['estado'] ?? '',
                ];
                $i++;
            }
            return $result;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPediRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaRows($areaId = null): array
    {
        $sql = "SELECT
                    po.id_poa,
                    po.id_pedi,
                    COALESCE(ar.nombre, po.nombre_area) AS nombre_area,
                    po.presupuesto_anual,
                    po.estado_actividad,
                    po.observaciones,
                    po.estado
                FROM poa po
                LEFT JOIN area ar ON po.area_id = ar.id";
        $params = [];
        if ($areaId && $areaId !== '') {
            $sql .= " WHERE po.area_id = :area_id";
            $params[':area_id'] = (int)$areaId;
        }
        $sql .= " ORDER BY po.id_poa DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaActividadesRows($areaId = null): array
    {
        $observacionAvanceExpr = $this->getPoaObservacionAvanceExpression();

        $sql = "SELECT
                    COALESCE(NULLIF(a.eje, ''), ee.nombre, '') AS eje,
                    COALESCE(NULLIF(a.objetivo_estrategico, ''), oe.nombre, '') AS objetivo_estrategico,
                    COALESCE(NULLIF(a.objetivo_estrategia, ''), es.nombre, '') AS objetivo_estrategia,
                    a.nombre_actividad,
                    a.observacion_actividad,
                    a.observaciones,
                    COALESCE(a.avance_ejecutado, 0) AS avance_ejecutado,
                    {$observacionAvanceExpr} AS observaciones_avance,
                    COALESCE(NULLIF(a.meta, ''), pm.meta_texto, '') AS meta_pedi,
                    COALESCE(NULLIF(a.sede, ''), s.nombre, '') AS sede,
                    a.laboratorio,
                    a.ene_pct, a.feb_pct, a.mar_pct, a.abr_pct, a.may_pct, a.jun_pct,
                    a.jul_pct, a.ago_pct, a.sep_pct, a.oct_pct, a.nov_pct, a.dic_pct,
                    a.presupuesto_planificado,
                    a.presupuesto_ejecutado,
                    COALESCE(ar.nombre, '') AS area_responsable,
                    a.estado
                FROM poa_actividades a
                LEFT JOIN eje_estrategico ee ON ee.id = a.eje_id
                LEFT JOIN objetivo_estrategico oe ON oe.id = a.objetivo_id
                LEFT JOIN estrategia es ON es.id = a.estrategia_id
                LEFT JOIN sede s ON s.id = a.sede_id
                LEFT JOIN area ar ON ar.id = a.area_id
                LEFT JOIN (SELECT eje_id, MAX(meta_texto) AS meta_texto FROM pedi_metas WHERE anio = YEAR(CURDATE()) GROUP BY eje_id) pm ON pm.eje_id = a.eje_id";
        $params = [];
        if ($areaId && $areaId !== '') {
            $sql .= " WHERE a.area_id = :area_id";
            $params[':area_id'] = (int)$areaId;
        }
        $sql .= " ORDER BY a.id_actividad DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $mesesLabel = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            $mesesKey = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
            $mesesCampos = ['ene_pct','feb_pct','mar_pct','abr_pct','may_pct','jun_pct','jul_pct','ago_pct','sep_pct','oct_pct','nov_pct','dic_pct'];
            $mesActual = (int) date('n');
            $result = [];
            foreach ($rows as $row) {
                $planificado = (float) ($row['presupuesto_planificado'] ?? 0);
                $ejecutado = (float) ($row['presupuesto_ejecutado'] ?? 0);
                $porcentaje = $planificado > 0
                    ? max(0, min(100, round((($planificado - $ejecutado) / $planificado) * 100, 2)))
                    : 0;

                $seleccionados = 0;
                $cumplidos = 0;
                foreach ($mesesCampos as $i => $campoMes) {
                    $valorMes = $row[$campoMes] ?? null;
                    if ($valorMes === null || $valorMes === '' || (float) $valorMes <= 0) {
                        continue;
                    }
                    $seleccionados++;
                    if ($i < $mesActual) {
                        $cumplidos++;
                    }
                }
                $avancePlanificado = $seleccionados > 0
                    ? round(($cumplidos / $seleccionados) * 100)
                    : 0;

                $entry = [
                    'Eje Estratégico (PEDI)' => $row['eje'] ?? '',
                    'OBJETIVO ESTRATÉGICO (PEDI)' => $row['objetivo_estrategico'] ?? '',
                    'ESTRATEGIA (PEDI)' => $row['objetivo_estrategia'] ?? '',
                    'NOMBRE DEL PROYECTO/ ACTIVIDAD' => $row['nombre_actividad'] ?? '',
                    'DESCRIPCIÓN' => $row['observacion_actividad'] ?? '',
                    'META (PEDI)' => $row['meta_pedi'] ?? '',
                    'SEDE' => $row['sede'] ?? '',
                    'LABORATORIO' => $row['laboratorio'] ?? '',
                ];
                foreach ($mesesLabel as $i => $label) {
                    $key = strtolower($label) . '_pct';
                    $val = $row[$key] ?? null;
                    $pct = ($val !== null && $val !== '' && (float)$val > 0) ? (float)$val : 0;
                    $entry[$mesesKey[$i]] = $pct . '%';
                }
                $entry['AVANCE PLANIFICADO'] = $avancePlanificado . '%';
                $entry['PRESUPUESTO PLANIFICADO'] = '$' . number_format($planificado, 2);
                $entry['PRESUPUESTO EJECUTADO'] = '$' . number_format($ejecutado, 2);
                $entry['Ejecución Presupuestaria (%)'] = $porcentaje . '%';
                $entry['PROCESOS'] = $row['area_responsable'] ?? '';
                $entry['OBSERVACIONES'] = $row['observaciones'] ?? '';
                $entry['ESTADO'] = $row['estado'] ?? '';

                $result[] = $entry;
            }
            return $result;
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaActividadesRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaObservacionAvanceExpression(): string
    {
        if ($this->poaObservacionAvanceColumnCache !== null) {
            return $this->poaObservacionAvanceColumnCache;
        }

        $candidates = ['observaciones_avance', 'obeservaciones_avance'];

        foreach ($candidates as $column) {
            try {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM poa_actividades LIKE :column");
                $stmt->execute([':column' => $column]);
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $this->poaObservacionAvanceColumnCache = 'COALESCE(a.' . $column . ", '')";
                    return $this->poaObservacionAvanceColumnCache;
                }
            } catch (PDOException $e) {
                // Ignore and continue with other candidates.
            }
        }

        $this->poaObservacionAvanceColumnCache = "''";
        return $this->poaObservacionAvanceColumnCache;
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
