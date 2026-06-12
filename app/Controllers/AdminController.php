<?php
// app/Controllers/AdminController.php
require_once __DIR__ . '/../Models/PasantiaModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ProyectoAdministracion.php';
require_once __DIR__ . '/../Models/ProyectoEstudianteCarrera.php';
require_once __DIR__ . '/../Models/Publicacion.php';
require_once __DIR__ . '/../Models/Ponencia.php';
require_once __DIR__ . '/../Models/PediModel.php';
require_once __DIR__ . '/../Models/PoaModel.php';
require_once __DIR__ . '/../Models/PoaActividadModel.php';
require_once __DIR__ . '/../Models/ConfiguracionPlanificacionModel.php';
require_once __DIR__ . '/../Models/ConvenioModel.php';
require_once __DIR__ . '/../Models/AdminDashboardModel.php';
require_once __DIR__ . '/../Models/AuthAccountModel.php';
require_once __DIR__ . '/../Models/PasswordResetModel.php';
require_once __DIR__ . '/../Models/AdminPermissionModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';
require_once __DIR__ . '/../Models/AdminReportesModel.php';
require_once __DIR__ . '/../Models/EntidadModel.php';
require_once __DIR__ . '/../Helpers/AuthSecurity.php';

class AdminController
{
    private $basePath;
    private $pasantiaModel;
    private $userModel;
    private $authAccountModel;
    private $resetModel;
    private $proyectoModel;
    private $carreraModel;
    private $publicacionModel;
    private $ponenciaModel;
    private $pediModel;
    private $poaModel;
    private $actividadModel;
    private $configPlanModel;
    private $convenioModel;
    private $dashboardModel;
    private $permissionModel;
    private $auditLogModel;
    private $reportesModel;
    private $entidadModel;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseConectadosv2-master1/public';
        }

        $this->pasantiaModel = new PasantiaModel();
        $this->userModel = new UserModel();
        $this->proyectoModel = new ProyectoAdministracion();
        $this->carreraModel = new ProyectoEstudianteCarrera();
        $this->publicacionModel = new Publicacion();
        $this->ponenciaModel = new Ponencia();
        $this->pediModel = new PediModel();
        $this->poaModel = new PoaModel();
        $this->actividadModel = new PoaActividadModel();
        $this->configPlanModel = new ConfiguracionPlanificacionModel();
        $this->convenioModel = new ConvenioModel();
        $this->dashboardModel = new AdminDashboardModel();
        $this->authAccountModel = new AuthAccountModel();
        $this->resetModel = new PasswordResetModel();
        $this->permissionModel = new AdminPermissionModel();
        $this->auditLogModel = new AuditLogModel();
        $this->reportesModel = new AdminReportesModel();
        $this->entidadModel = new EntidadModel();

        $this->enforcePasswordChangeRedirect();
        $this->enforceRoutePermission();
    }

    public function loginForm()
    {
        // Si ya está autenticado como admin, redirigir al dashboard
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            if (!empty($_SESSION['must_change_password'])) {
                header("Location: " . $this->basePath . "/admin/password/change");
                exit();
            }

            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        // Si está autenticado como estudiante, no permitir acceso al login de admin
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $basePath = $this->basePath;
        $title = 'Login Administrador - Superarse Conectados';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = 'Panel de Administración';
        $moduleCss = ['login.css'];
        $moduleHeadStyles = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
        $moduleBodyScripts = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'];
        $csrfToken = AuthSecurity::generateCsrfToken('admin_login');
        $content = __DIR__ . '/../Views/admin/login.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function checkLogin()
    {
        if (!AuthSecurity::validateCsrfToken('admin_login', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/admin/login?error=invalid_request");
            exit();
        }

        if (
            !isset($_POST['email'], $_POST['password'])
            || empty($_POST['email'])
            || empty($_POST['password'])
        ) {
            header("Location: " . $this->basePath . "/admin/login?error=campos_vacios");
            exit();
        }

        $email = strtolower(trim($_POST['email']));
        $password = (string) $_POST['password'];
        $account = $this->authAccountModel->findAdminAccountByEmail($email);

        if (!$account || empty($account['password_hash']) || !password_verify($password, $account['password_hash'])) {
            header("Location: " . $this->basePath . "/admin/login?error=invalid_credentials");
            exit();
        }

        $this->clearStudentSession();
        session_regenerate_id(true);

        $_SESSION['is_admin'] = true;
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['nombres_completos'] = $account['display_name'];
        $_SESSION['id_usuario'] = 0;
        $_SESSION['auth_account_id'] = (int) $account['id'];
        $_SESSION['auth_role'] = 'admin';
        $_SESSION['admin_email'] = $account['email'];
        $_SESSION['must_change_password'] = !empty($account['must_change_password']);

        $this->loadAdminPermissionsToSession((int) $account['id']);

        $this->authAccountModel->recordSuccessfulLogin((int) $account['id']);

        if (!empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/admin/password/change");
            exit();
        }

        header("Location: " . $this->basePath . "/admin/dashboard");
        exit();
    }

    public function showChangePasswordForm()
    {
        if (empty($_SESSION['is_admin']) || ($_SESSION['auth_role'] ?? null) !== 'admin') {
            header("Location: " . $this->basePath . "/admin/login?error=not_authenticated");
            exit();
        }

        $basePath = $this->basePath;
        $title = 'Cambiar Contraseña Administrador - Superarse Conectados';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = 'Panel de Administración';
        $moduleCss = ['login.css'];
        $moduleHeadStyles = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
        $moduleBodyScripts = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'];
        $csrfToken = AuthSecurity::generateCsrfToken('admin_password_change');
        $content = __DIR__ . '/../Views/admin/change_password.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function changePassword()
    {
        if (empty($_SESSION['is_admin']) || ($_SESSION['auth_role'] ?? null) !== 'admin' || empty($_SESSION['auth_account_id'])) {
            header("Location: " . $this->basePath . "/admin/login?error=not_authenticated");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_password_change', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/admin/password/change?error=invalid_request");
            exit();
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            header("Location: " . $this->basePath . "/admin/password/change?error=campos_vacios");
            exit();
        }

        $account = $this->authAccountModel->findById((int) $_SESSION['auth_account_id']);
        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
            header("Location: " . $this->basePath . "/admin/password/change?error=invalid_current_password");
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            header("Location: " . $this->basePath . "/admin/password/change?error=password_mismatch");
            exit();
        }

        if ($currentPassword === $newPassword) {
            header("Location: " . $this->basePath . "/admin/password/change?error=same_password");
            exit();
        }

        $policyError = AuthSecurity::validatePasswordPolicy($newPassword);
        if ($policyError !== null) {
            header("Location: " . $this->basePath . "/admin/password/change?error=policy_invalid&message=" . urlencode($policyError));
            exit();
        }

        $updated = $this->authAccountModel->updatePasswordById((int) $account['id'], password_hash($newPassword, PASSWORD_DEFAULT));
        if (!$updated) {
            header("Location: " . $this->basePath . "/admin/password/change?error=password_update_failed");
            exit();
        }

        $_SESSION['must_change_password'] = false;
        session_regenerate_id(true);

        header("Location: " . $this->basePath . "/admin/dashboard");
        exit();
    }

    public function dashboard()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }
        $resumen = $this->dashboardModel->getResumenEjecutivo();
        $porMes = $this->dashboardModel->getRegistrosPorMes(12);
        $topEmpresas = $this->dashboardModel->getTopEmpresas(8);
        $porCarrera = $this->dashboardModel->getDistribucionPorCarrera(8);
        $porModalidad = $this->dashboardModel->getDistribucionModalidad();
        $recientes = $this->dashboardModel->getPracticasRecientes(10);
        $alertas = $this->dashboardModel->getAlertasOperativas();
        $resumenInstitucional = $this->dashboardModel->getResumenInstitucional();

        $this->render('admin/dashboard', [
            'title' => 'Dashboard Gerencial',
            'resumen' => $resumen,
            'porMes' => $porMes,
            'topEmpresas' => $topEmpresas,
            'porCarrera' => $porCarrera,
            'porModalidad' => $porModalidad,
            'recientes' => $recientes,
            'alertas' => $alertas,
            'resumenInstitucional' => $resumenInstitucional,
            'moduleCss' => ['admin-dashboard.css'],
            'moduleJs' => ['admin-dashboard.js'],
            'moduleHeadScripts' => ['https://cdn.jsdelivr.net/npm/chart.js']
        ]);
    }

    public function auditoriaPhasTwo()
    {
        // Verificar que el usuario esté autenticado como administrador
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        // Obtener parámetros de paginación y búsqueda
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $search = $_GET['search'] ?? null;
        $sortBy = $_GET['sortBy'] ?? 'fecha';
        $sortDir = $_GET['sortDir'] ?? 'DESC';

        $offset = ($page - 1) * $limit;

        // Obtener datos combinados (actividades y planes)
        $registros = $this->pasantiaModel->getAuditDataCombined($offset, $limit, $search, $sortBy, $sortDir);
        $totalRegistros = $this->pasantiaModel->countAuditDataCombined($search);
        $totalPages = ceil($totalRegistros / $limit);

        // Obtener registros eliminados con manejo de errores
        try {
            $registrosEliminados = $this->pasantiaModel->getRegistrosEliminados(100, 0);
            if (!is_array($registrosEliminados)) {
                $registrosEliminados = [];
            }

            $totalEliminados = $this->pasantiaModel->countRegistrosEliminados();
            if (!is_numeric($totalEliminados)) {
                $totalEliminados = 0;
            }
        } catch (Exception $e) {
            error_log("Error obteniendo registros eliminados: " . $e->getMessage());
            $registrosEliminados = [];
            $totalEliminados = 0;
        }

        $this->render('admin/auditoria/auditoria_fase_dos', [
            'title' => 'Auditoría',
            'registros' => $registros,
            'totalRegistros' => $totalRegistros,
            'totalPages' => $totalPages,
            'registrosEliminados' => $registrosEliminados,
            'totalEliminados' => $totalEliminados,
            'search' => $search,
            'sortBy' => $sortBy,
            'limit' => $limit,
            'page' => $page,
            'moduleCss' => ['auditoria.css', 'auditoria-custom.css'],
            'moduleJs' => ['auditoria-script.js']
        ]);
    }

    public function auditoriaGeneral()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $search = trim($_GET['search'] ?? '');
        $table = trim($_GET['table'] ?? '');
        $module = trim($_GET['module'] ?? '');
        $action = strtoupper(trim($_GET['action'] ?? ''));
        if (!in_array($action, ['INSERT', 'UPDATE', 'DELETE'], true)) {
            $action = '';
        }

        $moduleTableGroups = $this->getAuditModuleTableGroups();
        $tableList = [];
        if ($module !== '' && isset($moduleTableGroups[$module])) {
            $tableList = $moduleTableGroups[$module]['tables'];
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $logs = $this->auditLogModel->getLogs($limit, $offset, $search, $table, $action, $tableList);
        $totalLogs = $this->auditLogModel->countLogs($search, $table, $action, $tableList);
        $totalPages = max(1, (int) ceil($totalLogs / $limit));
        $availableTables = $this->auditLogModel->getDistinctTables();

        $this->render('admin/auditoria/auditoria_general', [
            'title' => 'Auditoría General',
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'totalPages' => $totalPages,
            'page' => $page,
            'search' => $search,
            'module' => $module,
            'table' => $table,
            'action' => $action,
            'availableTables' => $availableTables,
            'moduleTableGroups' => $moduleTableGroups,
        ]);
    }

    public function exportAuditoriaGeneralCsv()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $search = trim($_GET['search'] ?? '');
        $table = trim($_GET['table'] ?? '');
        $module = trim($_GET['module'] ?? '');
        $action = strtoupper(trim($_GET['action'] ?? ''));
        if (!in_array($action, ['INSERT', 'UPDATE', 'DELETE'], true)) {
            $action = '';
        }

        $moduleTableGroups = $this->getAuditModuleTableGroups();
        $tableList = [];
        if ($module !== '' && isset($moduleTableGroups[$module])) {
            $tableList = $moduleTableGroups[$module]['tables'];
        }

        $rows = $this->auditLogModel->getLogsForExport($search, $table, $action, 50000, $tableList);
        $filename = 'auditoria_general_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'fecha_hora',
            'modulo',
            'tabla',
            'accion',
            'record_pk',
            'actor_tipo',
            'actor_id_admin',
            'actor_id_estudiante',
            'actor_nombre',
            'request_uri',
            'request_method',
            'ip',
            'diff_campos',
            'diff_valores_anteriores',
            'diff_valores_nuevos',
            'diff_resumen',
            'before_data',
            'after_data',
        ]);

        foreach ($rows as $row) {
            $diff = $this->buildAuditDiff((string) ($row['action_type'] ?? ''), (string) ($row['before_data'] ?? ''), (string) ($row['after_data'] ?? ''));
            $moduleName = $this->resolveAuditModuleName((string) ($row['table_name'] ?? ''));

            fputcsv($out, [
                $row['event_time'] ?? '',
                $moduleName,
                $row['table_name'] ?? '',
                $row['action_type'] ?? '',
                $row['record_pk'] ?? '',
                $row['actor_type'] ?? '',
                $row['actor_account_id'] ?? '',
                $row['actor_student_id'] ?? '',
                $row['actor_name'] ?? '',
                $row['request_uri'] ?? '',
                $row['request_method'] ?? '',
                $row['ip_address'] ?? '',
                implode(' | ', $diff['changed_fields']),
                implode(' | ', $diff['old_values']),
                implode(' | ', $diff['new_values']),
                implode(' || ', $diff['summary_lines']),
                $row['before_data'] ?? '',
                $row['after_data'] ?? '',
            ]);
        }

        fclose($out);
        exit();
    }

    public function exportAuditoriaGeneralExcel()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $search = trim($_GET['search'] ?? '');
        $table = trim($_GET['table'] ?? '');
        $module = trim($_GET['module'] ?? '');
        $action = strtoupper(trim($_GET['action'] ?? ''));
        if (!in_array($action, ['INSERT', 'UPDATE', 'DELETE'], true)) {
            $action = '';
        }

        $moduleTableGroups = $this->getAuditModuleTableGroups();
        $tableList = [];
        if ($module !== '' && isset($moduleTableGroups[$module])) {
            $tableList = $moduleTableGroups[$module]['tables'];
        }

        $rows = $this->auditLogModel->getLogsForExport($search, $table, $action, 50000, $tableList);
        $filename = 'auditoria_general_' . date('Ymd_His') . '.xlsx';
        $excelRows = [];

        foreach ($rows as $row) {
            $diff = $this->buildAuditDiff((string) ($row['action_type'] ?? ''), (string) ($row['before_data'] ?? ''), (string) ($row['after_data'] ?? ''));
            $moduleName = $this->resolveAuditModuleName((string) ($row['table_name'] ?? ''));

            $excelRows[] = [
                'fecha_hora' => (string) ($row['event_time'] ?? ''),
                'modulo' => (string) $moduleName,
                'tabla' => (string) ($row['table_name'] ?? ''),
                'accion' => (string) ($row['action_type'] ?? ''),
                'record_pk' => (string) ($row['record_pk'] ?? ''),
                'actor_tipo' => (string) ($row['actor_type'] ?? ''),
                'actor_id_admin' => (string) ($row['actor_account_id'] ?? ''),
                'actor_id_estudiante' => (string) ($row['actor_student_id'] ?? ''),
                'actor_nombre' => (string) ($row['actor_name'] ?? ''),
                'request_uri' => (string) ($row['request_uri'] ?? ''),
                'request_method' => (string) ($row['request_method'] ?? ''),
                'ip' => (string) ($row['ip_address'] ?? ''),
                'diff_campos' => implode(' | ', $diff['changed_fields']),
                'diff_valores_anteriores' => implode(' | ', $diff['old_values']),
                'diff_valores_nuevos' => implode(' | ', $diff['new_values']),
                'diff_resumen' => implode(' || ', $diff['summary_lines']),
                'before_data' => (string) ($row['before_data'] ?? ''),
                'after_data' => (string) ($row['after_data'] ?? ''),
            ];
        }

        $this->streamXlsxDownload(
            $filename,
            $excelRows,
            [
                'fecha_hora' => 'Fecha Hora',
                'modulo' => 'Modulo',
                'tabla' => 'Tabla',
                'accion' => 'Accion',
                'record_pk' => 'Record PK',
                'actor_tipo' => 'Actor Tipo',
                'actor_id_admin' => 'Actor ID Admin',
                'actor_id_estudiante' => 'Actor ID Estudiante',
                'actor_nombre' => 'Actor Nombre',
                'request_uri' => 'Request URI',
                'request_method' => 'Request Method',
                'ip' => 'IP',
                'diff_campos' => 'Diff Campos',
                'diff_valores_anteriores' => 'Diff Valores Anteriores',
                'diff_valores_nuevos' => 'Diff Valores Nuevos',
                'diff_resumen' => 'Diff Resumen',
                'before_data' => 'Before Data',
                'after_data' => 'After Data',
            ],
            'Auditoria General'
        );
    }

    public function reportes()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $areas = [];
        try {
            $db = $this->reportesModel->getConnection();
            $stmt = $db->query("SELECT id, nombre FROM procesos WHERE estado = 1 ORDER BY nombre");
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $areas = [];
        }

        $this->render('admin/reportes/index', [
            'title' => 'Reportes de Prácticas',
            'areas' => $areas,
        ]);
    }

    public function reportesVinculacion()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $sections = [
            [
                'key' => 'vinculacion_proyectos',
                'label' => 'Proyectos',
                'description' => 'Reporte de proyectos de vinculación.',
            ],
            [
                'key' => 'vinculacion_proyectos_carrera',
                'label' => 'Proyectos por Carrera',
                'description' => 'Relación de proyectos de vinculación y carreras.',
            ],
        ];

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Vinculación',
            'moduleTitle' => 'Vinculación',
            'sections' => $sections,
        ]);
    }

    public function reportesInvestigacion()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $sections = [
            [
                'key' => 'investigacion_proyectos',
                'label' => 'Proyectos',
                'description' => 'Reporte de proyectos de investigación.',
            ],
            [
                'key' => 'investigacion_publicaciones',
                'label' => 'Publicaciones',
                'description' => 'Listado de publicaciones registradas.',
            ],
            [
                'key' => 'investigacion_ponencias',
                'label' => 'Ponencias',
                'description' => 'Listado de ponencias registradas.',
            ],
            [
                'key' => 'investigacion_proyectos_carrera',
                'label' => 'Proyectos por Carrera',
                'description' => 'Relación de proyectos de investigación y carreras.',
            ],
        ];

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Investigación',
            'moduleTitle' => 'Investigación',
            'sections' => $sections,
        ]);
    }

    public function reportesPlanificacion()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $sections = [
            [
                'key' => 'planificacion_pedi',
                'label' => 'Plan Estratégico de Desarrollo Institucional',
                'description' => 'Reporte del PEDI.',
            ],
            [
                'key' => 'planificacion_poa',
                'label' => 'Plan Operativo Anual',
                'description' => 'Reporte del POA.',
            ],
            [
                'key' => 'planificacion_poa_actividades',
                'label' => 'Actividades de Plan Operativo',
                'description' => 'Reporte de actividades del POA.',
            ],
        ];

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Planificación',
            'moduleTitle' => 'Planificación',
            'sections' => $sections,
        ]);
    }

    public function planificacionEnMantenimiento()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        http_response_code(503);
        $this->render('admin/mantenimiento', [
            'title' => 'Planificación en mantenimiento',
            'moduleTitle' => 'Planificación',
            'maintenanceMessage' => 'Este módulo está en mantenimiento. Pronto estará disponible nuevamente.',
        ]);
    }

    public function exportReporteModulo()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $module = strtolower(trim($_GET['module'] ?? 'practicas'));
        $allowedModules = [
            'practicas',
            'convenios',
            'vinculacion',
            'vinculacion_proyectos',
            'vinculacion_proyectos_carrera',
            'investigacion',
            'investigacion_proyectos',
            'investigacion_publicaciones',
            'investigacion_ponencias',
            'investigacion_proyectos_carrera',
            'planificacion',
            'planificacion_pedi',
            'planificacion_poa',
            'planificacion_poa_actividades',
        ];
        if (!in_array($module, $allowedModules, true)) {
            $module = 'practicas';
        }

        $format = $this->normalizeReportFormat($_GET['format'] ?? 'excel');
        $areaId = isset($_GET['area_id']) && $_GET['area_id'] !== '' ? $_GET['area_id'] : null;
        $exportData = $this->reportesModel->getDataForModuleExport($module, $areaId);
        $rows = $exportData['rows'] ?? [];
        $label = $exportData['label'] ?? ucfirst($module);
        $reportTitle = 'Reporte Administrativo';
        date_default_timezone_set('America/Guayaquil');
        $downloadedAt = date('d/m/Y H:i:s');
        $userName = $_SESSION['nombres_completos'] ?? 'Administrador';

        if ($format === 'pdf') {
            $html = $this->buildStyledReportHtml($reportTitle, (string) $label, $downloadedAt, $rows, 'pdf', $module, $userName);
            $paper = $module === 'planificacion_poa_actividades' ? 'A3' : 'A4';
            $this->renderPdfDownload('reporte_' . $module . '_' . date('Ymd_His') . '.pdf', $html, $paper, 'landscape');
        }

        $filename = 'reporte_' . $module . '_' . date('Ymd_His') . '.xlsx';
        $this->streamXlsxDownload($filename, $rows, null, (string) $label, $module);
    }

    private function buildStyledReportHtml($reportTitle, $moduleLabel, $downloadedAt, array $rows, $target = 'pdf', $module = '', $userName = '')
    {
        if ($module === 'planificacion_poa_actividades') {
            return $this->buildPoaActividadesStyledReportHtml($reportTitle, $moduleLabel, $downloadedAt, $rows, $target, $userName);
        }

        $target = strtolower((string) $target);
        $headers = !empty($rows) ? array_keys((array) $rows[0]) : [];

        $isExcel = $target === 'excel';
        $pagePadding = $isExcel ? '14px' : '16px';
        $bodySize = $isExcel ? '11px' : '9.5px';
        $headerSize = $isExcel ? '10px' : '8.5px';

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= '*{box-sizing:border-box;}';
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;font-size:' . $bodySize . ';padding:' . $pagePadding . ';margin:0;padding-bottom:28px;}';
        $html .= 'table{width:100%;border-collapse:collapse;border:1px solid #cbd5e1;}';
        $html .= 'th,td{border:1px solid #cbd5e1;padding:6px 7px;vertical-align:middle;}';
        $html .= 'th{background:#4c1d95;color:#ffffff;font-size:' . $headerSize . ';text-transform:uppercase;letter-spacing:0.4px;font-weight:700;}';
        $html .= 'td{color:#1e293b;}';
        $html .= 'tr:nth-child(even) td{background:#f1f5f9;}';
        $html .= '.empty{padding:16px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:6px;font-size:12px;}';
        $html .= '</style></head><body>';

        $html .= '<div style="background:#4c1d95;padding:14px 18px;border-radius:6px 6px 0 0;margin-bottom:10px;">';
        $html .= '<div style="font-size:17px;font-weight:700;color:#ffffff;margin:0;">' . htmlspecialchars((string) $reportTitle, ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '<div style="margin-top:5px;font-size:10px;color:#94a3b8;">';
        $html .= '<span style="margin-right:18px;"><strong style="color:#e2e8f0;">Modulo:</strong> ' . htmlspecialchars((string) $moduleLabel, ENT_QUOTES, 'UTF-8') . '</span>';
        $html .= '<span><strong style="color:#e2e8f0;">Descargado:</strong> ' . htmlspecialchars((string) $downloadedAt, ENT_QUOTES, 'UTF-8') . '</span>';
        $html .= '</div></div>';

        if (empty($rows)) {
            $html .= '<div class="empty" style="margin-top:10px;">No hay datos para exportar.</div>';
        } else {
            $html .= '<table><thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($headers as $header) {
                    $html .= '<td>' . htmlspecialchars((string) ($row[$header] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $logoPath = __DIR__ . '/../../public/Assets/img/logoSuperarse.png';
        $logoB64 = '';
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
        }

        $html .= '<div id="footer" style="position:fixed;bottom:0;left:0;right:0;padding:4px 16px;font-size:7.5px;color:#64748b;text-align:center;background:#ffffff;">';
        $html .= '<div style="display:flex;flex-direction:column;align-items:center;gap:2px;">';
        if ($logoB64 !== '') {
            $html .= '<img src="data:image/png;base64,' . $logoB64 . '" style="height:18px;width:auto;">';
        }
        $html .= '<div>';
        $html .= '<span>&copy; 2025 Instituto Superarse. Todos los derechos reservados.</span>';
        if ($userName !== '') {
            $html .= '<span> &mdash; Descargado por: ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '</div></div></div>';
        $html .= '</body></html>';

        return $html;
    }

    private function buildPoaActividadesStyledReportHtml($reportTitle, $moduleLabel, $downloadedAt, array $rows, $target = 'pdf', $userName = '')
    {
        $target = strtolower((string) $target);
        $isExcel = $target === 'excel';
        $pagePadding = $isExcel ? '12px' : '14px';
        $bodySize = $isExcel ? '10.5px' : '9px';
        $headerSize = $isExcel ? '9px' : '8px';

        $generalHeaders = [
            'EJE ESTRATÉGICO (PEDI)',
            'OBJETIVO ESTRATÉGICO (PEDI)',
            'ESTRATEGIA (PEDI)',
            'NOMBRE DEL PROYECTO/ ACTIVIDAD',
            'DESCRIPCIÓN',
            'META (PEDI)',
            'SEDE',
            'LABORATORIO',
            'PRESUPUESTO PLANIFICADO',
            'PRESUPUESTO EJECUTADO',
            'EJECUCIÓN PRESUPUESTARIA (%)',
            'PROCESOS',
            'OBSERVACIONES',
            'ESTADO',
        ];
        $monthHeaders = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= '*{box-sizing:border-box;}';
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;font-size:' . $bodySize . ';padding:' . $pagePadding . ';margin:0;padding-bottom:28px;}';
        $html .= '.top{background:#4c1d95;padding:14px 18px;border-radius:6px 6px 0 0;margin-bottom:10px;text-align:center;}';
        $html .= '.top h1{margin:0;color:#fff;font-size:38px;font-weight:700;line-height:1.1;}';
        $html .= '.top .sub{margin-top:6px;font-size:14px;color:#d8cfff;}';
        $html .= 'h2.section{margin:10px 0 6px;font-size:26px;color:#334155;letter-spacing:0.3px;}';
        $html .= 'table{width:100%;border-collapse:collapse;border:2px solid #334155;margin-bottom:12px;}';
        $html .= 'th,td{border:1px solid #cbd5e1;padding:4px 6px;vertical-align:middle;}';
        $html .= 'th{background:#4c1d95;color:#fff;font-size:' . $headerSize . ';text-transform:uppercase;letter-spacing:0.4px;font-weight:700;}';
        $html .= 'td{font-size:' . $bodySize . ';}';
        $html .= 'tbody tr:nth-child(even) td{background:#f8fafc;}';
        $html .= '.center{text-align:center;}';
        $html .= '.tick{color:#166534;font-weight:700;font-size:12px;font-family:"DejaVu Sans","Segoe UI Symbol","Arial Unicode MS",sans-serif;}';
        $html .= '.dash{color:#cbd5e1;font-weight:700;}';
        $html .= '.empty{padding:16px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:6px;font-size:12px;}';
        $html .= '.cron-head{background:#4c1d95;color:#fff;font-weight:700;text-align:center;text-transform:uppercase;}';
        $html .= '</style></head><body>';

        $html .= '<div class="top">';
        $html .= '<h1>' . htmlspecialchars((string) $reportTitle, ENT_QUOTES, 'UTF-8') . '</h1>';
        $html .= '<div class="sub"><strong>Módulo:</strong> ' . htmlspecialchars((string) $moduleLabel, ENT_QUOTES, 'UTF-8') . '&nbsp;&nbsp;&nbsp; <strong>Descargado:</strong> ' . htmlspecialchars((string) $downloadedAt, ENT_QUOTES, 'UTF-8') . '</div>';
        $html .= '</div>';

        if (empty($rows)) {
            $html .= '<div class="empty">No hay datos para exportar.</div>';
        } else {
            $html .= '<h2 class="section">TABLA GENERAL</h2>';
            $html .= '<table><thead><tr>';
            foreach ($generalHeaders as $header) {
                $html .= '<th>' . htmlspecialchars($header, ENT_QUOTES, 'UTF-8') . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($generalHeaders as $header) {
                    $html .= '<td>' . htmlspecialchars((string) ($row[$header] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';

            $html .= '<h2 class="section">TABLA CRONOGRAMA</h2>';
            $html .= '<table><thead>';
            $html .= '<tr>';
            $html .= '<th class="cron-head" colspan="12">CRONOGRAMA</th>';
            $html .= '<th class="cron-head">PROCESOS</th>';
            $html .= '</tr><tr>';
            foreach ($monthHeaders as $month) {
                $html .= '<th>' . $month . '</th>';
            }
            $html .= '<th>PROCESOS</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach ($monthHeaders as $month) {
                    $value = (string) ($row[$month] ?? '—');
                    if ($value === 'V') {
                        $html .= '<td class="center"><span class="tick">&#x2714;</span></td>';
                    } else {
                        $html .= '<td class="center"><span class="dash">—</span></td>';
                    }
                }
                $html .= '<td class="center">' . htmlspecialchars((string) ($row['PROCESOS'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $logoPath = __DIR__ . '/../../public/Assets/img/logoSuperarse.png';
        $logoB64 = '';
        if (file_exists($logoPath)) {
            $logoB64 = base64_encode(file_get_contents($logoPath));
        }

        $html .= '<div id="footer" style="position:fixed;bottom:0;left:0;right:0;padding:4px 16px;font-size:7.5px;color:#64748b;text-align:center;background:#ffffff;">';
        $html .= '<div style="display:flex;flex-direction:column;align-items:center;gap:2px;">';
        if ($logoB64 !== '') {
            $html .= '<img src="data:image/png;base64,' . $logoB64 . '" style="height:18px;width:auto;">';
        }
        $html .= '<div>';
        $html .= '<span>&copy; 2025 Instituto Superarse. Todos los derechos reservados.</span>';
        if ($userName !== '') {
            $html .= '<span> &mdash; Descargado por: ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '</div></div></div>';
        $html .= '</body></html>';

        return $html;
    }

    public function exportReporteEmpresasEstudiantesCsv()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $format = $this->normalizeReportFormat($_GET['format'] ?? 'excel');
        $rows = $this->reportesModel->getEmpresasConEstudiantes();

        if ($format === 'pdf') {
            $html = '<h2>Reporte: Empresas con estudiantes</h2>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
            $html .= '<tr><th>Empresa</th><th>RUC</th><th>Estudiante</th><th>Cédula</th><th>Carrera</th><th>Modalidad</th><th>Fase</th></tr>';
            foreach ($rows as $row) {
                $estadoFase = (int) ($row['estado_fase_uno_completado'] ?? 0);
                if ($estadoFase === 2) {
                    $fase = 'Práctica Finalizada';
                } elseif ($estadoFase === 1) {
                    $fase = 'Fase 2';
                } else {
                    $fase = 'Fase 1';
                }
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string) ($row['empresa'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['ruc'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars(trim((string) ($row['estudiante'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['carrera'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['modalidad'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . $fase . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            $this->renderPdfDownload('reporte_empresas_estudiantes_' . date('Ymd_His') . '.pdf', $html);
        }

        $filename = 'reporte_empresas_estudiantes_' . date('Ymd_His') . '.xlsx';
        $excelRows = [];
        foreach ($rows as $row) {
            $excelRows[] = [
                'empresa' => (string) ($row['empresa'] ?? ''),
                'ruc' => (string) ($row['ruc'] ?? ''),
                'id_practica' => (string) ($row['id_practica'] ?? ''),
                'estudiante_id' => (string) ($row['estudiante_id'] ?? ''),
                'identificacion' => (string) ($row['identificacion'] ?? ''),
                'estudiante' => trim((string) ($row['estudiante'] ?? '')),
                'carrera' => (string) ($row['carrera'] ?? ''),
                'modalidad' => (string) ($row['modalidad'] ?? ''),
                'fase' => ((int) ($row['estado_fase_uno_completado'] ?? 0) === 2
                    ? 'Práctica Finalizada'
                    : (((int) ($row['estado_fase_uno_completado'] ?? 0) === 1) ? 'Fase 2' : 'Fase 1')),
                'fecha_registro' => (string) ($row['fecha_registro'] ?? ''),
            ];
        }

        $this->streamXlsxDownload(
            $filename,
            $excelRows,
            [
                'empresa' => 'Empresa',
                'ruc' => 'RUC',
                'id_practica' => 'ID Practica',
                'estudiante_id' => 'ID Estudiante',
                'identificacion' => 'Cedula',
                'estudiante' => 'Estudiante',
                'carrera' => 'Carrera',
                'modalidad' => 'Modalidad',
                'fase' => 'Fase',
                'fecha_registro' => 'Fecha Registro',
            ],
            'Empresas y Estudiantes'
        );
    }

    public function exportReporteModalidadCarreraExcel()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $format = $this->normalizeReportFormat($_GET['format'] ?? 'excel');
        $groups = $this->reportesModel->getDistribucionModalidadPorCarreraDetallada();

        if ($format === 'pdf') {
            $html = '<h2>Reporte: Distribución de modalidad por carrera</h2>';
            if (empty($groups)) {
                $html .= '<p>No hay datos para exportar.</p>';
            } else {
                foreach ($groups as $carrera => $rows) {
                    $html .= '<h3>' . htmlspecialchars((string) $carrera, ENT_QUOTES, 'UTF-8') . '</h3>';
                    $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
                    $html .= '<tr><th>Modalidad</th><th>Cédula</th><th>Estudiante</th></tr>';
                    foreach ($rows as $row) {
                        $html .= '<tr>';
                        $html .= '<td>' . htmlspecialchars((string) ($row['modalidad'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                        $html .= '<td>' . htmlspecialchars((string) ($row['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                        $html .= '<td>' . htmlspecialchars((string) ($row['estudiante'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                        $html .= '</tr>';
                    }
                    $html .= '</table><br>';
                }
            }

            $this->renderPdfDownload('reporte_modalidad_por_carrera_' . date('Ymd_His') . '.pdf', $html);
        }

        $filename = 'reporte_modalidad_por_carrera_' . date('Ymd_His') . '.xlsx';
        $sheets = [];

        if (empty($groups)) {
            $sheets[] = [
                'title' => 'Sin datos',
                'rows' => [],
            ];
        } else {
            foreach ($groups as $carrera => $rows) {
                $normalizedRows = [];
                foreach ($rows as $row) {
                    $normalizedRows[] = [
                        'modalidad' => (string) ($row['modalidad'] ?? ''),
                        'identificacion' => (string) ($row['identificacion'] ?? ''),
                        'estudiante' => (string) ($row['estudiante'] ?? ''),
                    ];
                }

                $sheets[] = [
                    'title' => (string) $carrera,
                    'rows' => $normalizedRows,
                ];
            }
        }

        $this->streamXlsxDownloadBySheets(
            $filename,
            $sheets,
            [
                'modalidad' => 'Modalidad',
                'identificacion' => 'Cedula',
                'estudiante' => 'Estudiante',
            ]
        );
    }

    public function exportReporteEstudiantesFaseCsv()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $format = $this->normalizeReportFormat($_GET['format'] ?? 'excel');
        $fase = strtolower(trim($_GET['fase'] ?? 'fase_uno'));
        if (!in_array($fase, ['fase_uno', 'fase_dos'], true)) {
            $fase = 'fase_uno';
        }

        $rows = $this->reportesModel->getEstudiantesByFase($fase);

        if ($format === 'pdf') {
            $html = '<h2>Reporte de estudiantes por fase</h2>';
            $html .= '<p><strong>Filtro:</strong> ' . htmlspecialchars(strtoupper(str_replace('_', ' ', $fase)), ENT_QUOTES, 'UTF-8') . '</p>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
            $html .= '<tr><th>Cédula</th><th>Estudiante</th><th>Email</th><th>Carrera</th><th>Empresa</th><th>Modalidad</th><th>Fase</th></tr>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars((string) ($row['identificacion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars(trim((string) ($row['estudiante'] ?? '')), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['carrera'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['empresa'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['modalidad'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td>' . htmlspecialchars((string) ($row['fase'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            $this->renderPdfDownload('reporte_estudiantes_' . $fase . '_' . date('Ymd_His') . '.pdf', $html);
        }

        $filename = 'reporte_estudiantes_' . $fase . '_' . date('Ymd_His') . '.xlsx';
        $excelRows = [];
        foreach ($rows as $row) {
            $excelRows[] = [
                'id_practica' => (string) ($row['id_practica'] ?? ''),
                'identificacion' => (string) ($row['identificacion'] ?? ''),
                'estudiante' => trim((string) ($row['estudiante'] ?? '')),
                'email' => (string) ($row['email'] ?? ''),
                'carrera' => (string) ($row['carrera'] ?? ''),
                'empresa' => (string) ($row['empresa'] ?? ''),
                'ruc' => (string) ($row['ruc'] ?? ''),
                'modalidad' => (string) ($row['modalidad'] ?? ''),
                'fase' => (string) ($row['fase'] ?? ''),
                'fecha_registro' => (string) ($row['fecha_registro'] ?? ''),
            ];
        }

        $this->streamXlsxDownload(
            $filename,
            $excelRows,
            [
                'id_practica' => 'ID Practica',
                'identificacion' => 'Cedula',
                'estudiante' => 'Estudiante',
                'email' => 'Email',
                'carrera' => 'Carrera',
                'empresa' => 'Empresa',
                'ruc' => 'RUC',
                'modalidad' => 'Modalidad',
                'fase' => 'Fase',
                'fecha_registro' => 'Fecha Registro',
            ],
            'Estudiantes por Fase'
        );
    }

    private function normalizeReportFormat($format)
    {
        $value = strtolower(trim((string) $format));
        return in_array($value, ['excel', 'pdf'], true) ? $value : 'excel';
    }

    private function renderPdfDownload($filename, $html, $paper = 'A4', $orientation = 'landscape')
    {
        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml('<meta charset="utf-8">' . (string) $html);
        $dompdf->setPaper((string) $paper, (string) $orientation);
        $dompdf->render();

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, ['Attachment' => true]);
        exit();
    }

    private function streamXlsxDownload($filename, array $rows, ?array $columns = null, $sheetTitle = 'Reporte', $module = '')
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->sanitizeExcelSheetName((string) $sheetTitle));

        $this->writeRowsToSheet($sheet, $rows, $columns, $module);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        exit();
    }

    private function streamXlsxDownloadBySheets($filename, array $sheets, ?array $columns = null)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $first = true;

        foreach ($sheets as $item) {
            $title = isset($item['title']) ? (string) $item['title'] : 'Hoja';
            $rows = isset($item['rows']) && is_array($item['rows']) ? $item['rows'] : [];

            if ($first) {
                $sheet = $spreadsheet->getActiveSheet();
                $first = false;
            } else {
                $sheet = $spreadsheet->createSheet();
            }

            $sheet->setTitle($this->sanitizeExcelSheetName($title));
            $this->writeRowsToSheet($sheet, $rows, $columns);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        exit();
    }

    private function writeRowsToSheet($sheet, array $rows, ?array $columns = null, $module = '')
    {
        if (strpos($module, 'planificacion_poa_actividades') !== false) {
            $this->writePoaActividadesRowsToSheet($sheet, $rows);
            return;
        }

        if ($columns !== null) {
            $keys = array_keys($columns);
            $labels = array_values($columns);
        } elseif (!empty($rows)) {
            $keys = array_keys((array) $rows[0]);
            $labels = $keys;
        } else {
            $keys = [];
            $labels = [];
        }

        if (empty($keys)) {
            $sheet->setCellValue('A1', 'No hay datos para exportar.');
            return;
        }

        $isPoaActividades = strpos($module, 'planificacion_poa_actividades') !== false;
        $headerRow = 1;

        if ($isPoaActividades) {
            $monthKeys = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
            $monthStart = null;
            $monthEnd = null;
            foreach ($keys as $i => $k) {
                if (in_array($k, $monthKeys)) {
                    if ($monthStart === null) $monthStart = $i;
                    $monthEnd = $i;
                }
            }
            if ($monthStart !== null) {
                $startCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($monthStart + 1);
                $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($monthEnd + 1);
                $mergeRange = $startCol . '1:' . $endCol . '1';
                $sheet->mergeCells($mergeRange);
                $cell = $sheet->getCell($startCol . '1');
                $cell->setValueExplicit('CRONOGRAMA', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->getStyle($mergeRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C1D95']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
                $headerRow = 2;
            }
        }

        foreach ($labels as $index => $label) {
            $column = $index + 1;
            $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $headerRow;
            $cell = $sheet->getCell($cellRef);
            $cell->setValueExplicit((string) $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $lastHeaderColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($keys));
        $headerStyle = $sheet->getStyle('A' . $headerRow . ':' . $lastHeaderColumn . $headerRow);
        $headerStyle->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FF4C1D95');

        $dataStartRow = $isPoaActividades ? 3 : 2;
        $rowNumber = $dataStartRow;
        foreach ($rows as $row) {
            foreach ($keys as $index => $key) {
                $value = $row[$key] ?? '';
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }

                $column = $index + 1;
                $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . (string) $rowNumber;
                $cell = $sheet->getCell($cellRef);
                $cell->setValueExplicit((string) $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowNumber++;
        }

        for ($column = 1; $column <= count($keys); $column++) {
            $columnRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($columnRef)->setAutoSize(true);
        }
    }

    private function writePoaActividadesRowsToSheet($sheet, array $rows)
    {
        if (empty($rows)) {
            $sheet->setCellValue('A1', 'No hay datos para exportar.');
            return;
        }

        $generalHeaders = [
            'EJE ESTRATÉGICO (PEDI)',
            'OBJETIVO ESTRATÉGICO (PEDI)',
            'ESTRATEGIA (PEDI)',
            'NOMBRE DEL PROYECTO/ ACTIVIDAD',
            'DESCRIPCIÓN',
            'META (PEDI)',
            'SEDE',
            'LABORATORIO',
            'PRESUPUESTO PLANIFICADO',
            'PRESUPUESTO EJECUTADO',
            'EJECUCIÓN PRESUPUESTARIA (%)',
            'PROCESOS',
            'OBSERVACIONES',
            'ESTADO',
        ];
        $monthHeaders = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];

        $generalCols = count($generalHeaders);
        $cronCols = count($monthHeaders) + 1;

        $sheet->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($generalCols) . '1');
        $sheet->setCellValue('A1', 'TABLA GENERAL');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF334155');

        $headerRowGeneral = 2;
        foreach ($generalHeaders as $index => $label) {
            $column = $index + 1;
            $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $headerRowGeneral;
            $sheet->setCellValueExplicit($cellRef, $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }

        $lastGeneralCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($generalCols);
        $sheet->getStyle('A2:' . $lastGeneralCol . '2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C1D95']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        $rowNumber = 3;
        foreach ($rows as $row) {
            foreach ($generalHeaders as $index => $key) {
                $column = $index + 1;
                $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $rowNumber;
                $sheet->setCellValueExplicit($cellRef, (string) ($row[$key] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $rowNumber++;
        }

        $cronTitleRow = $rowNumber + 1;
        $sheet->mergeCells('A' . $cronTitleRow . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cronCols) . $cronTitleRow);
        $sheet->setCellValue('A' . $cronTitleRow, 'TABLA CRONOGRAMA');
        $sheet->getStyle('A' . $cronTitleRow)->getFont()->setBold(true)->setSize(12)->getColor()->setARGB('FF334155');

        $cronGroupRow = $cronTitleRow + 1;
        $sheet->mergeCells('A' . $cronGroupRow . ':L' . $cronGroupRow);
        $sheet->setCellValue('A' . $cronGroupRow, 'CRONOGRAMA');
        $sheet->setCellValue('M' . $cronGroupRow, 'PROCESOS');

        $sheet->getStyle('A' . $cronGroupRow . ':M' . $cronGroupRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C1D95']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        $cronHeaderRow = $cronGroupRow + 1;
        foreach ($monthHeaders as $index => $label) {
            $column = $index + 1;
            $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $cronHeaderRow;
            $sheet->setCellValueExplicit($cellRef, $label, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $sheet->setCellValueExplicit('M' . $cronHeaderRow, 'PROCESOS', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->getStyle('A' . $cronHeaderRow . ':M' . $cronHeaderRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4C1D95']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        $cronDataRow = $cronHeaderRow + 1;
        foreach ($rows as $row) {
            foreach ($monthHeaders as $index => $key) {
                $column = $index + 1;
                $cellRef = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column) . $cronDataRow;
                $rawValue = (string) ($row[$key] ?? '—');
                if ($rawValue === 'V') {
                    $sheet->setCellValueExplicit($cellRef, '✔', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getStyle($cellRef)->applyFromArray([
                        'font' => ['bold' => true, 'name' => 'Segoe UI Symbol', 'color' => ['argb' => 'FF15803D']],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                } else {
                    $sheet->setCellValueExplicit($cellRef, '—', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->getStyle($cellRef)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFCBD5E1']],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }
            }
            $sheet->setCellValueExplicit('M' . $cronDataRow, (string) ($row['PROCESOS'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $cronDataRow++;
        }

        $lastRow = $cronDataRow - 1;
        $sheet->getStyle('A2:' . $lastGeneralCol . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);

        foreach (range('A', $lastGeneralCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function sanitizeExcelSheetName($name)
    {
        $clean = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', (string) $name);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        if ($clean === '') {
            $clean = 'Hoja';
        }

        return mb_substr($clean, 0, 31, 'UTF-8');
    }

    private function getAuditModuleTableGroups()
    {
        return [
            'practicas' => [
                'label' => 'Prácticas (Admin + Estudiantes)',
                'tables' => ['practicas_estudiantes', 'entidades', 'tutores_empresariales', 'programa_trabajo', 'actividades_diarias'],
            ],
            'investigacion_vinculacion' => [
                'label' => 'Investigación y Vinculación',
                'tables' => ['proyectos_administracion', 'proyecto_estudiantes_carrera', 'publicaciones', 'ponencias'],
            ],
            'planificacion' => [
                'label' => 'Planificación Estratégica',
                'tables' => ['pedi', 'poa', 'poa_actividades'],
            ],
            'convenios' => [
                'label' => 'Convenios',
                'tables' => ['convenios'],
            ],
            'pagos' => [
                'label' => 'Pagos',
                'tables' => ['payments'],
            ],
            'cuentas_permisos' => [
                'label' => 'Cuentas y Permisos',
                'tables' => ['access_accounts', 'access_account_permissions', 'password_reset_requests'],
            ],
        ];
    }

    private function resolveAuditModuleName($tableName)
    {
        foreach ($this->getAuditModuleTableGroups() as $group) {
            if (in_array($tableName, $group['tables'], true)) {
                return $group['label'];
            }
        }

        return 'Otros';
    }

    private function buildAuditDiff($actionType, $beforeJson, $afterJson)
    {
        $before = $this->decodeAuditJson($beforeJson);
        $after = $this->decodeAuditJson($afterJson);

        $changedFields = [];
        $oldValues = [];
        $newValues = [];
        $summaryLines = [];

        if ($actionType === 'INSERT') {
            foreach ($after as $field => $newValue) {
                $changedFields[] = (string) $field;
                $oldValues[] = '';
                $newValues[] = $this->normalizeAuditValue($newValue);
                $summaryLines[] = $field . ': ' . $this->normalizeAuditValue($newValue);
            }

            return [
                'changed_fields' => $changedFields,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'summary_lines' => $summaryLines,
            ];
        }

        if ($actionType === 'DELETE') {
            foreach ($before as $field => $oldValue) {
                $changedFields[] = (string) $field;
                $oldValues[] = $this->normalizeAuditValue($oldValue);
                $newValues[] = '';
                $summaryLines[] = $field . ': ' . $this->normalizeAuditValue($oldValue);
            }

            return [
                'changed_fields' => $changedFields,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'summary_lines' => $summaryLines,
            ];
        }

        $allFields = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allFields as $field) {
            $oldValue = array_key_exists($field, $before) ? $before[$field] : null;
            $newValue = array_key_exists($field, $after) ? $after[$field] : null;

            if ($oldValue !== $newValue) {
                $changedFields[] = (string) $field;
                $oldValues[] = $this->normalizeAuditValue($oldValue);
                $newValues[] = $this->normalizeAuditValue($newValue);
                $summaryLines[] = $field . ': ' . $this->normalizeAuditValue($oldValue) . ' -> ' . $this->normalizeAuditValue($newValue);
            }
        }

        return [
            'changed_fields' => $changedFields,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'summary_lines' => $summaryLines,
        ];
    }

    private function decodeAuditJson($jsonText)
    {
        if (!is_string($jsonText) || trim($jsonText) === '') {
            return [];
        }

        $decoded = json_decode($jsonText, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeAuditValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    public function editarRegistro()
    {
        // Verificar autenticación
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        // Obtener ID del registro
        $registroId = $_GET['id'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit();
        }

        // Aquí iría la lógica de edición
        // Por ahora retornamos éxito para permitir que funcione
        echo json_encode([
            'success' => true,
            'message' => 'Registro #' . intval($registroId) . ' listo para editar',
            'id' => intval($registroId)
        ]);
        exit();
    }

    public function editarRegistroView()
    {
        // Verificar autenticación
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        // Obtener ID del registro
        $registroId = $_GET['id'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        // Obtener el registro de auditoría
        $registro = $this->pasantiaModel->getRegistroAuditoriaById(intval($registroId));

        if (!$registro) {
            $_SESSION['error'] = 'Registro no encontrado';
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        $this->render('admin/auditoria/editar_auditoria', [
            'title' => 'Editar Registro de Auditoría',
            'tipoRegistro' => $registro['tipo_registro'],
            'datos' => $registro,
            'moduleCss' => ['forms.css']
        ]);
    }

    public function eliminarRegistro()
    {
        // Verificar autenticación
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        // Obtener ID del registro
        $registroId = $_GET['id'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit();
        }

        try {
            $registroId = intval($registroId);

            // Obtener información del registro antes de eliminarlo
            $registro = $this->pasantiaModel->getRegistroAuditoriaById($registroId);

            if (!$registro) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
                exit();
            }

            // Determinar tipo de registro
            $tipo = $registro['tipo_registro'];

            // Preparar datos para auditoría
            $datosAuditoria = [
                'estudiante' => $registro['estudiante_nombre'] ?? 'N/A',
                'estudiante_id' => $registro['estudiante_id'] ?? null,
                'descripcion' => $registro['descripcion'] ?? $registro['actividad'] ?? '',
                'empresa' => $registro['empresa_nombre'] ?? '',
                'horas' => $registro['horas_cumplidas'] ?? 0,
                'fecha_inicio' => $registro['fecha_inicio'] ?? null,
                'fecha_fin' => $registro['fecha_fin'] ?? null,
                'programa' => $registro['programa_descripcion'] ?? ''
            ];

            // Registrar en auditoría ANTES de eliminar
            $registradoEnAuditoria = $this->pasantiaModel->registrarEliminacion($tipo, $registroId, $datosAuditoria);

            if (!$registradoEnAuditoria) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo registrar la eliminación en auditoría']);
                exit();
            }

            // Ahora eliminar el registro
            $eliminado = false;
            if ($tipo === 'ACTIVIDAD') {
                $eliminado = $this->pasantiaModel->eliminarActividad($registroId);
            } else if ($tipo === 'PLAN') {
                $eliminado = $this->pasantiaModel->eliminarPlan($registroId);
            }

            if ($eliminado) {
                error_log("Registro $tipo ID $registroId eliminado correctamente y registrado en auditoría");
                echo json_encode([
                    'success' => true,
                    'message' => 'Registro #' . $registroId . ' (' . $tipo . ') eliminado correctamente'
                ]);
                exit();
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'No se pudo eliminar el registro'
                ]);
                exit();
            }
        } catch (Exception $e) {
            error_log("Error al eliminar: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ]);
            exit();
        }
    }

    public function guardarCambiosAuditoria()
    {
        // Verificar autenticación
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            $_SESSION['error'] = 'No autorizado';
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        // Validar ID
        $registroId = $_POST['id'] ?? null;
        $tipo = $_POST['tipo'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            $_SESSION['error'] = 'ID inválido';
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        try {
            $registroId = intval($registroId);

            // Preparar datos
            $datos = [
                'actividad' => $_POST['actividad'] ?? '',
                'horas' => $_POST['horas'] ?? 0,
                'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
                'hora_inicio' => $_POST['hora_inicio'] ?? null,
                'hora_fin' => $_POST['hora_fin'] ?? null,
                'departamento' => $_POST['departamento'] ?? null,
                'funcion_asignada' => $_POST['funcion_asignada'] ?? null
            ];

            // Actualizar según tipo
            $actualizado = false;
            if ($tipo === 'ACTIVIDAD') {
                $actualizado = $this->pasantiaModel->actualizarActividad($registroId, $datos);
            } else if ($tipo === 'PLAN') {
                $actualizado = $this->pasantiaModel->actualizarPlan($registroId, $datos);
            }

            if ($actualizado) {
                $_SESSION['success'] = 'Registro actualizado correctamente';
                error_log("Registro $tipo ID $registroId actualizado correctamente");
            } else {
                $_SESSION['error'] = 'No se encontraron cambios o el registro no existe';
            }

            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        } catch (Exception $e) {
            error_log("Error al guardar cambios: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar cambios: ' . $e->getMessage();
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }
    }

    public function logout()
    {
        // Limpiar variables de sesión de admin
        unset($_SESSION['is_admin']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['auth_account_id']);
        unset($_SESSION['auth_role']);
        unset($_SESSION['must_change_password']);
        unset($_SESSION['admin_permissions']);
        session_destroy();

        header("Location: " . $this->basePath . "/admin/login");
        exit();
    }

    public function practicas()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $buscar = $_GET['buscar'] ?? '';
        $fase = $_GET['fase'] ?? ($_GET['estado'] ?? '');
        $estadoPractica = strtoupper(trim((string) ($_GET['estado_practica'] ?? 'TODOS')));
        if ($estadoPractica === 'CANCELADA') {
            $estadoPractica = 'NO FINALIZADO';
        }
        if (!in_array($estadoPractica, ['ACTIVA', 'FINALIZADA', 'NO FINALIZADO', 'TODOS'], true)) {
            $estadoPractica = 'TODOS';
        }
        $pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limite = 15;
        $offset = ($pagina - 1) * $limite;

        // Obtener total general (para contador)
        $totalRegistros = $this->pasantiaModel->contarPracticas($buscar, $fase, $estadoPractica);

        // Obtener registros paginados
        $pasantias = $this->pasantiaModel->getPracticasPaginadas($buscar, $fase, $limite, $offset, $estadoPractica);

        // Contadores KPI
        $totalCompletadas = $this->pasantiaModel->contarPorEstado(1, $estadoPractica);
        $totalPendientes = $this->pasantiaModel->contarPorEstado(0, $estadoPractica);
        $kpiActiva       = $this->pasantiaModel->contarPorEstadoPractica('ACTIVA');
        $kpiFinalizada   = $this->pasantiaModel->contarPorEstadoPractica('FINALIZADA');
        $kpiNoFinalizado = $this->pasantiaModel->contarPorEstadoPractica('NO FINALIZADO');

        $totalPaginas = ceil($totalRegistros / $limite);

        $this->render('admin/practicas/index', [
            'title' => 'Gestión de Prácticas',
            'pasantias' => $pasantias,
            'totalRegistros' => $totalRegistros,
            'totalCompletadas' => $totalCompletadas,
            'totalPendientes' => $totalPendientes,
            'kpiActiva' => $kpiActiva,
            'kpiFinalizada' => $kpiFinalizada,
            'kpiNoFinalizado' => $kpiNoFinalizado,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas,
            'estado' => $fase,
            'estadoPractica' => $estadoPractica,
            'buscar' => $buscar
        ]);
    }

    public function entidades()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $search = trim((string) ($_GET['buscar'] ?? ''));
        $entidades = $this->entidadModel->getEntidades($search);

        $this->render('admin/entidades/index', [
            'title' => 'Gestión de Entidades',
            'entidades' => $entidades,
            'buscar' => $search,
        ]);
    }

    public function crearEntidad()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $csrfToken = AuthSecurity::generateCsrfToken('admin_entidad_create');
        $programas = $this->entidadModel->getProgramas();
        $tutores = $this->entidadModel->getTutoresEmpresariales();

        $this->render('admin/entidades/form', [
            'title' => 'Crear Entidad',
            'modo' => 'crear',
            'csrfToken' => $csrfToken,
            'entidad' => [
                'nombre_empresa' => '',
                'ruc' => '',
                'razon_social' => '',
                'persona_contacto' => '',
                'telefono_contacto' => '',
                'email_contacto' => '',
                'plazas_disponibles' => 0,
                'estado' => 'Disponible',
                'direccion' => '',
                'id_programa' => '',
                'id_tutor_empresarial' => '',
                'tutor_cedula' => '',
                'tutor_nombre_completo' => '',
                'tutor_funcion' => '',
                'tutor_telefono' => '',
                'tutor_email' => '',
                'tutor_departamento' => '',
            ],
            'programas' => $programas,
            'tutores' => $tutores,
            'errors' => [],
        ]);
    }

    public function guardarEntidad()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_entidad_create', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido. Intenta nuevamente.';
            header('Location: ' . $this->basePath . '/admin/entidades/crear');
            exit();
        }

        $payload = $this->sanitizeEntidadPayload($_POST);
        $errors = $this->validateEntidadPayload($payload, null);

        if (!empty($errors)) {
            $csrfToken = AuthSecurity::generateCsrfToken('admin_entidad_create');
            $programas = $this->entidadModel->getProgramas();
            $tutores = $this->entidadModel->getTutoresEmpresariales();

            $this->render('admin/entidades/form', [
                'title' => 'Crear Entidad',
                'modo' => 'crear',
                'csrfToken' => $csrfToken,
                'entidad' => $payload,
                'programas' => $programas,
                'tutores' => $tutores,
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $idEntidad = $this->entidadModel->crearEntidad($payload);
            $_SESSION['success'] = 'Entidad creada correctamente (ID: ' . $idEntidad . ').';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        } catch (Throwable $e) {
            error_log('Error al crear entidad: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo crear la entidad. Revisa los datos e intenta nuevamente.';
            header('Location: ' . $this->basePath . '/admin/entidades/crear');
            exit();
        }
    }

    public function editarEntidad($idEntidad)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $idEntidad = (int) $idEntidad;
        if ($idEntidad <= 0) {
            $_SESSION['error'] = 'ID de entidad inválido.';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        }

        $entidad = $this->entidadModel->getEntidadById($idEntidad);
        if (!$entidad) {
            $_SESSION['error'] = 'Entidad no encontrada.';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        }

        $csrfToken = AuthSecurity::generateCsrfToken('admin_entidad_update_' . $idEntidad);
        $programas = $this->entidadModel->getProgramas();
        $tutores = $this->entidadModel->getTutoresEmpresariales();

        $entidad['tutor_cedula'] = $entidad['tutor_cedula'] ?? '';
        $entidad['tutor_nombre_completo'] = $entidad['tutor_nombre'] ?? '';
        $entidad['tutor_funcion'] = $entidad['tutor_funcion'] ?? '';
        $entidad['tutor_telefono'] = $entidad['tutor_telefono'] ?? '';
        $entidad['tutor_email'] = $entidad['tutor_email'] ?? '';
        $entidad['tutor_departamento'] = $entidad['tutor_departamento'] ?? '';

        $this->render('admin/entidades/form', [
            'title' => 'Editar Entidad',
            'modo' => 'editar',
            'csrfToken' => $csrfToken,
            'entidad' => $entidad,
            'programas' => $programas,
            'tutores' => $tutores,
            'errors' => [],
        ]);
    }

    public function actualizarEntidad($idEntidad)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $idEntidad = (int) $idEntidad;
        if ($idEntidad <= 0) {
            $_SESSION['error'] = 'ID de entidad inválido.';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_entidad_update_' . $idEntidad, $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token CSRF inválido. Intenta nuevamente.';
            header('Location: ' . $this->basePath . '/admin/entidades/editar/' . $idEntidad);
            exit();
        }

        $entidadActual = $this->entidadModel->getEntidadById($idEntidad);
        if (!$entidadActual) {
            $_SESSION['error'] = 'Entidad no encontrada.';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        }

        $payload = $this->sanitizeEntidadPayload($_POST);
        $errors = $this->validateEntidadPayload($payload, $idEntidad);

        if (!empty($errors)) {
            $csrfToken = AuthSecurity::generateCsrfToken('admin_entidad_update_' . $idEntidad);
            $programas = $this->entidadModel->getProgramas();
            $tutores = $this->entidadModel->getTutoresEmpresariales();

            $payload['id_entidad'] = $idEntidad;

            $this->render('admin/entidades/form', [
                'title' => 'Editar Entidad',
                'modo' => 'editar',
                'csrfToken' => $csrfToken,
                'entidad' => $payload,
                'programas' => $programas,
                'tutores' => $tutores,
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $this->entidadModel->actualizarEntidad($idEntidad, $payload);
            $_SESSION['success'] = 'Entidad actualizada correctamente.';
            header('Location: ' . $this->basePath . '/admin/entidades');
            exit();
        } catch (Throwable $e) {
            error_log('Error al actualizar entidad: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo actualizar la entidad. Revisa los datos e intenta nuevamente.';
            header('Location: ' . $this->basePath . '/admin/entidades/editar/' . $idEntidad);
            exit();
        }
    }

    private function sanitizeEntidadPayload(array $input): array
    {
        return [
            'nombre_empresa' => trim((string) ($input['nombre_empresa'] ?? '')),
            'ruc' => preg_replace('/\D+/', '', (string) ($input['ruc'] ?? '')),
            'razon_social' => trim((string) ($input['razon_social'] ?? '')),
            'persona_contacto' => trim((string) ($input['persona_contacto'] ?? '')),
            'telefono_contacto' => trim((string) ($input['telefono_contacto'] ?? '')),
            'email_contacto' => trim((string) ($input['email_contacto'] ?? '')),
            'plazas_disponibles' => (string) ($input['plazas_disponibles'] ?? '0'),
            'estado' => trim((string) ($input['estado'] ?? 'Disponible')),
            'direccion' => trim((string) ($input['direccion'] ?? '')),
            'id_programa' => (int) ($input['id_programa'] ?? 0),
            'id_tutor_empresarial' => (int) ($input['id_tutor_empresarial'] ?? 0),
            'tutor_cedula' => trim((string) ($input['tutor_cedula'] ?? '')),
            'tutor_nombre_completo' => trim((string) ($input['tutor_nombre_completo'] ?? '')),
            'tutor_funcion' => trim((string) ($input['tutor_funcion'] ?? '')),
            'tutor_telefono' => trim((string) ($input['tutor_telefono'] ?? '')),
            'tutor_email' => trim((string) ($input['tutor_email'] ?? '')),
            'tutor_departamento' => trim((string) ($input['tutor_departamento'] ?? '')),
        ];
    }

    private function validateEntidadPayload(array &$payload, ?int $idEntidad): array
    {
        $errors = [];

        if ($payload['nombre_empresa'] === '') {
            $errors['nombre_empresa'] = 'El nombre de la entidad es obligatorio.';
        }

        if ($payload['ruc'] === '') {
            $errors['ruc'] = 'El RUC es obligatorio.';
        } elseif (!preg_match('/^\d{10,13}$/', $payload['ruc'])) {
            $errors['ruc'] = 'El RUC debe contener entre 10 y 13 dígitos.';
        } elseif ($this->entidadModel->existeRuc($payload['ruc'], $idEntidad)) {
            $errors['ruc'] = 'Ya existe una entidad registrada con ese RUC.';
        }

        $plazas = filter_var($payload['plazas_disponibles'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($plazas === false) {
            $errors['plazas_disponibles'] = 'Las plazas disponibles deben ser un número entero mayor o igual a 0.';
        } else {
            $payload['plazas_disponibles'] = (int) $plazas;
        }

        $estadoPermitido = ['Disponible', 'No Disponible'];
        if (!in_array($payload['estado'], $estadoPermitido, true)) {
            $errors['estado'] = 'El estado seleccionado no es válido.';
        }

        if ($payload['email_contacto'] !== '' && !filter_var($payload['email_contacto'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_contacto'] = 'El correo de contacto no es válido.';
        }

        if ($payload['id_programa'] > 0 && !$this->entidadModel->existePrograma((int) $payload['id_programa'])) {
            $errors['id_programa'] = 'El programa seleccionado no existe.';
        }

        if ($payload['id_tutor_empresarial'] > 0 && !$this->entidadModel->existeTutor((int) $payload['id_tutor_empresarial'])) {
            $errors['id_tutor_empresarial'] = 'El tutor empresarial seleccionado no existe.';
        }

        $hayDatosTutor = ($payload['tutor_cedula'] !== ''
            || $payload['tutor_nombre_completo'] !== ''
            || $payload['tutor_funcion'] !== ''
            || $payload['tutor_telefono'] !== ''
            || $payload['tutor_email'] !== ''
            || $payload['tutor_departamento'] !== '');

        if ($hayDatosTutor) {
            if ($payload['tutor_cedula'] === '') {
                $errors['tutor_cedula'] = 'La cédula del tutor es obligatoria cuando se ingresan datos del tutor.';
            }
            if ($payload['tutor_nombre_completo'] === '') {
                $errors['tutor_nombre_completo'] = 'El nombre del tutor es obligatorio cuando se ingresan datos del tutor.';
            }
            if ($payload['tutor_email'] !== '' && !filter_var($payload['tutor_email'], FILTER_VALIDATE_EMAIL)) {
                $errors['tutor_email'] = 'El correo del tutor no es válido.';
            }
        }

        return $errors;
    }

    public function vinculacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyectosVinculacion = $this->proyectoModel->obtenerPorTipo('VINCULACION') ?? [];
        $carreras = $this->carreraModel->obtenerTodasv() ?? [];

        $this->render('admin/vinculacion/index', [
            'title' => 'Vinculación',
            'proyectosVinculacion' => $proyectosVinculacion,
            'carreras' => $carreras
        ]);
    }

    public function investigacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyectos = $this->proyectoModel->obtenerPorTipo('INVESTIGACION') ?? [];
        $publicaciones = $this->publicacionModel->obtenerTodas() ?? [];
        $ponencias = $this->ponenciaModel->obtenerTodas() ?? [];
        $carreras = $this->carreraModel->obtenerTodas() ?? [];

        $this->render('admin/investigacion/index', [
            'title' => 'Investigación',
            'proyectos' => $proyectos,
            'publicaciones' => $publicaciones,
            'ponencias' => $ponencias,
            'carreras' => $carreras
        ]);
    }

    /* Método para renderizar vistas con layout */

    protected function render($view, $data = [])
    {
        extract($data);

        $basePath = $this->basePath;

        $nombreCompleto = $_SESSION['nombres_completos'] ?? 'Administrador';

        $pendingResetCount = 0;
        try {
            $pendingResetCount = $this->resetModel->countPending();
        } catch (Throwable $e) {
            // tabla aún no migrada
        }

        $content = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($content)) {
            die("Vista no encontrada: " . $content);
        }

        require __DIR__ . '/../Views/Layouts/admin_layout.php';
    }

    private function sendResetPasswordEmail($toEmail, $displayName, $tempPassword)
    {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = getenv('SMTP_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('SMTP_USER');
            $mail->Password   = getenv('SMTP_PASS');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(getenv('SMTP_USER'), 'Superarse Conectados');
            $mail->addAddress($toEmail, $displayName);
            $mail->Subject = 'Tu contraseña ha sido restablecida - Superarse Conectados';
            $mail->isHTML(true);
            $mail->Body = "<p>Hola <strong>" . htmlspecialchars($displayName) . "</strong>,</p>"
                . "<p>Un administrador ha restablecido tu contraseña de acceso al sistema.</p>"
                . "<p>Tu contraseña temporal es: <strong style='font-size:16px;letter-spacing:2px;'>"
                . htmlspecialchars($tempPassword) . "</strong></p>"
                . "<p>Al ingresar, se te pedirá que la cambies por una nueva.</p>"
                . "<p>Superarse Conectados</p>";
            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('Error al enviar email de restablecimiento: ' . $e->getMessage());
            return false;
        }
    }

    private function clearStudentSession()
    {
        unset($_SESSION['authenticated']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['identificacion']);
    }

    private function enforcePasswordChangeRedirect()
    {
        if (empty($_SESSION['is_admin']) || empty($_SESSION['must_change_password'])) {
            return;
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if ($this->basePath !== '' && strpos($currentPath, $this->basePath) === 0) {
            $currentPath = substr($currentPath, strlen($this->basePath));
        }

        $normalizedPath = rtrim($currentPath, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        $allowedPaths = ['/admin/password/change', '/admin/logout', '/admin/forgot-password', '/admin/forgot-password/submit'];
        if (!in_array($normalizedPath, $allowedPaths, true)) {
            header("Location: " . $this->basePath . "/admin/password/change");
            exit();
        }
    }

    private function loadAdminPermissionsToSession($accountId)
    {
        $accountId = (int) $accountId;
        if ($accountId <= 0) {
            $_SESSION['admin_permissions'] = ['enabled' => false, 'matrix' => []];
            return;
        }

        $_SESSION['admin_permissions'] = $this->permissionModel->getPermissionsByAccountId($accountId);
    }

    private function getPermissionModules()
    {
        return [
            'dashboard' => 'Dashboard',
            'practicas' => 'Prácticas',
            'vinculacion' => 'Vinculación',
            'investigacion' => 'Investigación',
            'plan_estrategico' => 'Planificación Estratégica',
            'pedi' => 'PEDI',
            'poa' => 'POA',
            'convenios' => 'Convenios',
            'auditoria' => 'Auditoría',
            'reportes' => 'Reportes',
            'cuentas' => 'Cuentas',
            'configuracion' => 'Configuración',
            'solicitudes' => 'Solicitudes de Restablecimiento',
        ];
    }

    private function hasPermission($moduleKey, $action)
    {
        $accountId = (int) ($_SESSION['auth_account_id'] ?? 0);
        if ($accountId <= 0) {
            return false;
        }

        if (!isset($_SESSION['admin_permissions']) || !is_array($_SESSION['admin_permissions'])) {
            $this->loadAdminPermissionsToSession($accountId);
        }

        $permissionState = $_SESSION['admin_permissions'] ?? ['enabled' => false, 'matrix' => []];
        if (empty($permissionState['enabled'])) {
            // Modo compatibilidad: si aún no hay permisos configurados, mantiene acceso completo.
            return true;
        }

        $matrix = $permissionState['matrix'] ?? [];
        if (!isset($matrix[$moduleKey])) {
            // Fallback: si pedi/poa no está en la matriz, usar plan_estrategico
            $fallbackByModule = [
                'pedi' => 'plan_estrategico',
                'poa' => 'plan_estrategico',
            ];
            $fallbackKey = $fallbackByModule[$moduleKey] ?? null;
            if ($fallbackKey !== null && isset($matrix[$fallbackKey])) {
                return !empty($matrix[$fallbackKey][$action]);
            }
            return false;
        }

        return !empty($matrix[$moduleKey][$action]);
    }

    private function denyPermission($moduleKey, $action)
    {
        $_SESSION['error'] = 'No tienes permiso para ' . $action . ' en el módulo ' . $moduleKey . '.';
        http_response_code(403);

        if (!headers_sent()) {
            header('Location: ' . $this->basePath . '/admin/dashboard');
            exit();
        }

        echo '403 - Acceso denegado';
        exit();
    }

    private function resolvePermissionRequirement()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        if ($this->basePath !== '' && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        if ($uri === '') {
            $uri = '/';
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $publicAllowed = [
            '/admin/login',
            '/admin/login/check',
            '/admin/logout',
            '/admin/password/change',
            '/admin/forgot-password',
            '/admin/forgot-password/submit',
        ];

        if (in_array($uri, $publicAllowed, true)) {
            return null;
        }

        if ($uri === '/admin/dashboard') {
            return ['dashboard', 'view'];
        }

        if ($uri === '/admin/practicas') {
            return ['practicas', 'view'];
        }

        if (preg_match('#^/admin/practicas/(editar|eliminar)/\d+$#', $uri)) {
            return ['practicas', $method === 'POST' ? 'edit' : 'view'];
        }

        if ($uri === '/admin/entidades') {
            return ['practicas', 'view'];
        }
        if (in_array($uri, ['/admin/entidades/crear', '/admin/entidades/guardar'], true)) {
            return ['practicas', 'create'];
        }
        if (preg_match('#^/admin/entidades/editar/\d+$#', $uri) || preg_match('#^/admin/entidades/actualizar/\d+$#', $uri)) {
            return ['practicas', 'edit'];
        }

        if ($uri === '/admin/vinculacion') {
            return ['vinculacion', 'view'];
        }
        if (in_array($uri, ['/admin/proyecto/crear_vinculacion', '/admin/carrera/crearV', '/admin/guardar-proyecto-vinculacion', '/admin/guardar-carrera-proyectoV'], true)) {
            return ['vinculacion', 'create'];
        }
        if (preg_match('#^/admin/vinculacion/editar/\d+$#', $uri) || $uri === '/admin/proyecto/actualizarVinculacion' || preg_match('#^/admin/carrera/editarV/\d+$#', $uri) || $uri === '/admin/carrera/actualizarV') {
            return ['vinculacion', 'edit'];
        }
        if (preg_match('#^/admin/vinculacion/eliminar/\d+$#', $uri) || preg_match('#^/admin/carrera/eliminarV/\d+$#', $uri)) {
            return ['vinculacion', 'delete'];
        }

        if ($uri === '/admin/investigacion') {
            return ['investigacion', 'view'];
        }
        if (in_array($uri, ['/admin/proyecto/crear', '/admin/publicacion/crear', '/admin/ponencia/crear', '/admin/carrera/crear', '/admin/guardar-proyecto-investigacion', '/admin/guardar-publicacion', '/admin/guardar-ponencia', '/admin/guardar-carrera-proyecto'], true)) {
            return ['investigacion', 'create'];
        }
        if (preg_match('#^/admin/proyecto/editar/\d+$#', $uri) || $uri === '/admin/proyecto/actualizar' || preg_match('#^/admin/publicacion/editar/\d+$#', $uri) || $uri === '/admin/publicacion/actualizar' || preg_match('#^/admin/ponencia/editar/\d+$#', $uri) || $uri === '/admin/ponencia/actualizar' || preg_match('#^/admin/carrera/editar/\d+$#', $uri) || $uri === '/admin/carrera/actualizar') {
            return ['investigacion', 'edit'];
        }
        if (preg_match('#^/admin/proyecto/eliminar/\d+$#', $uri) || preg_match('#^/admin/publicacion/eliminar/\d+$#', $uri) || preg_match('#^/admin/ponencia/eliminar/\d+$#', $uri) || preg_match('#^/admin/carrera/eliminar/\d+$#', $uri)) {
            return ['investigacion', 'delete'];
        }

        if ($uri === '/admin/plan-estrategico') {
            return ['plan_estrategico', 'view'];
        }
        if ($uri === '/admin/pedi') {
            return ['pedi', 'view'];
        }
        if ($uri === '/admin/poa') {
            return ['poa', 'view'];
        }
        if (in_array($uri, ['/admin/pedi/create', '/admin/pedi/store'], true)) {
            return ['pedi', 'create'];
        }
        if (preg_match('#^/admin/pedi/edit/\d+$#', $uri) || $uri === '/admin/pedi/update') {
            return ['pedi', 'edit'];
        }
        if (preg_match('#^/admin/pedi/eliminar/\d+$#', $uri)) {
            return ['pedi', 'delete'];
        }
        if (in_array($uri, ['/admin/poa/create', '/admin/poa/store', '/admin/actividad/create', '/admin/actividad/store'], true)) {
            return ['plan_estrategico', 'create'];
        }
        if (preg_match('#^/admin/poa/edit/\d+$#', $uri) || $uri === '/admin/poa/update' || preg_match('#^/admin/actividad/edit/\d+$#', $uri) || $uri === '/admin/actividad/update') {
            return ['plan_estrategico', 'edit'];
        }
        if (preg_match('#^/admin/poa/eliminar/\d+$#', $uri) || preg_match('#^/admin/actividad/eliminar/\d+$#', $uri)) {
            return ['plan_estrategico', 'delete'];
        }

        if ($uri === '/admin/configuracion') {
            return ['configuracion', 'view'];
        }
        if (in_array($uri, [
            '/admin/configuracion/proceso/store',
            '/admin/configuracion/eje/store',
            '/admin/configuracion/objetivo/store',
            '/admin/configuracion/estrategia/store',
        ], true)) {
            return ['configuracion', 'create'];
        }
        if (in_array($uri, [
            '/admin/configuracion/proceso/update',
            '/admin/configuracion/eje/update',
            '/admin/configuracion/objetivo/update',
            '/admin/configuracion/estrategia/update',
        ], true)) {
            return ['configuracion', 'edit'];
        }
        if (preg_match('#^/admin/configuracion/proceso/eliminar/\d+$#', $uri)
            || preg_match('#^/admin/configuracion/eje/eliminar/\d+$#', $uri)
            || preg_match('#^/admin/configuracion/objetivo/eliminar/\d+$#', $uri)
            || preg_match('#^/admin/configuracion/estrategia/eliminar/\d+$#', $uri)) {
            return ['configuracion', 'delete'];
        }

        if ($uri === '/admin/convenio') {
            return ['convenios', 'view'];
        }
        if (in_array($uri, ['/admin/convenio/crear', '/admin/convenio/guardar'], true)) {
            return ['convenios', 'create'];
        }
        if (preg_match('#^/admin/convenio/editar/\d+$#', $uri) || $uri === '/admin/convenio/actualizar') {
            return ['convenios', 'edit'];
        }
        if (preg_match('#^/admin/convenio/eliminar/\d+$#', $uri)) {
            return ['convenios', 'delete'];
        }

        if (
            $uri === '/admin/auditoria-fase-dos'
            || $uri === '/admin/auditoria-general'
            || $uri === '/admin/auditoria-general/export/csv'
            || $uri === '/admin/auditoria-general/export/excel'
        ) {
            return ['auditoria', 'view'];
        }

        if (
            $uri === '/admin/reportes'
            || $uri === '/admin/reportes/vinculacion'
            || $uri === '/admin/reportes/investigacion'
            || $uri === '/admin/reportes/planificacion'
            || $uri === '/admin/reportes/export/modulo'
            || $uri === '/admin/reportes/export/empresas-estudiantes'
            || $uri === '/admin/reportes/export/modalidad-carrera'
            || $uri === '/admin/reportes/export/estudiantes-fase'
        ) {
            return ['reportes', 'view'];
        }

        if (in_array($uri, ['/admin/accounts', '/admin/accounts/store', '/admin/accounts/toggle', '/admin/student-accounts/provision', '/admin/student-accounts/toggle', '/admin/student-accounts/reset'], true) || preg_match('#^/admin/accounts/permissions/\d+$#', $uri) || $uri === '/admin/accounts/permissions/update') {
            return ['cuentas', 'edit'];
        }

        if (in_array($uri, ['/admin/reset-requests', '/admin/reset-requests/resolve'], true)) {
            return ['solicitudes', 'edit'];
        }

        return null;
    }

    private function enforceRoutePermission()
    {
        if (empty($_SESSION['is_admin']) || ($_SESSION['auth_role'] ?? null) !== 'admin') {
            return;
        }

        $requirement = $this->resolvePermissionRequirement();
        if ($requirement === null) {
            return;
        }

        [$moduleKey, $action] = $requirement;
        if (!$this->hasPermission($moduleKey, $action)) {
            $this->denyPermission($moduleKey, $action);
        }
    }

    private function normalizePermissionInput(array $rawPermissions)
    {
        $actions = ['view', 'create', 'edit', 'delete'];
        $normalized = [];

        foreach ($this->getPermissionModules() as $moduleKey => $label) {
            $moduleRaw = $rawPermissions[$moduleKey] ?? [];
            $normalized[$moduleKey] = [
                'view' => !empty($moduleRaw['view']),
                'create' => !empty($moduleRaw['create']),
                'edit' => !empty($moduleRaw['edit']),
                'delete' => !empty($moduleRaw['delete']),
            ];

            if ($normalized[$moduleKey]['create'] || $normalized[$moduleKey]['edit'] || $normalized[$moduleKey]['delete']) {
                $normalized[$moduleKey]['view'] = true;
            }

            foreach ($actions as $action) {
                $normalized[$moduleKey][$action] = (bool) $normalized[$moduleKey][$action];
            }
        }

        return $normalized;
    }

    /* Metodos para Guardar Nuevos Registros */

    /* === GESTIÓN DE CUENTAS ADMIN === */

    private function buildAccountsReturnQuery($rawQuery = '')
    {
        $rawQuery = is_string($rawQuery) ? ltrim($rawQuery, "? ") : '';
        if ($rawQuery === '') {
            return '';
        }

        parse_str($rawQuery, $parsed);
        $allowed = ['admin_q', 'admin_page', 'student_q', 'student_program', 'student_page'];
        $clean = [];

        foreach ($allowed as $key) {
            if (isset($parsed[$key])) {
                $clean[$key] = is_string($parsed[$key]) ? trim($parsed[$key]) : $parsed[$key];
            }
        }

        return http_build_query($clean);
    }

    private function redirectToAccounts($returnQuery = '')
    {
        $query = $this->buildAccountsReturnQuery($returnQuery);
        $location = $this->basePath . '/admin/accounts';

        if ($query !== '') {
            $location .= '?' . $query;
        }

        header('Location: ' . $location);
        exit();
    }

    public function adminAccounts()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $perPage = 20;

        $adminSearch = trim($_GET['admin_q'] ?? '');
        $adminPage = max(1, (int) ($_GET['admin_page'] ?? 1));
        $totalAdmins = $this->authAccountModel->countAdminAccounts($adminSearch);
        $totalAdminPages = max(1, (int) ceil($totalAdmins / $perPage));
        $adminPage = min($adminPage, $totalAdminPages);
        $adminOffset = ($adminPage - 1) * $perPage;
        $accounts = $this->authAccountModel->getAdminAccountsPaged($perPage, $adminOffset, $adminSearch);

        $studentSearch = trim($_GET['student_q'] ?? '');
        $studentProgram = trim($_GET['student_program'] ?? '');
        $studentPage = max(1, (int) ($_GET['student_page'] ?? 1));
        $programs = $this->userModel->getDistinctProgramasActivos();
        $totalStudents = $this->userModel->countEstudiantesFiltered($studentSearch, $studentProgram);
        $totalStudentPages = max(1, (int) ceil($totalStudents / $perPage));
        $studentPage = min($studentPage, $totalStudentPages);
        $studentOffset = ($studentPage - 1) * $perPage;
        $students = $this->userModel->getEstudiantesPaged($perPage, $studentOffset, $studentSearch, $studentProgram);

        $identifications = array_values(array_filter(array_map(function ($student) {
            return trim($student['numero_identificacion'] ?? '');
        }, $students)));

        $studentAccountsIndex = $this->authAccountModel->getStudentAccountsByIdentifications($identifications);
        $csrfTokenCreate      = AuthSecurity::generateCsrfToken('admin_account_create');
        $csrfTokenToggle      = AuthSecurity::generateCsrfToken('admin_account_toggle');
        $csrfTokenStudent     = AuthSecurity::generateCsrfToken('student_account_provision');
        $csrfTokenStudentToggle = AuthSecurity::generateCsrfToken('student_account_toggle');
        $csrfTokenStudentReset = AuthSecurity::generateCsrfToken('student_account_reset');
        $currentQuery = $this->buildAccountsReturnQuery($_SERVER['QUERY_STRING'] ?? '');

        $this->render('admin/accounts/index', [
            'title'                => 'Gestión de Cuentas',
            'accounts'             => $accounts,
            'students'             => $students,
            'programs'             => $programs,
            'studentAccountsIndex' => $studentAccountsIndex,
            'csrfTokenCreate'      => $csrfTokenCreate,
            'csrfTokenToggle'      => $csrfTokenToggle,
            'csrfTokenStudent'     => $csrfTokenStudent,
            'csrfTokenStudentToggle' => $csrfTokenStudentToggle,
            'csrfTokenStudentReset'  => $csrfTokenStudentReset,
            'adminSearch'          => $adminSearch,
            'adminPage'            => $adminPage,
            'totalAdmins'          => $totalAdmins,
            'totalAdminPages'      => $totalAdminPages,
            'studentSearch'        => $studentSearch,
            'studentProgram'       => $studentProgram,
            'studentPage'          => $studentPage,
            'totalStudents'        => $totalStudents,
            'totalStudentPages'    => $totalStudentPages,
            'currentQuery'         => $currentQuery,
        ]);
    }

    public function storeAdminAccount()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_account_create', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $displayName    = trim($_POST['display_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $identification = trim($_POST['numero_identificacion'] ?? '');
        $tempPassword   = $_POST['temp_password'] ?? '';

        if ($displayName === '' || $email === '' || $tempPassword === '') {
            $_SESSION['error'] = 'Nombre, correo y contraseña temporal son obligatorios.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $policyError = AuthSecurity::validatePasswordPolicy($tempPassword);
        if ($policyError !== null) {
            $_SESSION['error'] = 'Contraseña inválida: ' . $policyError;
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $result = $this->authAccountModel->createAdminAccount([
            'display_name'          => $displayName,
            'email'                 => $email,
            'numero_identificacion' => $identification,
            'password_hash'         => password_hash($tempPassword, PASSWORD_DEFAULT),
            'must_change_password'  => 1,
        ]);

        if ($result['success']) {
            $newAccountId = (int) ($result['account']['id'] ?? 0);
            if ($newAccountId > 0) {
                $fullPermissions = [];
                foreach ($this->getPermissionModules() as $moduleKey => $label) {
                    $fullPermissions[$moduleKey] = [
                        'view' => true,
                        'create' => true,
                        'edit' => true,
                        'delete' => true,
                    ];
                }
                $this->permissionModel->setPermissions($newAccountId, $fullPermissions);
            }
            $_SESSION['success'] = "Cuenta creada para {$displayName}. Contraseña temporal: {$tempPassword}";
        } else {
            $_SESSION['error'] = $result['message'] ?? 'No fue posible crear la cuenta.';
        }

        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function toggleAdminAccount()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_account_toggle', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId   = (int) ($_POST['account_id'] ?? 0);
        $newStatus   = (int) ($_POST['new_status'] ?? 0);
        $myAccountId = (int) ($_SESSION['auth_account_id'] ?? 0);

        if ($accountId === 0 || $accountId === $myAccountId) {
            $_SESSION['error'] = 'No puedes modificar tu propia cuenta.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $this->authAccountModel->setActiveStatus($accountId, $newStatus === 1);
        $_SESSION['success'] = 'Estado de la cuenta actualizado correctamente.';
        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function editAdminPermissions($accountId)
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $accountId = (int) $accountId;
        $account = $this->authAccountModel->findById($accountId);

        if (!$account || ($account['role'] ?? '') !== 'admin') {
            $_SESSION['error'] = 'Cuenta de administrador no encontrada.';
            $this->redirectToAccounts($_GET['return_query'] ?? '');
        }

        $permissionState = $this->permissionModel->getPermissionsByAccountId($accountId);
        $modules = $this->getPermissionModules();

        if (empty($permissionState['enabled'])) {
            $matrix = [];
            foreach ($modules as $moduleKey => $label) {
                $matrix[$moduleKey] = [
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ];
            }
            $permissionState = ['enabled' => true, 'matrix' => $matrix];
        }

        $csrfTokenPermissions = AuthSecurity::generateCsrfToken('admin_permissions_update');
        $returnQuery = $this->buildAccountsReturnQuery($_GET['return_query'] ?? '');

        $this->render('admin/accounts/permissions', [
            'title' => 'Permisos de Administrador',
            'account' => $account,
            'modules' => $modules,
            'actions' => ['view', 'create', 'edit', 'delete'],
            'permissionsMatrix' => $permissionState['matrix'],
            'csrfTokenPermissions' => $csrfTokenPermissions,
            'returnQuery' => $returnQuery,
        ]);
    }

    public function updateAdminPermissions()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('admin_permissions_update', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        if ($accountId <= 0) {
            $_SESSION['error'] = 'Cuenta de administrador inválida.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $account = $this->authAccountModel->findById($accountId);
        if (!$account || ($account['role'] ?? '') !== 'admin') {
            $_SESSION['error'] = 'Cuenta de administrador no encontrada.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $rawPermissions = $_POST['permissions'] ?? [];
        $normalized = $this->normalizePermissionInput(is_array($rawPermissions) ? $rawPermissions : []);

        $saved = $this->permissionModel->setPermissions($accountId, $normalized);
        if (!$saved) {
            $_SESSION['error'] = 'No se pudieron actualizar los permisos.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        if ($accountId === (int) ($_SESSION['auth_account_id'] ?? 0)) {
            $this->loadAdminPermissionsToSession($accountId);
        }

        $_SESSION['success'] = 'Permisos actualizados correctamente para ' . ($account['display_name'] ?? 'administrador') . '.';
        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function provisionStudentAccount()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_account_provision', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['error'] = 'Estudiante no válido.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $student = $this->userModel->findActiveStudentById($userId);
        if (!$student) {
            $_SESSION['error'] = 'No se encontró un estudiante activo con ese identificador.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $existingAccount = $this->authAccountModel->findStudentAccountByIdentification(
            trim($student['numero_identificacion'] ?? '')
        );

        if ($existingAccount) {
            $_SESSION['error'] = 'Ese estudiante ya tiene una cuenta de acceso creada.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $account = $this->authAccountModel->ensureStudentAccount($student);
        if (!$account) {
            $_SESSION['error'] = 'No fue posible crear la cuenta del estudiante.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $_SESSION['success'] = 'Cuenta creada para el estudiante. La contraseña inicial es su número de identificación y deberá cambiarla al ingresar.';
        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function toggleStudentAccount()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_account_toggle', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $newStatus = (int) ($_POST['new_status'] ?? 0);
        $account = $this->authAccountModel->findById($accountId);

        if (!$account || ($account['role'] ?? '') !== 'student') {
            $_SESSION['error'] = 'Cuenta de estudiante no válida.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $updated = $this->authAccountModel->setStudentActiveStatus($accountId, $newStatus === 1);
        $_SESSION[$updated ? 'success' : 'error'] = $updated
            ? 'Estado de la cuenta del estudiante actualizado correctamente.'
            : 'No se pudo actualizar el estado de la cuenta del estudiante.';

        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function resetStudentAccountPassword()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_account_reset', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $account = $this->authAccountModel->findById($accountId);

        if (!$account || ($account['role'] ?? '') !== 'student') {
            $_SESSION['error'] = 'Cuenta de estudiante no válida.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $identification = trim($account['numero_identificacion'] ?? '');
        if ($identification === '') {
            $_SESSION['error'] = 'La cuenta no tiene número de identificación para restablecer contraseña.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $updated = $this->authAccountModel->resetToTemporaryPassword(
            $accountId,
            password_hash($identification, PASSWORD_DEFAULT)
        );

        $_SESSION[$updated ? 'success' : 'error'] = $updated
            ? 'Contraseña restablecida. La clave temporal es la cédula del estudiante y deberá cambiarla al ingresar.'
            : 'No se pudo restablecer la contraseña del estudiante.';

        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    /* === SOLICITUDES DE RESTABLECIMIENTO === */

    public function passwordResetRequests()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $requests          = $this->resetModel->getAllRequests(200);
        $csrfTokensResolve = [];
        $csrfTokensDiscard = [];

        foreach ($requests as $req) {
            if ($req['status'] === 'pending') {
                if (!empty($req['account_id'])) {
                    $csrfTokensResolve[$req['id']] = AuthSecurity::generateCsrfToken(
                        'admin_reset_resolve_' . $req['id']
                    );
                }
                $csrfTokensDiscard[$req['id']] = AuthSecurity::generateCsrfToken(
                    'admin_reset_discard_' . $req['id']
                );
            }
        }

        $this->render('admin/reset_requests', [
            'title'             => 'Solicitudes de Restablecimiento',
            'requests'          => $requests,
            'csrfTokensResolve' => $csrfTokensResolve,
            'csrfTokensDiscard' => $csrfTokensDiscard,
        ]);
    }

    public function resolvePasswordReset()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);

        if (!AuthSecurity::validateCsrfToken('admin_reset_resolve_' . $requestId, $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resetRequest = $this->resetModel->findById($requestId);

        if (!$resetRequest || $resetRequest['status'] !== 'pending' || empty($resetRequest['account_id'])) {
            $_SESSION['error'] = 'Solicitud no válida o ya fue procesada.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $tempPassword = AuthSecurity::generateTempPassword();
        $updated = $this->authAccountModel->resetToTemporaryPassword(
            (int) $resetRequest['account_id'],
            password_hash($tempPassword, PASSWORD_DEFAULT)
        );

        if (!$updated) {
            $_SESSION['error'] = 'No fue posible restablecer la contraseña. Intente de nuevo.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resolvedBy = $_SESSION['admin_email'] ?? 'admin';
        $this->resetModel->resolveRequest($requestId, $resolvedBy);

        $_SESSION['temp_password_revealed'] = $tempPassword;

        $account = $this->authAccountModel->findById((int) $resetRequest['account_id']);
        if ($account && !empty($account['email'])) {
            $emailSent = $this->sendResetPasswordEmail(
                $account['email'],
                $account['display_name'],
                $tempPassword
            );
            if ($emailSent) {
                $_SESSION['temp_password_email_sent'] = true;
            }
        }

        header("Location: " . $this->basePath . "/admin/reset-requests");
        exit();
    }

    public function discardPasswordReset()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $requestId = (int) ($_POST['request_id'] ?? 0);

        if (!AuthSecurity::validateCsrfToken('admin_reset_discard_' . $requestId, $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesión del formulario expirada. Intente de nuevo.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resetRequest = $this->resetModel->findById($requestId);

        if (!$resetRequest || $resetRequest['status'] !== 'pending') {
            $_SESSION['error'] = 'Solicitud no válida o ya fue procesada.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resolvedBy = $_SESSION['admin_email'] ?? 'admin';
        $discarded = $this->resetModel->discardRequest($requestId, $resolvedBy);

        $_SESSION[$discarded ? 'success' : 'error'] = $discarded
            ? 'Solicitud descartada correctamente.'
            : 'No fue posible descartar la solicitud. Intente de nuevo.';

        header("Location: " . $this->basePath . "/admin/reset-requests");
        exit();
    }

    /* === OLVIDÉ MI CONTRASEÑA (admin) === */

    public function showForgotPasswordFormAdmin()
    {
        if (!empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        $basePath          = $this->basePath;
        $title             = 'Recuperar acceso administrativo';
        $headerTitle       = 'Superarse Conectados';
        $headerSubtitle    = '';
        $moduleCss         = ['login.css'];
        $moduleJs          = [];
        $moduleHeadStyles  = [];
        $moduleBodyScripts = [];
        $csrfToken         = AuthSecurity::generateCsrfToken('admin_forgot_password');
        $content           = __DIR__ . '/../Views/admin/forgot_password.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function requestPasswordResetAdmin()
    {
        if (!AuthSecurity::validateCsrfToken('admin_forgot_password', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/admin/forgot-password?error=invalid_request");
            exit();
        }

        $email = strtolower(trim($_POST['email'] ?? ''));

        if ($email === '') {
            header("Location: " . $this->basePath . "/admin/forgot-password?error=campos_vacios");
            exit();
        }

        $account = $this->authAccountModel->findAdminAccountByEmail($email);

        if ($account) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $this->resetModel->createRequest(
                (int) $account['id'],
                'admin',
                $account['display_name'],
                $account['email'],
                $ipAddress
            );
        }

        // Siempre redirigir con éxito para no revelar si el correo existe
        header("Location: " . $this->basePath . "/admin/forgot-password?success=1");
        exit();
    }

    /* Metodos originales */

    public function guardarProyectoInvestigacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $guardado = $this->proyectoModel->crearInvestigacion($_POST);

            if ($guardado) {
                $_SESSION['success'] = "Proyecto creado correctamente";
            } else {
                $_SESSION['error'] = "Error al crear proyecto";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function guardarProyectoVinculacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $guardado = $this->proyectoModel->crearVinculacion($_POST);

            if ($guardado) {
                $_SESSION['success'] = "Proyecto creado correctamente";
            } else {
                $_SESSION['error'] = "Error al crear proyecto";
            }

            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }
    }

    public function guardarCarreraProyecto()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'id_proyecto' => $_POST['id_proyecto'],
                'carrera' => $_POST['carrera'],
                'nro_estudiantes' => $_POST['nro_estudiantes']
            ];

            $guardado = $this->carreraModel->agregarCarrera($data);

            if ($guardado) {
                $_SESSION['success'] = "Carrera agregada correctamente";
            } else {
                $_SESSION['error'] = "Error al agregar carrera";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function guardarCarreraProyectoV()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'id_proyecto' => $_POST['id_proyecto'],
                'carrera' => $_POST['carrera'],
                'nro_estudiantes' => $_POST['nro_estudiantes']
            ];

            $guardado = $this->carreraModel->agregarCarrera($data);

            if ($guardado) {
                $_SESSION['success'] = "Carrera agregada correctamente";
            } else {
                $_SESSION['error'] = "Error al agregar carrera";
            }

            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }
    }

    public function guardarPublicacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $guardado = $this->publicacionModel->crear($_POST);

            if ($guardado) {
                $_SESSION['success'] = "Publicación creada correctamente";
            } else {
                $_SESSION['error'] = "Error al crear publicación";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function guardarPonencia()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $guardado = $this->ponenciaModel->crear($_POST);

            if ($guardado) {
                $_SESSION['success'] = "Ponencia creada correctamente";
            } else {
                $_SESSION['error'] = "Error al crear ponencia";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    /* Metodos para Mostrar Formularios de Creación */

    public function mostrarCrearProyecto()
    {
        $this->convenioModel->caducarVencidos();
        $conveniosActivos = $this->convenioModel->obtenerConveniosActivos();

        $this->render('admin/investigacion/crear_proyecto', [
            'title' => 'Nuevo Proyecto',
            'conveniosActivos' => $conveniosActivos
        ]);
    }

    public function mostrarCrearProyectoVinculacion()
    {
        $this->convenioModel->caducarVencidos();
        $conveniosActivos = $this->convenioModel->obtenerConveniosActivos();

        $this->render('admin/vinculacion/crear_proyecto', [
            'title' => 'Nuevo Proyecto',
            'conveniosActivos' => $conveniosActivos
        ]);
    }

    public function mostrarCrearPublicacion()
    {
        $this->render('admin/investigacion/crear_publicacion', [
            'title' => 'Nueva Publicación'
        ]);
    }

    public function mostrarCrearPonencia()
    {
        $this->render('admin/investigacion/crear_ponencia', [
            'title' => 'Nueva Ponencia'
        ]);
    }

    public function mostrarCrearCarrera()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyectos = $this->proyectoModel->obtenerActivosInvestigacion();

        $this->render('admin/investigacion/crear_carrera_proyecto', [
            'title' => 'Agregar Carrera',
            'proyectos' => $proyectos
        ]);
    }

    public function mostrarCrearCarreraV()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyectos = $this->proyectoModel->obtenerActivosVinculacion();

        $this->render('admin/vinculacion/crear_carrera_proyectoV', [
            'title' => 'Agregar Carrera',
            'proyectos' => $proyectos
        ]);
    }

    /* Metodos para Editar */

    public function editarProyecto($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyecto = $this->proyectoModel->obtenerPorId($id);

        if (!$proyecto) {
            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }

        $this->render('admin/investigacion/editar_proyecto', [
            'title' => 'Editar Proyecto',
            'proyecto' => $proyecto
        ]);
    }

    public function editarProyectoVinculacion($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $proyecto = $this->proyectoModel->obtenerVinculacionPorId($id);

        if (!$proyecto) {
            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }

        $this->render('admin/vinculacion/editar_proyecto', [
            'title' => 'Editar Proyecto',
            'proyecto' => $proyecto
        ]);
    }

    public function editarPublicacion($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $publicacion = $this->publicacionModel->obtenerPorId($id);

        if (!$publicacion) {
            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }

        $this->render('admin/investigacion/editar_publicacion', [
            'title' => 'Editar Publicación',
            'publicacion' => $publicacion
        ]);
    }

    public function editarPonencia($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $ponencia = $this->ponenciaModel->obtenerPorId($id);

        if (!$ponencia) {
            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }

        $this->render('admin/investigacion/editar_ponencia', [
            'title' => 'Editar Ponencia',
            'ponencia' => $ponencia
        ]);
    }

    public function editarCarrera($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        // Traemos la carrera junto con el nombre del proyecto
        $carrera = $this->carreraModel->obtenerPorIdConProyecto($id);

        if (!$carrera) {
            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }

        $this->render('admin/investigacion/editar_carrera_proyecto', [
            'title' => 'Editar Carrera',
            'carrera' => $carrera
        ]);
    }

    public function editarCarreraV($id)
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        // Traemos la carrera junto con el nombre del proyecto
        $carrera = $this->carreraModel->obtenerPorIdConProyecto($id);

        if (!$carrera) {
            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }

        $this->render('admin/vinculacion/editar_carrera_proyectoV', [
            'title' => 'Editar Carrera',
            'carrera' => $carrera
        ]);
    }

    /* Metodos para Actualziar */

    public function actualizarProyecto()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id_proyecto'] ?? null;

            if (!$id) {
                header("Location: " . $this->basePath . "/admin/investigacion");
                exit();
            }

            $actualizado = $this->proyectoModel->actualizarInvestigacion($id, $_POST);

            if ($actualizado) {
                $_SESSION['success'] = "Proyecto actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar proyecto";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function actualizarProyectoVinculacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id_proyecto'] ?? null;

            if (!$id) {
                header("Location: " . $this->basePath . "/admin/vinculacion");
                exit();
            }

            $actualizado = $this->proyectoModel->actualizarVinculacion($id, $_POST);

            if ($actualizado) {
                $_SESSION['success'] = "Proyecto actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar proyecto";
            }

            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }
    }

    public function actualizarPublicacion()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id_publicacion'] ?? null;

            if (!$id) {
                header("Location: " . $this->basePath . "/admin/investigacion");
                exit();
            }

            $actualizado = $this->publicacionModel->actualizar($id, $_POST);

            if ($actualizado) {
                $_SESSION['success'] = "Publicación actualizada correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar publicación";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function actualizarPonencia()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id_ponencia'] ?? null;

            if (!$id) {
                header("Location: " . $this->basePath . "/admin/investigacion");
                exit();
            }

            $actualizado = $this->ponenciaModel->actualizar($id, $_POST);

            if ($actualizado) {
                $_SESSION['success'] = "Ponencia actualizada correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar ponencia";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function actualizarCarrera()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $carrera = $_POST['carrera'] ?? '';
            $nro_estudiantes = $_POST['nro_estudiantes'] ?? '';

            if (!$id) {
                $_SESSION['error'] = "ID de carrera inválido";
                header("Location: " . $this->basePath . "/admin/investigacion");
                exit();
            }

            $data = [
                'carrera' => $carrera,
                'nro_estudiantes' => $nro_estudiantes
            ];

            $actualizado = $this->carreraModel->actualizarCarrera($id, $data);

            if ($actualizado) {
                $_SESSION['success'] = "Carrera actualizada correctamente";
            } else {
                $_SESSION['error'] = "No se pudo actualizar la carrera";
            }

            header("Location: " . $this->basePath . "/admin/investigacion");
            exit();
        }
    }

    public function actualizarCarreraV()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $carrera = $_POST['carrera'] ?? '';
            $nro_estudiantes = $_POST['nro_estudiantes'] ?? '';

            if (!$id) {
                $_SESSION['error'] = "ID de carrera inválido";
                header("Location: " . $this->basePath . "/admin/vinculacion");
                exit();
            }

            $data = [
                'carrera' => $carrera,
                'nro_estudiantes' => $nro_estudiantes
            ];

            $actualizado = $this->carreraModel->actualizarCarrera($id, $data);

            if ($actualizado) {
                $_SESSION['success'] = "Carrera actualizada correctamente";
            } else {
                $_SESSION['error'] = "No se pudo actualizar la carrera";
            }

            header("Location: " . $this->basePath . "/admin/vinculacion");
            exit();
        }
    }

    public function configuracionPlanificacion()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $tab = (string) ($_GET['tab'] ?? 'procesos');
        if (!in_array($tab, ['procesos', 'ejes', 'objetivos', 'estrategias'], true)) {
            $tab = 'procesos';
        }

        $editProcesoId = (int) ($_GET['edit_proceso'] ?? 0);
        $editEjeId = (int) ($_GET['edit_eje'] ?? 0);
        $editObjetivoId = (int) ($_GET['edit_objetivo'] ?? 0);
        $editEstrategiaId = (int) ($_GET['edit_estrategia'] ?? 0);

        $estrategias = $this->configPlanModel->obtenerEstrategias();
        foreach ($estrategias as &$estrategiaItem) {
            $estrategiaItem['metas'] = $this->configPlanModel->obtenerMetasPorEstrategia((int) ($estrategiaItem['id'] ?? 0));
        }
        unset($estrategiaItem);

        $this->render('admin/plan_estrategico/configuracion', [
            'title' => 'Configuracion de Planificacion',
            'activeTab' => $tab,
            'procesos' => $this->configPlanModel->obtenerProcesos(),
            'ejes' => $this->configPlanModel->obtenerEjes(),
            'objetivos' => $this->configPlanModel->obtenerObjetivos(),
            'estrategias' => $estrategias,
            'editProceso' => $editProcesoId > 0 ? $this->configPlanModel->obtenerProcesoPorId($editProcesoId) : null,
            'editEje' => $editEjeId > 0 ? $this->configPlanModel->obtenerEjePorId($editEjeId) : null,
            'editObjetivo' => $editObjetivoId > 0 ? $this->configPlanModel->obtenerObjetivoPorId($editObjetivoId) : null,
            'editEstrategia' => $editEstrategiaId > 0 ? $this->configPlanModel->obtenerEstrategiaDetallePorId($editEstrategiaId) : null,
        ]);
    }

    public function guardarProcesoConfiguracion()
    {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del proceso es obligatorio.';
            $this->redirectConfiguracion('procesos');
        }

        $ok = $this->configPlanModel->crearProceso($nombre, $this->toBoolEstado($_POST['estado'] ?? '1'));
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Proceso creado correctamente.'
            : 'No se pudo crear el proceso.';

        $this->redirectConfiguracion('procesos');
    }

    public function actualizarProcesoConfiguracion()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));

        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos invalidos para actualizar el proceso.';
            $this->redirectConfiguracion('procesos');
        }

        $ok = $this->configPlanModel->actualizarProceso($id, $nombre, $this->toBoolEstado($_POST['estado'] ?? '1'));
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Proceso actualizado correctamente.'
            : 'No se pudo actualizar el proceso.';

        $this->redirectConfiguracion('procesos');
    }

    public function eliminarProcesoConfiguracion($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Proceso invalido.';
            $this->redirectConfiguracion('procesos');
        }

        if ($this->configPlanModel->procesoEnUso($id)) {
            $_SESSION['error'] = 'No se puede eliminar el proceso porque ya se usa en POA. Marquelo como inactivo.';
            $this->redirectConfiguracion('procesos');
        }

        $ok = $this->configPlanModel->eliminarProceso($id);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Proceso eliminado correctamente.'
            : 'No se pudo eliminar el proceso.';

        $this->redirectConfiguracion('procesos');
    }

    public function guardarEjeConfiguracion()
    {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del eje es obligatorio.';
            $this->redirectConfiguracion('ejes');
        }

        $ok = $this->configPlanModel->crearEje([
            'nombre' => $nombre,
            'observaciones' => (string) ($_POST['observaciones'] ?? ''),
            'estado' => $this->toBoolEstado($_POST['estado'] ?? '1'),
        ]);

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Eje estrategico registrado correctamente.'
            : 'No se pudo registrar el eje estrategico.';

        $this->redirectConfiguracion('ejes');
    }

    public function actualizarEjeConfiguracion()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos invalidos para actualizar el eje.';
            $this->redirectConfiguracion('ejes');
        }

        $ok = $this->configPlanModel->actualizarEje($id, [
            'nombre' => $nombre,
            'observaciones' => (string) ($_POST['observaciones'] ?? ''),
            'estado' => $this->toBoolEstado($_POST['estado'] ?? '1'),
        ]);

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Eje estrategico actualizado correctamente.'
            : 'No se pudo actualizar el eje estrategico.';

        $this->redirectConfiguracion('ejes');
    }

    public function eliminarEjeConfiguracion($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Eje invalido.';
            $this->redirectConfiguracion('ejes');
        }

        if ($this->configPlanModel->ejeEnUsoEnPoaActivo($id)) {
            $_SESSION['error'] = 'No se puede eliminar el eje porque esta referenciado por POA activos. Marquelo como inactivo.';
            $this->redirectConfiguracion('ejes');
        }

        $ok = $this->configPlanModel->eliminarEje($id);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Eje estrategico eliminado correctamente.'
            : 'No se pudo eliminar el eje estrategico.';

        $this->redirectConfiguracion('ejes');
    }

    public function guardarObjetivoConfiguracion()
    {
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $ejeId = (int) ($_POST['eje_id'] ?? 0);

        if ($codigo === '' || $nombre === '' || $ejeId <= 0) {
            $_SESSION['error'] = 'Complete codigo, nombre y eje para registrar el objetivo.';
            $this->redirectConfiguracion('objetivos');
        }

        $ok = $this->configPlanModel->crearObjetivo([
            'codigo' => $codigo,
            'nombre' => $nombre,
            'eje_id' => $ejeId,
            'observaciones' => (string) ($_POST['observaciones'] ?? ''),
            'estado' => $this->toBoolEstado($_POST['estado'] ?? '1'),
        ]);

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Objetivo estrategico registrado correctamente.'
            : 'No se pudo registrar el objetivo estrategico. Verifique que el codigo no este duplicado.';

        $this->redirectConfiguracion('objetivos');
    }

    public function actualizarObjetivoConfiguracion()
    {
        $id = (int) ($_POST['id'] ?? 0);
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $ejeId = (int) ($_POST['eje_id'] ?? 0);

        if ($id <= 0 || $codigo === '' || $nombre === '' || $ejeId <= 0) {
            $_SESSION['error'] = 'Datos invalidos para actualizar el objetivo.';
            $this->redirectConfiguracion('objetivos');
        }

        $ok = $this->configPlanModel->actualizarObjetivo($id, [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'eje_id' => $ejeId,
            'observaciones' => (string) ($_POST['observaciones'] ?? ''),
            'estado' => $this->toBoolEstado($_POST['estado'] ?? '1'),
        ]);

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Objetivo estrategico actualizado correctamente.'
            : 'No se pudo actualizar el objetivo estrategico.';

        $this->redirectConfiguracion('objetivos');
    }

    public function eliminarObjetivoConfiguracion($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Objetivo invalido.';
            $this->redirectConfiguracion('objetivos');
        }

        if ($this->configPlanModel->objetivoEnUsoEnPoaActivo($id)) {
            $_SESSION['error'] = 'No se puede eliminar el objetivo porque esta referenciado por POA activos. Marquelo como inactivo.';
            $this->redirectConfiguracion('objetivos');
        }

        $ok = $this->configPlanModel->eliminarObjetivo($id);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Objetivo estrategico eliminado correctamente.'
            : 'No se pudo eliminar el objetivo estrategico.';

        $this->redirectConfiguracion('objetivos');
    }

    public function guardarEstrategiaConfiguracion()
    {
        $payload = $this->buildEstrategiaPayloadFromPost();
        if (!empty($payload['error'])) {
            $_SESSION['error'] = $payload['error'];
            $this->redirectConfiguracion('estrategias');
        }

        $ok = $this->configPlanModel->crearEstrategiaConDetalle(
            $payload['estrategia'],
            $payload['linea_base'],
            $payload['metas']
        );

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Estrategia, linea base y metas registradas correctamente.'
            : 'No se pudo registrar la estrategia. Verifique codigo unico y metas por anio no duplicadas.';

        $this->redirectConfiguracion('estrategias');
    }

    public function actualizarEstrategiaConfiguracion()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Estrategia invalida para actualizar.';
            $this->redirectConfiguracion('estrategias');
        }

        $payload = $this->buildEstrategiaPayloadFromPost();
        if (!empty($payload['error'])) {
            $_SESSION['error'] = $payload['error'];
            $this->redirectConfiguracion('estrategias');
        }

        $ok = $this->configPlanModel->actualizarEstrategiaConDetalle(
            $id,
            $payload['estrategia'],
            $payload['linea_base'],
            $payload['metas']
        );

        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Estrategia y detalle actualizados correctamente.'
            : 'No se pudo actualizar la estrategia.';

        $this->redirectConfiguracion('estrategias');
    }

    public function eliminarEstrategiaConfiguracion($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Estrategia invalida.';
            $this->redirectConfiguracion('estrategias');
        }

        if ($this->configPlanModel->estrategiaEnUsoEnPoaActivo($id)) {
            $_SESSION['error'] = 'No se puede eliminar la estrategia porque esta en uso por POA activos. Marquela como inactiva.';
            $this->redirectConfiguracion('estrategias');
        }

        $ok = $this->configPlanModel->eliminarEstrategia($id);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Estrategia eliminada correctamente.'
            : 'No se pudo eliminar la estrategia.';

        $this->redirectConfiguracion('estrategias');
    }

    private function buildEstrategiaPayloadFromPost(): array
    {
        $objetivoId = (int) ($_POST['objetivo_estrategico_id'] ?? 0);
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $porcentajePartida = (float) ($_POST['porcentaje_partida'] ?? 0);

        if ($objetivoId <= 0 || $codigo === '' || $nombre === '') {
            return ['error' => 'Complete objetivo, codigo y nombre de la estrategia.'];
        }

        $metas = $this->parseMetasFromPost();
        if (isset($metas['error'])) {
            return ['error' => $metas['error']];
        }

        if (empty($metas)) {
            return ['error' => 'Debe agregar al menos una meta anual.'];
        }

        return [
            'estrategia' => [
                'objetivo_estrategico_id' => $objetivoId,
                'codigo' => $codigo,
                'nombre' => $nombre,
                'observaciones' => (string) ($_POST['observaciones'] ?? ''),
                'estado' => $this->toBoolEstado($_POST['estado'] ?? '1'),
            ],
            'linea_base' => [
                'porcentaje_partida' => $porcentajePartida,
                'observaciones' => (string) ($_POST['linea_base_observaciones'] ?? ''),
            ],
            'metas' => $metas,
        ];
    }

    private function parseMetasFromPost(): array
    {
        $anios = (array) ($_POST['meta_anio'] ?? []);
        $porcentajes = (array) ($_POST['meta_porcentaje'] ?? []);
        $observaciones = (array) ($_POST['meta_observaciones'] ?? []);

        $metas = [];
        $aniosVista = [];

        $total = max(count($anios), count($porcentajes), count($observaciones));
        for ($i = 0; $i < $total; $i++) {
            $anio = (int) ($anios[$i] ?? 0);
            $porcentaje = (float) ($porcentajes[$i] ?? 0);
            $obs = trim((string) ($observaciones[$i] ?? ''));

            if ($anio <= 0 && $porcentaje <= 0 && $obs === '') {
                continue;
            }

            if ($anio <= 0) {
                return ['error' => 'Cada meta anual debe tener un anio valido.'];
            }

            if (isset($aniosVista[$anio])) {
                return ['error' => 'No puede registrar dos metas con el mismo anio para una estrategia.'];
            }

            $aniosVista[$anio] = true;
            $metas[] = [
                'anio' => $anio,
                'porcentaje_esperado' => $porcentaje,
                'observaciones' => $obs,
            ];
        }

        return $metas;
    }

    private function toBoolEstado($value): bool
    {
        return (string) $value !== '0';
    }

    private function redirectConfiguracion(string $tab): void
    {
        header('Location: ' . $this->basePath . '/admin/configuracion?tab=' . urlencode($tab));
        exit();
    }

    /* Metodos del Plan Estrategico */

    public function planEstrategicoIndex()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $poa = $this->poaModel->obtenerTodos() ?? [];
        $estrategias = $this->poaModel->obtenerEstrategiasCatalogo() ?? [];
        $sedes = $this->poaModel->obtenerSedes() ?? [];
        $procesos = $this->poaModel->obtenerProcesos() ?? [];

        $selectedPoaId = (int) ($_GET['poa'] ?? 0);
        $selectedPoa = null;
        $selectedPoaActividades = [];
        if ($selectedPoaId > 0) {
            $selectedPoa = $this->poaModel->obtenerPorId($selectedPoaId);
            if ($selectedPoa) {
                $selectedPoaActividades = $this->actividadModel->obtenerPorPoaId($selectedPoaId) ?? [];
            }
        }

        $canCreatePoa = $this->hasPermission('poa', 'create');
        $canEditPoa = $this->hasPermission('poa', 'edit');
        $canDeletePoa = $this->hasPermission('poa', 'delete');

        $viewName = $selectedPoaId > 0 && $selectedPoa
            ? 'admin/plan_estrategico/gestionar_poa'
            : 'admin/plan_estrategico/pedi_poa_index';

        $this->render($viewName, [
            'title' => 'Planificación Estratégica',
            'poa' => $poa,
            'estrategias' => $estrategias,
            'sedes' => $sedes,
            'procesos' => $procesos,
            'selectedPoaId' => $selectedPoaId,
            'selectedPoa' => $selectedPoa,
            'selectedPoaActividades' => $selectedPoaActividades,
            'canCreatePoa' => $canCreatePoa,
            'canEditPoa' => $canEditPoa,
            'canDeletePoa' => $canDeletePoa,
        ]);
    }

    public function pediIndex()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pedi = $this->pediModel->obtenerTodos() ?? [];
        $canCreatePedi = $this->hasPermission('pedi', 'create');
        $canEditPedi = $this->hasPermission('pedi', 'edit');
        $canDeletePedi = $this->hasPermission('pedi', 'delete');

        $this->render('admin/pedi/index', [
            'title' => 'PEDI',
            'pedi' => $pedi,
            'canCreatePedi' => $canCreatePedi,
            'canEditPedi' => $canEditPedi,
            'canDeletePedi' => $canDeletePedi,
        ]);
    }

    public function poaIndex()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $poa = $this->actividadModel->obtenerTodos() ?? [];
        $canCreatePoa = $this->hasPermission('poa', 'create');
        $canEditPoa = $this->hasPermission('poa', 'edit');
        $canDeletePoa = $this->hasPermission('poa', 'delete');

        $this->render('admin/poa/index', [
            'title' => 'POA',
            'poa' => $poa,
            'canCreatePoa' => $canCreatePoa,
            'canEditPoa' => $canEditPoa,
            'canDeletePoa' => $canDeletePoa,
        ]);
    }

    public function guardarPoa()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $estrategiaId = (int) ($_POST['estrategia_id'] ?? 0);
        $sedeId = (int) ($_POST['sede_id'] ?? 0);
        $anioPlanificacion = (int) ($_POST['anio_planificacion'] ?? date('Y'));
        $presupuestoTotal = (float) ($_POST['presupuesto_total_aprobado'] ?? 0);
        $procesosIds = array_map('intval', (array) ($_POST['procesos_ids'] ?? []));

        if ($estrategiaId <= 0 || $sedeId <= 0 || $anioPlanificacion <= 0) {
            $_SESSION['error'] = 'Debe completar estrategia, sede y año para crear el POA.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        if (empty($procesosIds)) {
            $_SESSION['error'] = 'Debe seleccionar al menos un proceso o área responsable.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $data = [
            'estrategia_id' => $estrategiaId,
            'sede_id' => $sedeId,
            'anio_planificacion' => $anioPlanificacion,
            'presupuesto_total_aprobado' => $presupuestoTotal,
            'estado_aprobacion' => $_POST['estado_aprobacion'] ?? 'borrador',
            'estado' => true,
            'observaciones' => $_POST['observaciones'] ?? '',
            'procesos_ids' => $procesosIds,
        ];

        $nuevoId = (int) $this->poaModel->crear($data);
        if ($nuevoId <= 0) {
            $_SESSION['error'] = 'No se pudo crear el POA. Verifique si ya existe esa combinación de estrategia, sede y año.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $_SESSION['success'] = 'Cabecera POA creada correctamente. Ahora puede agregar actividades/proyectos.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico?poa=" . $nuevoId);
        exit();
    }

    public function actualizarPoa()
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'POA inválido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $poaActual = $this->poaModel->obtenerPorId($id);
        if (!$poaActual) {
            $_SESSION['error'] = 'POA no encontrado.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $presupuestoTotal = (float) ($_POST['presupuesto_total_aprobado'] ?? 0);
        $presupuestoUsado = $this->actividadModel->obtenerPresupuestoUsadoPorPoa($id);
        if ($presupuestoTotal < $presupuestoUsado) {
            $_SESSION['error'] = 'El presupuesto total aprobado no puede ser menor al presupuesto ya asignado en actividades.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico?poa=" . $id);
            exit();
        }

        $procesosIds = array_map('intval', (array) ($_POST['procesos_ids'] ?? []));
        if (empty($procesosIds)) {
            $_SESSION['error'] = 'Debe seleccionar al menos un proceso o área responsable.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico?poa=" . $id);
            exit();
        }

        $data = [
            'estrategia_id' => (int) ($_POST['estrategia_id'] ?? 0),
            'sede_id' => (int) ($_POST['sede_id'] ?? 0),
            'anio_planificacion' => (int) ($_POST['anio_planificacion'] ?? date('Y')),
            'presupuesto_total_aprobado' => $presupuestoTotal,
            'estado_aprobacion' => $_POST['estado_aprobacion'] ?? 'borrador',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => true,
            'procesos_ids' => $procesosIds,
        ];

        $ok = $this->poaModel->actualizar($id, $data);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Cabecera POA actualizada correctamente.'
            : 'No se pudo actualizar el POA.';

        header("Location: " . $this->basePath . "/admin/plan-estrategico?poa=" . $id);
        exit();
    }

    public function guardarActividad()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $idPoa = (int) ($_POST['poa_id'] ?? 0);
        $procesosIds = array_values(array_filter(
            array_map('intval', (array) ($_POST['proceso_ids'] ?? [])),
            static function ($id) { return $id > 0; }
        ));
        $procesosIds = array_values(array_unique($procesosIds));

        if (count($procesosIds) === 0) {
            $_SESSION['error'] = 'Debe seleccionar al menos un proceso.';
            header("Location: " . $this->basePath . "/admin/actividad/create");
            exit();
        }

        if (count($procesosIds) > 3) {
            $_SESSION['error'] = 'Solo puede seleccionar hasta 3 procesos.';
            header("Location: " . $this->basePath . "/admin/actividad/create");
            exit();
        }

        if (!$idPoa) {
            $estrategiaId = !empty($_POST['estrategia_id']) ? (int) $_POST['estrategia_id'] : 0;
            $sedeId = !empty($_POST['sede_id']) ? (int) $_POST['sede_id'] : 0;
            if ($estrategiaId && $sedeId) {
                $poaData = [
                    'estrategia_id' => $estrategiaId,
                    'sede_id' => $sedeId,
                    'anio_planificacion' => (int) date('Y'),
                    'presupuesto_total_aprobado' => (float) ($_POST['presupuesto_asignado'] ?? 0),
                    'estado_aprobacion' => 'Aprobado',
                    'observaciones' => '',
                    'estado' => true,
                    'procesos_ids' => $procesosIds,
                ];
                $idPoa = $this->poaModel->crear($poaData);
                if (!$idPoa) {
                    $_SESSION['error'] = 'No se pudo crear el POA.';
                    header("Location: " . $this->basePath . "/admin/poa");
                    exit();
                }
            } else {
                $_SESSION['error'] = 'Debe seleccionar un POA o especificar estrategia y sede.';
                header("Location: " . $this->basePath . "/admin/poa");
                exit();
            }
        }

        $presupuestoActividad = (float) ($_POST['presupuesto_asignado'] ?? 0);
        $estadoForm = strtoupper(trim((string) ($_POST['estado'] ?? 'ACTIVO')));
        $fechaFin = trim((string) ($_POST['fecha_fin'] ?? ''));
        $estaCaducado = ($fechaFin !== '' && $fechaFin < date('Y-m-d'));
        $estadoActivo = $estaCaducado ? true : ($estadoForm !== 'INACTIVO');
        $observacionesGenerales = trim((string) ($_POST['observaciones'] ?? ''));
        $observacionAvance = trim((string) ($_POST['observaciones_avance'] ?? ''));

        $data = [
            'poa_id' => $idPoa,
            'tipo_registro' => $_POST['tipo_registro'] ?? 'Actividad',
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'laboratorio' => trim((string) ($_POST['laboratorio'] ?? '')),
            'meta' => trim((string) ($_POST['meta'] ?? '')),
            'presupuesto_asignado' => $presupuestoActividad,
            'presupuesto_ejecutado' => (float) ($_POST['presupuesto_ejecutado'] ?? 0),
            'avance_actividad' => (float) ($_POST['avance_actividad'] ?? 0),
            'observaciones' => $observacionesGenerales !== '' ? $observacionesGenerales : $observacionAvance,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $fechaFin,
            'estado' => $estadoActivo,
        ];

        if ($data['nombre'] === '') {
            $_SESSION['error'] = 'El nombre es obligatorio para la actividad/proyecto.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        if (!in_array($data['tipo_registro'], ['Proyecto', 'Actividad'], true)) {
            $data['tipo_registro'] = 'Actividad';
        }

        $nuevoId = $this->actividadModel->crear($data);
        if ($nuevoId <= 0) {
            $_SESSION['error'] = 'No se pudo registrar la actividad/proyecto.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $this->actividadModel->guardarCronogramaActividad($nuevoId, $_POST['cronograma'] ?? []);

        $_SESSION['success'] = 'Actividad/proyecto registrado correctamente.';
        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function actualizarActividad()
    {
        $id = (int) ($_POST['id_actividad'] ?? 0);
        $actividadAnterior = $this->actividadModel->obtenerPorId($id);

        if (!$actividadAnterior) {
            $_SESSION['error'] = 'Actividad no encontrada.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $idPoa = (int) ($actividadAnterior['poa_id'] ?? 0);
        $estrategiaId = !empty($_POST['estrategia_id']) ? (int) $_POST['estrategia_id'] : null;
        $sedeId = !empty($_POST['sede_id']) ? (int) $_POST['sede_id'] : null;
        $procesosIds = array_values(array_filter(
            array_map('intval', (array) ($_POST['proceso_ids'] ?? [])),
            static function ($pid) { return $pid > 0; }
        ));
        $procesosIds = array_values(array_unique($procesosIds));

        if (count($procesosIds) === 0) {
            $_SESSION['error'] = 'Debe seleccionar al menos un proceso.';
            header("Location: " . $this->basePath . "/admin/actividad/edit/" . $id);
            exit();
        }

        if (count($procesosIds) > 3) {
            $_SESSION['error'] = 'Solo puede seleccionar hasta 3 procesos.';
            header("Location: " . $this->basePath . "/admin/actividad/edit/" . $id);
            exit();
        }

        $poa = $this->poaModel->obtenerPorId($idPoa);
        if ($poa) {
            $poaData = [
                'estrategia_id' => $estrategiaId ?? (int) $poa['estrategia_id'],
                'sede_id' => $sedeId ?? (int) $poa['sede_id'],
                'anio_planificacion' => (int) ($poa['anio_planificacion'] ?? date('Y')),
                'presupuesto_total_aprobado' => (float) ($poa['presupuesto_total_aprobado'] ?? 0),
                'estado_aprobacion' => $poa['estado_aprobacion'] ?? 'Aprobado',
                'observaciones' => '',
                'estado' => true,
                'procesos_ids' => $procesosIds,
            ];
            $this->poaModel->actualizar($idPoa, $poaData);
        }

        $presupuestoActividad = (float) ($_POST['presupuesto_asignado'] ?? 0);
        $estadoForm = strtoupper(trim((string) ($_POST['estado'] ?? 'ACTIVO')));
        $fechaFin = trim((string) ($_POST['fecha_fin'] ?? ''));
        $estaCaducado = ($fechaFin !== '' && $fechaFin < date('Y-m-d'));
        $estadoActivo = $estaCaducado ? true : ($estadoForm !== 'INACTIVO');
        $observacionesGenerales = trim((string) ($_POST['observaciones'] ?? ''));
        $observacionAvance = trim((string) ($_POST['observaciones_avance'] ?? ''));

        $data = [
            'poa_id' => $idPoa,
            'tipo_registro' => $_POST['tipo_registro'] ?? 'Actividad',
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'laboratorio' => trim((string) ($_POST['laboratorio'] ?? '')),
            'meta' => trim((string) ($_POST['meta'] ?? '')),
            'presupuesto_asignado' => $presupuestoActividad,
            'presupuesto_ejecutado' => (float) ($_POST['presupuesto_ejecutado'] ?? 0),
            'avance_actividad' => (float) ($_POST['avance_actividad'] ?? 0),
            'observaciones' => $observacionesGenerales !== '' ? $observacionesGenerales : $observacionAvance,
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $fechaFin,
            'estado' => $estadoActivo,
        ];

        if (!in_array($data['tipo_registro'], ['Proyecto', 'Actividad'], true)) {
            $data['tipo_registro'] = 'Actividad';
        }

        $actualizado = $this->actividadModel->actualizar($id, $data);

        $this->actividadModel->guardarCronogramaActividad($id, $_POST['cronograma'] ?? []);

        $_SESSION[$actualizado ? 'success' : 'error'] = $actualizado
            ? 'Actividad/proyecto actualizado correctamente.'
            : 'No se pudo actualizar la actividad/proyecto.';

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function cronogramaActividad($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $actividad = $this->actividadModel->obtenerPorId((int) $id);
        if (!$actividad) {
            $_SESSION['error'] = 'Actividad no encontrada para cronograma.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $cronograma = $this->actividadModel->obtenerCronogramaPorActividad((int) $id);

        $this->render('admin/plan_estrategico/cronograma_actividad', [
            'title' => 'Cronograma mensual de actividad',
            'actividad' => $actividad,
            'cronograma' => $cronograma,
        ]);
    }

    public function guardarCronogramaActividad($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $actividad = $this->actividadModel->obtenerPorId((int) $id);
        if (!$actividad) {
            $_SESSION['error'] = 'Actividad no encontrada para guardar cronograma.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $entradas = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $entradas[$mes] = [
                'avance' => $_POST['avance'][$mes] ?? 0,
                'estado_semaforo' => $_POST['estado_semaforo'][$mes] ?? 'no_cumple',
                'observaciones' => $_POST['observaciones'][$mes] ?? '',
            ];
        }

        $ok = $this->actividadModel->guardarCronogramaActividad((int) $id, $entradas);
        $_SESSION[$ok ? 'success' : 'error'] = $ok
            ? 'Cronograma mensual actualizado correctamente.'
            : 'No se pudo actualizar el cronograma mensual.';

        header("Location: " . $this->basePath . "/admin/actividad/cronograma/" . (int) $id);
        exit();
    }

    public function crearPoa()
    {
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function editarPoa($id)
    {
        header("Location: " . $this->basePath . "/admin/plan-estrategico?poa=" . (int) $id);
        exit();
    }

    public function crearActividad()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $db = $this->pediModel->getConnection();
        $ejes = $db->query("SELECT id, nombre FROM ejes_estrategicos WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT id, nombre, eje_id FROM objetivos_estrategicos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT id, nombre, objetivo_estrategico_id FROM estrategias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $sedes = $db->query("SELECT id, nombre FROM sedes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $procesos = $db->query("SELECT id, nombre FROM procesos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $metasPedi = $this->obtenerMetasPediPorEstrategia($db);
        $poas = $this->poaModel->obtenerTodos();
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $actividad = [
            'id' => 0,
            'poa_id' => 0,
            'nombre' => '',
            'descripcion' => '',
            'laboratorio' => '',
            'meta' => '',
            'presupuesto_asignado' => '0',
            'presupuesto_ejecutado' => '0',
            'avance_actividad' => '0',
            'observaciones' => '',
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'estado' => 1,
            'eje_id' => '',
            'objetivo_id' => '',
            'estrategia_id' => '',
            'sede_id' => '',
            'proceso_ids' => '',
        ];

        $this->render('admin/plan_estrategico/editar_actividad', [
            'title' => 'Crear Actividad',
            'actividad' => $actividad,
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
            'sedes' => $sedes,
            'procesos' => $procesos,
            'metasPedi' => $metasPedi,
            'poas' => $poas,
            'cronograma' => [],
            'meses' => $meses,
        ]);
    }

    public function editarActividad($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $actividad = $this->actividadModel->obtenerPorId((int) $id);
        if (!$actividad) {
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = $this->pediModel->getConnection();
        $ejes = $db->query("SELECT id, nombre FROM ejes_estrategicos WHERE estado = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT id, nombre, eje_id FROM objetivos_estrategicos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT id, nombre, objetivo_estrategico_id FROM estrategias ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $sedes = $db->query("SELECT id, nombre FROM sedes ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $procesos = $db->query("SELECT id, nombre FROM procesos ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $metasPedi = $this->obtenerMetasPediPorEstrategia($db);

        $cronograma = $this->actividadModel->obtenerCronogramaPorActividad((int) $id);
        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        $this->render('admin/plan_estrategico/editar_actividad', [
            'title' => 'Editar Actividad',
            'actividad' => $actividad,
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
            'sedes' => $sedes,
            'procesos' => $procesos,
            'metasPedi' => $metasPedi,
            'poas' => [],
            'cronograma' => $cronograma,
            'meses' => $meses,
        ]);
    }

    private function obtenerMetasPediPorEstrategia(PDO $db): array
    {
        $anioActual = (int) date('Y');
        $fallbackSql = "SELECT
                            TRIM(p.objetivo_estrategia) AS estrategia_nombre,
                            SUBSTRING_INDEX(
                                GROUP_CONCAT(NULLIF(TRIM(pm.meta_texto), '') ORDER BY pm.anio DESC SEPARATOR '||'),
                                '||',
                                1
                            ) AS meta_texto
                        FROM pedi p
                        LEFT JOIN pedi_metas pm ON pm.pedi_id = p.id_pedi
                        WHERE TRIM(COALESCE(p.objetivo_estrategia, '')) <> ''
                        GROUP BY TRIM(p.objetivo_estrategia)";

        $fallbackStmt = $db->prepare($fallbackSql);
        $fallbackStmt->execute();
        $fallbackByStrategyName = [];
        foreach ($fallbackStmt->fetchAll(PDO::FETCH_ASSOC) as $fallbackRow) {
            $strategyName = trim((string) ($fallbackRow['estrategia_nombre'] ?? ''));
            $metaValue = trim((string) ($fallbackRow['meta_texto'] ?? ''));
            if ($strategyName === '' || $metaValue === '') {
                continue;
            }
            $fallbackByStrategyName[mb_strtolower($strategyName, 'UTF-8')] = $metaValue;
        }

        $sql = "SELECT
                    es.id AS estrategia_id,
                    es.nombre AS estrategia_nombre,
                    COALESCE(
                        CAST(ml.porcentaje_esperado AS CHAR),
                        pm.meta_texto,
                        ''
                    ) AS meta_pedi
                FROM estrategias es
                LEFT JOIN objetivos_estrategicos obj ON obj.id = es.objetivo_estrategico_id
                LEFT JOIN ejes_estrategicos eje ON eje.id = obj.eje_id
                LEFT JOIN metas_linea_base ml ON ml.id = (
                    SELECT ml2.id
                    FROM metas_linea_base ml2
                    INNER JOIN lineas_base lb2 ON lb2.id = ml2.linea_base_id
                    WHERE lb2.estrategia_id = es.id
                    ORDER BY (ml2.anio = :anio_actual) DESC, ml2.anio DESC, ml2.id DESC
                    LIMIT 1
                )
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
                        INNER JOIN pedi p ON pm.pedi_id = p.id_pedi
                        INNER JOIN ejes_estrategicos e ON e.nombre = p.eje
                        WHERE pm.eje_id IS NULL
                    ) m
                    GROUP BY m.eje_id
                ) pm ON pm.eje_id = eje.id
                ORDER BY es.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':anio_actual', $anioActual, PDO::PARAM_INT);
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $id = (int) ($row['estrategia_id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $meta = trim((string) ($row['meta_pedi'] ?? ''));
            if ($meta === '') {
                $strategyNameKey = mb_strtolower(trim((string) ($row['estrategia_nombre'] ?? '')), 'UTF-8');
                if ($strategyNameKey !== '' && isset($fallbackByStrategyName[$strategyNameKey])) {
                    $meta = trim((string) $fallbackByStrategyName[$strategyNameKey]);
                }
            }

            if ($meta !== '' && is_numeric($meta)) {
                $meta = rtrim(rtrim(number_format((float) $meta, 2, '.', ''), '0'), '.');
            }
            $map[$id] = $meta;
        }

        return $map;
    }

    public function crearPedi()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $this->render('admin/pedi/crear', [
            'title' => 'Crear PEDI',
        ]);
    }

    public function guardarPedi()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'avance' => 0,
            'avance_estrategia' => 0,
            'estado' => 'activo',
            'linea_base' => $_POST['linea_base'] ?? '',
            'meta_2024' => $_POST['meta_2024'] ?? '',
            'meta_2025' => $_POST['meta_2025'] ?? '',
            'meta_2026' => $_POST['meta_2026'] ?? '',
            'meta_2027' => $_POST['meta_2027'] ?? '',
            'meta_2028' => $_POST['meta_2028'] ?? '',
        ];

        try {
            $created = $this->pediModel->crear($data);
            $_SESSION[$created ? 'success' : 'error'] = $created
                ? 'PEDI creado correctamente.'
                : 'No se pudo crear el PEDI.';
        } catch (Exception $e) {
            error_log("Error guardarPedi: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar el PEDI.';
        }

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function editarPedi($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pedi = $this->pediModel->obtenerPorId((int)$id);
        if (!$pedi) {
            $_SESSION['error'] = 'PEDI no encontrado.';
            header("Location: " . $this->basePath . "/admin/pedi");
            exit();
        }

        $this->render('admin/pedi/editar', [
            'title' => 'Editar PEDI',
            'pedi' => $pedi,
        ]);
    }

    public function actualizarPedi()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id_pedi'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header("Location: " . $this->basePath . "/admin/pedi");
            exit();
        }

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'avance' => $_POST['avance'] ?? 0,
            'avance_estrategia' => $_POST['avance_estrategia'] ?? 0,
            'estado' => $_POST['estado'] ?? 'activo',
            'linea_base' => $_POST['linea_base'] ?? '',
            'meta_2024' => $_POST['meta_2024'] ?? '',
            'meta_2025' => $_POST['meta_2025'] ?? '',
            'meta_2026' => $_POST['meta_2026'] ?? '',
            'meta_2027' => $_POST['meta_2027'] ?? '',
            'meta_2028' => $_POST['meta_2028'] ?? '',
        ];

        try {
            $updated = $this->pediModel->actualizar($id, $data);
            $_SESSION[$updated ? 'success' : 'error'] = $updated
                ? 'PEDI actualizado correctamente.'
                : 'No se pudo actualizar el PEDI.';
        } catch (Exception $e) {
            error_log("Error actualizarPedi: " . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el PEDI.';
        }

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function eliminarPedi($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->pediModel->eliminar((int)$id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'PEDI eliminado correctamente.'
            : 'No se pudo eliminar el PEDI.';

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function eliminarPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->poaModel->eliminar((int) $id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'POA eliminado correctamente.'
            : 'No se pudo eliminar el POA.';

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarActividadPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $actividad = $this->actividadModel->obtenerPorId((int) $id);
        $poaId = (int) ($actividad['poa_id'] ?? 0);

        $eliminado = $this->actividadModel->eliminar((int) $id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Actividad/proyecto eliminado correctamente.'
            : 'No se pudo eliminar la actividad/proyecto.';

        $target = $this->basePath . '/admin/plan-estrategico';
        if ($poaId > 0) {
            $target .= '?poa=' . $poaId;
        }
        header("Location: " . $target);
        exit();
    }

    private function recalcularAvanceEstrategiaPedi($idPedi)
    {
        return;
    }

    public function eliminarProyectoInvestigacion($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->proyectoModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Proyecto eliminado correctamente'
            : 'No se pudo eliminar el proyecto';

        header("Location: " . $this->basePath . "/admin/investigacion");
        exit();
    }

    public function eliminarProyectoVinculacion($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->proyectoModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Proyecto eliminado correctamente'
            : 'No se pudo eliminar el proyecto';

        header("Location: " . $this->basePath . "/admin/vinculacion");
        exit();
    }

    public function eliminarPublicacion($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->publicacionModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Publicación eliminada correctamente'
            : 'No se pudo eliminar la publicación';

        header("Location: " . $this->basePath . "/admin/investigacion");
        exit();
    }

    public function eliminarPonencia($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->ponenciaModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Ponencia eliminada correctamente'
            : 'No se pudo eliminar la ponencia';

        header("Location: " . $this->basePath . "/admin/investigacion");
        exit();
    }

    public function eliminarCarreraInvestigacion($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->carreraModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Carrera eliminada correctamente'
            : 'No se pudo eliminar la carrera';

        header("Location: " . $this->basePath . "/admin/investigacion");
        exit();
    }

    public function eliminarCarreraVinculacion($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->carreraModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Carrera eliminada correctamente'
            : 'No se pudo eliminar la carrera';

        header("Location: " . $this->basePath . "/admin/vinculacion");
        exit();
    }

    /* Convenios */
    public function convenio()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $this->convenioModel->caducarVencidos();

        $convenios = $this->convenioModel->obtenerConvenios();

        $this->render('admin/convenio/index', [
            'title' => 'Convenios',
            'convenios' => $convenios
        ]);
    }

    public function mostrarCrearConvenio()
    {
        $this->render('admin/convenio/crear_convenio', [
            'title' => 'Crear Convenio'
        ]);
    }

    public function guardarConvenio()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = $_POST;
            $data['estado'] = 'Activo';
            $data['estado_convenio'] = 'vigente';

            $guardado = $this->convenioModel->crear($data);

            if ($guardado) {
                $_SESSION['success'] = "Convenio creado correctamente";
            } else {
                $_SESSION['error'] = "Error al crear convenio";
            }

            header("Location: " . $this->basePath . "/admin/convenio");
            exit();
        }
    }

    public function editarConvenio($id)
    {
        $convenio = $this->convenioModel->obtenerPorId($id);

        if (!$convenio) {
            header("Location: " . $this->basePath . "/admin/convenio");
            exit();
        }

        $this->render('admin/convenio/editar_convenio', [
            'title' => 'Editar Convenio',
            'convenio' => $convenio
        ]);
    }

    public function actualizarConvenio()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $id = $_POST['id_convenio'];

            $actualizado = $this->convenioModel->actualizar($id, $_POST);

            if ($actualizado) {
                $_SESSION['success'] = "Convenio actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar convenio";
            }

            header("Location: " . $this->basePath . "/admin/convenio");
            exit();
        }
    }

    public function eliminarConvenio($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->convenioModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Convenio eliminado correctamente'
            : 'No se pudo eliminar el convenio';

        header("Location: " . $this->basePath . "/admin/convenio");
        exit();
    }
}
