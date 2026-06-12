<?php

require_once __DIR__ . '/Database.php';

class PasswordResetModel
{
    private $conn;
    private $tableName = 'password_reset_requests';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createRequest($accountId, $role, $displayName, $contact, $ipAddress)
    {
        if ($accountId !== null && $this->hasPendingRequest($accountId)) {
            return ['success' => true, 'already_exists' => true];
        }

        $query = "INSERT INTO {$this->tableName}
                  (account_id, role, display_name, contact, status, ip_address)
                  VALUES (:account_id, :role, :display_name, :contact, 'pending', :ip_address)";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':account_id'   => $accountId,
                ':role'         => $role,
                ':display_name' => $displayName,
                ':contact'      => $contact,
                ':ip_address'   => $ipAddress,
            ]);
            return ['success' => true, 'id' => (int) $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            error_log('Error al crear solicitud de reset: ' . $e->getMessage());
            return ['success' => false];
        }
    }

    public function hasPendingRequest($accountId)
    {
        $query = "SELECT id FROM {$this->tableName}
                  WHERE account_id = :account_id AND status = 'pending'
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':account_id' => $accountId]);
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getAllRequests($limit = 100)
    {
        $query = "SELECT * FROM {$this->tableName}
                  ORDER BY
                    CASE status WHEN 'pending' THEN 0 ELSE 1 END,
                    requested_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener solicitudes: ' . $e->getMessage());
            return [];
        }
    }

    public function countPending()
    {
        $query = "SELECT COUNT(*) FROM {$this->tableName} WHERE status = 'pending'";

        try {
            return (int) $this->conn->query($query)->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function resolveRequest($requestId, $resolvedBy)
    {
        $query = "UPDATE {$this->tableName}
                  SET status = 'resolved',
                      resolved_by = :resolved_by,
                      resolved_at = NOW()
                  WHERE id = :id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute([
                ':resolved_by' => $resolvedBy,
                ':id'          => $requestId,
            ]);
        } catch (PDOException $e) {
            error_log('Error al resolver solicitud de reset: ' . $e->getMessage());
            return false;
        }
    }

    public function discardRequest($requestId, $resolvedBy)
    {
        $query = "UPDATE {$this->tableName}
                  SET status = 'discarded',
                      resolved_by = :resolved_by,
                      resolved_at = NOW()
                  WHERE id = :id AND status = 'pending'";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':resolved_by' => $resolvedBy,
                ':id'          => $requestId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Compatibilidad: si la BD no soporta el estado "discarded" (por ejemplo, ENUM),
            // se marca como resuelta con etiqueta para distinguirla en historial.
            $fallbackQuery = "UPDATE {$this->tableName}
                              SET status = 'resolved',
                                  resolved_by = :resolved_by,
                                  resolved_at = NOW()
                              WHERE id = :id AND status = 'pending'";

            try {
                $fallbackStmt = $this->conn->prepare($fallbackQuery);
                $fallbackStmt->execute([
                    ':resolved_by' => '[DESCARTADA] ' . $resolvedBy,
                    ':id'          => $requestId,
                ]);
                return $fallbackStmt->rowCount() > 0;
            } catch (PDOException $fallbackException) {
                error_log('Error al descartar solicitud de reset: ' . $e->getMessage());
                error_log('Error en fallback de descarte: ' . $fallbackException->getMessage());
                return false;
            }
        }
    }

    public function findById($id)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
}
