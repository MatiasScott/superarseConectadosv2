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
    private $convenioModel;
    private $dashboardModel;

    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
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
        $this->convenioModel = new ConvenioModel();
        $this->dashboardModel = new AdminDashboardModel();
        $this->authAccountModel = new AuthAccountModel();
        $this->resetModel = new PasswordResetModel();

        $this->enforcePasswordChangeRedirect();
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
        $estado = $_GET['estado'] ?? '';
        $pagina = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limite = 15;
        $offset = ($pagina - 1) * $limite;

        // Obtener total general (para contador)
        $totalRegistros = $this->pasantiaModel->contarPracticas($buscar, $estado);

        // Obtener registros paginados
        $pasantias = $this->pasantiaModel->getPracticasPaginadas($buscar, $estado, $limite, $offset);

        // Contadores KPI
        $totalCompletadas = $this->pasantiaModel->contarPorEstado(1);
        $totalPendientes = $this->pasantiaModel->contarPorEstado(0);

        $totalPaginas = ceil($totalRegistros / $limite);

        $this->render('admin/practicas/index', [
            'title' => 'Gestión de Prácticas',
            'pasantias' => $pasantias,
            'totalRegistros' => $totalRegistros,
            'totalCompletadas' => $totalCompletadas,
            'totalPendientes' => $totalPendientes,
            'paginaActual' => $pagina,
            'totalPaginas' => $totalPaginas,
            'estado' => $estado,
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

        foreach ($requests as $req) {
            if ($req['status'] === 'pending' && !empty($req['account_id'])) {
                $csrfTokensResolve[$req['id']] = AuthSecurity::generateCsrfToken(
                    'admin_reset_resolve_' . $req['id']
                );
            }
        }

        $this->render('admin/reset_requests', [
            'title'             => 'Solicitudes de Restablecimiento',
            'requests'          => $requests,
            'csrfTokensResolve' => $csrfTokensResolve,
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

    /* Metodos del Plan Estrategico */

    public function planEstrategicoIndex()
    {
        if (!isset($_SESSION['is_admin'])) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $pedi = $this->pediModel->obtenerTodos() ?? [];
        $poa = $this->poaModel->obtenerTodos() ?? [];
        $actividades = $this->actividadModel->obtenerTodos() ?? [];

        $this->render('admin/plan_estrategico/pedi_poa_index', [
            'title' => 'Planificación Estratégica',
            'pedi' => $pedi,
            'poa' => $poa,
            'actividades' => $actividades
        ]);
    }

    public function crearPedi()
    {
        $this->render('admin/plan_estrategico/crear_pedi', [
            'title' => 'Crear PEDI'
        ]);
    }

    public function guardarPedi()
    {
        $model = new PediModel();

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'] ?? '',
            'objetivo_estrategia' => $_POST['objetivo_estrategia'] ?? '',
            'avance' => $_POST['avance'] ?? 0,
            'estado' => $_POST['estado'] ?? 'ACTIVO'
        ];

        $model->crear($data);

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function editarPedi($id)
    {
        $model = new PediModel();
        $pedi = $model->obtenerPorId($id);

        $this->render('admin/plan_estrategico/editar_pedi', [
            'title' => 'Editar PEDI',
            'pedi' => $pedi
        ]);
    }

    public function actualizarPedi()
    {
        $model = new PediModel();

        $id = $_POST['id_pedi'];

        $data = [
            'objetivo_estrategico' => $_POST['objetivo_estrategico'],
            'objetivo_estrategia' => $_POST['objetivo_estrategia'],
            'avance' => $_POST['avance'],
            'estado' => $_POST['estado']
        ];

        $model->actualizar($id, $data);

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
            header("Location: " . $this->basePath . "/admin/plan-estrategico");
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

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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

        $id = $_POST['id_poa'];

        $data = [
            'id_pedi' => $_POST['id_pedi'],
            'nombre_area' => $_POST['nombre_area'],
            'presupuesto_anual' => $_POST['presupuesto_anual'],
            'estado_actividad' => $_POST['estado_actividad'],
            'observaciones' => $_POST['observaciones'],
            'estado' => $_POST['estado']
        ];

        $model->actualizar($id, $data);

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function crearActividad()
    {
        $poaModel = new PoaModel();
        $poa = $poaModel->obtenerTodos();

        $this->render('admin/plan_estrategico/crear_actividad', [
            'title' => 'Crear Actividad',
            'poa' => $poa
        ]);
    }

    public function guardarActividad()
    {
        $model = new PoaActividadModel();

        $data = [
            'id_poa' => $_POST['id_poa'],
            'nombre_actividad' => $_POST['nombre_actividad'],
            'avance' => $_POST['avance'],
            'estado' => $_POST['estado']
        ];

        $model->crear($data);

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function editarActividad($id)
    {
        $actividadModel = new PoaActividadModel();
        $poaModel = new PoaModel();

        $actividad = $actividadModel->obtenerPorId($id);
        $poa = $poaModel->obtenerTodos();

        $this->render('admin/plan_estrategico/editar_actividad', [
            'title' => 'Editar Actividad',
            'actividad' => $actividad,
            'poa' => $poa
        ]);
    }

    public function actualizarActividad()
    {
        $model = new PoaActividadModel();

        $id = $_POST['id_actividad'];

        $data = [
            'id_poa' => $_POST['id_poa'],
            'nombre_actividad' => $_POST['nombre_actividad'],
            'avance' => $_POST['avance'],
            'estado' => $_POST['estado']
        ];

        $model->actualizar($id, $data);

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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

        $eliminado = $this->pediModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'PEDI eliminado correctamente'
            : 'No se pudo eliminar el PEDI';

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->poaModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'POA eliminado correctamente'
            : 'No se pudo eliminar el POA';

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
        exit();
    }

    public function eliminarActividadPoa($id)
    {
        if (!isset($_SESSION['is_admin']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $eliminado = $this->actividadModel->eliminar($id);
        $_SESSION[$eliminado ? 'success' : 'error'] = $eliminado
            ? 'Actividad eliminada correctamente'
            : 'No se pudo eliminar la actividad';

        header("Location: " . $this->basePath . "/admin/plan-estrategico");
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
