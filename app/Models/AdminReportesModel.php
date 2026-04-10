<?php

require_once __DIR__ . '/Database.php';

class AdminReportesModel extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa,
                    COALESCE(NULLIF(TRIM(e.ruc), ''), 'N/A') AS ruc,
                    pe.id_practica,
                    pe.fecha_registro,
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    pe.estado_fase_uno_completado,
                    u.id AS estudiante_id,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id
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
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa,
                    COALESCE(NULLIF(TRIM(e.ruc), ''), 'N/A') AS ruc,
                    pe.id_practica,
                    pe.fecha_registro,
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS modalidad,
                    pe.estado_fase_uno_completado,
                    u.id AS estudiante_id,
                    COALESCE(NULLIF(TRIM(u.numero_identificacion), ''), 'N/A') AS identificacion,
                    CONCAT(
                        COALESCE(u.primer_nombre, ''), ' ',
                        COALESCE(u.segundo_nombre, ''), ' ',
                        COALESCE(u.primer_apellido, ''), ' ',
                        COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS carrera
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                LEFT JOIN entidades e ON e.id_entidad = pe.entidad_id
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

    public function getDataForModuleExport(string $module): array
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
                    'rows' => $this->getPoaActividadesRows(),
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
                    id_pedi,
                    objetivo_estrategico,
                    avance,
                    objetivo_estrategia,
                    avance_estrategia,
                    estado
                FROM pedi
                ORDER BY id_pedi DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPediRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaRows(): array
    {
        $sql = "SELECT
                    po.id_poa,
                    po.id_pedi,
                    po.nombre_area,
                    po.presupuesto_anual,
                    po.estado_actividad,
                    po.observaciones,
                    po.estado
                FROM poa po
                ORDER BY po.id_poa DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaRows -> ' . $e->getMessage());
            return [];
        }
    }

    private function getPoaActividadesRows(): array
    {
        $sql = "SELECT
                    a.id_actividad,
                    a.id_poa,
                    a.nombre_actividad,
                    a.presupuesto_actividad,
                    a.fecha_inicio,
                    a.fecha_fin,
                    a.avance,
                    a.observacion_actividad,
                    a.estado
                FROM poa_actividades a
                ORDER BY a.id_actividad DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminReportesModel::getPoaActividadesRows -> ' . $e->getMessage());
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
