<?php

require_once __DIR__ . '/Database.php';

class AuditLogModel extends Database
{
    private $tableName = 'audit_log_entries';

    public function getLogs($limit = 100, $offset = 0, $search = '', $table = '', $action = '', array $tableList = [])
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->tableName} WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                actor_name LIKE :search
                OR request_uri LIKE :search
                OR table_name LIKE :search
                OR before_data LIKE :search
                OR after_data LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        if ($table !== '') {
            $sql .= " AND table_name = :table_name";
            $params[':table_name'] = $table;
        }

        if (!empty($tableList)) {
            $placeholders = [];
            foreach (array_values($tableList) as $idx => $tableName) {
                $key = ':tbl_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $tableName;
            }

            $sql .= " AND table_name IN (" . implode(', ', $placeholders) . ")";
        }

        if ($action !== '') {
            $sql .= " AND action_type = :action_type";
            $params[':action_type'] = $action;
        }

        $sql .= " ORDER BY event_time DESC, id DESC LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countLogs($search = '', $table = '', $action = '', array $tableList = [])
    {
        $db = $this->getConnection();

        $sql = "SELECT COUNT(*) FROM {$this->tableName} WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                actor_name LIKE :search
                OR request_uri LIKE :search
                OR table_name LIKE :search
                OR before_data LIKE :search
                OR after_data LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        if ($table !== '') {
            $sql .= " AND table_name = :table_name";
            $params[':table_name'] = $table;
        }

        if (!empty($tableList)) {
            $placeholders = [];
            foreach (array_values($tableList) as $idx => $tableName) {
                $key = ':tbl_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $tableName;
            }

            $sql .= " AND table_name IN (" . implode(', ', $placeholders) . ")";
        }

        if ($action !== '') {
            $sql .= " AND action_type = :action_type";
            $params[':action_type'] = $action;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function getDistinctTables()
    {
        $db = $this->getConnection();
        $sql = "SELECT DISTINCT table_name FROM {$this->tableName} ORDER BY table_name ASC";

        try {
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(static function ($row) {
                return (string) ($row['table_name'] ?? '');
            }, $rows);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getLogsForExport($search = '', $table = '', $action = '', $maxRows = 50000, array $tableList = [])
    {
        $db = $this->getConnection();

        $sql = "SELECT * FROM {$this->tableName} WHERE 1=1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (
                actor_name LIKE :search
                OR request_uri LIKE :search
                OR table_name LIKE :search
                OR before_data LIKE :search
                OR after_data LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        if ($table !== '') {
            $sql .= " AND table_name = :table_name";
            $params[':table_name'] = $table;
        }

        if (!empty($tableList)) {
            $placeholders = [];
            foreach (array_values($tableList) as $idx => $tableName) {
                $key = ':tbl_' . $idx;
                $placeholders[] = $key;
                $params[$key] = $tableName;
            }

            $sql .= " AND table_name IN (" . implode(', ', $placeholders) . ")";
        }

        if ($action !== '') {
            $sql .= " AND action_type = :action_type";
            $params[':action_type'] = $action;
        }

        $sql .= " ORDER BY event_time DESC, id DESC LIMIT :limit";

        $stmt = $db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', max(1, (int) $maxRows), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
