<?php
// app/Models/AsignaturaModel.php
require_once 'Database.php';

class AsignaturaModel extends Database
{

  public function getAsignaturasByIdentificacion(string $identificacion)
  {
    $db = $this->getConnection();
    $query = "SELECT DISTINCT
                    a.codigo AS codigo,
                    a.nombre AS nombre,
                    a.estado AS estado, 
                    'N/D' AS creditos
                  FROM 
                    users u
                  JOIN 
                    programas p ON u.programa = p.programa
                  JOIN 
                    asignaturas a ON p.id = a.programa_id
                  WHERE 
                    u.numero_identificacion = :identificacion
                  ORDER BY 
                    a.nombre ASC";

    $stmt = $db->prepare($query);

    try {
      $stmt->execute([':identificacion' => $identificacion]);
      $rawAsignaturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Error de SQL en AsignaturaModel: " . $e->getMessage());
      return ['SQL_ERROR' => $e->getMessage()];
    }
    $groupedAsignaturas = [
      'N1' => [],
      'N2' => [],
      'N3' => [],
      'N4' => [],
      'N5' => [],
      'Otras' => []
    ];

    foreach ($rawAsignaturas as $asignatura) {
      $nombre = $asignatura['nombre'];
      $nivelEncontrado = false;
      if (preg_match('/-N(\d+)/', $nombre, $matches)) {
        $nivel = 'N' . $matches[1];
        if (in_array($nivel, array_keys($groupedAsignaturas))) {
          $groupedAsignaturas[$nivel][] = $asignatura;
          $nivelEncontrado = true;
        }
      }
      if (!$nivelEncontrado) {
        $groupedAsignaturas['Prácticas y Vinculación'][] = $asignatura;
      }
    }
    return $groupedAsignaturas;
  }
}