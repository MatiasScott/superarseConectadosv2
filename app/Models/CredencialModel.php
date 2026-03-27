<?php
// app/Models/CredencialModel.php
require_once 'Database.php';

class CredencialModel extends Database
{

  public function getCredencialesByUserId(int $userId)
  {
    $db = $this->getConnection();

    try {
      // 1. Obtener datos del usuario principal
      $userQuery = "SELECT usuario, numero_identificacion FROM users WHERE id = :userId LIMIT 1";
      $userStmt = $db->prepare($userQuery);
      $userStmt->execute([':userId' => $userId]);
      $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);

      if (!$userInfo) {
        // Si no existe el usuario principal, salimos
        return [];
      }

      // El ID de usuario para Moodle debe ser el numero_identificacion (CEDULA)
      $cedulaParaMoodle = $userInfo['numero_identificacion'];
      
      // 2. Obtener la CLAVE de Moodle usando el numero_identificacion (CEDULA)
      $userQueryMoodle = "SELECT CLAVE, NIVEL FROM usersMoodle WHERE CEDULA = :cedula LIMIT 1";
      $userStmtMoodle = $db->prepare($userQueryMoodle);
      // Usamos el valor de la CEDULA/numero_identificacion para la búsqueda
      $userStmtMoodle->execute([':cedula' => $cedulaParaMoodle]);
      $userInfoMoodle = $userStmtMoodle->fetch(PDO::FETCH_ASSOC);

      // --- Asignación de variables ---
      $plataformaUsuario = $userInfo['usuario']; // Usuario principal (ej: email o nombre de usuario)
      $plataformaUsuarioMoodle = $cedulaParaMoodle; // La cédula es el usuario de Moodle
      
      // Corregido: Verificamos si la clave existe y accedemos a 'CLAVE', no a 'usuario'
      // Si no existe, usamos un valor por defecto (ej: 'N/A')
      $plataformaContraseñaMoodle = $userInfoMoodle ? $userInfoMoodle['CLAVE'] : 'Clave no encontrada';
      $plataformaNivelMoodle = $userInfoMoodle ? $userInfoMoodle['NIVEL'] : 'Nivel no encontrado';
      
      // 3. Obtener credenciales de la plataforma general
      $plataformaQuery = "SELECT 
                              plataforma, 
                              url AS link_acceso,
                              password_quemada AS clave_acceso 
                            FROM 
                              credenciales_plataforma 
                            ORDER BY 
                              plataforma ASC";

      $plataformaStmt = $db->prepare($plataformaQuery);
      $plataformaStmt->execute();
      $plataformas = $plataformaStmt->fetchAll(PDO::FETCH_ASSOC);
      
      $credencialesFinal = [];
      foreach ($plataformas as $p) {
        $credencialesFinal[] = [
          'plataforma' => $p['plataforma'],
          'usuario_acceso' => $plataformaUsuario,
          'usuario_acceso_moodle' => $plataformaUsuarioMoodle,
          'clave_acceso' => $p['clave_acceso'],
          'clave_acceso_moodle' => $plataformaContraseñaMoodle,
          'nivel_acceso_moodle' => $plataformaNivelMoodle,
          'link_acceso' => $p['link_acceso']
        ];
      }

      return $credencialesFinal;
    } catch (PDOException $e) {
      error_log("Error de SQL en CredencialModel: " . $e->getMessage());
      return [];
    }
  }
}