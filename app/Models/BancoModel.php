<?php
// app/Models/BancoModel.php
require_once 'Database.php';

class BancoModel extends Database
{

    protected $table_name = "bancos_info";

    public function getAllBancosActivos()
    {
        $db = $this->getConnection();
        $query = "SELECT id, nombre_banco, tipo_cuenta, numero_cuenta FROM " . $this->table_name . " WHERE activo = 1 ORDER BY id ASC";
        $stmt = $db->prepare($query);

        try {
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener información de bancos: " . $e->getMessage());
            return [];
        }
    }
}
