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
        // Si ya est� autenticado como admin, redirigir al dashboard
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            if (!empty($_SESSION['must_change_password'])) {
                header("Location: " . $this->basePath . "/admin/password/change");
                exit();
            }

            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        // Si est� autenticado como estudiante, no permitir acceso al login de admin
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $basePath = $this->basePath;
        $title = 'Login Administrador - Superarse Conectados';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = 'Panel de Administraci�n';
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
        $title = 'Cambiar Contrase�a Administrador - Superarse Conectados';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = 'Panel de Administraci�n';
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
        // Verificar que el usuario est� autenticado como administrador
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        // Obtener par�metros de paginaci�n y b�squeda
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
            'title' => 'Auditor�a',
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
            'title' => 'Auditor�a General',
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
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("INSERT INTO eje_estrategico (nombre, descripcion, estado) VALUES (?, ?, 'activo')")
            ->execute([$nombre, $descripcion]);

        $_SESSION['success'] = 'Eje estrat�gico creado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'ID de eje inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($nombre === '') {
            $_SESSION['error'] = 'El nombre del eje es obligatorio.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("UPDATE eje_estrategico SET nombre = ?, descripcion = ? WHERE id = ?")
            ->execute([$nombre, $descripcion, $id]);

        $_SESSION['success'] = 'Eje estrat�gico actualizado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarEje($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID de eje inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM objetivo_estrategico WHERE eje_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} objetivo(s) dependen de este eje.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE eje_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de este eje.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("DELETE FROM eje_estrategico WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Eje estrat�gico eliminado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'El nombre del �rea es obligatorio.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO area (nombre) VALUES (?)")->execute([$nombre]);

        $_SESSION['success'] = '�rea creada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'Datos inv�lidos.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE area SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);

        $_SESSION['success'] = '�rea actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarArea($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa WHERE area_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} POA(s) dependen de esta �rea.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE area_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de esta �rea.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("DELETE FROM area WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = '�rea eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO sede (nombre) VALUES (?)")->execute([$nombre]);

        $_SESSION['success'] = 'Sede creada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'Datos inv�lidos.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE sede SET nombre = ? WHERE id = ?")->execute([$nombre, $id]);

        $_SESSION['success'] = 'Sede actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarSede($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE sede_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} actividad(es) POA dependen de esta sede.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("DELETE FROM sede WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Sede eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO objetivo_estrategico (eje_id, nombre, estado) VALUES (?, ?, 'activo')")->execute([$ejeId, $nombre]);

        $_SESSION['success'] = 'Objetivo Estrat�gico creado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'Datos inv�lidos.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE objetivo_estrategico SET nombre = ?, eje_id = ? WHERE id = ?")->execute([$nombre, $ejeId, $id]);

        $_SESSION['success'] = 'Objetivo Estrat�gico actualizado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarObjetivo($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM estrategia WHERE objetivo_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} estrategia(s) dependen de este objetivo.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $stmt2 = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE objetivo_id = ?");
        $stmt2->execute([$id]);
        $hijos2 = (int)$stmt2->fetchColumn();
        if ($hijos2 > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos2} actividad(es) POA dependen de este objetivo.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("DELETE FROM objetivo_estrategico WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Objetivo Estrat�gico eliminado correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("INSERT INTO estrategia (objetivo_id, nombre, estado) VALUES (?, ?, 'activo')")->execute([$objetivoId, $nombre]);

        $_SESSION['success'] = 'Estrategia creada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            $_SESSION['error'] = 'Datos inv�lidos.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();
        $db->prepare("UPDATE estrategia SET nombre = ?, objetivo_id = ? WHERE id = ?")->execute([$nombre, $objetivoId, $id]);

        $_SESSION['success'] = 'Estrategia actualizada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarEstrategia($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if ($id <= 0) {
            $_SESSION['error'] = 'ID inv�lido.';
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db = (new PediModel())->getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) FROM poa_actividades WHERE estrategia_id = ?");
        $stmt->execute([$id]);
        $hijos = (int)$stmt->fetchColumn();
        if ($hijos > 0) {
            $_SESSION['error'] = "No se puede eliminar: {$hijos} actividad(es) POA dependen de esta estrategia.";
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
            exit();
        }

        $db->prepare("DELETE FROM estrategia WHERE id = ?")->execute([$id]);

        $_SESSION['success'] = 'Estrategia eliminada correctamente.';
        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            'title' => 'Reportes de Pr�cticas',
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
                'description' => 'Reporte de proyectos de vinculaci�n.',
            ],
            [
                'key' => 'vinculacion_proyectos_carrera',
                'label' => 'Proyectos por Carrera',
                'description' => 'Relaci�n de proyectos de vinculaci�n y carreras.',
            ],
        ];

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Vinculaci�n',
            'moduleTitle' => 'Vinculaci�n',
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
                'description' => 'Reporte de proyectos de investigaci�n.',
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
                'description' => 'Relaci�n de proyectos de investigaci�n y carreras.',
            ],
        ];

        $this->render('admin/reportes/module_page', [
            'title' => 'Reportes - Investigaci�n',
            'moduleTitle' => 'Investigaci�n',
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
                'label' => 'Plan Estrat�gico de Desarrollo Institucional',
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
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;font-size:' . $bodySize . ';padding:' . $pagePadding . ';margin:0;padding-bottom:28px;}';
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
            $html .= 'td:nth-child(2){font-weight:600;color:#1e293b;min-width:70px;}';
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
            $html .= 'td:nth-child(2){font-weight:600;color:#1e293b;}';
            $html .= 'td:nth-child(9),td:nth-child(10),td:nth-child(11),td:nth-child(12),td:nth-child(13),td:nth-child(14),td:nth-child(15),td:nth-child(16),td:nth-child(17),td:nth-child(18),td:nth-child(19),td:nth-child(20){text-align:center;font-size:6px;padding:3px 2px;min-width:28px;}';
            $html .= 'td:nth-child(21),td:nth-child(22){text-align:center;font-weight:700;}';
            $html .= 'td:nth-child(24),td:nth-child(25){text-align:right;font-weight:600;}';
            $html .= 'td:nth-child(26){text-align:center;font-weight:700;}';
            $html .= 'th:nth-child(9),th:nth-child(10),th:nth-child(11),th:nth-child(12),th:nth-child(13),th:nth-child(14),th:nth-child(15),th:nth-child(16),th:nth-child(17),th:nth-child(18),th:nth-child(19),th:nth-child(20){text-align:center;font-size:6px;padding:4px 2px;}';
            $html .= 'table.cronograma-table{table-layout:fixed;}';
            $html .= 'table.cronograma-table th,table.cronograma-table td{text-align:center;font-size:6px;padding:4px 2px;width:8.33%;}';
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
            $html .= '<span style="margin-right:18px;"><strong style="color:#e2e8f0;">M�dulo:</strong> ' . htmlspecialchars((string) $moduleLabel, ENT_QUOTES, 'UTF-8') . '</span>';
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

        $logoB64 = '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';
        $html .= '<div id="footer" style="position:fixed;bottom:0;left:0;right:0;padding:4px 16px;font-size:7.5px;color:#64748b;text-align:center;background:#ffffff;">';
        $html .= '<div style="display:flex;flex-direction:column;align-items:center;gap:2px;">';
        $html .= '<img src="data:image/png;base64,' . $logoB64 . '" style="height:18px;width:auto;">';
        $html .= '<div>';
        $html .= '<span>� 2025 Instituto Superarse. Todos los derechos reservados.</span>';
        if ($userName !== '') {
            $html .= '<span> � Descargado por: ' . htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') . '</span>';
        }
        $html .= '</div></div></div>';
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

            $cronograma['PROCESOS'] = $source['PROCESOS'] ?? '';

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
        $tableClass = $includeCronogramaHeader ? ' class="cronograma-table"' : '';
        $html = '<table' . $tableClass . '>';

        if ($includeCronogramaHeader && count($headers) > 0) {
            $html .= '<colgroup>';
            foreach ($headers as $_header) {
                $html .= '<col style="width:' . number_format(100 / count($headers), 2, '.', '') . '%;">';
            }
            $html .= '</colgroup>';
        }

        $html .= '<thead>';

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
                        $headerLabel = htmlspecialchars((string) $headers[$i], ENT_QUOTES, 'UTF-8');
                        if ($headerLabel === 'PROCESOS') {
                            $html .= '<th style="background:#4c1d95;color:#ffffff;font-size:7px;text-align:center;">PROCESOS</th>';
                        } else {
                            $html .= '<th style="background:#4c1d95;"></th>';
                        }
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
                if ($includeCronogramaHeader) {
                    $value = $item[$header] ?? '';

                    if ($header === 'PROCESOS') {
                        $html .= '<td style="font-size:6.5px;color:#1e293b;">' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
                        continue;
                    }

                    $numericValue = is_numeric($value)
                        ? (float) $value
                        : (float) str_replace('%', '', (string) $value);

                    if ($numericValue > 0) {
                        $html .= '<td><span style="display:inline-block;width:12px;height:12px;border-radius:9999px;background:#dcfce7;position:relative;"><span style="position:absolute;left:4px;top:2px;width:3px;height:6px;border-right:1.5px solid #166534;border-bottom:1.5px solid #166534;transform:rotate(45deg);"></span></span></td>';
                    } else {
                        $html .= '<td><span style="color:#cbd5e1;font-size:10px;">&mdash;</span></td>';
                    }
                    continue;
                }

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
            $html .= '<tr><th>Empresa</th><th>RUC</th><th>Estudiante</th><th>C�dula</th><th>Carrera</th><th>Modalidad</th><th>Fase</th></tr>';
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
            $html = '<h2>Reporte: Distribuci�n de modalidad por carrera</h2>';
            if (empty($groups)) {
                $html .= '<p>No hay datos para exportar.</p>';
            } else {
                foreach ($groups as $carrera => $rows) {
                    $html .= '<h3>' . htmlspecialchars((string) $carrera, ENT_QUOTES, 'UTF-8') . '</h3>';
                    $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
                    $html .= '<tr><th>Modalidad</th><th>C�dula</th><th>Estudiante</th></tr>';
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
            $html .= '<tr><th>C�dula</th><th>Estudiante</th><th>Email</th><th>Carrera</th><th>Empresa</th><th>Modalidad</th><th>Fase</th></tr>';
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
                'label' => 'Pr�cticas (Admin + Estudiantes)',
                'tables' => ['practicas_estudiantes', 'entidades', 'tutores_empresariales', 'programa_trabajo', 'actividades_diarias'],
            ],
            'investigacion_vinculacion' => [
                'label' => 'Investigaci�n y Vinculaci�n',
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
        // Verificar autenticaci�n
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        // Obtener ID del registro
        $registroId = $_GET['id'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inv�lido']);
            exit();
        }

        // Aqu� ir�a la l�gica de edici�n
        // Por ahora retornamos �xito para permitir que funcione
        echo json_encode([
            'success' => true,
            'message' => 'Registro #' . intval($registroId) . ' listo para editar',
            'id' => intval($registroId)
        ]);
        exit();
    }

    public function editarRegistroView()
    {
        // Verificar autenticaci�n
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

        // Obtener el registro de auditor�a
        $registro = $this->pasantiaModel->getRegistroAuditoriaById(intval($registroId));

        if (!$registro) {
            $_SESSION['error'] = 'Registro no encontrado';
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        $this->render('admin/auditoria/editar_auditoria', [
            'title' => 'Editar Registro de Auditor�a',
            'tipoRegistro' => $registro['tipo_registro'],
            'datos' => $registro,
            'moduleCss' => ['forms.css']
        ]);
    }

    public function eliminarRegistro()
    {
        // Verificar autenticaci�n
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit();
        }

        // Obtener ID del registro
        $registroId = $_GET['id'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inv�lido']);
            exit();
        }

        try {
            $registroId = intval($registroId);

            // Obtener informaci�n del registro antes de eliminarlo
            $registro = $this->pasantiaModel->getRegistroAuditoriaById($registroId);

            if (!$registro) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
                exit();
            }

            // Determinar tipo de registro
            $tipo = $registro['tipo_registro'];

            // Preparar datos para auditor�a
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

            // Registrar en auditor�a ANTES de eliminar
            $registradoEnAuditoria = $this->pasantiaModel->registrarEliminacion($tipo, $registroId, $datosAuditoria);

            if (!$registradoEnAuditoria) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'No se pudo registrar la eliminaci�n en auditor�a']);
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
                error_log("Registro $tipo ID $registroId eliminado correctamente y registrado en auditor�a");
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
        // Verificar autenticaci�n
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            $_SESSION['error'] = 'No autorizado';
            header("Location: " . $this->basePath . "/admin/auditoria-fase-dos");
            exit();
        }

        // Validar ID
        $registroId = $_POST['id'] ?? null;
        $tipo = $_POST['tipo'] ?? null;

        if (!$registroId || !is_numeric($registroId)) {
            $_SESSION['error'] = 'ID inv�lido';
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

            // Actualizar seg�n tipo
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
        // Limpiar variables de sesi�n de admin
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
            'title' => 'Gesti�n de Pr�cticas',
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
            'title' => 'Vinculaci�n',
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
            'title' => 'Investigaci�n',
            'proyectos' => $proyectos,
            'publicaciones' => $publicaciones,
            'ponencias' => $ponencias,
            'carreras' => $carreras
        ]);
    }

    /* M�todo para renderizar vistas con layout */

    protected function render($view, $data = [])
    {
        extract($data);

        $basePath = $this->basePath;

        $nombreCompleto = $_SESSION['nombres_completos'] ?? 'Administrador';

        $pendingResetCount = 0;
        try {
            $pendingResetCount = $this->resetModel->countPending();
        } catch (Throwable $e) {
            // tabla a�n no migrada
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
            $mail->Subject = 'Tu contrase�a ha sido restablecida - Superarse Conectados';
            $mail->isHTML(true);
            $mail->Body = "<p>Hola <strong>" . htmlspecialchars($displayName) . "</strong>,</p>"
                . "<p>Un administrador ha restablecido tu contrase�a de acceso al sistema.</p>"
                . "<p>Tu contrase�a temporal es: <strong style='font-size:16px;letter-spacing:2px;'>"
                . htmlspecialchars($tempPassword) . "</strong></p>"
                . "<p>Al ingresar, se te pedir� que la cambies por una nueva.</p>"
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
            'practicas' => 'Pr�cticas',
            'vinculacion' => 'Vinculaci�n',
            'investigacion' => 'Investigaci�n',
            'plan_estrategico' => 'Planificación Estratégica',
            'pedi' => 'PEDI',
            'poa' => 'POA',
            'convenios' => 'Convenios',
            'auditoria' => 'Auditor�a',
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
            // Modo compatibilidad: si a�n no hay permisos configurados, mantiene acceso completo.
            return true;
        }

        $matrix = $permissionState['matrix'] ?? [];
        if (!isset($matrix[$moduleKey])) {
            // Compatibilidad: instalaciones anteriores usaban plan_estrategico para PEDI/POA.
            $fallbackByModule = [
                'pedi' => 'plan_estrategico',
                'poa' => 'plan_estrategico',
                'configuracion' => 'plan_estrategico',
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
        $_SESSION['error'] = 'No tienes permiso para ' . $action . ' en el m�dulo ' . $moduleKey . '.';
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

    /* === GESTI?"N DE CUENTAS ADMIN === */

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
            'title'                => 'Gesti�n de Cuentas',
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $displayName    = trim($_POST['display_name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $identification = trim($_POST['numero_identificacion'] ?? '');
        $tempPassword   = $_POST['temp_password'] ?? '';

        if ($displayName === '' || $email === '' || $tempPassword === '') {
            $_SESSION['error'] = 'Nombre, correo y contrase�a temporal son obligatorios.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $policyError = AuthSecurity::validatePasswordPolicy($tempPassword);
        if ($policyError !== null) {
            $_SESSION['error'] = 'Contrase�a inv�lida: ' . $policyError;
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
            $_SESSION['success'] = "Cuenta creada para {$displayName}. Contrase�a temporal: {$tempPassword}";
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        if ($accountId <= 0) {
            $_SESSION['error'] = 'Cuenta de administrador inv�lida.';
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            $_SESSION['error'] = 'Estudiante no v�lido.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $student = $this->userModel->findActiveStudentById($userId);
        if (!$student) {
            $_SESSION['error'] = 'No se encontr� un estudiante activo con ese identificador.';
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

        $_SESSION['success'] = 'Cuenta creada para el estudiante. La contrase�a inicial es su n�mero de identificaci�n y deber� cambiarla al ingresar.';
        $this->redirectToAccounts($_POST['return_query'] ?? '');
    }

    public function toggleStudentAccount()
    {
        if (empty($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_account_toggle', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $newStatus = (int) ($_POST['new_status'] ?? 0);
        $account = $this->authAccountModel->findById($accountId);

        if (!$account || ($account['role'] ?? '') !== 'student') {
            $_SESSION['error'] = 'Cuenta de estudiante no v�lida.';
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $accountId = (int) ($_POST['account_id'] ?? 0);
        $account = $this->authAccountModel->findById($accountId);

        if (!$account || ($account['role'] ?? '') !== 'student') {
            $_SESSION['error'] = 'Cuenta de estudiante no v�lida.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $identification = trim($account['numero_identificacion'] ?? '');
        if ($identification === '') {
            $_SESSION['error'] = 'La cuenta no tiene n�mero de identificaci�n para restablecer contrase�a.';
            $this->redirectToAccounts($_POST['return_query'] ?? '');
        }

        $updated = $this->authAccountModel->resetToTemporaryPassword(
            $accountId,
            password_hash($identification, PASSWORD_DEFAULT)
        );

        $_SESSION[$updated ? 'success' : 'error'] = $updated
            ? 'Contrase�a restablecida. La clave temporal es la c�dula del estudiante y deber� cambiarla al ingresar.'
            : 'No se pudo restablecer la contrase�a del estudiante.';

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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resetRequest = $this->resetModel->findById($requestId);

        if (!$resetRequest || $resetRequest['status'] !== 'pending' || empty($resetRequest['account_id'])) {
            $_SESSION['error'] = 'Solicitud no v�lida o ya fue procesada.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $tempPassword = AuthSecurity::generateTempPassword();
        $updated = $this->authAccountModel->resetToTemporaryPassword(
            (int) $resetRequest['account_id'],
            password_hash($tempPassword, PASSWORD_DEFAULT)
        );

        if (!$updated) {
            $_SESSION['error'] = 'No fue posible restablecer la contrase�a. Intente de nuevo.';
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
            $_SESSION['error'] = 'Sesi�n del formulario expirada. Intente de nuevo.';
            header("Location: " . $this->basePath . "/admin/reset-requests");
            exit();
        }

        $resetRequest = $this->resetModel->findById($requestId);

        if (!$resetRequest || $resetRequest['status'] !== 'pending') {
            $_SESSION['error'] = 'Solicitud no v�lida o ya fue procesada.';
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

    /* === OLVID?? MI CONTRASE?'A (admin) === */

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

        // Siempre redirigir con �xito para no revelar si el correo existe
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
                $_SESSION['success'] = "Publicaci�n creada correctamente";
            } else {
                $_SESSION['error'] = "Error al crear publicaci�n";
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

    /* Metodos para Mostrar Formularios de Creaci�n */

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
            'title' => 'Nueva Publicaci�n'
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
            'title' => 'Editar Publicaci�n',
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
                $_SESSION['success'] = "Publicaci�n actualizada correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar publicaci�n";
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
                $_SESSION['error'] = "ID de carrera inv�lido";
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
                $_SESSION['error'] = "ID de carrera inv�lido";
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
            $cronograma[$campo] = isset($_POST[$campo]) ? 100.0 : 0.0;
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
            $cronograma[$campo] = isset($_POST[$campo]) ? 100.0 : 0.0;
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
            $_SESSION['error'] = 'Datos inv�lidos para actualizar avance ejecutado.';
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
            ? 'Publicaci�n eliminada correctamente'
            : 'No se pudo eliminar la publicaci�n';

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


