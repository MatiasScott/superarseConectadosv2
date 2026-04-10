<?php
// app/Models/Database.php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Helpers/AuditTrail.php';

class Database
{
    private static $envLoaded = false;

    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct()
    {
        $this->loadEnvironment();

        $this->host = $this->env('DB_HOST', 'localhost');
        // Fallback compatible con la configuración histórica del proyecto.
        $this->db_name = $this->env('DB_NAME', 'superar1_conectados');
        $this->username = $this->env('DB_USER', 'root');
        $this->password = $this->env('DB_PASS', 'Superarse.2025');
    }

    public function getConnection()
    {
        $this->ensureDatabaseConfig();

        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            AuditTrail::bootstrap($this->conn);
        } catch (PDOException $exception) {
            error_log("Error de conexión a base de datos: " . $exception->getMessage());
            http_response_code(500);
            echo "Error interno de conexión.";
            die();
        }
        return $this->conn;
    }

    private function loadEnvironment(): void
    {
        if (self::$envLoaded) {
            return;
        }

        $rootPath = dirname(__DIR__, 2);
        $envFile = $rootPath . '/.env';

        if (file_exists($envFile)) {
            // Mutable evita que variables vacías del entorno web bloqueen valores del .env.
            Dotenv\Dotenv::createMutable($rootPath)->safeLoad();
        }

        self::$envLoaded = true;
    }

    private function ensureDatabaseConfig(): void
    {
        if (
            !empty($this->host)
            && !empty($this->db_name)
            && $this->username !== null
            && $this->password !== null
        ) {
            return;
        }

        $this->loadEnvironment();

        $this->host = $this->env('DB_HOST', 'localhost');
        $this->db_name = $this->env('DB_NAME', 'superar1_conectados');
        $this->username = $this->env('DB_USER', 'root');
        $this->password = $this->env('DB_PASS', 'Superarse.2025');
    }

    private function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        $value = trim((string) $value);
        return $value === '' ? $default : $value;
    }
}