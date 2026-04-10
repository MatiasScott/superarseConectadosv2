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

        $this->host = $this->env('DB_HOST');
        $this->db_name = $this->env('DB_NAME');
        $this->username = $this->env('DB_USER');
        $this->password = $this->env('DB_PASS');
    }

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->ensureDatabaseConfig();
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            AuditTrail::bootstrap($this->conn);
        } catch (RuntimeException $exception) {
            error_log('Configuración de base de datos incompleta: ' . $exception->getMessage());
            http_response_code(500);
            echo 'Configuración interna incompleta.';
            die();
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
        $this->loadEnvironment();

        $this->host = $this->env('DB_HOST');
        $this->db_name = $this->env('DB_NAME');
        $this->username = $this->env('DB_USER');
        $this->password = $this->env('DB_PASS');

        $missing = [];
        foreach ([
            'DB_HOST' => $this->host,
            'DB_NAME' => $this->db_name,
            'DB_USER' => $this->username,
            'DB_PASS' => $this->password,
        ] as $key => $value) {
            if ($value === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException('Faltan variables de entorno requeridas: ' . implode(', ', $missing));
        }
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