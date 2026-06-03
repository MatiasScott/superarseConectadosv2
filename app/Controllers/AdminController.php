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
require_once __DIR__ . '/../Models/ConvenioModel.php';
require_once __DIR__ . '/../Models/AdminDashboardModel.php';
require_once __DIR__ . '/../Models/AuthAccountModel.php';
require_once __DIR__ . '/../Models/PasswordResetModel.php';
require_once __DIR__ . '/../Models/AdminPermissionModel.php';
require_once __DIR__ . '/../Models/AuditLogModel.php';
require_once __DIR__ . '/../Models/AdminReportesModel.php';
require_once __DIR__ . '/../Helpers/AuthSecurity.php';
require_once __DIR__ . '/../Helpers/BasePath.php';

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
    private $convenioModel;
    private $dashboardModel;
    private $permissionModel;
    private $auditLogModel;
    private $reportesModel;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $this->basePath = BasePath::detect();

        $this->pasantiaModel = new PasantiaModel();
        $this->userModel = new UserModel();
        $this->proyectoModel = new ProyectoAdministracion();
        $this->carreraModel = new ProyectoEstudianteCarrera();
        $this->publicacionModel = new Publicacion();
        $this->ponenciaModel = new Ponencia();
        $this->pediModel = new PediModel();
        $this->poaModel = new PoaModel();
        $this->actividadModel = new PoaActividadModel();
        $this->convenioModel = new ConvenioModel();
        $this->dashboardModel = new AdminDashboardModel();
        $this->authAccountModel = new AuthAccountModel();
        $this->resetModel = new PasswordResetModel();
        $this->permissionModel = new AdminPermissionModel();
        $this->auditLogModel = new AuditLogModel();
        $this->reportesModel = new AdminReportesModel();

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

    public function configuracion()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pediModel = new PediModel();
        $pedi = $pediModel->obtenerTodos();

        $db = $pediModel->getConnection();
        $ejes = $db->query("SELECT * FROM eje_estrategico ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $areas = $db->query("SELECT * FROM area ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $sedes = $db->query("SELECT * FROM sede ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT o.*, e.nombre AS eje_nombre FROM objetivo_estrategico o LEFT JOIN eje_estrategico e ON o.eje_id = e.id ORDER BY o.nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT s.*, o.nombre AS objetivo_nombre FROM estrategia s LEFT JOIN objetivo_estrategico o ON s.objetivo_id = o.id ORDER BY s.nombre")->fetchAll(PDO::FETCH_ASSOC);
        $canCreateConfiguracion = $this->hasPermission('configuracion', 'create');
        $canEditConfiguracion = $this->hasPermission('configuracion', 'edit');
        $canDeleteConfiguracion = $this->hasPermission('configuracion', 'delete');

        $this->render('admin/configuracion/index', [
            'title' => 'Configuración del Sistema',
            'pedi' => $pedi,
            'ejes' => $ejes,
            'areas' => $areas,
            'sedes' => $sedes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
            'canCreateConfiguracion' => $canCreateConfiguracion,
            'canEditConfiguracion' => $canEditConfiguracion,
            'canDeleteConfiguracion' => $canDeleteConfiguracion,
        ]);
    }

    public function guardarEje()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del eje es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("INSERT INTO eje_estrategico (nombre, descripcion, estado) VALUES (?, ?, 'activo')")
            ->execute([$nombre, $descripcion]);

        $_SESSION['success'] = 'Eje estratégico creado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function actualizarEje()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id_eje'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de eje inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del eje es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("UPDATE eje_estrategico SET nombre = ?, descripcion = ? WHERE id = ?")
            ->execute([$nombre, $descripcion, $id]);

        $_SESSION['success'] = 'Eje estratégico actualizado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarEje($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID de eje inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM objetivo_estrategico WHERE eje_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} objetivo(s) dependen de este eje.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE eje_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de este eje.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("DELETE FROM eje_estrategico WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Eje estratégico eliminado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function guardarArea()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del área es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO area (nombre) VALUES (?)")->execute([$nombre]);

        $_SESSION['success'] = 'Área creada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function actualizarArea()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos inválidos.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE area SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);

        $_SESSION['success'] = 'Área actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarArea($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa WHERE area_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} POA(s) dependen de esta área.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE area_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de esta área.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("DELETE FROM area WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Área eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function guardarSede()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre de la sede es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO sede (nombre) VALUES (?)")->execute([$nombre]);

        $_SESSION['success'] = 'Sede creada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function actualizarSede()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos inválidos.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE sede SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);

        $_SESSION['success'] = 'Sede actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarSede($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE sede_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} actividad(es) POA dependen de esta sede.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("DELETE FROM sede WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Sede eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function guardarObjetivo()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $ejeId = !empty($_POST['eje_id']) ? (int)$_POST['eje_id'] : null;
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO objetivo_estrategico (eje_id, nombre, estado) VALUES (?, ?, 'activo')")->execute([$ejeId, $nombre]);

        $_SESSION['success'] = 'Objetivo Estratégico creado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function actualizarObjetivo()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $ejeId = !empty($_POST['eje_id']) ? (int)$_POST['eje_id'] : null;
        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos inválidos.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE objetivo_estrategico SET nombre = ?, eje_id = ? WHERE id = ?")->execute([$nombre, $ejeId, $id]);

        $_SESSION['success'] = 'Objetivo Estratégico actualizado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarObjetivo($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM estrategia WHERE objetivo_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} estrategia(s) dependen de este objetivo.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE objetivo_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de este objetivo.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("DELETE FROM objetivo_estrategico WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Objetivo Estratégico eliminado correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function guardarEstrategia()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $nombre = trim($_POST['nombre'] ?? '');
        $objetivoId = !empty($_POST['objetivo_id']) ? (int)$_POST['objetivo_id'] : null;
        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre es obligatorio.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO estrategia (objetivo_id, nombre, estado) VALUES (?, ?, 'activo')")->execute([$objetivoId, $nombre]);

        $_SESSION['success'] = 'Estrategia creada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function actualizarEstrategia()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $objetivoId = !empty($_POST['objetivo_id']) ? (int)$_POST['objetivo_id'] : null;
        if ($id <= 0 || $nombre === '') {
            $_SESSION['error'] = 'Datos inválidos.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE estrategia SET nombre = ?, objetivo_id = ? WHERE id = ?")->execute([$nombre, $objetivoId, $id]);

        $_SESSION['success'] = 'Estrategia actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarEstrategia($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inválido.';
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE estrategia_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} actividad(es) POA dependen de esta estrategia.";
            header("Location: " . $this->basePath . "/admin/configuracion");
            exit();
        }

        $db->prepare("DELETE FROM estrategia WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Estrategia eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function guardarConfiguracionPediModal()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pediModel = new PediModel();
        $id = (int) ($_POST['id_pedi'] ?? 0);

        $metas = $_POST['meta_modal'] ?? [];
        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'eje_id' => !empty($_POST['eje_id']) ? (int)$_POST['eje_id'] : null,
            'linea_base' => $_POST['linea_base_modal'] ?? '',
            'meta_2024' => $metas[2024] ?? '',
            'meta_2025' => $metas[2025] ?? '',
            'meta_2026' => $metas[2026] ?? '',
            'meta_2027' => $metas[2027] ?? '',
            'meta_2028' => $metas[2028] ?? '',
            'estado' => $_POST['estado'] ?? 'activo',
        ];

        try {
            if ($id > 0) {
                $pediModel->actualizar($id, $data);
                $_SESSION['success'] = 'PEDI actualizado correctamente.';
            } else {
                $data['avance'] = 0;
                $data['avance_estrategia'] = 0;
                $pediModel->crear($data);
                $_SESSION['success'] = 'PEDI creado correctamente.';
            }
        } catch (Exception $e) {
            error_log("Error guardar PEDI modal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar el PEDI.';
        }

        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
    }

    public function eliminarConfiguracionPedi($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pediModel = new PediModel();
        $eliminado = $pediModel->eliminar((int)$id);

        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'PEDI eliminado correctamente.'
            : 'No se pudo eliminar el PEDI.';

        header("Location: " . $this->basePath . "/admin/configuracion");
        exit();
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

        $db = $this->reportesModel->getConnection();
        $areas = $db->query("SELECT * FROM area ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

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
                'label' => 'Actividades POA',
                'description' => 'Reporte de actividades del POA con cronograma y presupuesto.',
            ],
        ];

        $db = $this->reportesModel->getConnection();
        $areas = $db->query("SELECT * FROM area ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Planificación',
            'moduleTitle' => 'Planificación',
            'sections' => $sections,
            'areas' => $areas,
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
        $downloadedAt = date('d/m/Y H:i:s');
        if ($format === 'pdf') {
            $html = $this->buildStyledReportHtml($reportTitle, (string) $label, $downloadedAt, $rows, 'pdf', $module);
            $paper = $module === 'planificacion_poa_actividades' ? 'A3' : 'A4';
            $this->renderPdfDownload('reporte_' . $module . '_' . date('Ymd_His') . '.pdf', $html, $paper, 'landscape');
        }

        $filename = 'reporte_' . $module . '_' . date('Ymd_His') . '.xlsx';
        $this->streamXlsxDownload($filename, $rows, null, (string) $label, $module);
    }

    private function buildStyledReportHtml($reportTitle, $moduleLabel, $downloadedAt, array $rows, $target = 'pdf', $module = '')
    {
        $target = strtolower((string) $target);
        $headers = !empty($rows) ? array_keys((array) $rows[0]) : [];
        $colCount = count($headers);

        $isExcel = $target === 'excel';
        $pagePadding = $isExcel ? '14px' : '16px';
        $bodySize = $isExcel ? '11px' : '9.5px';
        $headerSize = $isExcel ? '10px' : '8.5px';

        $moduleClass = $module ? 'report-' . preg_replace('/[^a-z0-9_-]/', '', strtolower($module)) : '';

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">';
        $html .= '<style>';
        $html .= '*{box-sizing:border-box;}';
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;font-size:' . $bodySize . ';padding:' . $pagePadding . ';margin:0;}';
        $html .= 'table{width:100%;border-collapse:collapse;border:1px solid #cbd5e1;}';
        $html .= 'th,td{border:1px solid #cbd5e1;padding:6px 7px;vertical-align:middle;}';
        $html .= 'th{background:#4c1d95;color:#ffffff;font-size:' . $headerSize . ';text-transform:uppercase;letter-spacing:0.4px;font-weight:700;}';
        $html .= 'td{color:#1e293b;}';
        $html .= 'tr:nth-child(even) td{background:#f1f5f9;}';
        $html .= '.empty{padding:16px;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:6px;font-size:12px;}';

        // Module-specific adjustments
        if ($moduleClass === 'report-planificacion_pedi') {
            $html .= 'table{border:2px solid #334155;border-radius:6px;}';
            $html .= 'th{background:#4c1d95;padding:7px 6px;font-size:7.5px;border-color:#334155;text-align:center;}';
            $html .= 'td{padding:5px 6px;font-size:8.5px;border-color:#e2e8f0;}';
            $html .= 'td:first-child{text-align:center;font-weight:700;width:22px;background:#f8fafc;}';
            $html .= 'td:nth-child(2){font-weight:600;color:#6d28d9;min-width:70px;}';
            $html .= 'td:nth-child(3){min-width:160px;}';
            $html .= 'td:nth-child(4){min-width:150px;}';
            $html .= 'td:nth-child(5),td:nth-child(6),td:nth-child(7),td:nth-child(8),td:nth-child(9),td:nth-child(10){text-align:center;font-size:8px;min-width:42px;padding:5px 3px;background:#fafafa;}';
            $html .= 'td:nth-child(11){text-align:center;font-weight:700;font-size:8px;text-transform:uppercase;min-width:55px;}';
            $html .= 'td:nth-child(11):before{content:"";}';
            $html .= 'tr:nth-child(even) td{background:#ffffff;}';
            $html .= 'tr:nth-child(even) td:first-child{background:#f8fafc;}';
            $html .= 'tr:hover td{background:#eef2ff;}';
        } elseif ($moduleClass === 'report-planificacion_poa_actividades') {
            $html .= 'table{border:2px solid #334155;border-radius:6px;}';
            $html .= 'th{background:#4c1d95;padding:5px 4px;font-size:6.5px;border-color:#334155;text-align:center;line-height:1.15;}';
            $html .= 'td{padding:3px 4px;font-size:6.5px;border-color:#e2e8f0;}';
            $html .= 'td:nth-child(1){text-align:center;width:22px;background:#f8fafc;}';
            $html .= 'td:nth-child(2){font-weight:600;color:#6d28d9;}';
            $html .= 'td:nth-child(9),td:nth-child(10),td:nth-child(11),td:nth-child(12),td:nth-child(13),td:nth-child(14),td:nth-child(15),td:nth-child(16),td:nth-child(17),td:nth-child(18),td:nth-child(19),td:nth-child(20){text-align:center;font-size:6px;padding:3px 2px;min-width:28px;}';
            $html .= 'td:nth-child(21),td:nth-child(22){text-align:center;font-weight:700;}';
            $html .= 'td:nth-child(24),td:nth-child(25){text-align:right;font-weight:600;}';
            $html .= 'td:nth-child(26){text-align:center;font-weight:700;}';
            $html .= 'th:nth-child(9),th:nth-child(10),th:nth-child(11),th:nth-child(12),th:nth-child(13),th:nth-child(14),th:nth-child(15),th:nth-child(16),th:nth-child(17),th:nth-child(18),th:nth-child(19),th:nth-child(20){text-align:center;font-size:6px;padding:4px 2px;}';
            $html .= 'tr:nth-child(even) td{background:#ffffff;}';
            $html .= 'tr:hover td{background:#eef2ff;}';
        } elseif ($moduleClass === 'report-planificacion_poa') {
            $html .= 'table{border:2px solid #334155;border-radius:6px;}';
            $html .= 'th{background:#4c1d95;padding:6px 5px;font-size:7px;border-color:#334155;text-align:center;}';
            $html .= 'td{padding:4px 5px;font-size:7.5px;border-color:#e2e8f0;}';
            $html .= 'td:nth-child(4){min-width:140px;}';
            $html .= 'td:nth-child(5){min-width:120px;}';
            $html .= 'td:nth-child(9){font-size:7px;color:#475569;}';
            $html .= 'td:nth-child(10),td:nth-child(11){text-align:right;font-weight:600;}';
            $html .= 'td:nth-child(12){text-align:center;font-weight:700;color:#6d28d9;}';
        } elseif ($colCount >= 12) {
            $html .= 'th,td{font-size:7px;padding:3px 4px;}';
            $html .= 'th{font-size:6.5px;}';
        } elseif ($colCount >= 8) {
            $html .= 'th,td{font-size:8px;padding:4px 5px;}';
            $html .= 'th{font-size:7px;}';
        }

        $html .= '</style></head><body>';

        // Head section (card-like for PDF)
        if (!$isExcel) {
            $html .= '<table style="border:none;margin-bottom:10px;"><tr><td style="border:none;padding:0;">';
            $html .= '<div style="background:#4c1d95;padding:14px 18px;border-radius:6px 6px 0 0;">';
            $html .= '<div style="font-size:17px;font-weight:700;color:#ffffff;margin:0;">' . htmlspecialchars((string) $reportTitle, ENT_QUOTES, 'UTF-8') . '</div>';
            $html .= '<div style="margin-top:5px;font-size:10px;color:#94a3b8;">';
            $html .= '<span style="margin-right:18px;"><strong style="color:#e2e8f0;">Módulo:</strong> ' . htmlspecialchars((string) $moduleLabel, ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '<span><strong style="color:#e2e8f0;">Descargado:</strong> ' . htmlspecialchars((string) $downloadedAt, ENT_QUOTES, 'UTF-8') . '</span>';
            $html .= '</div></div>';
            $html .= '</td></tr></table>';
        }

        // Table section
        if (empty($rows)) {
            $html .= '<div class="empty" style="margin-top:10px;">No hay datos para exportar.</div>';
        } elseif ($moduleClass === 'report-planificacion_poa_actividades') {
            $splitRows = $this->splitPoaActividadesRowsForExport($rows);
            $generalRows = $splitRows['general'] ?? [];
            $cronogramaRows = $splitRows['cronograma'] ?? [];

            $html .= '<div style="margin:8px 0 6px 0;font-size:11px;font-weight:700;color:#334155;">TABLA GENERAL</div>';
            $html .= $this->buildReportTableHtml($generalRows, false);

            $html .= '<div style="margin:14px 0 6px 0;font-size:11px;font-weight:700;color:#334155;">TABLA CRONOGRAMA</div>';
            $html .= $this->buildReportTableHtml($cronogramaRows, true);
        } else {
            $html .= $this->buildReportTableHtml($rows, false);
        }

        $html .= '</body></html>';

        return $html;
    }

    private function splitPoaActividadesRowsForExport(array $rows)
    {
        $monthKeys = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
        $generalRows = [];
        $cronogramaRows = [];

        foreach ($rows as $row) {
            $source = is_array($row) ? $row : (array) $row;

            $general = $source;
            foreach ($monthKeys as $monthKey) {
                unset($general[$monthKey]);
            }
            unset($general['AVANCE PLANIFICADO']);
            $generalRows[] = $general;

            $cronograma = [];

            foreach ($monthKeys as $monthKey) {
                $cronograma[$monthKey] = $source[$monthKey] ?? '0%';
            }

            $cronogramaRows[] = $cronograma;
        }

        return [
            'general' => $generalRows,
            'cronograma' => $cronogramaRows,
        ];
    }

    private function buildReportTableHtml(array $rows, $includeCronogramaHeader = false)
    {
        if (empty($rows)) {
            return '<div class="empty" style="margin-top:10px;">No hay datos para exportar.</div>';
        }

        $headers = array_keys((array) $rows[0]);
        $html = '<table><thead>';

        if ($includeCronogramaHeader) {
            $monthKeys = ['ENE','FEB','MAR','ABR','MAY','JUN','JUL','AGO','SEP','OCT','NOV','DIC'];
            $monthStart = null;
            $monthEnd = null;
            foreach ($headers as $i => $header) {
                if (in_array($header, $monthKeys, true)) {
                    if ($monthStart === null) {
                        $monthStart = $i;
                    }
                    $monthEnd = $i;
                }
            }

            if ($monthStart !== null) {
                $html .= '<tr>';
                for ($i = 0; $i < count($headers); $i++) {
                    if ($i === $monthStart) {
                        $colspan = $monthEnd - $monthStart + 1;
                        $html .= '<th colspan="' . $colspan . '" style="text-align:center;background:#4c1d95;color:#ffffff;font-size:8px;">CRONOGRAMA</th>';
                    } elseif ($i < $monthStart || $i > $monthEnd) {
                        $html .= '<th style="background:#4c1d95;"></th>';
                    }
                }
                $html .= '</tr>';
            }
        }

        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $item = is_array($row) ? $row : (array) $row;
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<td>' . htmlspecialchars((string) ($item[$header] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
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
                $fase = ((int) ($row['estado_fase_uno_completado'] ?? 0) === 1) ? 'Fase 2' : 'Fase 1';
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
                'fase' => ((int) ($row['estado_fase_uno_completado'] ?? 0) === 1) ? 'Fase 2' : 'Fase 1',
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

        // CRONOGRAMA merged header row for POA activities
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

        // Individual column headers
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
            'solicitudes' => 'Solicitudes de Restablecimiento',
            'configuracion' => 'Configuración',
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
            // Compatibilidad: instalaciones anteriores usaban plan_estrategico para PEDI/POA.
            $fallbackByModule = [
                'pedi' => 'plan_estrategico',
                'poa' => 'plan_estrategico',
            ];

            $fallback = $fallbackByModule[$moduleKey] ?? null;
            if ($fallback !== null && isset($matrix[$fallback])) {
                return !empty($matrix[$fallback][$action]);
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
        if (in_array($uri, ['/admin/pedi/create', '/admin/pedi/store'], true)) {
            return ['pedi', 'create'];
        }
        if (preg_match('#^/admin/pedi/edit/\d+$#', $uri) || $uri === '/admin/pedi/update') {
            return ['pedi', 'edit'];
        }
        if (preg_match('#^/admin/pedi/eliminar/\d+$#', $uri)) {
            return ['pedi', 'delete'];
        }

        if ($uri === '/admin/poa') {
            return ['poa', 'view'];
        }
        if (in_array($uri, ['/admin/poa/create', '/admin/poa/store', '/admin/actividad/create', '/admin/actividad/store'], true)) {
            return ['poa', 'create'];
        }
        if (preg_match('#^/admin/poa/edit/\d+$#', $uri) || $uri === '/admin/poa/update' || preg_match('#^/admin/actividad/edit/\d+$#', $uri) || $uri === '/admin/actividad/update' || $uri === '/admin/actividad/avance-update') {
            return ['poa', 'edit'];
        }
        if (preg_match('#^/admin/poa/eliminar/\d+$#', $uri) || preg_match('#^/admin/actividad/eliminar/\d+$#', $uri)) {
            return ['poa', 'delete'];
        }

        if ($uri === '/admin/configuracion') {
            return ['configuracion', 'view'];
        }
        if (strpos($uri, '/admin/configuracion/') === 0) {
            if ($method !== 'POST') {
                return ['configuracion', 'view'];
            }

            if ($uri === '/admin/configuracion/guardar-pedi-modal') {
                $idPedi = (int) ($_POST['id_pedi'] ?? 0);
                return ['configuracion', $idPedi > 0 ? 'edit' : 'create'];
            }

            if (preg_match('#^/admin/configuracion/eliminar-#', $uri)) {
                return ['configuracion', 'delete'];
            }

            if (preg_match('#^/admin/configuracion/actualizar-#', $uri)) {
                return ['configuracion', 'edit'];
            }

            if (preg_match('#^/admin/configuracion/guardar-#', $uri)) {
                return ['configuracion', 'create'];
            }

            return ['configuracion', 'edit'];
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

    /* === GESTI�"N DE CUENTAS ADMIN === */

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

    /* === OLVID�? MI CONTRASE�'A (admin) === */

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

    /* Metodos del Plan Estrategico */

    public function planEstrategicoIndex()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $this->render('admin/plan_estrategico/pedi_poa_index', [
            'title' => 'Planificación'
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

        $poa = $this->poaModel->obtenerTodos() ?? [];
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

    public function crearPedi()
    {
        $db = (new PediModel())->getConnection();
        $ejes = $db->query("SELECT * FROM eje_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT * FROM objetivo_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT * FROM estrategia WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/plan_estrategico/crear_pedi', [
            'title' => 'Crear PEDI',
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
        ]);
    }

    public function guardarPedi()
    {
        $model = new PediModel();

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'linea_base' => '',
            'meta_2024' => '',
            'meta_2024_pct' => 0,
            'meta_2025' => '',
            'meta_2025_pct' => 0,
            'meta_2026' => '',
            'meta_2026_pct' => 0,
            'meta_2027' => '',
            'meta_2027_pct' => 0,
            'meta_2028' => '',
            'meta_2028_pct' => 0,
            'avance' => 0,
            'avance_estrategia' => 0,
            'estado' => $_POST['estado'] ?? 'activo'
        ];

        $creado = $model->crear($data);

        if ($creado) {
            $db = $this->pediModel->getConnection();
            $nuevoId = (int)$db->lastInsertId();
            if ($nuevoId > 0) {
                $this->pediModel->recalcularAvanceObjetivoPorPediId($nuevoId);
            }
        }

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function editarPedi($id)
    {
        $model = new PediModel();
        $pedi = $model->obtenerPorId($id);
        $db = $model->getConnection();
        $ejes = $db->query("SELECT * FROM eje_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT * FROM objetivo_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT * FROM estrategia WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/plan_estrategico/editar_pedi', [
            'title' => 'Editar PEDI',
            'pedi' => $pedi,
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias
        ]);
    }

    public function actualizarPedi()
    {
        $model = new PediModel();

        $id = (int)($_POST['id_pedi'] ?? 0);
        $pediAnterior = $model->obtenerPorId($id);

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'],
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'],
            'linea_base' => $pediAnterior['linea_base'] ?? '',
            'meta_2024' => $pediAnterior['meta_2024'] ?? '',
            'meta_2024_pct' => (float)($pediAnterior['meta_2024_pct'] ?? 0),
            'meta_2025' => $pediAnterior['meta_2025'] ?? '',
            'meta_2025_pct' => (float)($pediAnterior['meta_2025_pct'] ?? 0),
            'meta_2026' => $pediAnterior['meta_2026'] ?? '',
            'meta_2026_pct' => (float)($pediAnterior['meta_2026_pct'] ?? 0),
            'meta_2027' => $pediAnterior['meta_2027'] ?? '',
            'meta_2027_pct' => (float)($pediAnterior['meta_2027_pct'] ?? 0),
            'meta_2028' => $pediAnterior['meta_2028'] ?? '',
            'meta_2028_pct' => (float)($pediAnterior['meta_2028_pct'] ?? 0),
            'avance' => (float)($pediAnterior['avance'] ?? 0),
            'avance_estrategia' => (float)($pediAnterior['avance_estrategia'] ?? 0),
            'estado' => $pediAnterior['estado'] ?? 'activo'
        ];

        $model->actualizar($id, $data);

        if ($id > 0) {
            $this->pediModel->recalcularAvanceObjetivoPorPediId($id);
            if (!empty($pediAnterior['id_pedi'])) {
                $this->pediModel->recalcularAvanceObjetivoPorPediId((int)$pediAnterior['id_pedi']);
            }
        }

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function crearPoa()
    {
        $pediModel = new PediModel();
        $pedi = $pediModel->obtenerTodos();

        $this->render('admin/plan_estrategico/crear_poa', [
            'title' => 'Crear POA',
            'pedi' => $pedi
        ]);
    }

    public function guardarPoa()
    {
        $model = new PoaModel();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $data = [
            'id_pedi' => $_POST['id_pedi'] ?? null,
            'nombre_area' => $_POST['nombre_area'] ?? '',
            'presupuesto_anual' => $_POST['presupuesto_anual'] ?? 0,
            'estado_actividad' => $_POST['estado_actividad'] ?? 'no ejecutada',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => $_POST['estado'] ?? 'activo'
        ];

        $model->crear($data);

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function editarPoa($id)
    {
        $poaModel = new PoaModel();
        $pediModel = new PediModel();

        $poa = $poaModel->obtenerPorId($id);
        $pedi = $pediModel->obtenerTodos();

        $this->render('admin/plan_estrategico/editar_poa', [
            'title' => 'Editar POA',
            'poa' => $poa,
            'pedi' => $pedi
        ]);
    }

    public function actualizarPoa()
    {
        $model = new PoaModel();
        $actividadModel = new PoaActividadModel();

        $id = (int) ($_POST['id_poa'] ?? 0);
        $poaActual = $model->obtenerPorId($id);

        if (!$poaActual) {
            $_SESSION['error'] = 'POA no encontrado.';
            header("Location: " . $this->basePath . "/admin/pedi");
            exit();
        }

        $presupuestoAnual = (float) ($_POST['presupuesto_anual'] ?? 0);
        $presupuestoUsado = $actividadModel->obtenerPresupuestoUsadoPorPoa($id);
        if ($presupuestoAnual < $presupuestoUsado) {
            $_SESSION['error'] = 'El presupuesto anual del POA no puede ser menor al presupuesto ya asignado en actividades.';
            header("Location: " . $this->basePath . "/admin/poa/edit/" . $id);
            exit();
        }

        $data = [
            'id_pedi' => (int) ($_POST['id_pedi'] ?? 0),
            'nombre_area' => $_POST['nombre_area'] ?? '',
            'presupuesto_anual' => $presupuestoAnual,
            'estado_actividad' => $_POST['estado_actividad'] ?? 'no ejecutada',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];

        $model->actualizar($id, $data);

        $this->recalcularAvanceEstrategiaPedi((int) $data['id_pedi']);
        if ((int) $poaActual['id_pedi'] !== (int) $data['id_pedi']) {
            $this->recalcularAvanceEstrategiaPedi((int) $poaActual['id_pedi']);
        }

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function crearActividad()
    {
        $db = (new PediModel())->getConnection();
        $areas = $db->query("SELECT * FROM area ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $sedes = $db->query("SELECT * FROM sede ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $ejes = $db->query("SELECT * FROM eje_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT * FROM objetivo_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT * FROM estrategia WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $metasEje = $db->query("SELECT pm.eje_id, pm.meta_texto, pm.porcentaje FROM pedi_metas pm WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NOT NULL UNION SELECT e.id AS eje_id, pm.meta_texto, pm.porcentaje FROM pedi_metas pm JOIN pedi p ON pm.pedi_id = p.id_pedi JOIN eje_estrategico e ON p.eje = e.nombre WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/plan_estrategico/crear_actividad', [
            'title' => 'Crear Actividad',
            'areas' => $areas,
            'sedes' => $sedes,
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
            'metasEje' => $metasEje
        ]);
    }

    public function guardarActividad()
    {
        $model = new PoaActividadModel();
        $db = (new PediModel())->getConnection();

        $avanceEjecutado = (float) ($_POST['avance_ejecutado'] ?? 0);
        if ($avanceEjecutado < 0) {
            $avanceEjecutado = 0;
        }
        if ($avanceEjecutado > 100) {
            $avanceEjecutado = 100;
        }

        $ejeId = !empty($_POST['eje_id']) ? (int) $_POST['eje_id'] : null;
        $metaPedi = null;
        if ($ejeId) {
            $stmt = $db->prepare("SELECT meta_texto FROM pedi_metas WHERE eje_id = ? AND anio = YEAR(CURDATE()) LIMIT 1");
            $stmt->execute([$ejeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $stmt = $db->prepare("SELECT pm.meta_texto FROM pedi_metas pm JOIN pedi p ON pm.pedi_id = p.id_pedi JOIN eje_estrategico e ON p.eje = e.nombre AND e.id = ? WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NULL LIMIT 1");
                $stmt->execute([$ejeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $metaPedi = $row ? $row['meta_texto'] : null;
        }

        $cronogramaMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $cronograma = [];
        foreach ($cronogramaMeses as $mes) {
            $campo = $mes . '_pct';
            $valor = $_POST[$campo] ?? 0;
            $cronograma[$campo] = is_numeric($valor) ? (float) $valor : 0;
        }

        $data = [
            'eje_id' => $ejeId,
            'objetivo_id' => !empty($_POST['objetivo_id']) ? (int) $_POST['objetivo_id'] : null,
            'estrategia_id' => !empty($_POST['estrategia_id']) ? (int) $_POST['estrategia_id'] : null,
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'nombre_actividad' => $_POST['nombre_actividad'] ?? '',
            'meta' => $metaPedi ?? '',
            'area_id' => !empty($_POST['area_id']) ? (int) $_POST['area_id'] : null,
            'sede_id' => !empty($_POST['sede_id']) ? (int) $_POST['sede_id'] : null,
            'laboratorio' => $_POST['laboratorio'] ?? '',
            'sede' => $_POST['sede'] ?? '',
            'presupuesto_planificado' => (float) ($_POST['presupuesto_planificado'] ?? 0),
            'presupuesto_ejecutado' => (float) ($_POST['presupuesto_ejecutado'] ?? 0),
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'avance' => 0,
            'avance_ejecutado' => $avanceEjecutado,
            'observaciones_avance' => trim((string) ($_POST['observaciones_avance'] ?? '')),
            'observacion_actividad' => $_POST['observacion_actividad'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => (!empty($_POST['fecha_fin']) && $_POST['fecha_fin'] < date('Y-m-d')) ? 'CADUCADO' : ($_POST['estado'] ?? 'ACTIVO'),
            'ene_pct' => $cronograma['ene_pct'],
            'feb_pct' => $cronograma['feb_pct'],
            'mar_pct' => $cronograma['mar_pct'],
            'abr_pct' => $cronograma['abr_pct'],
            'may_pct' => $cronograma['may_pct'],
            'jun_pct' => $cronograma['jun_pct'],
            'jul_pct' => $cronograma['jul_pct'],
            'ago_pct' => $cronograma['ago_pct'],
            'sep_pct' => $cronograma['sep_pct'],
            'oct_pct' => $cronograma['oct_pct'],
            'nov_pct' => $cronograma['nov_pct'],
            'dic_pct' => $cronograma['dic_pct'],
        ];

        $creado = $model->crear($data);

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function editarActividad($id)
    {
        $actividadModel = new PoaActividadModel();

        $actividad = $actividadModel->obtenerPorId($id);

        $db = (new PediModel())->getConnection();
        $areas = $db->query("SELECT * FROM area ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $sedes = $db->query("SELECT * FROM sede ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $ejes = $db->query("SELECT * FROM eje_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $objetivos = $db->query("SELECT * FROM objetivo_estrategico WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $estrategias = $db->query("SELECT * FROM estrategia WHERE estado = 'activo' ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
        $metasEje = $db->query("SELECT pm.eje_id, pm.meta_texto, pm.porcentaje FROM pedi_metas pm WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NOT NULL UNION SELECT e.id AS eje_id, pm.meta_texto, pm.porcentaje FROM pedi_metas pm JOIN pedi p ON pm.pedi_id = p.id_pedi JOIN eje_estrategico e ON p.eje = e.nombre WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);

        $this->render('admin/plan_estrategico/editar_actividad', [
            'title' => 'Editar Actividad',
            'actividad' => $actividad,
            'areas' => $areas,
            'sedes' => $sedes,
            'ejes' => $ejes,
            'objetivos' => $objetivos,
            'estrategias' => $estrategias,
            'metasEje' => $metasEje
        ]);
    }

    public function actualizarActividad()
    {
        $model = new PoaActividadModel();
        $db = (new PediModel())->getConnection();

        $avanceEjecutado = (float) ($_POST['avance_ejecutado'] ?? 0);
        if ($avanceEjecutado < 0) {
            $avanceEjecutado = 0;
        }
        if ($avanceEjecutado > 100) {
            $avanceEjecutado = 100;
        }

        $id = (int) ($_POST['id_actividad'] ?? 0);
        $actividadAnterior = $model->obtenerPorId($id);

        if (!$actividadAnterior) {
            $_SESSION['error'] = 'Actividad no encontrada.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $ejeId = !empty($_POST['eje_id']) ? (int) $_POST['eje_id'] : null;
        $metaPedi = null;
        if ($ejeId) {
            $stmt = $db->prepare("SELECT meta_texto FROM pedi_metas WHERE eje_id = ? AND anio = YEAR(CURDATE()) LIMIT 1");
            $stmt->execute([$ejeId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $stmt = $db->prepare("SELECT pm.meta_texto FROM pedi_metas pm JOIN pedi p ON pm.pedi_id = p.id_pedi JOIN eje_estrategico e ON p.eje = e.nombre AND e.id = ? WHERE pm.anio = YEAR(CURDATE()) AND pm.eje_id IS NULL LIMIT 1");
                $stmt->execute([$ejeId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            $metaPedi = $row ? $row['meta_texto'] : null;
        }

        $cronogramaMeses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        $cronograma = [];
        foreach ($cronogramaMeses as $mes) {
            $campo = $mes . '_pct';
            $valor = $_POST[$campo] ?? 0;
            $cronograma[$campo] = is_numeric($valor) ? (float) $valor : 0;
        }

        $data = [
            'eje_id' => $ejeId,
            'objetivo_id' => !empty($_POST['objetivo_id']) ? (int) $_POST['objetivo_id'] : null,
            'estrategia_id' => !empty($_POST['estrategia_id']) ? (int) $_POST['estrategia_id'] : null,
            'eje' => $_POST['eje'] ?? '',
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'nombre_actividad' => $_POST['nombre_actividad'] ?? '',
            'meta' => $metaPedi ?? $actividadAnterior['meta'] ?? '',
            'area_id' => !empty($_POST['area_id']) ? (int) $_POST['area_id'] : null,
            'sede_id' => !empty($_POST['sede_id']) ? (int) $_POST['sede_id'] : null,
            'laboratorio' => $_POST['laboratorio'] ?? '',
            'sede' => $_POST['sede'] ?? '',
            'presupuesto_planificado' => (float) ($_POST['presupuesto_planificado'] ?? 0),
            'presupuesto_ejecutado' => (float) ($_POST['presupuesto_ejecutado'] ?? 0),
            'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'avance' => 0,
            'avance_ejecutado' => $avanceEjecutado,
            'observaciones_avance' => trim((string) ($_POST['observaciones_avance'] ?? '')),
            'observacion_actividad' => $_POST['observacion_actividad'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'estado' => (!empty($_POST['fecha_fin']) && $_POST['fecha_fin'] < date('Y-m-d')) ? 'CADUCADO' : ($_POST['estado'] ?? 'ACTIVO'),
            'ene_pct' => $cronograma['ene_pct'],
            'feb_pct' => $cronograma['feb_pct'],
            'mar_pct' => $cronograma['mar_pct'],
            'abr_pct' => $cronograma['abr_pct'],
            'may_pct' => $cronograma['may_pct'],
            'jun_pct' => $cronograma['jun_pct'],
            'jul_pct' => $cronograma['jul_pct'],
            'ago_pct' => $cronograma['ago_pct'],
            'sep_pct' => $cronograma['sep_pct'],
            'oct_pct' => $cronograma['oct_pct'],
            'nov_pct' => $cronograma['nov_pct'],
            'dic_pct' => $cronograma['dic_pct'],
        ];

        $model->actualizar($id, $data);

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function actualizarAvanceActividad()
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $idActividad = (int) ($_POST['id_actividad'] ?? 0);
        $avanceRaw = $_POST['avance_ejecutado'] ?? null;
        $observacionAvance = trim((string) ($_POST['observaciones_avance'] ?? ''));

        if ($idActividad <= 0 || $avanceRaw === null || $avanceRaw === '' || !is_numeric($avanceRaw)) {
            $_SESSION['error'] = 'Datos inválidos para actualizar avance ejecutado.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $avanceEjecutado = (float) $avanceRaw;
        if ($avanceEjecutado < 0 || $avanceEjecutado > 100) {
            $_SESSION['error'] = 'El avance ejecutado debe estar entre 0 y 100.';
            header("Location: " . $this->basePath . "/admin/poa");
            exit();
        }

        $actualizado = $this->actividadModel->actualizarAvanceEjecutado($idActividad, $avanceEjecutado, $observacionAvance);

        $_SESSION[$actualizado ? 'success' : 'error'] = $actualizado
            ? 'Avance ejecutado actualizado correctamente.'
            : 'No se pudo actualizar el avance ejecutado.';

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
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

    public function eliminarPedi($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pedi = $this->pediModel->obtenerPorId((int)$id);
        $eliminado = $this->pediModel->eliminar($id);

        if ($eliminado && !empty($pedi['objetivo_estrategico'])) {
            $idReferencia = null;
            $db = $this->pediModel->getConnection();
            $sql = "SELECT id_pedi FROM pedi
                    WHERE objetivo_estrategico = :objetivo
                      AND YEAR(fecha_creacion) = :anio
                    ORDER BY id_pedi DESC
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':objetivo' => $pedi['objetivo_estrategico'],
                ':anio' => (int)($pedi['anio_creacion'] ?? 0)
            ]);
            $idReferencia = (int)$stmt->fetchColumn();

            if ($idReferencia > 0) {
                $this->pediModel->recalcularAvanceObjetivoPorPediId($idReferencia);
            }
        }

        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'PEDI eliminado correctamente'
            : 'No se pudo eliminar el PEDI';

        header("Location: " . $this->basePath . "/admin/pedi");
        exit();
    }

    public function eliminarPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $poa = $this->poaModel->obtenerPorId((int) $id);

        $eliminado = $this->poaModel->eliminar($id);

        if ($eliminado && $poa) {
            $this->recalcularAvanceEstrategiaPedi((int) ($poa['id_pedi'] ?? 0));
        }

        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'POA eliminado correctamente'
            : 'No se pudo eliminar el POA';

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    public function eliminarActividadPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $actividad = $this->actividadModel->obtenerPorId((int) $id);
        $poa = null;
        if ($actividad && !empty($actividad['id_poa'])) {
            $poa = $this->poaModel->obtenerPorId((int) $actividad['id_poa']);
        }

        $eliminado = $this->actividadModel->eliminar($id);

        if ($eliminado && $poa) {
            $this->recalcularAvanceEstrategiaPedi((int) ($poa['id_pedi'] ?? 0));
        }

        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Actividad eliminada correctamente'
            : 'No se pudo eliminar la actividad';

        header("Location: " . $this->basePath . "/admin/poa");
        exit();
    }

    private function recalcularAvanceEstrategiaPedi($idPedi)
    {
        $idPedi = (int) $idPedi;
        if ($idPedi <= 0) {
            return;
        }

        $pedi = $this->pediModel->obtenerPorId($idPedi);
        if (!$pedi) {
            return;
        }

        $avanceCalculado = $this->actividadModel->calcularAvanceEstrategiaPorPedi($idPedi);

        $this->pediModel->actualizar($idPedi, [
            'objetivo_estrategico' => $pedi['objetivo_estrategico'] ?? '',
            'avance' => $pedi['avance'] ?? 0,
            'objetivo_estrategia' => $pedi['objetivo_estrategia'] ?? '',
            'avance_estrategia' => $avanceCalculado,
            'estado' => $pedi['estado'] ?? 'ACTIVO'
        ]);

        $this->pediModel->recalcularAvanceObjetivoPorPediId($idPedi);
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

