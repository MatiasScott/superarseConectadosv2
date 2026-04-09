<?php

require_once __DIR__ . '/Database.php';

class AuthAccountModel
{
    private $conn;
    private $tableName = 'access_accounts';

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function findById($accountId)
    {
        $query = "SELECT * FROM {$this->tableName} WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':id' => $accountId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error al buscar cuenta de acceso por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function findStudentAccountByIdentification($identification)
    {
        $query = "SELECT * FROM {$this->tableName}
                  WHERE role = 'student'
                    AND numero_identificacion = :numero_identificacion
                    AND is_active = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':numero_identificacion' => $identification]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error al buscar cuenta de estudiante: ' . $e->getMessage());
            return null;
        }
    }

    public function findAdminAccountByEmail($email)
    {
        $query = "SELECT * FROM {$this->tableName}
                  WHERE role = 'admin'
                    AND email = :email
                    AND is_active = 1
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':email' => strtolower(trim($email))]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error al buscar cuenta de administrador: ' . $e->getMessage());
            return null;
        }
    }

    public function ensureStudentAccount(array $user)
    {
        $identification = trim($user['numero_identificacion'] ?? '');

        if ($identification === '') {
            return null;
        }

        $existingAccount = $this->findStudentAccountByIdentification($identification);
        if ($existingAccount) {
            return $existingAccount;
        }

        return $this->createStudentAccount($user);
    }

    public function updatePasswordById($accountId, $passwordHash)
    {
        $query = "UPDATE {$this->tableName}
                  SET password_hash = :password_hash,
                      must_change_password = 0,
                      updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute([
                ':password_hash' => $passwordHash,
                ':id' => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('Error al actualizar contraseña: ' . $e->getMessage());
            return false;
        }
    }

    public function recordSuccessfulLogin($accountId)
    {
        $query = "UPDATE {$this->tableName}
                  SET last_login_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([':id' => $accountId]);
        } catch (PDOException $e) {
            error_log('Error al registrar último acceso: ' . $e->getMessage());
        }
    }

    public function createAdminAccount(array $data)
    {
        $email = strtolower(trim($data['email'] ?? ''));
        $displayName = trim($data['display_name'] ?? '');
        $identification = trim($data['numero_identificacion'] ?? '');
        $passwordHash = $data['password_hash'] ?? '';
        $mustChangePassword = !empty($data['must_change_password']) ? 1 : 0;

        if ($email === '' || $displayName === '' || $passwordHash === '') {
            return [
                'success' => false,
                'message' => 'Faltan datos obligatorios para crear la cuenta.',
            ];
        }

        if ($this->findAdminAccountByEmail($email)) {
            return [
                'success' => false,
                'message' => 'Ya existe una cuenta activa con ese correo.',
            ];
        }

        $query = "INSERT INTO {$this->tableName}
                  (role, user_id, numero_identificacion, email, display_name, password_hash, must_change_password, is_active, created_at, updated_at)
                  VALUES ('admin', NULL, :numero_identificacion, :email, :display_name, :password_hash, :must_change_password, 1, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':numero_identificacion' => $identification !== '' ? $identification : null,
                ':email' => $email,
                ':display_name' => $displayName,
                ':password_hash' => $passwordHash,
                ':must_change_password' => $mustChangePassword,
            ]);

            return [
                'success' => true,
                'account' => $this->findById((int) $this->conn->lastInsertId()),
            ];
        } catch (PDOException $e) {
            error_log('Error al crear cuenta de administrador: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'No fue posible crear la cuenta de administrador.',
            ];
        }
    }

    public function getAllAdminAccounts()
    {
        $query = "SELECT id, display_name, email, numero_identificacion, is_active,
                         must_change_password, last_login_at, created_at
                  FROM {$this->tableName}
                  WHERE role = 'admin'
                  ORDER BY created_at ASC";

        try {
            return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener cuentas admin: ' . $e->getMessage());
            return [];
        }
    }

    public function countAdminAccounts($search = '')
    {
        $query = "SELECT COUNT(*)
                  FROM {$this->tableName}
                  WHERE role = 'admin'";
        $params = [];

        if ($search !== '') {
            $query .= " AND (display_name LIKE :search OR email LIKE :search OR numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('Error al contar cuentas admin: ' . $e->getMessage());
            return 0;
        }
    }

    public function getAdminAccountsPaged($limit, $offset, $search = '')
    {
        $query = "SELECT id, display_name, email, numero_identificacion, is_active,
                         must_change_password, last_login_at, created_at
                  FROM {$this->tableName}
                  WHERE role = 'admin'";
        $params = [];

        if ($search !== '') {
            $query .= " AND (display_name LIKE :search OR email LIKE :search OR numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $query .= " ORDER BY created_at DESC
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        try {
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error al obtener cuentas admin paginadas: ' . $e->getMessage());
            return [];
        }
    }

    public function getStudentAccountsIndexedByIdentification()
    {
        $query = "SELECT id, user_id, numero_identificacion, display_name, is_active,
                         must_change_password, last_login_at, created_at
                  FROM {$this->tableName}
                  WHERE role = 'student'";

        try {
            $rows = $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
            $indexed = [];

            foreach ($rows as $row) {
                $indexed[$row['numero_identificacion']] = $row;
            }

            return $indexed;
        } catch (PDOException $e) {
            error_log('Error al obtener cuentas de estudiantes: ' . $e->getMessage());
            return [];
        }
    }

    public function getStudentAccountsByIdentifications(array $identifications)
    {
        if (empty($identifications)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach (array_values($identifications) as $index => $value) {
            $key = ':id_' . $index;
            $placeholders[] = $key;
            $params[$key] = $value;
        }

        $query = "SELECT id, user_id, numero_identificacion, display_name, is_active,
                         must_change_password, last_login_at, created_at
                  FROM {$this->tableName}
                  WHERE role = 'student'
                    AND numero_identificacion IN (" . implode(', ', $placeholders) . ")";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $indexed = [];

            foreach ($rows as $row) {
                $indexed[$row['numero_identificacion']] = $row;
            }

            return $indexed;
        } catch (PDOException $e) {
            error_log('Error al obtener cuentas de estudiantes por cédula: ' . $e->getMessage());
            return [];
        }
    }

    public function setActiveStatus($accountId, $isActive)
    {
        $query = "UPDATE {$this->tableName}
                  SET is_active = :is_active, updated_at = NOW()
                  WHERE id = :id AND role = 'admin'";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute([
                ':is_active' => $isActive ? 1 : 0,
                ':id'        => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('Error al cambiar estado de cuenta admin: ' . $e->getMessage());
            return false;
        }
    }

    public function setStudentActiveStatus($accountId, $isActive)
    {
        $query = "UPDATE {$this->tableName}
                  SET is_active = :is_active, updated_at = NOW()
                  WHERE id = :id AND role = 'student'";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute([
                ':is_active' => $isActive ? 1 : 0,
                ':id'        => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('Error al cambiar estado de cuenta estudiante: ' . $e->getMessage());
            return false;
        }
    }

    public function resetToTemporaryPassword($accountId, $hash)
    {
        $query = "UPDATE {$this->tableName}
                  SET password_hash = :hash,
                      must_change_password = 1,
                      updated_at = NOW()
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        try {
            return $stmt->execute([
                ':hash' => $hash,
                ':id'   => $accountId,
            ]);
        } catch (PDOException $e) {
            error_log('Error al restablecer contraseña temporal: ' . $e->getMessage());
            return false;
        }
    }

    private function createStudentAccount(array $user)
    {
        $displayName = trim(implode(' ', array_filter([
            $user['primer_nombre'] ?? '',
            $user['segundo_nombre'] ?? '',
            $user['primer_apellido'] ?? '',
            $user['segundo_apellido'] ?? '',
        ])));

        if ($displayName === '') {
            $displayName = 'Estudiante ' . trim($user['numero_identificacion'] ?? '');
        }

        $query = "INSERT INTO {$this->tableName}
                  (role, user_id, numero_identificacion, email, display_name, password_hash, must_change_password, is_active, created_at, updated_at)
                  VALUES ('student', :user_id, :numero_identificacion, NULL, :display_name, :password_hash, 1, 1, NOW(), NOW())";
        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute([
                ':user_id' => $user['id'],
                ':numero_identificacion' => trim($user['numero_identificacion']),
                ':display_name' => $displayName,
                ':password_hash' => password_hash(trim($user['numero_identificacion']), PASSWORD_DEFAULT),
            ]);

            return $this->findById((int) $this->conn->lastInsertId());
        } catch (PDOException $e) {
            error_log('Error al crear cuenta inicial de estudiante: ' . $e->getMessage());

            return $this->findStudentAccountByIdentification(trim($user['numero_identificacion'] ?? ''));
        }
    }
}