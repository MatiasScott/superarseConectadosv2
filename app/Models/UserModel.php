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
        $query = "SELECT * FROM " . $this->table_name . " WHERE numero_identificacion = :cedula LIMIT 1";

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
        $query = "SELECT * FROM " . $this->table_name . " WHERE numero_identificacion = :identificacion LIMIT 1";

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
        $query = "SELECT programas.* FROM programas INNER JOIN users ON users.programa = programas.programa WHERE numero_identificacion = :identificacion LIMIT 1";

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
        ORDER BY u.primer_apellido ASC, u.primer_nombre ASC";

        try {
            $stmt = $this->conn->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todos los estudiantes: " . $e->getMessage());
            return [];
        }
    }
}
