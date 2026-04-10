<?php

require_once __DIR__ . '/Database.php';

class AdminPermissionModel
{
    private $conn;
    private $tableName = 'access_account_permissions';

    private $actions = ['view', 'create', 'edit', 'delete'];

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTable();
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function hasAnyPermissions($accountId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([':account_id' => (int) $accountId]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log('Error verificando permisos de admin: ' . $e->getMessage());
            return false;
        }
    }

    public function getPermissionsByAccountId($accountId)
    {
        $sql = "SELECT module_key, can_view, can_create, can_edit, can_delete
                FROM {$this->tableName}
                WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($sql);

        try {
            $stmt->execute([':account_id' => (int) $accountId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error obteniendo permisos de admin: ' . $e->getMessage());
            return ['enabled' => false, 'matrix' => []];
        }

        if (empty($rows)) {
            return ['enabled' => false, 'matrix' => []];
        }

        $matrix = [];
        foreach ($rows as $row) {
            $module = (string) ($row['module_key'] ?? '');
            if ($module === '') {
                continue;
            }

            $matrix[$module] = [
                'view' => (int) ($row['can_view'] ?? 0) === 1,
                'create' => (int) ($row['can_create'] ?? 0) === 1,
                'edit' => (int) ($row['can_edit'] ?? 0) === 1,
                'delete' => (int) ($row['can_delete'] ?? 0) === 1,
            ];
        }

        return [
            'enabled' => true,
            'matrix' => $matrix,
        ];
    }

    public function setPermissions($accountId, array $permissions)
    {
        $accountId = (int) $accountId;

        try {
            $this->conn->beginTransaction();

            $deleteSql = "DELETE FROM {$this->tableName} WHERE account_id = :account_id";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute([':account_id' => $accountId]);

            $insertSql = "INSERT INTO {$this->tableName}
                (account_id, module_key, can_view, can_create, can_edit, can_delete, created_at, updated_at)
                VALUES
                (:account_id, :module_key, :can_view, :can_create, :can_edit, :can_delete, NOW(), NOW())";
            $insertStmt = $this->conn->prepare($insertSql);

            foreach ($permissions as $moduleKey => $values) {
                $insertStmt->execute([
                    ':account_id' => $accountId,
                    ':module_key' => $moduleKey,
                    ':can_view' => !empty($values['view']) ? 1 : 0,
                    ':can_create' => !empty($values['create']) ? 1 : 0,
                    ':can_edit' => !empty($values['edit']) ? 1 : 0,
                    ':can_delete' => !empty($values['delete']) ? 1 : 0,
                ]);
            }

            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }

            error_log('Error guardando permisos de admin: ' . $e->getMessage());
            return false;
        }
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            account_id INT UNSIGNED NOT NULL,
            module_key VARCHAR(60) NOT NULL,
            can_view TINYINT(1) NOT NULL DEFAULT 0,
            can_create TINYINT(1) NOT NULL DEFAULT 0,
            can_edit TINYINT(1) NOT NULL DEFAULT 0,
            can_delete TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_account_module (account_id, module_key),
            KEY idx_account_id (account_id),
            CONSTRAINT fk_access_permissions_account
                FOREIGN KEY (account_id) REFERENCES access_accounts(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            $this->conn->exec($sql);
        } catch (PDOException $e) {
            error_log('Error creando tabla de permisos de admin: ' . $e->getMessage());
        }
    }
}
