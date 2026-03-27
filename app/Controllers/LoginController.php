<?php
// app/Controllers/LoginController.php

require_once '../app/Models/UserModel.php';

class LoginController
{
    private $basePath;
    private $userModel;

    public function __construct()
    {
        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
        }
        
        $this->userModel = new UserModel();
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function index()
    {
        // Si ya está autenticado como estudiante, redirigir al dashboard
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }
        
        // Si está autenticado como admin, no permitir acceso al login de estudiante
        if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
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
        $content = __DIR__ . '/../Views/login/index.php';

        require __DIR__ . '/../Views/Layouts/auth_layout.php';
    }

    public function check()
    {
        if (!isset($_POST['numero_identificacion']) || empty($_POST['numero_identificacion'])) {
            header("Location: " . $this->basePath . "/login?error=campos_vacios");
            exit();
        }
        $cedula = trim($_POST['numero_identificacion']);
        $user = $this->userModel->findByCedula($cedula);
        if ($user) {
            // Limpiar cualquier sesión de admin previa
            unset($_SESSION['is_admin']);
            unset($_SESSION['admin_logged_in']);
            
            // Establecer sesión de estudiante
            $_SESSION['authenticated'] = true;
            $_SESSION['logged_in'] = true;
            $_SESSION['id_usuario'] = $user['id'];
            $_SESSION['identificacion'] = $user['numero_identificacion'];
            $_SESSION['nombres_completos'] = $user['primer_nombre'] . ' ' . $user['primer_apellido'];
            
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        } else {
            header("Location: " . $this->basePath . "/login?error=cedula_no_encontrada");
            exit();
        }
    }

    public function logout()
    {
        // Limpiar solo las variables de sesión del estudiante
        unset($_SESSION['authenticated']);
        unset($_SESSION['logged_in']);
        unset($_SESSION['id_usuario']);
        unset($_SESSION['identificacion']);
        unset($_SESSION['nombres_completos']);
        
        session_destroy();
        header("Location: " . $this->basePath . "/login");
        exit();
    }
}