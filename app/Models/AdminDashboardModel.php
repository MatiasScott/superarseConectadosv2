<?php
require_once __DIR__ . '/Database.php';

class AdminDashboardModel extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getResumenEjecutivo(): array
    {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN pe.estado = 'ACTIVA' THEN 1 ELSE 0 END) AS total_activas,
                    COUNT(DISTINCT CASE WHEN pe.estado = 'ACTIVA' THEN u.programa END) AS carreras_activas
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id";

        try {
            $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

            $totalGeneral = (int)($row['total'] ?? 0);
            $totalActivas = (int)($row['total_activas'] ?? 0);

            return [
                'total' => $totalActivas,
                'cumplimiento' => $totalGeneral > 0 ? round(($totalActivas / $totalGeneral) * 100, 1) : 0,
                'carreras_activas' => (int)($row['carreras_activas'] ?? 0),
            ];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getResumenEjecutivo -> ' . $e->getMessage());
            return [
                'total' => 0,
                'cumplimiento' => 0,
                'carreras_activas' => 0,
            ];
        }
    }

    public function getRegistrosPorMes(int $months = 12): array
    {
        $sql = "SELECT
                    DATE_FORMAT(pe.fecha_registro, '%Y-%m') AS periodo,
                    COUNT(*) AS total
                FROM practicas_estudiantes pe
                WHERE pe.fecha_registro >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(pe.fecha_registro, '%Y-%m')
                ORDER BY periodo ASC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getRegistrosPorMes -> ' . $e->getMessage());
            return [];
        }
    }

    public function getTopEmpresas(int $limit = 8): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS etiqueta,
                    COUNT(*) AS total
                FROM practicas_estudiantes pe
                INNER JOIN entidades e ON e.id_entidad = pe.entidad_id
                GROUP BY etiqueta
                ORDER BY total DESC
                LIMIT :lim";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getTopEmpresas -> ' . $e->getMessage());
            return [];
        }
    }

    public function getDistribucionPorCarrera(int $limit = 8): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(u.programa), ''), 'Sin carrera') AS etiqueta,
                    COUNT(*) AS total
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                GROUP BY etiqueta
                ORDER BY total DESC
                LIMIT :lim";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getDistribucionPorCarrera -> ' . $e->getMessage());
            return [];
        }
    }

    public function getDistribucionModalidad(): array
    {
        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(pe.modalidad), ''), 'Sin modalidad') AS etiqueta,
                    COUNT(*) AS total
                FROM practicas_estudiantes pe
                GROUP BY etiqueta
                ORDER BY total DESC";

        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getDistribucionModalidad -> ' . $e->getMessage());
            return [];
        }
    }

    public function getPracticasRecientes(int $limit = 10): array
    {
        $sql = "SELECT
                    pe.id_practica,
                    pe.fecha_registro,
                    pe.estado_fase_uno_completado,
                    pe.modalidad,
                    CONCAT(
                        u.primer_nombre, ' ', COALESCE(u.segundo_nombre, ''), ' ',
                        u.primer_apellido, ' ', COALESCE(u.segundo_apellido, '')
                    ) AS estudiante,
                    u.programa,
                    COALESCE(NULLIF(TRIM(e.nombre_empresa), ''), 'Sin empresa') AS empresa
                FROM practicas_estudiantes pe
                INNER JOIN users u ON u.id = pe.user_id
                INNER JOIN entidades e ON e.id_entidad = pe.entidad_id
                ORDER BY pe.fecha_registro DESC
                LIMIT :lim";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getPracticasRecientes -> ' . $e->getMessage());
            return [];
        }
    }

    public function getAlertasOperativas(): array
    {
        $alertas = [
            'pendientes_mayores_15_dias' => 0,
            'completadas_sin_actividades' => 0,
        ];

        try {
            $sqlPendientes = "SELECT COUNT(*) AS total
                              FROM practicas_estudiantes pe
                              WHERE pe.estado_fase_uno_completado = 0
                              AND pe.fecha_registro <= DATE_SUB(NOW(), INTERVAL 15 DAY)";
            $alertas['pendientes_mayores_15_dias'] = (int)($this->db->query($sqlPendientes)->fetchColumn() ?: 0);

            $sqlSinActividad = "SELECT COUNT(*) AS total
                                FROM practicas_estudiantes pe
                                LEFT JOIN actividades_diarias ad ON ad.practica_id = pe.id_practica
                                WHERE pe.estado_fase_uno_completado = 1
                                GROUP BY pe.id_practica
                                HAVING COUNT(ad.id_actividad_diaria) = 0";

            $stmt = $this->db->query($sqlSinActividad);
            $alertas['completadas_sin_actividades'] = $stmt ? count($stmt->fetchAll(PDO::FETCH_ASSOC)) : 0;
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getAlertasOperativas -> ' . $e->getMessage());
        }

        return $alertas;
    }

    public function getResumenInstitucional(): array
    {
        $resumen = [
            'vinculacion_activos' => 0,
            'investigacion_activos' => 0,
            'publicaciones_total' => 0,
            'ponencias_total' => 0,
            'convenios_total' => 0,
            'convenios_activos' => 0,
            'convenios_por_vencer_30_dias' => 0,
        ];

        try {
            $sqlProyectos = "SELECT
                                SUM(CASE WHEN tipo_proyecto = 'VINCULACION' AND estado = 'ACTIVO' THEN 1 ELSE 0 END) AS vinculacion_activos,
                                SUM(CASE WHEN tipo_proyecto = 'INVESTIGACION' AND estado = 'ACTIVO' THEN 1 ELSE 0 END) AS investigacion_activos
                             FROM proyectos_administracion";

            $p = $this->db->query($sqlProyectos)->fetch(PDO::FETCH_ASSOC) ?: [];
            $resumen['vinculacion_activos'] = (int)($p['vinculacion_activos'] ?? 0);
            $resumen['investigacion_activos'] = (int)($p['investigacion_activos'] ?? 0);

            $resumen['publicaciones_total'] = (int)($this->db->query("SELECT COUNT(*) FROM publicaciones")->fetchColumn() ?: 0);
            $resumen['ponencias_total'] = (int)($this->db->query("SELECT COUNT(*) FROM ponencias")->fetchColumn() ?: 0);

            $sqlConvenios = "SELECT
                                COUNT(*) AS convenios_total,
                                SUM(CASE WHEN estado = 'Activo' THEN 1 ELSE 0 END) AS convenios_activos,
                                SUM(CASE WHEN estado = 'Activo' AND fecha_fin IS NOT NULL AND fecha_fin <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS convenios_por_vencer_30_dias
                             FROM convenios";

            $c = $this->db->query($sqlConvenios)->fetch(PDO::FETCH_ASSOC) ?: [];
            $resumen['convenios_total'] = (int)($c['convenios_total'] ?? 0);
            $resumen['convenios_activos'] = (int)($c['convenios_activos'] ?? 0);
            $resumen['convenios_por_vencer_30_dias'] = (int)($c['convenios_por_vencer_30_dias'] ?? 0);
        } catch (PDOException $e) {
            error_log('AdminDashboardModel::getResumenInstitucional -> ' . $e->getMessage());
        }

        return $resumen;
    }
}
