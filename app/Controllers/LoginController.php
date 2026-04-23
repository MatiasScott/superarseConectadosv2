<?php
// app/Controllers/LoginController.php

require_once '../app/Models/UserModel.php';
require_once '../app/Models/AuthAccountModel.php';
require_once '../app/Models/PasswordResetModel.php';
require_once '../app/Helpers/AuthSecurity.php';

class LoginController
{
    private $basePath;
    private $userModel;
    private $authAccountModel;
    private $resetModel;

    public function __construct()
    {
        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
        }

        $this->userModel = new UserModel();
        $this->authAccountModel = new AuthAccountModel();
        $this->resetModel = new PasswordResetModel();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function index()
    {
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && !empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/password/change");
            exit();
        }

        // Si ya está autenticado como estudiante, redirigir al dashboard
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        // Si está autenticado como admin, no permitir acceso al login de estudiante
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
            if (!empty($_SESSION['must_change_password'])) {
                header("Location: " . $this->basePath . "/admin/password/change");
                exit();
            }

            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        $basePath = $this->basePath;
        $title = 'Login - Superarse Conectados v2';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = '';
        $moduleCss = ['login.css'];
        $moduleJs = ['login.js'];
        $moduleHeadStyles = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
        $moduleBodyScripts = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'];
        $csrfToken = AuthSecurity::generateCsrfToken('student_login');
        $content = __DIR__ . '/../Views/login/index.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function check()
    {
        if (!AuthSecurity::validateCsrfToken('student_login', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/login?error=invalid_request");
            exit();
        }

        if (
            !isset($_POST['numero_identificacion'], $_POST['password'])
            || empty($_POST['numero_identificacion'])
            || empty($_POST['password'])
        ) {
            header("Location: " . $this->basePath . "/login?error=campos_vacios");
            exit();
        }

        $cedula = trim($_POST['numero_identificacion']);
        $password = (string) $_POST['password'];
        $user = $this->userModel->findByCedula($cedula);

        if (!$user) {
            header("Location: " . $this->basePath . "/login?error=cedula_no_encontrada");
            exit();
        }

        $account = $this->authAccountModel->ensureStudentAccount($user);
        if (!$account || empty($account['password_hash']) || !password_verify($password, $account['password_hash'])) {
            header("Location: " . $this->basePath . "/login?error=invalid_credentials");
            exit();
        }

        $this->clearAdminSession();
        session_regenerate_id(true);

        $_SESSION['authenticated'] = true;
        $_SESSION['logged_in'] = true;
        $_SESSION['id_usuario'] = $user['id'];
        $_SESSION['identificacion'] = $user['numero_identificacion'];
        $_SESSION['nombres_completos'] = trim(($user['primer_nombre'] ?? '') . ' ' . ($user['primer_apellido'] ?? ''));
        $_SESSION['auth_account_id'] = (int) $account['id'];
        $_SESSION['auth_role'] = 'student';
        $_SESSION['must_change_password'] = !empty($account['must_change_password']);

        $this->authAccountModel->recordSuccessfulLogin((int) $account['id']);

        if (!empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/password/change");
            exit();
        }

        header("Location: " . $this->basePath . "/estudiante/informacion");
        exit();
    }

    public function showChangePasswordForm()
    {
        if (empty($_SESSION['authenticated']) || ($_SESSION['auth_role'] ?? null) !== 'student') {
            header("Location: " . $this->basePath . "/login?error=not_authenticated");
            exit();
        }

        $basePath = $this->basePath;
        $title = 'Cambiar Contraseña - Superarse Conectados';
        $headerTitle = 'Superarse Conectados';
        $headerSubtitle = '';
        $moduleCss = ['login.css'];
        $moduleHeadStyles = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
        $moduleBodyScripts = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'];
        $csrfToken = AuthSecurity::generateCsrfToken('student_password_change');
        $content = __DIR__ . '/../Views/login/change_password.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function changePassword()
    {
        if (empty($_SESSION['authenticated']) || ($_SESSION['auth_role'] ?? null) !== 'student' || empty($_SESSION['auth_account_id'])) {
            header("Location: " . $this->basePath . "/login?error=not_authenticated");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_password_change', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/password/change?error=invalid_request");
            exit();
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            header("Location: " . $this->basePath . "/password/change?error=campos_vacios");
            exit();
        }

        $account = $this->authAccountModel->findById((int) $_SESSION['auth_account_id']);
        if (!$account || !password_verify($currentPassword, $account['password_hash'])) {
            header("Location: " . $this->basePath . "/password/change?error=invalid_current_password");
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            header("Location: " . $this->basePath . "/password/change?error=password_mismatch");
            exit();
        }

        if ($currentPassword === $newPassword) {
            header("Location: " . $this->basePath . "/password/change?error=same_password");
            exit();
        }

        $policyError = AuthSecurity::validatePasswordPolicy($newPassword);
        if ($policyError !== null) {
            header("Location: " . $this->basePath . "/password/change?error=policy_invalid&message=" . urlencode($policyError));
            exit();
        }

        $updated = $this->authAccountModel->updatePasswordById((int) $account['id'], password_hash($newPassword, PASSWORD_DEFAULT));
        if (!$updated) {
            header("Location: " . $this->basePath . "/password/change?error=password_update_failed");
            exit();
        }

        $_SESSION['must_change_password'] = false;
        session_regenerate_id(true);

        header("Location: " . $this->basePath . "/estudiante/informacion");
        exit();
    }

    public function logout()
    {
        // Limpiar solo las variables de sesión del estudiante
        unset($_SESSION['authenticated']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['id_usuario']);
        unset($_SESSION['identificacion']);
        unset($_SESSION['nombres_completos']);
        unset($_SESSION['auth_account_id']);
        unset($_SESSION['auth_role']);
        unset($_SESSION['must_change_password']);

        session_destroy();
        header("Location: " . $this->basePath . "/login");
        exit();
    }

    private function clearAdminSession()
    {
        unset($_SESSION['is_admin']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_email']);
    }

    /* === OLVIDÉ MI CONTRASEÑA (estudiante) === */

    public function showForgotPasswordForm()
    {
        if (!empty($_SESSION['authenticated'])) {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $basePath          = $this->basePath;
        $title             = 'Recuperar acceso';
        $headerTitle       = 'Superarse Conectados';
        $headerSubtitle    = '';
        $moduleCss         = ['login.css'];
        $moduleJs          = [];
        $moduleHeadStyles  = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'];
        $moduleBodyScripts = ['https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'];
        $csrfToken         = AuthSecurity::generateCsrfToken('student_forgot_password');
        $content           = __DIR__ . '/../Views/login/forgot_password.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function requestPasswordReset()
    {
        if (!AuthSecurity::validateCsrfToken('student_forgot_password', $_POST['csrf_token'] ?? '')) {
            header("Location: " . $this->basePath . "/forgot-password?error=invalid_request");
            exit();
        }

        $cedula = trim($_POST['numero_identificacion'] ?? '');

        if ($cedula === '') {
            header("Location: " . $this->basePath . "/forgot-password?error=campos_vacios");
            exit();
        }

        $user = $this->userModel->findByCedula($cedula);

        if ($user) {
            $account   = $this->authAccountModel->ensureStudentAccount($user);
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $displayName = trim(($user['primer_nombre'] ?? '') . ' ' . ($user['primer_apellido'] ?? ''));

            if ($account) {
                $this->resetModel->createRequest(
                    (int) $account['id'],
                    'student',
                    $displayName ?: $cedula,
                    $cedula,
                    $ipAddress
                );
            }
        }

        // Siempre redirigir con éxito para no revelar si la cédula existe
        header("Location: " . $this->basePath . "/forgot-password?success=1");
        exit();
    }
}
