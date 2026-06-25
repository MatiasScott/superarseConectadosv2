<?php
require_once __DIR__ . '/Database.php';

class EntidadModel extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getEntidades(string $search = ''): array
    {
        $sql = "SELECT
                    e.*,
                    p.programa AS programa_nombre,
                    p.codigo AS programa_codigo,
                    t.nombre_completo AS tutor_nombre,
                    t.cedula AS tutor_cedula,
                    t.funcion AS tutor_funcion,
                    t.email AS tutor_email,
                    t.telefono AS tutor_telefono,
                    t.departamento AS tutor_departamento
                FROM entidades e
                LEFT JOIN programas p ON p.id = e.id_programa
                LEFT JOIN tutores_empresariales t ON t.id_tutor_empresa = e.id_tutor_empresarial";

        $params = [];
        if ($search !== '') {
            $sql .= "
                WHERE (
                    e.nombre_empresa LIKE :search
                    OR e.ruc LIKE :search
                    OR e.razon_social LIKE :search
                    OR e.persona_contacto LIKE :search
                    OR p.programa LIKE :search
                    OR t.nombre_completo LIKE :search
                    OR t.cedula LIKE :search
                )";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY e.id_entidad DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEntidadById(int $idEntidad): ?array
    {
        $sql = "SELECT
                    e.*,
                    p.programa AS programa_nombre,
                    p.codigo AS programa_codigo,
                    t.nombre_completo AS tutor_nombre,
                    t.cedula AS tutor_cedula,
                    t.funcion AS tutor_funcion,
                    t.email AS tutor_email,
                    t.telefono AS tutor_telefono,
                    t.departamento AS tutor_departamento
                FROM entidades e
                LEFT JOIN programas p ON p.id = e.id_programa
                LEFT JOIN tutores_empresariales t ON t.id_tutor_empresa = e.id_tutor_empresarial
                WHERE e.id_entidad = :id_entidad
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_entidad' => $idEntidad]);

        $entity = $stmt->fetch(PDO::FETCH_ASSOC);
        return $entity ?: null;
    }

    public function getProgramas(): array
    {
        $stmt = $this->db->query("SELECT id, codigo, programa, estado FROM programas ORDER BY programa ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTutoresEmpresariales(): array
    {
        $stmt = $this->db->query("SELECT id_tutor_empresa, cedula, nombre_completo, funcion, telefono, email, departamento FROM tutores_empresariales ORDER BY nombre_completo ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function existePrograma(int $idPrograma): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM programas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $idPrograma]);
        return (bool) $stmt->fetchColumn();
    }

    public function existeTutor(int $idTutor): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM tutores_empresariales WHERE id_tutor_empresa = :id LIMIT 1");
        $stmt->execute([':id' => $idTutor]);
        return (bool) $stmt->fetchColumn();
    }

    public function existeRuc(string $ruc, ?int $excluirIdEntidad = null): bool
    {
        $rucNormalizado = preg_replace('/\D+/', '', $ruc);

        $sql = "SELECT 1
                FROM entidades
                WHERE REPLACE(REPLACE(REPLACE(ruc, '-', ''), ' ', ''), '.', '') = :ruc";
        $params = [':ruc' => $rucNormalizado];

        if ($excluirIdEntidad !== null) {
            $sql .= " AND id_entidad <> :id_entidad";
            $params[':id_entidad'] = $excluirIdEntidad;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function existeRucEnPrograma(string $ruc, int $idPrograma, ?int $excluirIdEntidad = null): bool
    {
        $rucNormalizado = preg_replace('/\D+/', '', $ruc);

        $sql = "SELECT 1
                FROM entidades
                WHERE REPLACE(REPLACE(REPLACE(ruc, '-', ''), ' ', ''), '.', '') = :ruc
                  AND id_programa = :id_programa";
        $params = [
            ':ruc' => $rucNormalizado,
            ':id_programa' => $idPrograma,
        ];

        if ($excluirIdEntidad !== null) {
            $sql .= " AND id_entidad <> :id_entidad";
            $params[':id_entidad'] = $excluirIdEntidad;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public function crearEntidad(array $payload): int
    {
        $this->db->beginTransaction();

        try {
            $idTutor = $this->resolverTutor($payload);

            $sql = "INSERT INTO entidades (
                        nombre_empresa,
                        ruc,
                        razon_social,
                        persona_contacto,
                        telefono_contacto,
                        email_contacto,
                        plazas_disponibles,
                        estado,
                        direccion,
                        id_programa,
                        id_tutor_empresarial
                    ) VALUES (
                        :nombre_empresa,
                        :ruc,
                        :razon_social,
                        :persona_contacto,
                        :telefono_contacto,
                        :email_contacto,
                        :plazas_disponibles,
                        :estado,
                        :direccion,
                        :id_programa,
                        :id_tutor_empresarial
                    )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nombre_empresa' => $payload['nombre_empresa'],
                ':ruc' => $payload['ruc'],
                ':razon_social' => $payload['razon_social'] ?: null,
                ':persona_contacto' => $payload['persona_contacto'] ?: null,
                ':telefono_contacto' => $payload['telefono_contacto'] ?: null,
                ':email_contacto' => $payload['email_contacto'] ?: null,
                ':plazas_disponibles' => (int) $payload['plazas_disponibles'],
                ':estado' => $payload['estado'],
                ':direccion' => $payload['direccion'] ?: null,
                ':id_programa' => $payload['id_programa'] > 0 ? (int) $payload['id_programa'] : null,
                ':id_tutor_empresarial' => $idTutor,
            ]);

            $idEntidad = (int) $this->db->lastInsertId();
            $this->db->commit();

            return $idEntidad;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function actualizarEntidad(int $idEntidad, array $payload): bool
    {
        $this->db->beginTransaction();

        try {
            $idTutor = $this->resolverTutor($payload);

            $sql = "UPDATE entidades SET
                        nombre_empresa = :nombre_empresa,
                        ruc = :ruc,
                        razon_social = :razon_social,
                        persona_contacto = :persona_contacto,
                        telefono_contacto = :telefono_contacto,
                        email_contacto = :email_contacto,
                        plazas_disponibles = :plazas_disponibles,
                        estado = :estado,
                        direccion = :direccion,
                        id_programa = :id_programa,
                        id_tutor_empresarial = :id_tutor_empresarial
                    WHERE id_entidad = :id_entidad";

            $stmt = $this->db->prepare($sql);
            $ok = $stmt->execute([
                ':id_entidad' => $idEntidad,
                ':nombre_empresa' => $payload['nombre_empresa'],
                ':ruc' => $payload['ruc'],
                ':razon_social' => $payload['razon_social'] ?: null,
                ':persona_contacto' => $payload['persona_contacto'] ?: null,
                ':telefono_contacto' => $payload['telefono_contacto'] ?: null,
                ':email_contacto' => $payload['email_contacto'] ?: null,
                ':plazas_disponibles' => (int) $payload['plazas_disponibles'],
                ':estado' => $payload['estado'],
                ':direccion' => $payload['direccion'] ?: null,
                ':id_programa' => $payload['id_programa'] > 0 ? (int) $payload['id_programa'] : null,
                ':id_tutor_empresarial' => $idTutor,
            ]);

            $this->db->commit();
            return $ok;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function resolverTutor(array $payload): ?int
    {
        $idTutor = (int) ($payload['id_tutor_empresarial'] ?? 0);
        $cedula = trim((string) ($payload['tutor_cedula'] ?? ''));
        $nombre = trim((string) ($payload['tutor_nombre_completo'] ?? ''));
        $funcion = trim((string) ($payload['tutor_funcion'] ?? ''));
        $telefono = trim((string) ($payload['tutor_telefono'] ?? ''));
        $email = trim((string) ($payload['tutor_email'] ?? ''));
        $departamento = trim((string) ($payload['tutor_departamento'] ?? ''));

        $hayDatosTutor = ($cedula !== '' || $nombre !== '' || $funcion !== '' || $telefono !== '' || $email !== '' || $departamento !== '');

        if ($idTutor > 0) {
            if ($hayDatosTutor) {
                $sql = "UPDATE tutores_empresariales SET
                            cedula = :cedula,
                            nombre_completo = :nombre_completo,
                            funcion = :funcion,
                            telefono = :telefono,
                            email = :email,
                            departamento = :departamento
                        WHERE id_tutor_empresa = :id_tutor_empresa";

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_tutor_empresa' => $idTutor,
                    ':cedula' => $cedula,
                    ':nombre_completo' => $nombre,
                    ':funcion' => $funcion ?: null,
                    ':telefono' => $telefono ?: null,
                    ':email' => $email ?: null,
                    ':departamento' => $departamento ?: null,
                ]);
            }

            return $idTutor;
        }

        if (!$hayDatosTutor) {
            return null;
        }

        if ($cedula !== '') {
            $stmt = $this->db->prepare("SELECT id_tutor_empresa FROM tutores_empresariales WHERE cedula = :cedula LIMIT 1");
            $stmt->execute([':cedula' => $cedula]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $idTutorExistente = (int) $existing['id_tutor_empresa'];
                $update = $this->db->prepare("UPDATE tutores_empresariales SET
                        nombre_completo = :nombre_completo,
                        funcion = :funcion,
                        telefono = :telefono,
                        email = :email,
                        departamento = :departamento
                    WHERE id_tutor_empresa = :id_tutor_empresa");
                $update->execute([
                    ':id_tutor_empresa' => $idTutorExistente,
                    ':nombre_completo' => $nombre,
                    ':funcion' => $funcion ?: null,
                    ':telefono' => $telefono ?: null,
                    ':email' => $email ?: null,
                    ':departamento' => $departamento ?: null,
                ]);

                return $idTutorExistente;
            }
        }

        $insert = $this->db->prepare("INSERT INTO tutores_empresariales
                (cedula, nombre_completo, funcion, telefono, email, departamento)
            VALUES (:cedula, :nombre_completo, :funcion, :telefono, :email, :departamento)");
        $insert->execute([
            ':cedula' => $cedula,
            ':nombre_completo' => $nombre,
            ':funcion' => $funcion ?: null,
            ':telefono' => $telefono ?: null,
            ':email' => $email ?: null,
            ':departamento' => $departamento ?: null,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
