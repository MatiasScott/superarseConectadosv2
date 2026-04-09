<?php
// app/Models/UserModel.php

require_once 'Database.php';

class UserModel
{
    private $conn;
    private $table_name = "users";

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    public function findByCedula($cedula)
    {
                $query = "SELECT * FROM " . $this->table_name . "
                                    WHERE numero_identificacion = :cedula
                                        AND UPPER(TRIM(estado)) = 'ACTIVO'
                                        AND UPPER(TRIM(programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
                                    ORDER BY id DESC
                                    LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':cedula', $cedula);

        try {
            $stmt->execute();
            $user = $stmt->fetch();
            return $user ? $user : null;
        } catch (PDOException $e) {
            error_log("Error al buscar usuario por cédula: " . $e->getMessage());
            return null;
        }
    }

    public function getUserInfoByIdentificacion($identificacion)
    {
                $query = "SELECT * FROM " . $this->table_name . "
                                    WHERE numero_identificacion = :identificacion
                                        AND UPPER(TRIM(estado)) = 'ACTIVO'
                                        AND UPPER(TRIM(programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
                                    ORDER BY id DESC
                                    LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':identificacion', $identificacion);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener información del usuario: " . $e->getMessage());
            return null;
        }
    }

    public function getProgramaInfoByIdentificacion($identificacion)
    {
                $query = "SELECT programas.*
                                    FROM programas
                                    INNER JOIN users ON users.programa = programas.programa
                                    WHERE users.numero_identificacion = :identificacion
                                        AND UPPER(TRIM(users.estado)) = 'ACTIVO'
                                        AND UPPER(TRIM(users.programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
                                    ORDER BY users.id DESC
                                    LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':identificacion', $identificacion);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener información del programa: " . $e->getMessage());
            return null;
        }
    }
    public function getTutoresAcademicosByPrograma($programa)
    {
        $query = "SELECT * FROM docentes
INNER JOIN programas on programas.id = docentes.id_programa
WHERE programas.programa = :programa";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':programa', $programa);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener información del tutor academico: " . $e->getMessage());
            return null;
        }
    }

    public function getAllEstudiantes()
    {
        $query = "SELECT 
            u.id as id_usuario,
            u.numero_identificacion,
            u.codigo_matricula,
            CONCAT(u.primer_nombre, ' ', COALESCE(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', COALESCE(u.segundo_apellido, '')) as nombre_completo,
            u.correo_electronico,
            u.programa,
            u.telefono,
            u.direccion
        FROM " . $this->table_name . " u
                WHERE UPPER(TRIM(u.estado)) = 'ACTIVO'
                    AND UPPER(TRIM(u.programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
        ORDER BY u.primer_apellido ASC, u.primer_nombre ASC";

        try {
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todos los estudiantes: " . $e->getMessage());
            return [];
        }
    }

    public function findActiveStudentById($userId)
    {
        $query = "SELECT * FROM " . $this->table_name . "
                                    WHERE id = :user_id
                                        AND UPPER(TRIM(estado)) = 'ACTIVO'
                                        AND UPPER(TRIM(programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
                                    LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

        try {
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error al buscar estudiante por ID: " . $e->getMessage());
            return null;
        }
    }

    public function getDistinctProgramasActivos()
    {
        $query = "SELECT DISTINCT TRIM(programa) AS programa
                  FROM " . $this->table_name . "
                  WHERE UPPER(TRIM(estado)) = 'ACTIVO'
                    AND UPPER(TRIM(programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')
                    AND TRIM(programa) <> ''
                  ORDER BY TRIM(programa) ASC";

        try {
            $stmt = $this->conn->query($query);
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'programa');
        } catch (PDOException $e) {
            error_log("Error al obtener programas activos: " . $e->getMessage());
            return [];
        }
    }

    public function countEstudiantesFiltered($search = '', $programa = '')
    {
        $where = [
            "UPPER(TRIM(u.estado)) = 'ACTIVO'",
            "UPPER(TRIM(u.programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')",
        ];
        $params = [];

        if ($programa !== '') {
            $where[] = "TRIM(u.programa) = :programa";
            $params[':programa'] = $programa;
        }

        if ($search !== '') {
            $where[] = "(
                u.numero_identificacion LIKE :search
                OR CONCAT(
                    COALESCE(u.primer_nombre, ''), ' ',
                    COALESCE(u.segundo_nombre, ''), ' ',
                    COALESCE(u.primer_apellido, ''), ' ',
                    COALESCE(u.segundo_apellido, '')
                ) LIKE :search
                OR u.programa LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        $query = "SELECT COUNT(*)
                  FROM " . $this->table_name . " u
                  WHERE " . implode(' AND ', $where);

        $stmt = $this->conn->prepare($query);

        try {
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error al contar estudiantes filtrados: " . $e->getMessage());
            return 0;
        }
    }

    public function getEstudiantesPaged($limit, $offset, $search = '', $programa = '')
    {
        $where = [
            "UPPER(TRIM(u.estado)) = 'ACTIVO'",
            "UPPER(TRIM(u.programa)) NOT IN ('AUTO EVALUACION', 'AUTO EVALUCION', 'SEGUIMIENTO DOCENTE', 'EJEMPLO 1', 'EJEMPLO')",
        ];
        $params = [];

        if ($programa !== '') {
            $where[] = "TRIM(u.programa) = :programa";
            $params[':programa'] = $programa;
        }

        if ($search !== '') {
            $where[] = "(
                u.numero_identificacion LIKE :search
                OR CONCAT(
                    COALESCE(u.primer_nombre, ''), ' ',
                    COALESCE(u.segundo_nombre, ''), ' ',
                    COALESCE(u.primer_apellido, ''), ' ',
                    COALESCE(u.segundo_apellido, '')
                ) LIKE :search
                OR u.programa LIKE :search
            )";
            $params[':search'] = '%' . $search . '%';
        }

        $query = "SELECT 
                    u.id as id_usuario,
                    u.numero_identificacion,
                    u.codigo_matricula,
                    CONCAT(u.primer_nombre, ' ', COALESCE(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', COALESCE(u.segundo_apellido, '')) as nombre_completo,
                    u.correo_electronico,
                    u.programa,
                    u.telefono,
                    u.direccion
                FROM " . $this->table_name . " u
                WHERE " . implode(' AND ', $where) . "
                ORDER BY u.primer_apellido ASC, u.primer_nombre ASC
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
            error_log("Error al obtener estudiantes paginados: " . $e->getMessage());
            return [];
        }
    }
}
