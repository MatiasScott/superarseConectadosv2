<?php

class AuditTrail
{
    private static $initialized = false;

    private static $tablesToAudit = [
        'practicas_estudiantes',
        'entidades',
        'tutores_empresariales',
        'programa_trabajo',
        'actividades_diarias',
        'proyectos_administracion',
        'proyecto_estudiantes_carrera',
        'publicaciones',
        'ponencias',
        'pedi',
        'poa',
        'poa_actividades',
        'convenios',
        'payments',
        'access_accounts',
        'access_account_permissions',
        'password_reset_requests',
    ];

    public static function bootstrap(PDO $conn): void
    {
        self::ensureAuditTable($conn);
        self::enforceRetention($conn);

        if (!self::$initialized) {
            self::ensureTriggers($conn);
            self::$initialized = true;
        }

        self::applySessionContext($conn);
    }

    private static function enforceRetention(PDO $conn): void
    {
        $sql = "DELETE FROM audit_log_entries
                WHERE event_time < DATE_SUB(NOW(), INTERVAL 24 MONTH)";

        try {
            $conn->exec($sql);
        } catch (Throwable $e) {
            error_log('No se pudo aplicar retención de auditoría (24 meses): ' . $e->getMessage());
        }
    }

    private static function ensureAuditTable(PDO $conn): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS audit_log_entries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            table_name VARCHAR(120) NOT NULL,
            action_type ENUM('INSERT','UPDATE','DELETE') NOT NULL,
            record_pk VARCHAR(255) NULL,
            before_data LONGTEXT NULL,
            after_data LONGTEXT NULL,
            actor_type VARCHAR(30) NULL,
            actor_account_id INT NULL,
            actor_student_id INT NULL,
            actor_name VARCHAR(255) NULL,
            request_uri VARCHAR(255) NULL,
            request_method VARCHAR(20) NULL,
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            KEY idx_event_time (event_time),
            KEY idx_table_name (table_name),
            KEY idx_action_type (action_type),
            KEY idx_actor_account_id (actor_account_id),
            KEY idx_actor_student_id (actor_student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            $conn->exec($sql);
        } catch (Throwable $e) {
            error_log('No se pudo crear audit_log_entries: ' . $e->getMessage());
        }
    }

    private static function ensureTriggers(PDO $conn): void
    {
        foreach (self::$tablesToAudit as $tableName) {
            if (!self::tableExists($conn, $tableName)) {
                continue;
            }

            $columns = self::getTableColumns($conn, $tableName);
            if (empty($columns)) {
                continue;
            }

            $pkColumns = self::getPrimaryKeyColumns($conn, $tableName);

            self::createTrigger($conn, $tableName, 'INSERT', $columns, $pkColumns);
            self::createTrigger($conn, $tableName, 'UPDATE', $columns, $pkColumns);
            self::createTrigger($conn, $tableName, 'DELETE', $columns, $pkColumns);
        }
    }

    private static function createTrigger(PDO $conn, string $tableName, string $action, array $columns, array $pkColumns): void
    {
        $triggerName = self::buildTriggerName($tableName, $action);

        if (self::triggerExists($conn, $triggerName)) {
            return;
        }

        $recordPkExpr = self::buildRecordPkExpression($pkColumns, $action);
        $beforeExpr = ($action === 'INSERT') ? 'NULL' : self::buildJsonObjectExpression($columns, 'OLD');
        $afterExpr = ($action === 'DELETE') ? 'NULL' : self::buildJsonObjectExpression($columns, 'NEW');

        $sql = "CREATE TRIGGER `{$triggerName}`
            AFTER {$action} ON `{$tableName}`
            FOR EACH ROW
            INSERT INTO audit_log_entries (
                event_time,
                table_name,
                action_type,
                record_pk,
                before_data,
                after_data,
                actor_type,
                actor_account_id,
                actor_student_id,
                actor_name,
                request_uri,
                request_method,
                ip_address,
                user_agent
            )
            VALUES (
                NOW(),
                " . $conn->quote($tableName) . ",
                " . $conn->quote($action) . ",
                {$recordPkExpr},
                {$beforeExpr},
                {$afterExpr},
                @audit_actor_type,
                @audit_actor_account_id,
                @audit_actor_student_id,
                @audit_actor_name,
                @audit_request_uri,
                @audit_request_method,
                @audit_ip,
                @audit_user_agent
            )";

        try {
            $conn->exec($sql);
        } catch (Throwable $e) {
            error_log('No se pudo crear trigger de auditoría ' . $triggerName . ': ' . $e->getMessage());
        }
    }

    private static function applySessionContext(PDO $conn): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $actorType = 'system';
        $actorAccountId = null;
        $actorStudentId = null;
        $actorName = 'System';

        if (!empty($_SESSION['is_admin']) && !empty($_SESSION['auth_account_id'])) {
            $actorType = 'admin';
            $actorAccountId = (int) $_SESSION['auth_account_id'];
            $actorName = (string) ($_SESSION['nombres_completos'] ?? 'Administrador');
        } elseif (!empty($_SESSION['authenticated']) && !empty($_SESSION['id_usuario'])) {
            $actorType = 'student';
            $actorStudentId = (int) $_SESSION['id_usuario'];
            $actorName = (string) ($_SESSION['nombres_completos'] ?? 'Estudiante');
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $requestMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI');
        $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

        $statements = [
            '@audit_actor_type' => $actorType,
            '@audit_actor_account_id' => $actorAccountId,
            '@audit_actor_student_id' => $actorStudentId,
            '@audit_actor_name' => $actorName,
            '@audit_request_uri' => $requestUri,
            '@audit_request_method' => $requestMethod,
            '@audit_ip' => $ipAddress,
            '@audit_user_agent' => $userAgent,
        ];

        foreach ($statements as $varName => $value) {
            $sql = "SET {$varName} = " . self::toSqlValue($conn, $value);
            try {
                $conn->exec($sql);
            } catch (Throwable $e) {
                error_log('No se pudo establecer contexto de auditoría: ' . $e->getMessage());
            }
        }
    }

    private static function tableExists(PDO $conn, string $tableName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([':table_name' => $tableName]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function triggerExists(PDO $conn, string $triggerName): bool
    {
        $sql = "SELECT COUNT(*)
                FROM INFORMATION_SCHEMA.TRIGGERS
                WHERE TRIGGER_SCHEMA = DATABASE()
                  AND TRIGGER_NAME = :trigger_name";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([':trigger_name' => $triggerName]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function getTableColumns(PDO $conn, string $tableName): array
    {
        $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                ORDER BY ORDINAL_POSITION";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([':table_name' => $tableName]);
            return array_map(static function ($row) {
                return $row['COLUMN_NAME'];
            }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function getPrimaryKeyColumns(PDO $conn, string $tableName): array
    {
        $sql = "SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = :table_name
                  AND CONSTRAINT_NAME = 'PRIMARY'
                ORDER BY ORDINAL_POSITION";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([':table_name' => $tableName]);
            return array_map(static function ($row) {
                return $row['COLUMN_NAME'];
            }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function buildJsonObjectExpression(array $columns, string $rowAlias): string
    {
        if (empty($columns)) {
            return 'NULL';
        }

        $parts = [];
        foreach ($columns as $column) {
            $escapedColumn = str_replace('`', '``', $column);
            $parts[] = "'{$column}', {$rowAlias}.`{$escapedColumn}`";
        }

        return 'JSON_OBJECT(' . implode(', ', $parts) . ')';
    }

    private static function buildRecordPkExpression(array $pkColumns, string $action): string
    {
        if (empty($pkColumns)) {
            return 'NULL';
        }

        $rowAlias = ($action === 'INSERT') ? 'NEW' : 'OLD';
        $parts = [];

        foreach ($pkColumns as $column) {
            $escapedColumn = str_replace('`', '``', $column);
            $parts[] = "IFNULL(CAST({$rowAlias}.`{$escapedColumn}` AS CHAR), '')";
        }

        return 'CONCAT_WS(\'|\', ' . implode(', ', $parts) . ')';
    }

    private static function buildTriggerName(string $tableName, string $action): string
    {
        $base = 'trg_aud_' . substr($tableName, 0, 40) . '_' . strtolower($action);
        return substr($base, 0, 63);
    }

    private static function toSqlValue(PDO $conn, $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $conn->quote((string) $value);
    }
}
