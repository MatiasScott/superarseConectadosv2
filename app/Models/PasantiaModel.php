<?php
require_once 'Database.php';
class PasantiaModel extends Database
{
    private $db;

    public function __construct()
    {
        $this->db = $this->getConnection();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    public function getActiveDocentes()
    {
        $query = "SELECT id_docente, nombre_completo, estado FROM docentes WHERE estado = 'Activo' ORDER BY nombre_completo ASC";
        try {
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener docentes: " . $e->getMessage());
            return [];
        }
    }
    public function getActivePracticaByUserId(int $userId)
    {
        $query = "SELECT
            pe.id_practica,
            pe.modalidad,
            pe.estado_fase_uno_completado,
            pe.afiliacion_iess,
            ent.ruc,
            pmod.id_practica_modalidad,
            ent.nombre_empresa,
            ent.razon_social,
            ent.persona_contacto,
            ent.telefono_contacto,
            ent.email_contacto,
            ent.direccion,
            ent.plazas_disponibles,
            tutemp.nombre_completo,
            tutemp.cedula,
            tutemp.funcion,
            tutemp.email,
            tutemp.telefono,
            tutemp.departamento
        FROM
            practicas_estudiantes pe
        INNER JOIN practica_modalidad pmod ON pmod.modalidad = pe.modalidad
        INNER JOIN entidades ent ON ent.id_entidad = pe.entidad_id
        LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
        WHERE user_id = :userId LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPracticaById(int $practicaId)
    {
        $query = "SELECT
            pe.id_practica,
            pe.modalidad,
            pe.estado_fase_uno_completado,
            pe.afiliacion_iess,
            pe.user_id,
            pe.docente_asignado_id,
            pe.fecha_registro,
            ent.ruc,
            ent.nombre_empresa,
            pmod.id_practica_modalidad,
            ent.nombre_empresa AS entidad_nombre_empresa,
            ent.razon_social,
            ent.persona_contacto,
            ent.telefono_contacto,
            ent.email_contacto,
            ent.direccion,
            ent.plazas_disponibles,
            tutemp.nombre_completo AS tutor_emp_nombre_completo,
            tutemp.cedula AS tutor_emp_cedula,
            tutemp.funcion AS tutor_emp_funcion,
            tutemp.email AS tutor_emp_email,
            tutemp.telefono AS tutor_emp_telefono,
            tutemp.departamento AS tutor_emp_departamento,
            u.codigo_matricula,
            u.programa,
            CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) AS estudiante_nombre
        FROM
            practicas_estudiantes pe
        INNER JOIN practica_modalidad pmod ON pmod.modalidad = pe.modalidad
        INNER JOIN entidades ent ON ent.id_entidad = pe.entidad_id
        LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
        INNER JOIN users u ON u.id = pe.user_id
        WHERE pe.id_practica = :practicaId LIMIT 1";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':practicaId' => $practicaId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener práctica por ID: " . $e->getMessage());
            return null;
        }
    }

    private function getTutorAcademicoId(string $programa): ?int
    {
        $query = "SELECT docentes.id_docente FROM docentes INNER JOIN programas ON programas.id = docentes.id_programa WHERE programas.programa = :programa LIMIT 1";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':programa' => $programa]);
            $tutor = $stmt->fetch(PDO::FETCH_ASSOC);
            return $tutor ? (int)$tutor['id_docente'] : null;
        } catch (PDOException $e) {
            error_log("Error al obtener el Id del Tutor: " . $e->getMessage());
            return null;
        }
    }
    public function savePasantiaPhaseOne(array $data)
    {
        if (empty($data['user_id']) || empty($data['programa']) || empty($data['modalidad']) || empty($data['entidad_ruc'])) {
            error_log("Datos incompletos para Fase 1.");
            return false;
        }

        $this->db->beginTransaction();

        try {
            $user_id = $data['user_id'];
            $programa = $data['programa'];

            $docente_asignado_id = $this->getTutorAcademicoId($programa);
            if (!$docente_asignado_id) {
                throw new Exception("No se pudo asignar un docente para la carrera: " . $programa);
            }
            $entidad_id = null;
            $stmt = $this->db->prepare("SELECT id_entidad FROM entidades WHERE ruc = :ruc");
            $stmt->execute([':ruc' => $data['entidad_ruc']]);
            $existing_entidad = $stmt->fetch();

            $entidad_data = [
                ':nombre_empresa' => $data['entidad_nombre_empresa'] ?? 'N/A',
                ':ruc' => $data['entidad_ruc'],
                ':razon_social' => $data['entidad_razon_social'] ?? null,
                ':persona_contacto' => $data['entidad_persona_contacto'] ?? null,
                ':telefono_contacto' => $data['entidad_telefono_contacto'] ?? null,
                ':email_contacto' => $data['entidad_email_contacto'] ?? null,
                ':direccion' => $data['entidad_direccion'] ?? null,
                ':id_programa' => $data['id_programa'] ?? null
            ];

            if ($existing_entidad) {
                $entidad_id = $existing_entidad['id_entidad'];
                $update_query = "UPDATE entidades 
                SET nombre_empresa = :nombre_empresa, razon_social = :razon_social, persona_contacto = :persona_contacto, 
                    telefono_contacto = :telefono_contacto, email_contacto = :email_contacto, direccion = :direccion 
                WHERE id_entidad = :id_entidad";

                $update_data = [
                    ':nombre_empresa' => $entidad_data[':nombre_empresa'],
                    ':razon_social' => $entidad_data[':razon_social'],
                    ':persona_contacto' => $entidad_data[':persona_contacto'],
                    ':telefono_contacto' => $entidad_data[':telefono_contacto'],
                    ':email_contacto' => $entidad_data[':email_contacto'],
                    ':id_entidad' => $entidad_id,
                    ':direccion' => $entidad_data[':direccion']
                ];

                $stmt = $this->db->prepare($update_query);
                $stmt->execute($update_data);
            } else {
                $insert_query = "INSERT INTO entidades 
                (nombre_empresa, ruc, razon_social, persona_contacto, telefono_contacto, email_contacto, direccion, id_programa)
                VALUES (:nombre_empresa, :ruc, :razon_social, :persona_contacto, :telefono_contacto, :email_contacto, :direccion, :id_programa)";
                $stmt = $this->db->prepare($insert_query);
                $stmt->execute($entidad_data);
                $entidad_id = $this->db->lastInsertId();
            }

            $tutor_emp_id = null;

            $tutor_data = [
                ':cedula' => trim($data['tutor_emp_cedula'] ?? ''),
                ':nombre_completo' => trim($data['tutor_emp_nombre_completo'] ?? ''),
                ':funcion' => trim($data['tutor_emp_funcion'] ?? ''),
                ':telefono' => trim($data['tutor_emp_telefono'] ?? ''),
                ':email' => trim($data['tutor_emp_email'] ?? ''),
                ':departamento' => trim($data['tutor_emp_departamento'] ?? ''),
            ];

            $allEmpty = empty($tutor_data[':cedula'])
                && empty($tutor_data[':nombre_completo'])
                && empty($tutor_data[':funcion'])
                && empty($tutor_data[':telefono'])
                && empty($tutor_data[':email'])
                && empty($tutor_data[':departamento']);

            if (!$allEmpty) {
                $stmt = $this->db->prepare("SELECT id_tutor_empresa FROM tutores_empresariales WHERE cedula = :cedula");
                $stmt->execute([':cedula' => $tutor_data[':cedula']]);
                $existing_tutor = $stmt->fetch();

                if ($existing_tutor) {
                    $tutor_emp_id = $existing_tutor['id_tutor_empresa'];
                    $update_query = "UPDATE tutores_empresariales 
                    SET nombre_completo = :nombre_completo, funcion = :funcion, telefono = :telefono, 
                        email = :email, departamento = :departamento 
                    WHERE id_tutor_empresa = :id_tutor_empresa";

                    $update_data = [
                        ':nombre_completo' => $tutor_data[':nombre_completo'] ?: 'N/A',
                        ':funcion' => $tutor_data[':funcion'] ?: null,
                        ':telefono' => $tutor_data[':telefono'] ?: null,
                        ':email' => $tutor_data[':email'] ?: null,
                        ':departamento' => $tutor_data[':departamento'] ?: null,
                        ':id_tutor_empresa' => $tutor_emp_id,
                    ];

                    $stmt = $this->db->prepare($update_query);
                    $stmt->execute($update_data);
                } else {
                    $insert_query = "INSERT INTO tutores_empresariales 
                    (cedula, nombre_completo, funcion, telefono, email, departamento)
                    VALUES (:cedula, :nombre_completo, :funcion, :telefono, :email, :departamento)";
                    $stmt = $this->db->prepare($insert_query);
                    $stmt->execute($tutor_data);
                    $tutor_emp_id = $this->db->lastInsertId();
                }
            }

            $insert_practica_query = "
            INSERT INTO practicas_estudiantes (
                user_id, modalidad, docente_asignado_id, entidad_id, tutor_empresarial_id, 
                estado_fase_uno_completado, fecha_registro, proyecto_id, afiliacion_iess
            ) VALUES (
                :user_id, :modalidad, :docente_asignado_id, :entidad_id, :tutor_empresarial_id, 
                :estado_fase_uno_completado, :fecha_registro, :proyecto_id, :afiliacion_iess
            )
        ";

            $stmt = $this->db->prepare($insert_practica_query);
            $stmt->execute([
                ':user_id' => $user_id,
                ':modalidad' => $data['modalidad'],
                ':docente_asignado_id' => $docente_asignado_id,
                ':entidad_id' => $entidad_id,
                ':tutor_empresarial_id' => $tutor_emp_id ?? null,
                ':estado_fase_uno_completado' => 0,
                ':fecha_registro' => date('Y-m-d H:i:s'),
                ':proyecto_id' => $data['idProyecto'] ?? null,
                ':afiliacion_iess' => $data['afiliacion_iees'] ?? null
            ]);

            $practica_id = $this->db->lastInsertId();
            $this->db->commit();

            return (int)$practica_id;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al guardar Pasantia Fase 1: " . $e->getMessage() . " Linea: " . $e->getLine());
            return $e->getMessage();
        }
    }
    public function getProgramaTrabajo(int $practicaId, int $limit = 100, int $offset = 0)
    {
        $query = "SELECT * FROM programa_trabajo 
              WHERE practica_id = :practica_id 
              ORDER BY fecha_planificada ASC 
              LIMIT :limit OFFSET :offset";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':practica_id', $practicaId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener Plan de Aprendizaje: " . $e->getMessage());
            return [];
        }
    }
    public function addProgramaTrabajo(array $data)
    {
        $query = "
            INSERT INTO programa_trabajo (practica_id, actividad_planificada, departamento_area, funcion_asignada, fecha_planificada)
            VALUES (:practica_id, :actividad_planificada, :departamento_area, :funcion_asignada, :fecha_planificada)
        ";
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':practica_id' => $data['practica_id'],
                ':actividad_planificada' => $data['actividad_planificada'],
                ':departamento_area' => $data['departamento_area'] ?? null,
                ':funcion_asignada' => $data['funcion_asignada'] ?? null,
                ':fecha_planificada' => $data['fecha_planificada']
            ]);
        } catch (PDOException $e) {
            error_log("Error al insertar Plan de Aprendizaje: " . $e->getMessage());
            return false;
        }
    }

    public function updateProgramaTrabajo(array $data)
    {
        $query = "
        UPDATE programa_trabajo 
        SET actividad_planificada = :actividad_planificada,
            departamento_area = :departamento_area,
            funcion_asignada = :funcion_asignada,
            fecha_planificada = :fecha_planificada
        WHERE id_programa = :id
    ";

        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':actividad_planificada' => $data['actividad_planificada'],
                ':departamento_area' => $data['departamento_area'] ?? null,
                ':funcion_asignada' => $data['funcion_asignada'] ?? null,
                ':fecha_planificada' => $data['fecha_planificada'],
                ':id' => $data['id']
            ]);
        } catch (PDOException $e) {
            error_log("Error al actualizar Plan de Aprendizaje: " . $e->getMessage());
            return false;
        }
    }

    public function deleteProgramaTrabajo(int $id)
    {
        try {
            // Primero, obtener los datos del plan ANTES de eliminarlo
            $queryGet = "SELECT 
                            pt.id_programa,
                            pt.practica_id,
                            pt.nombre_actividad,
                            pt.horas,
                            pt.fecha_inicio,
                            pt.fecha_fin,
                            CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                            u.id as estudiante_id,
                            u.programa as programa_descripcion,
                            e.nombre_empresa
                        FROM programa_trabajo pt
                        INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                        INNER JOIN users u ON u.id = ps.user_id
                        INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
                        WHERE pt.id_programa = :id";

            $stmtGet = $this->db->prepare($queryGet);
            $stmtGet->execute([':id' => $id]);
            $datosEliminado = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($datosEliminado) {
                // Registrar en auditoría
                $datosAuditoria = [
                    'estudiante' => $datosEliminado['estudiante_nombre'] ?? 'N/A',
                    'estudiante_id' => $datosEliminado['estudiante_id'] ?? null,
                    'descripcion' => $datosEliminado['nombre_actividad'] ?? '',
                    'empresa' => $datosEliminado['nombre_empresa'] ?? '',
                    'horas' => $datosEliminado['horas'] ?? 0,
                    'fecha_inicio' => $datosEliminado['fecha_inicio'] ?? null,
                    'fecha_fin' => $datosEliminado['fecha_fin'] ?? null,
                    'programa' => $datosEliminado['programa_descripcion'] ?? ''
                ];

                $this->registrarEliminacion('PLAN', $id, $datosAuditoria);
            }

            // Luego eliminar de la tabla
            $query = "DELETE FROM programa_trabajo WHERE id_programa = :id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error al eliminar Plan de Aprendizaje: " . $e->getMessage());
            return false;
        }
    }

    public function getTotalProgramaTrabajo(int $practicaId)
    {
        $query = "SELECT COUNT(*) as total FROM programa_trabajo WHERE practica_id = :practica_id";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':practica_id' => $practicaId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error al contar Plan de Aprendizaje: " . $e->getMessage());
            return 0;
        }
    }
    public function getActividadesDiarias(int $practicaId)
    {
        $query = "SELECT * FROM actividades_diarias WHERE practica_id = :practica_id ORDER BY fecha_actividad DESC";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':practica_id' => $practicaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener Actividades Diarias: " . $e->getMessage());
            return [];
        }
    }

    public function getActividadesDiariasPaginated(int $practicaId, int $offset, int $limit, ?string $search = null, string $sortBy = 'fecha_actividad', string $sortDir = 'DESC')
    {
        $allowedSort = ['fecha_actividad', 'horas_invertidas', 'actividad_realizada'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'fecha_actividad';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM actividades_diarias WHERE practica_id = :practica_id";
        $params = [':practica_id' => $practicaId];

        if ($search) {
            $sql .= " AND (actividad_realizada LIKE :search OR observaciones LIKE :search OR fecha_actividad LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY {$sortBy} {$sortDir} LIMIT :offset, :limit";

        try {
            $stmt = $this->db->prepare($sql);
            // bind params
            $stmt->bindValue(':practica_id', $practicaId, PDO::PARAM_INT);
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en paginación Actividades Diarias: " . $e->getMessage());
            return [];
        }
    }

    public function countActividadesDiarias(int $practicaId, ?string $search = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM actividades_diarias WHERE practica_id = :practica_id";
        $params = [':practica_id' => $practicaId];
        if ($search) {
            $sql .= " AND (actividad_realizada LIKE :search OR observaciones LIKE :search OR fecha_actividad LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error al contar Actividades Diarias: " . $e->getMessage());
            return 0;
        }
    }
    public function getActividadDiaria(int $id, int $practicaId)
    {
        $query = "SELECT * FROM actividades_diarias WHERE id = :id AND practica_id = :practica_id";
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id, ':practica_id' => $practicaId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error al obtener Actividad Diaria: " . $e->getMessage());
            return null;
        }
    }

    public function updateActividadDiaria(array $data)
    {
        if (empty($data['id']) || empty($data['practica_id'])) {
            error_log("Validación fallida: Faltan id o practica_id para la actualización.");
            return false;
        }

        $horas_invertidas = (float) $data['horas_invertidas'];
        if ($horas_invertidas <= 0 || $horas_invertidas > 12.00) {
            error_log("Validación fallida: Horas invertidas fuera de rango al actualizar.");
            return false;
        }

        $query = "
        UPDATE actividades_diarias 
        SET 
            actividad_realizada = :actividad_realizada,
            horas_invertidas = :horas_invertidas,
            fecha_actividad = :fecha_actividad,
            hora_inicio = :hora_inicio,
            hora_fin = :hora_fin
        WHERE id_actividad_diaria = :id 
          AND practica_id = :practica_id
    ";

        try {
            $stmt = $this->db->prepare($query);
            $resultado = $stmt->execute([
                ':actividad_realizada' => $data['actividad_realizada'],
                ':horas_invertidas'   => $data['horas_invertidas'],
                ':fecha_actividad'    => $data['fecha_actividad'],
                ':hora_inicio'        => $data['hora_inicio'],
                ':hora_fin'           => $data['hora_fin'],
                ':id'                 => $data['id'],
                ':practica_id'        => $data['practica_id']
            ]);

            if ($resultado && $stmt->rowCount() === 0) {
                error_log("ADVERTENCIA: No se actualizó ningún registro (ID: {$data['id']}, Práctica: {$data['practica_id']})");
            }

            return $resultado;
        } catch (PDOException $e) {
            error_log("Error al actualizar Actividad Diaria: " . $e->getMessage());
            return false;
        }
    }

    public function deleteActividadDiaria(int $id, int $practicaId)
    {
        try {
            // Primero, obtener los datos de la actividad ANTES de eliminarla
            $queryGet = "SELECT 
                            ad.id_actividad_diaria,
                            ad.actividad_realizada,
                            ad.horas_invertidas,
                            ad.fecha_actividad,
                            CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                            u.id as estudiante_id,
                            u.programa as programa_descripcion,
                            e.nombre_empresa
                        FROM actividades_diarias ad
                        INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                        INNER JOIN users u ON u.id = ps.user_id
                        INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
                        WHERE ad.id_actividad_diaria = :id AND ad.practica_id = :practica_id";

            $stmtGet = $this->db->prepare($queryGet);
            $stmtGet->execute([':id' => $id, ':practica_id' => $practicaId]);
            $datosEliminado = $stmtGet->fetch(PDO::FETCH_ASSOC);

            if ($datosEliminado) {
                // Registrar en auditoría
                $datosAuditoria = [
                    'estudiante' => $datosEliminado['estudiante_nombre'] ?? 'N/A',
                    'estudiante_id' => $datosEliminado['estudiante_id'] ?? null,
                    'descripcion' => $datosEliminado['actividad_realizada'] ?? '',
                    'empresa' => $datosEliminado['nombre_empresa'] ?? '',
                    'horas' => $datosEliminado['horas_invertidas'] ?? 0,
                    'fecha_inicio' => $datosEliminado['fecha_actividad'] ?? null,
                    'fecha_fin' => $datosEliminado['fecha_actividad'] ?? null,
                    'programa' => $datosEliminado['programa_descripcion'] ?? ''
                ];

                $this->registrarEliminacion('ACTIVIDAD', $id, $datosAuditoria);
            }

            // Luego eliminar de la tabla
            $query = "DELETE FROM actividades_diarias WHERE id_actividad_diaria = :id AND practica_id = :practica_id";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':id' => $id,
                ':practica_id' => $practicaId
            ]);
        } catch (PDOException $e) {
            error_log("Error al eliminar Actividad Diaria: " . $e->getMessage());
            return false;
        }
    }

    public function addActividadDiaria(array $data)
    {
        $horas_invertidas = (float) $data['horas_invertidas'];
        if ($horas_invertidas <= 0 || $horas_invertidas > 12.00) {
            error_log("Validación fallida: Horas invertidas fuera del rango permitido.");
            return false;
        }

        $query = "
        INSERT INTO actividades_diarias (
            practica_id, 
            actividad_realizada, 
            horas_invertidas, 
            fecha_actividad, 
            hora_inicio, 
            hora_fin
        ) VALUES (
            :practica_id, 
            :actividad_realizada, 
            :horas_invertidas, 
            :fecha_actividad, 
            :hora_inicio, 
            :hora_fin
        )
    ";

        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':practica_id' => $data['practica_id'],
                ':actividad_realizada' => $data['actividad_realizada'],
                ':horas_invertidas' => $data['horas_invertidas'],
                ':fecha_actividad' => $data['fecha_actividad'],
                ':hora_inicio' => $data['hora_inicio'],
                ':hora_fin' => $data['hora_fin']
            ]);
        } catch (PDOException $e) {
            error_log("Error al insertar Actividad Diaria: " . $e->getMessage());
            return false;
        }
    }

    public function getEntidadByRUC($ruc, $idPrograma)
    {
        try {
            if ($ruc === '1702051704001') {
                $stmt = $this->db->prepare("
            SELECT * FROM entidades 
            LEFT JOIN tutores_empresariales te ON entidades.id_tutor_empresarial = te.id_tutor_empresa
            WHERE ruc = :ruc
            LIMIT 1
            ");
                $stmt->execute([':ruc' => $ruc]);
                $entidad = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->db->prepare("
            SELECT * FROM entidades 
            LEFT JOIN tutores_empresariales te ON entidades.id_tutor_empresarial = te.id_tutor_empresa
            WHERE ruc = :ruc AND entidades.id_programa = :idPrograma
            LIMIT 1
            ");
                $stmt->execute([':ruc' => $ruc, ':idPrograma' => $idPrograma]);
                $entidad = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return $entidad ?: null;
        } catch (Exception $e) {
            error_log("Error al buscar entidad por RUC: " . $e->getMessage());
            return null;
        }
    }

    public function getProyectos()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM proyectos");
            $stmt->execute();
            $entidad = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $entidad ?: null;
        } catch (Exception $e) {
            error_log("Error al buscar entidad por RUC: " . $e->getMessage());
            return null;
        }
    }

    public function getPracticaModalidad()
    {
        try {
            $stmt = $this->db->prepare("
            SELECT * FROM practica_modalidad 
            WHERE estado = 'Activo'
            ");
            $stmt->execute();
            $modalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $modalidades ?: null;
        } catch (Exception $e) {
            error_log("Error al buscar las modalidades: " . $e->getMessage());
            return null;
        }
    }

    public function getStatusPracticaByUserId(int $userId)
    {
        $query = "SELECT
	pe.estado_fase_uno_completado
    FROM
	practicas_estudiantes pe 
    WHERE user_id = :userId LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function marcarFaseUnoComoCompletada($idPractica)
    {
        $query = "UPDATE practicas_estudiantes
              SET estado_fase_uno_completado = 1
              WHERE id_practica = :id";

        $db = $this->getConnection();
        $stmt = $db->prepare($query);

        try {
            $stmt->execute([':id' => $idPractica]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error de SQL en PracticasModel: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerDatosPracticaEstudiante($id_practica)
    {
        $sql = "SELECT * FROM practicas_estudiantes WHERE id_practica = :id_practica";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id_practica', $id_practica, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Obtiene toda la información necesaria para el PDF de una práctica específica.
     * Usa la consulta unificada proporcionada por el usuario.
     * @param int $id_practica El ID de la práctica (pe.id_practica)
     * @return array|null Un array con todos los datos o null si no se encuentra.
     */
    public function getPracticeFullData(int $id_practica): ?array
    {
        // Esta consulta unifica datos de Práctica, Entidad, Tutor Empresarial y Usuario (Estudiante)
        $queryPractice = "SELECT
        u.codigo_matricula, 
        CONCAT(u.primer_nombre, ' ', u.segundo_nombre, ' ', u.primer_apellido, ' ',u.segundo_apellido) as 'nombre_completo',
        u.numero_identificacion, u.usuario, u.nivel, u.programa, u.periodo,
        pe.id_practica, pe.modalidad, pe.estado_fase_uno_completado, pe.afiliacion_iess,
        pe.docente_asignado_id, pe.proyecto_id, pe.user_id,
        ent.ruc, pmod.id_practica_modalidad,
        ent.nombre_empresa, ent.razon_social, ent.persona_contacto, ent.telefono_contacto, ent.email_contacto,
        ent.direccion, ent.plazas_disponibles,
        tutemp.nombre_completo AS tutor_emp_nombre_completo, tutemp.cedula, tutemp.funcion,
        tutemp.email AS tutor_emp_email, tutemp.telefono AS tutor_emp_telefono, tutemp.departamento,
        doc.nombre_completo AS docente_nombre, doc.email AS docente_email
        FROM
            practicas_estudiantes pe
        INNER JOIN practica_modalidad pmod ON pmod.modalidad = pe.modalidad
        INNER JOIN entidades ent ON ent.id_entidad = pe.entidad_id
        INNER JOIN users u on pe.user_id = u.id
        LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
        LEFT JOIN docentes doc on pe.docente_asignado_id = doc.id_docente
        WHERE pe.id_practica = :id_practica";

        $stmt = $this->db->prepare($queryPractice);
        $stmt->execute([':id_practica' => $id_practica]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null; // No hay práctica con ese ID
        }

        // --- AGREGAR Y REESTRUCTURAR LA DATA PARA LA VISTA ---
        // Reestructuramos el resultado plano de la consulta en un array $data
        // que se asemeje a la estructura que usa tu vista original (tab_pasantias.php).

        $data = [];

        // 1. Datos de la Práctica y Entidad
        $data['infoPractica'] = [
            'id_practica' => $result['id_practica'],
            'modalidad' => $result['modalidad'],
            'estado_fase_uno_completado' => $result['estado_fase_uno_completado'],
            'afiliacion_iess' => $result['afiliacion_iess'],
            'ruc' => $result['ruc'],
            'nombre_empresa' => $result['nombre_empresa'],
            'razon_social' => $result['razon_social'],
            'persona_contacto' => $result['persona_contacto'],
            'telefono_contacto' => $result['telefono_contacto'],
            'email_contacto' => $result['email_contacto'],
            'direccion' => $result['direccion'],
            'plazas_disponibles' => $result['plazas_disponibles'],
            // ... otros campos que necesites de la práctica ...
        ];

        // 2. Datos Personales del Estudiante
        $data['infoPersonal'] = [
            'codigo_matricula' => $result['codigo_matricula'],
            'numero_identificacion' => $result['numero_identificacion'],
            'usuario' => $result['usuario'],
            'nivel' => $result['nivel'],
            'programa' => $result['programa'],
            'periodo' => $result['periodo'],
            'nombre_completo' => $result['nombre_completo'], // Usamos el alias de la consulta
            // ...
        ];
        $data['nombreCompleto'] = $result['nombre_completo'];

        // 3. Datos del Tutor Empresarial
        $data['tutoresEmpresariales'] = [
            [
                'nombre_completo' => $result['tutor_emp_nombre_completo'],
                'cedula' => $result['cedula'],
                'funcion' => $result['funcion'],
                'email' => $result['tutor_emp_email'],
                'telefono' => $result['tutor_emp_telefono'],
                'departamento' => $result['departamento'],
            ]
        ];
        $data['cantidadTutores'] = 1; // Asumo 1 por la consulta LEFT JOIN

        // 4. Datos del Tutor Académico (Docente Asignado)
        // Se usa el nombre 'tutoresAcademicos' para seguir el patrón de tu vista
        $data['tutoresAcademicos'] = [
            [
                'nombre_completo' => $result['docente_nombre'],
                'email' => $result['docente_email']
            ]
        ];

        // 5. Datos de Proyecto (Si aplica)
        // Como tu consulta solo trae el ID del proyecto, necesitas un paso extra
        // para obtener la descripción, carreras y lugar si es necesario para el PDF.
        $data['infoProyectos'] = []; // Inicializamos vacío si no lo usas o lo agregas aquí.
        if ($result['proyecto_id']) {
            // Ejecuta otra consulta o un método para obtener detalles del proyecto
            // $project_details = $this->getProjectDetails($result['proyecto_id']);
            // $data['infoProyectos'] = [$project_details];
        }

        return $data;
    }

    public function getAllPasantias()
    {
        $query = "SELECT 
            pe.id_practica,
            pe.user_id,
            pe.modalidad,
            pe.docente_asignado_id,
            pe.entidad_id,
            pe.tutor_empresarial_id,
            pe.estado_fase_uno_completado,
            pe.fecha_registro,
            pe.proyecto_id,
            pe.afiliacion_iess,
            CONCAT(u.primer_nombre, ' ', COALESCE(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', COALESCE(u.segundo_apellido, '')) as estudiante_nombre,
            u.codigo_matricula,
            u.numero_identificacion,
            u.programa as nombre_carrera,
            ent.nombre_empresa,
            ent.razon_social,
            doc.nombre_completo as docente_nombre,
            tutemp.nombre_completo as tutor_empresarial_nombre
        FROM practicas_estudiantes pe
        INNER JOIN users u ON u.id = pe.user_id
        INNER JOIN entidades ent ON ent.id_entidad = pe.entidad_id
        LEFT JOIN docentes doc ON doc.id_docente = pe.docente_asignado_id
        LEFT JOIN tutores_empresariales tutemp ON tutemp.id_tutor_empresa = pe.tutor_empresarial_id
        ORDER BY pe.fecha_registro DESC";

        try {
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener todas las pasantías: " . $e->getMessage());
            return [];
        }
    }

    public function actualizarPasantia($id_practica, $datos)
    {
        $query = "UPDATE practicas_estudiantes SET 
                  estado_fase_uno_completado = :estado_fase_uno_completado
                  WHERE id_practica = :id_practica";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':estado_fase_uno_completado', $datos['estado_fase_uno_completado']);
            $stmt->bindParam(':id_practica', $id_practica);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar pasantía: " . $e->getMessage());
            return false;
        }
    }

    public function getPlanesDeTrabajo($offset = 0, $limit = 100, $search = null, $sortBy = 'pt.fecha_planificada', $sortDir = 'DESC')
    {
        $allowedSort = ['pt.fecha_planificada', 'pt.actividad_planificada', 'u.primer_nombre', 'ps.modalidad'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'pt.fecha_planificada';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT 
                    pt.id_programa as id_plan,
                    pt.practica_id,
                    pt.actividad_planificada,
                    pt.departamento_area,
                    pt.funcion_asignada,
                    pt.fecha_planificada,
                    CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                    u.codigo_matricula,
                    u.numero_identificacion,
                    ps.modalidad,
                    ps.fecha_registro as practica_fecha_registro,
                    e.nombre_empresa,
                    u.programa,
                    'PLAN' as tipo_registro
                FROM programa_trabajo pt
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id";

        $params = [];
        if ($search) {
            $sql .= " WHERE (u.primer_nombre LIKE :search OR u.primer_apellido LIKE :search OR pt.actividad_planificada LIKE :search OR u.codigo_matricula LIKE :search OR u.numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY {$sortBy} {$sortDir} LIMIT :offset, :limit";

        try {
            $stmt = $this->db->prepare($sql);
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getPlanesDeTrabajo: " . $e->getMessage());
            return [];
        }
    }

    public function countPlanesDeTrabajo($search = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM programa_trabajo pt
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id";

        $params = [];
        if ($search) {
            $sql .= " WHERE (u.primer_nombre LIKE :search OR u.primer_apellido LIKE :search OR pt.actividad_planificada LIKE :search OR u.codigo_matricula LIKE :search OR u.numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        try {
            $stmt = $this->db->prepare($sql);
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en countPlanesDeTrabajo: " . $e->getMessage());
            return 0;
        }
    }

    public function getAuditDataCombined($offset = 0, $limit = 100, $search = null, $sortBy = 'fecha', $sortDir = 'DESC')
    {
        $allowedSort = ['fecha', 'estudiante', 'empresa', 'modalidad'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'fecha';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        try {
            $combined = [];

            // Query para actividades diarias
            $sql_actividades = "
                SELECT 
                    ad.id_actividad_diaria as id,
                    ad.practica_id,
                    CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                    u.codigo_matricula,
                    u.numero_identificacion,
                    ps.modalidad,
                    ps.fecha_registro as practica_fecha_registro,
                    e.nombre_empresa,
                    u.programa,
                    'ACTIVIDAD' as tipo_registro,
                    ad.actividad_realizada as actividad,
                    ad.horas_invertidas as horas,
                    ad.fecha_actividad as fecha,
                    ad.hora_inicio as hora_inicio,
                    ad.hora_fin as hora_fin,
                    NULL as departamento_area,
                    NULL as funcion_asignada
                FROM actividades_diarias ad
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
            ";

            if ($search) {
                $sql_actividades .= " WHERE (u.primer_nombre LIKE ? OR u.primer_apellido LIKE ? OR ad.actividad_realizada LIKE ? OR u.codigo_matricula LIKE ? OR u.numero_identificacion LIKE ?)";
            }

            $stmt = $this->db->prepare($sql_actividades);
            if ($search) {
                $search_term = '%' . $search . '%';
                $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(4, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(5, $search_term, PDO::PARAM_STR);
            }
            $stmt->execute();
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $combined = array_merge($combined, $actividades);

            // Query para planes de aprendizaje
            $sql_planes = "
                SELECT 
                    pt.id_programa as id,
                    pt.practica_id,
                    CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                    u.codigo_matricula,
                    u.numero_identificacion,
                    ps.modalidad,
                    ps.fecha_registro as practica_fecha_registro,
                    e.nombre_empresa,
                    u.programa,
                    'PLAN' as tipo_registro,
                    pt.actividad_planificada as actividad,
                    NULL as horas,
                    pt.fecha_planificada as fecha,
                    NULL as hora_inicio,
                    NULL as hora_fin,
                    pt.departamento_area,
                    pt.funcion_asignada
                FROM programa_trabajo pt
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
            ";

            if ($search) {
                $sql_planes .= " WHERE (u.primer_nombre LIKE ? OR u.primer_apellido LIKE ? OR pt.actividad_planificada LIKE ? OR u.codigo_matricula LIKE ? OR u.numero_identificacion LIKE ?)";
            }

            $stmt = $this->db->prepare($sql_planes);
            if ($search) {
                $search_term = '%' . $search . '%';
                $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(4, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(5, $search_term, PDO::PARAM_STR);
            }
            $stmt->execute();
            $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $combined = array_merge($combined, $planes);

            // Mapping para el order by
            $sortByMap = [
                'fecha' => 'fecha',
                'estudiante' => 'estudiante_nombre',
                'empresa' => 'nombre_empresa',
                'modalidad' => 'modalidad'
            ];
            $sortField = $sortByMap[$sortBy] ?? 'fecha';

            // Ordenar en PHP
            usort($combined, function ($a, $b) use ($sortField, $sortDir) {
                $valA = $a[$sortField] ?? '';
                $valB = $b[$sortField] ?? '';

                $cmp = strcmp((string)$valA, (string)$valB);

                // Si es fecha, ordenar numéricamente
                if ($sortField === 'fecha') {
                    $cmp = strcmp((string)$valA, (string)$valB);
                }

                return $sortDir === 'ASC' ? $cmp : -$cmp;
            });

            // Aplicar paginación
            $result = array_slice($combined, (int)$offset, (int)$limit);

            return $result;
        } catch (PDOException $e) {
            error_log("Error en getAuditDataCombined: " . $e->getMessage());
            return [];
        }
    }

    public function countAuditDataCombined($search = null)
    {
        try {
            $totalCount = 0;

            // Contar actividades diarias
            $sql_actividades = "
                SELECT COUNT(*) as cnt 
                FROM actividades_diarias ad
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
            ";

            if ($search) {
                $sql_actividades .= " WHERE (u.primer_nombre LIKE ? OR u.primer_apellido LIKE ? OR ad.actividad_realizada LIKE ? OR u.codigo_matricula LIKE ? OR u.numero_identificacion LIKE ?)";
            }

            $stmt = $this->db->prepare($sql_actividades);
            if ($search) {
                $search_term = '%' . $search . '%';
                $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(4, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(5, $search_term, PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalCount += (int)($row['cnt'] ?? 0);

            // Contar planes de aprendizaje
            $sql_planes = "
                SELECT COUNT(*) as cnt 
                FROM programa_trabajo pt
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
            ";

            if ($search) {
                $sql_planes .= " WHERE (u.primer_nombre LIKE ? OR u.primer_apellido LIKE ? OR pt.actividad_planificada LIKE ? OR u.codigo_matricula LIKE ? OR u.numero_identificacion LIKE ?)";
            }

            $stmt = $this->db->prepare($sql_planes);
            if ($search) {
                $search_term = '%' . $search . '%';
                $stmt->bindValue(1, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(2, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(3, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(4, $search_term, PDO::PARAM_STR);
                $stmt->bindValue(5, $search_term, PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalCount += (int)($row['cnt'] ?? 0);

            return $totalCount;
        } catch (PDOException $e) {
            error_log("Error en countAuditDataCombined: " . $e->getMessage());
            return 0;
        }
    }

    public function getAllActividadesWithStudentInfo($offset = 0, $limit = 100, $search = null, $sortBy = 'ad.fecha_actividad', $sortDir = 'DESC')
    {
        $allowedSort = ['ad.fecha_actividad', 'ad.horas_invertidas', 'ad.actividad_realizada', 'u.primer_nombre', 'ps.modalidad'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'ad.fecha_actividad';
        }
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT 
                    ad.id_actividad_diaria as id,
                    ad.practica_id,
                    ad.actividad_realizada,
                    ad.horas_invertidas,
                    ad.fecha_actividad,
                    ad.hora_inicio,
                    ad.hora_fin,
                    CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                    u.codigo_matricula,
                    u.numero_identificacion,
                    ps.modalidad,
                    ps.fecha_registro as practica_fecha_registro,
                    e.nombre_empresa,
                    u.programa
                FROM actividades_diarias ad
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id";

        $params = [];
        if ($search) {
            $sql .= " WHERE (u.primer_nombre LIKE :search OR u.primer_apellido LIKE :search OR ad.actividad_realizada LIKE :search OR u.codigo_matricula LIKE :search OR u.numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY {$sortBy} {$sortDir} LIMIT :offset, :limit";

        try {
            $stmt = $this->db->prepare($sql);
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en getAllActividadesWithStudentInfo: " . $e->getMessage());
            return [];
        }
    }

    public function countAllActividades($search = null)
    {
        $sql = "SELECT COUNT(*) as cnt FROM actividades_diarias ad
                INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                INNER JOIN users u ON u.id = ps.user_id
                INNER JOIN entidades e ON e.id_entidad = ps.entidad_id";

        $params = [];
        if ($search) {
            $sql .= " WHERE (u.primer_nombre LIKE :search OR u.primer_apellido LIKE :search OR ad.actividad_realizada LIKE :search OR u.codigo_matricula LIKE :search OR u.numero_identificacion LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        try {
            $stmt = $this->db->prepare($sql);
            if (isset($params[':search'])) {
                $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en countAllActividades: " . $e->getMessage());
            return 0;
        }
    }

    public function eliminarPasantia($id_practica)
    {
        try {
            // Iniciar transacción
            $this->db->beginTransaction();

            error_log("Iniciando eliminación de pasantía ID: " . $id_practica);

            // 1. Primero eliminar plan de aprendizaje relacionado
            $query0 = "DELETE FROM programa_trabajo WHERE practica_id = :id_practica";
            $stmt0 = $this->db->prepare($query0);
            $stmt0->bindParam(':id_practica', $id_practica);
            $stmt0->execute();
            error_log("Programa trabajo eliminado: " . $stmt0->rowCount() . " filas");

            // 2. Eliminar actividades diarias relacionadas
            $query1 = "DELETE FROM actividades_diarias WHERE practica_id = :id_practica";
            $stmt1 = $this->db->prepare($query1);
            $stmt1->bindParam(':id_practica', $id_practica);
            $stmt1->execute();
            error_log("Actividades diarias eliminadas: " . $stmt1->rowCount() . " filas");

            // 3. Finalmente eliminar la pasantía
            $query2 = "DELETE FROM practicas_estudiantes WHERE id_practica = :id_practica";
            $stmt2 = $this->db->prepare($query2);
            $stmt2->bindParam(':id_practica', $id_practica);
            $resultado = $stmt2->execute();
            error_log("Pasantía eliminada: " . $stmt2->rowCount() . " filas");

            // Confirmar transacción
            $this->db->commit();
            error_log("Transacción completada exitosamente");

            return $resultado && $stmt2->rowCount() > 0;
        } catch (PDOException $e) {
            // Revertir cambios en caso de error
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error al eliminar pasantía ID " . $id_practica . ": " . $e->getMessage());
            return false;
        }
    }

    // Obtener un registro de auditoría por ID (puede ser ACTIVIDAD o PLAN)
    public function getRegistroAuditoriaById($id)
    {
        try {
            // Primero buscar en actividades_diarias
            $sql = "SELECT 
                        ad.id_actividad_diaria as id,
                        ad.practica_id,
                        CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                        u.id as estudiante_id,
                        u.codigo_matricula,
                        u.numero_identificacion,
                        ps.modalidad,
                        ps.fecha_registro as practica_fecha_registro,
                        e.nombre_empresa,
                        e.nombre_empresa as empresa_nombre,
                        u.programa as programa_descripcion,
                        'ACTIVIDAD' as tipo_registro,
                        ad.actividad_realizada as descripcion,
                        ad.horas_invertidas as horas_cumplidas,
                        ad.fecha_actividad as fecha,
                        ad.fecha_actividad as fecha_inicio,
                        ad.fecha_actividad as fecha_fin,
                        ad.hora_inicio as hora_inicio,
                        ad.hora_fin as hora_fin,
                        NULL as departamento_area,
                        NULL as funcion_asignada
                    FROM actividades_diarias ad
                    INNER JOIN practicas_estudiantes ps ON ps.id_practica = ad.practica_id
                    INNER JOIN users u ON u.id = ps.user_id
                    INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
                    WHERE ad.id_actividad_diaria = ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($resultado) {
                return $resultado;
            }

            // Si no encuentra en actividades, buscar en programa_trabajo
            $sql = "SELECT 
                        pt.id_programa as id,
                        pt.practica_id,
                        CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) as estudiante_nombre,
                        u.id as estudiante_id,
                        u.codigo_matricula,
                        u.numero_identificacion,
                        ps.modalidad,
                        ps.fecha_registro as practica_fecha_registro,
                        e.nombre_empresa,
                        e.nombre_empresa as empresa_nombre,
                        u.programa as programa_descripcion,
                        'PLAN' as tipo_registro,
                        pt.nombre_actividad as descripcion,
                        pt.horas as horas_cumplidas,
                        pt.fecha_actividad as fecha,
                        pt.fecha_inicio as fecha_inicio,
                        pt.fecha_fin as fecha_fin,
                        pt.hora_inicio as hora_inicio,
                        pt.hora_fin as hora_fin,
                        pt.departamento_area,
                        pt.funcion_asignada
                    FROM programa_trabajo pt
                    INNER JOIN practicas_estudiantes ps ON ps.id_practica = pt.practica_id
                    INNER JOIN users u ON u.id = ps.user_id
                    INNER JOIN entidades e ON e.id_entidad = ps.entidad_id
                    WHERE pt.id_programa = ?
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            return $resultado ? $resultado : null;
        } catch (PDOException $e) {
            error_log("Error en getRegistroAuditoriaById: " . $e->getMessage());
            return null;
        }
    }

    // Determinar tipo de registro (ACTIVIDAD o PLAN)
    public function obtenerTipoRegistro($id)
    {
        try {
            // Buscar en actividades_diarias
            $sql = "SELECT 'ACTIVIDAD' as tipo FROM actividades_diarias WHERE id_actividad_diaria = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return 'ACTIVIDAD';
            }

            // Buscar en programa_trabajo
            $sql = "SELECT 'PLAN' as tipo FROM programa_trabajo WHERE id_programa = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return 'PLAN';
            }

            return null;
        } catch (PDOException $e) {
            error_log("Error en obtenerTipoRegistro: " . $e->getMessage());
            return null;
        }
    }

    // Eliminar actividad diaria
    public function eliminarActividad($id)
    {
        try {
            $sql = "DELETE FROM actividades_diarias WHERE id_actividad_diaria = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al eliminar actividad ID " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    // Eliminar plan de trabajo
    public function eliminarPlan($id)
    {
        try {
            $sql = "DELETE FROM programa_trabajo WHERE id_programa = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $id, PDO::PARAM_INT);
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al eliminar plan ID " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    // Actualizar actividad diaria
    public function actualizarActividad($id, $datos)
    {
        try {
            $sql = "UPDATE actividades_diarias SET 
                    actividad_realizada = ?,
                    horas_invertidas = ?,
                    fecha_actividad = ?,
                    hora_inicio = ?,
                    hora_fin = ?
                    WHERE id_actividad_diaria = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $datos['actividad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(2, $datos['horas'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(3, $datos['fecha'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(4, $datos['hora_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(5, $datos['hora_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(6, $id, PDO::PARAM_INT);

            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar actividad ID " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    // Actualizar plan de trabajo
    public function actualizarPlan($id, $datos)
    {
        try {
            $sql = "UPDATE programa_trabajo SET 
                    nombre_actividad = ?,
                    horas = ?,
                    fecha_actividad = ?,
                    hora_inicio = ?,
                    hora_fin = ?,
                    departamento_area = ?,
                    funcion_asignada = ?
                    WHERE id_programa = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $datos['actividad'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(2, $datos['horas'] ?? 0, PDO::PARAM_STR);
            $stmt->bindValue(3, $datos['fecha'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(4, $datos['hora_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(5, $datos['hora_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(6, $datos['departamento'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(7, $datos['funcion_asignada'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(8, $id, PDO::PARAM_INT);

            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar plan ID " . $id . ": " . $e->getMessage());
            return false;
        }
    }

    // Métodos para auditoría de eliminaciones
    public function crearTablaAuditoria()
    {
        $sql = "CREATE TABLE IF NOT EXISTS registros_eliminados (
            id INT PRIMARY KEY AUTO_INCREMENT,
            tipo_registro VARCHAR(20) NOT NULL,
            id_original INT,
            estudiante_nombre VARCHAR(255),
            estudiante_id INT,
            descripcion TEXT,
            empresa_nombre VARCHAR(255),
            horas_cumplidas INT DEFAULT 0,
            fecha_eliminacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_inicio DATE,
            fecha_fin DATE,
            programa_descripcion TEXT
        )";

        try {
            $this->db->exec($sql);
            return true;
        } catch (PDOException $e) {
            error_log("Error al crear tabla de auditoría: " . $e->getMessage());
            return false;
        }
    }

    public function registrarEliminacion($tipoRegistro, $idOriginal, $datos)
    {
        $this->crearTablaAuditoria();

        $sql = "INSERT INTO registros_eliminados 
                (tipo_registro, id_original, estudiante_nombre, estudiante_id, descripcion, empresa_nombre, horas_cumplidas, fecha_inicio, fecha_fin, programa_descripcion)
                VALUES 
                (:tipo, :id_original, :estudiante, :estudiante_id, :descripcion, :empresa, :horas, :fecha_inicio, :fecha_fin, :programa)";

        try {
            $stmt = $this->db->prepare($sql);

            $stmt->bindValue(':tipo', $tipoRegistro, PDO::PARAM_STR);
            $stmt->bindValue(':id_original', $idOriginal, PDO::PARAM_INT);
            $stmt->bindValue(':estudiante', $datos['estudiante'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':estudiante_id', $datos['estudiante_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':descripcion', $datos['descripcion'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':empresa', $datos['empresa'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':horas', $datos['horas'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':fecha_inicio', $datos['fecha_inicio'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':fecha_fin', $datos['fecha_fin'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':programa', $datos['programa'] ?? null, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al registrar eliminación: " . $e->getMessage());
            return false;
        }
    }

    public function getRegistrosEliminados($limit = 20, $offset = 0)
    {
        $this->crearTablaAuditoria();

        $sql = "SELECT * FROM registros_eliminados 
                ORDER BY fecha_eliminacion DESC 
                LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al obtener registros eliminados: " . $e->getMessage());
            return [];
        }
    }

    public function countRegistrosEliminados()
    {
        $this->crearTablaAuditoria();

        $sql = "SELECT COUNT(*) as total FROM registros_eliminados";

        try {
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error al contar registros eliminados: " . $e->getMessage());
            return 0;
        }
    }

    public function buscarPasantias($texto)
    {
        $query = "SELECT
        pe.id_practica,
        pe.estado_fase_uno_completado,
        ent.nombre_empresa,
        CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) AS estudiante_nombre
    FROM practicas_estudiantes pe
    INNER JOIN entidades ent ON ent.id_entidad = pe.entidad_id
    INNER JOIN users u ON u.id = pe.user_id
    WHERE 
        CONCAT(u.primer_nombre, ' ', IFNULL(u.segundo_nombre, ''), ' ', u.primer_apellido, ' ', u.segundo_apellido) LIKE :texto
        OR ent.nombre_empresa LIKE :texto
    ORDER BY pe.id_practica DESC";

        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':texto', '%' . $texto . '%');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al buscar prácticas: " . $e->getMessage());
            return [];
        }
    }

    public function contarPorEstado($estado)
    {
        $sql = "SELECT COUNT(*) FROM practicas_estudiantes 
            WHERE estado_fase_uno_completado = :estado";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['estado' => $estado]);
        return $stmt->fetchColumn();
    }

    public function contarPracticas($buscar = '', $estado = '')
    {
        $sql = "SELECT COUNT(*) FROM practicas_estudiantes pe
            INNER JOIN users u ON u.id = pe.user_id
            INNER JOIN entidades e ON e.id_entidad = pe.entidad_id
            WHERE 1=1";

        $params = [];

        if ($buscar) {
            $sql .= " AND (CONCAT(u.primer_nombre,' ',u.primer_apellido) LIKE :buscar 
                  OR e.nombre_empresa LIKE :buscar)";
            $params['buscar'] = "%$buscar%";
        }

        if ($estado !== '') {
            $sql .= " AND pe.estado_fase_uno_completado = :estado";
            $params['estado'] = $estado;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getPracticasPaginadas($buscar, $estado, $limite, $offset)
    {
        $sql = "SELECT pe.*, 
            e.nombre_empresa,
            CONCAT(u.primer_nombre,' ',u.primer_apellido) AS estudiante_nombre
            FROM practicas_estudiantes pe
            INNER JOIN users u ON u.id = pe.user_id
            INNER JOIN entidades e ON e.id_entidad = pe.entidad_id
            WHERE 1=1";

        $params = [];

        if ($buscar) {
            $sql .= " AND (CONCAT(u.primer_nombre,' ',u.primer_apellido) LIKE :buscar 
                  OR e.nombre_empresa LIKE :buscar)";
            $params['buscar'] = "%$buscar%";
        }

        if ($estado !== '') {
            $sql .= " AND pe.estado_fase_uno_completado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " LIMIT :limite OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }

        $stmt->bindValue(":limite", (int)$limite, PDO::PARAM_INT);
        $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
