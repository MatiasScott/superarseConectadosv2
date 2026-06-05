<?php
// app/Controllers/PasantiaController.php

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../Models/PasantiaModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Helpers/AuthSecurity.php';

class PasantiaController
{
    private $basePath;
    private $pasantiaModel;
    private $userModel;

    public function __construct()
    {
        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
        }

        $this->pasantiaModel = new PasantiaModel();
        $this->userModel = new UserModel();

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Permitir acceso si es admin O si es un estudiante logueado
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
        $isStudent = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

        if ($isAdmin && !empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/admin/password/change");
            exit();
        }

        if ($isStudent && !empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/password/change");
            exit();
        }

        if (!$isAdmin && !$isStudent) {
            header("Location: " . $this->basePath . "/login");
            exit();
        }
    }

    private function normalizePracticeStatus(?string $status): string
    {
        $normalized = strtoupper(trim((string) ($status ?? 'ACTIVA')));

        if ($normalized === 'CANCELADA') {
            return 'NO FINALIZADO';
        }

        return $normalized === '' ? 'ACTIVA' : $normalized;
    }

    private function isPracticeLocked(?array $practica): bool
    {
        $status = $this->normalizePracticeStatus($practica['estado'] ?? 'ACTIVA');
        return in_array($status, ['FINALIZADA', 'NO FINALIZADO'], true);
    }

    private function getPracticeLockedMessage(?array $practica): string
    {
        $status = $this->normalizePracticeStatus($practica['estado'] ?? 'ACTIVA');
        $observacion = trim((string) ($practica['observacion'] ?? ''));

        if ($status === 'FINALIZADA') {
            $message = 'Estado de Práctica: FINALIZADA. Estimado/a estudiante: Se le informa que ha culminado satisfactoriamente su proceso de prácticas preprofesionales, cumpliendo con los requisitos establecidos. Felicitamos su esfuerzo y compromiso durante esta etapa de formación profesional.';
        } else {
            $message = 'Estado de Práctica: NO FINALIZADO. Estimado/a estudiante: Su proceso de prácticas preprofesionales no ha finalizado. Le recomendamos ponerse en contacto a la brevedad posible con el coordinador de prácticas preprofesionales, a fin de recibir orientación y completar los requisitos pendientes. Es importante regularizar su situación para evitar inconvenientes en su proceso académico.';
        }

        if ($observacion !== '') {
            $message .= ' Observación: ' . $observacion;
        }

        return $message;
    }

    private function redirectLockedPractice(string $fallbackTab = 'programa', int $activityPage = 1): void
    {
        $query = 'module=pasantias&tab=' . urlencode($fallbackTab);
        if ($fallbackTab === 'actividades') {
            $query .= '&activity_page=' . max(1, $activityPage);
        }

        header('Location: ' . $this->basePath . '/estudiante/informacion?' . $query);
        exit();
    }

    private function ensurePracticeEditableOrRedirect(?array $practica, string $fallbackTab = 'programa', int $activityPage = 1): void
    {
        if (!$this->isPracticeLocked($practica)) {
            return;
        }

        $_SESSION['mensaje'] = $this->getPracticeLockedMessage($practica);
        $this->redirectLockedPractice($fallbackTab, $activityPage);
    }

    public function index()
    {
        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);
        $docentes = $this->pasantiaModel->getActiveDocentes();
        $estudiante = $this->userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);

        if ($practica) {
            $_SESSION['mensaje'] = "Ya tienes un registro de práctica en el sistema (ID: {$practica['id_practica']}).";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }
        $data = [
            'docentes' => $docentes,
            'estudiante' => $estudiante,
            'mensaje' => $_SESSION['mensaje'] ?? null
        ];
        unset($_SESSION['mensaje']);

        $vistaRegistro = __DIR__ . '/../Views/estudiantes/registro_pasantia_fase1.php';

        if (!file_exists($vistaRegistro)) {
            $_SESSION['mensaje'] = 'El formulario de registro de Practicas Pre Profesionales esta integrado en el panel principal.';
            header('Location: ' . $this->basePath . '/estudiante/informacion');
            exit();
        }

        $this->renderStudent('estudiantes/registro_pasantia_fase1', $data);
    }

    public function saveFaseOne()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/estudiante/registro?error=metodo_invalido");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_fase_one', $_POST['csrf_token'] ?? '')) {
            $_SESSION['mensaje'] = "Error: token de seguridad inválido. Intenta nuevamente.";
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias");
            exit();
        }

        if (empty($_POST['modalidad']) || empty($_POST['entidad_ruc'])) {
            $_SESSION['mensaje'] = "Error: La modalidad y el RUC de la entidad son obligatorios.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }
        $userId = $_SESSION['id_usuario'];
        $estudiante = $this->userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);

        if (!$estudiante) {
            $_SESSION['mensaje'] = "Error interno: No se pudo obtener la información completa del estudiante.";
            header("Location: " . $this->basePath . "/estudiante/registro2");
            exit();
        }
        $data = [
            'user_id' => $userId,
            'programa' => $estudiante['programa'],
            'modalidad' => $_POST['modalidad'],

            'entidad_ruc' => trim($_POST['entidad_ruc']),
            'entidad_nombre_empresa' => $_POST['entidad_nombre_empresa'] ?? null,
            'entidad_razon_social' => $_POST['entidad_razon_social'] ?? null,
            'entidad_persona_contacto' => $_POST['entidad_persona_contacto'] ?? null,
            'entidad_telefono_contacto' => $_POST['entidad_telefono_contacto'] ?? null,
            'entidad_email_contacto' => $_POST['entidad_email_contacto'] ?? null,
            'entidad_direccion' => $_POST['entidad_direccion'] ?? null,
            'tutor_emp_cedula' => trim($_POST['tutor_emp_cedula']) ?? null,
            'tutor_emp_nombre_completo' => $_POST['tutor_emp_nombre_completo'] ?? null,
            'tutor_emp_funcion' => $_POST['tutor_emp_funcion'] ?? null,
            'tutor_emp_telefono' => $_POST['tutor_emp_telefono'] ?? null,
            'tutor_emp_email' => $_POST['tutor_emp_email'] ?? null,
            'tutor_emp_departamento' => $_POST['tutor_emp_departamento'] ?? null,
            'idProyecto' => $_POST['proyecto_seleccionado'] ?? null,
            'afiliacion_iees' => $_POST['afiliacion_iees'] ?? null
        ];
        $practica_id = $this->pasantiaModel->savePasantiaPhaseOne($data);

        if ($practica_id) {
            $_SESSION['mensaje'] = "Registro de práctica (ID: {$practica_id}) creado con éxito. Esperando aprobación.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        } else {
            $_SESSION['mensaje'] = "Error al completar el registro. Inténtalo de nuevo.";
            header("Location: " . $this->basePath . "/estudiante/registro");
            exit();
        }
    }

    public function showProgramaTrabajo()
    {
        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);
        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "La Fase 1 debe estar completa y aprobada para acceder al Plan de Aprendizaje.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $practicaId = $practica['id_practica'];
        $limit = (int)($_GET['limit'] ?? 100);
        $offset = (int)($_GET['offset'] ?? 0);

        $programaTrabajo = $this->pasantiaModel->getProgramaTrabajo($practicaId, $limit, $offset);
        $totalRegistros = $this->pasantiaModel->getTotalProgramaTrabajo($practicaId);

        $data = [
            'practicaId' => $practicaId,
            'programaTrabajo' => $programaTrabajo,
            'totalRegistros' => $totalRegistros,
            'offset' => $offset,
            'limit' => $limit,
            'mensaje' => $_SESSION['mensaje'] ?? null
        ];

        unset($_SESSION['mensaje']);

        $this->renderStudent('estudiantes/programa_trabajo', $data);
    }

    public function addProgramaTrabajo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'programa');

        $requiredFields = ['actividad_planificada', 'fecha_planificada'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios para el plan de aprendizaje.";
                header("Location: " . $this->basePath . "/estudiante/informacion");
                exit();
            }
        }

        $data = [
            'practica_id' => $practica['id_practica'],
            'actividad_planificada' => $_POST['actividad_planificada'],
            'departamento_area' => $_POST['departamento_area'] ?? null,
            'funcion_asignada' => $_POST['funcion_asignada'] ?? null,
            'fecha_planificada' => $_POST['fecha_planificada']
        ];

        if ($this->pasantiaModel->addProgramaTrabajo($data)) {
            $_SESSION['mensaje'] = "Actividad planificada agregada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al guardar la actividad planificada.";
        }

        header("Location: " . $this->basePath . "/estudiante/informacion");
        exit();
    }

    public function updateProgramaTrabajo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'programa');

        $id = $_POST['id'] ?? 0;
        $requiredFields = ['actividad_planificada', 'fecha_planificada'];

        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios.";
                header("Location: " . $this->basePath . "/estudiante/informacion");
                exit();
            }
        }

        $data = [
            'id' => $id,
            'actividad_planificada' => $_POST['actividad_planificada'],
            'departamento_area' => $_POST['departamento_area'] ?? null,
            'funcion_asignada' => $_POST['funcion_asignada'] ?? null,
            'fecha_planificada' => $_POST['fecha_planificada']
        ];

        if ($this->pasantiaModel->updateProgramaTrabajo($data)) {
            $_SESSION['mensaje'] = "Actividad actualizada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la actividad.";
        }

        header("Location: " . $this->basePath . "/estudiante/informacion");
        exit();
    }

    public function deleteProgramaTrabajo()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'programa');

        $id = $_POST['id'] ?? 0;

        if (empty($id)) {
            $_SESSION['mensaje'] = "Error: ID no proporcionado.";
        } else {
            if ($this->pasantiaModel->deleteProgramaTrabajo($id)) {
                $_SESSION['mensaje'] = "Actividad eliminada exitosamente.";
            } else {
                $_SESSION['mensaje'] = "Error al eliminar la actividad.";
            }
        }

        header("Location: " . $this->basePath . "/estudiante/informacion");  // ← CAMBIO AQUÍ
        exit();
    }

    public function showActividadesDiarias()
    {
        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);
        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "La Fase 1 debe estar completa y aprobada para acceder al reporte diario.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $practicaId = $practica['id_practica'];
        $actividadesDiarias = $this->pasantiaModel->getActividadesDiarias($practicaId);

        $data = [
            'practicaId' => $practicaId,
            'actividadesDiarias' => $actividadesDiarias,
            'mensaje' => $_SESSION['mensaje'] ?? null
        ];
        unset($_SESSION['mensaje']);

        $this->renderStudent('estudiantes/actividades_diarias', $data);
    }

    public function addActividadDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_actividad_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['mensaje'] = "Error: token de seguridad inválido. Intenta nuevamente.";
            $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
        $this->ensurePracticeEditableOrRedirect($practica, 'actividades', $activityPage);

        $requiredFields = ['actividad_realizada', 'fecha_actividad', 'hora_inicio', 'hora_fin', 'horas_invertidas'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios para el reporte diario.";
                header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
                exit();
            }
        }

        // Validar que la fecha no sea en el futuro
        $fecha_actividad = $_POST['fecha_actividad'];
        $fecha_hoy = date('Y-m-d');
        if ($fecha_actividad > $fecha_hoy) {
            $_SESSION['mensaje'] = "❌ Error: No puedes registrar actividades con fecha futura. Hoy es " . date('d/m/Y') . ".";
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
            exit();
        }

        // Validar que no haya otra actividad el mismo día
        $existeActividadMismaFecha = $this->pasantiaModel->countActividadesByDateAndPractica($practica['id_practica'], $fecha_actividad);
        if ($existeActividadMismaFecha > 0) {
            $_SESSION['mensaje'] = "⚠️ Error: Ya existe una actividad registrada para " . date('d/m/Y', strtotime($fecha_actividad)) . ". Solo puedes registrar una actividad por día.";
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
            exit();
        }

        $horas_invertidas = $this->calculateDurationHoursFromTime($_POST['hora_inicio'], $_POST['hora_fin']);
        if ($horas_invertidas <= 0 || $horas_invertidas > 12.00) {
            $_SESSION['mensaje'] = "Error: Las horas invertidas deben ser mayores a 0 y menores o iguales a 12.";
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
            exit();
        }

        $data = [
            'practica_id' => $practica['id_practica'],
            'actividad_realizada' => trim($_POST['actividad_realizada']),
            'horas_invertidas' => $horas_invertidas,
            'fecha_actividad' => $_POST['fecha_actividad'],
            'hora_inicio' => $_POST['hora_inicio'],
            'hora_fin' => $_POST['hora_fin']
        ];

        if ($this->pasantiaModel->addActividadDiaria($data)) {
            $_SESSION['mensaje'] = "Actividad diaria registrada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al guardar la actividad diaria.";
        }

        header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=actividades&activity_page=" . $activityPage);
        exit();
    }

    public function buscarTutorEmpresarialPorCedula()
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            http_response_code(403);
            echo json_encode(['error' => 'No autorizado']);
            exit();
        }

        header('Content-Type: application/json');

        $cedula = trim($_GET['cedula'] ?? '');
        if ($cedula === '') {
            echo json_encode(['found' => false]);
            exit();
        }

        $tutor = $this->pasantiaModel->buscarTutorPorCedula($cedula);
        if ($tutor) {
            echo json_encode(['found' => true, 'tutor' => $tutor]);
        } else {
            echo json_encode(['found' => false]);
        }
        exit();
    }

    public function buscarEntidadPorRUC()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $ruc = preg_replace('/\D+/', '', (string) ($_POST['ruc'] ?? ''));
        $idPrograma = is_numeric($_POST['idPrograma'] ?? null) ? (int) $_POST['idPrograma'] : null;

        if (empty($ruc)) {
            echo json_encode(['success' => false, 'message' => 'RUC requerido']);
            return;
        }

        try {
            $entidad = $this->pasantiaModel->getEntidadByRUC($ruc, $idPrograma);

            if ($entidad) {
                echo json_encode([
                    'success' => true,
                    'entidad' => $entidad,
                    'message' => 'Entidad encontrada'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Entidad no encontrada. Puede ingresar los datos manualmente.'
                ]);
            }
        } catch (Exception $e) {
            error_log("Error en buscarEntidadPorRUC: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar la entidad. Intente nuevamente.'
            ]);
        }
    }

    public function editActividadDiaria($id)
    {
        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);
        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'actividades');

        $actividad = $this->pasantiaModel->getActividadDiaria($id, $practica['id_practica']);
        if (!$actividad) {
            $_SESSION['mensaje'] = "Actividad no encontrada.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $data = [
            'practicaId' => $practica['id_practica'],
            'actividad' => $actividad,
            'mensaje' => $_SESSION['mensaje'] ?? null
        ];
        unset($_SESSION['mensaje']);

        $vistaEdicion = __DIR__ . '/../Views/estudiantes/edit_actividad_diaria.php';
        if (file_exists($vistaEdicion)) {
            $this->renderStudent('estudiantes/edit_actividad_diaria', $data);
            return;
        }

        $this->renderStudent('estudiantes/actividades_diarias', $data);
    }

    public function updateActividadDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->basePath}/estudiante/informacion");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_actividad_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['mensaje'] = "Error: token de seguridad inválido. Intenta nuevamente.";
            $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
            header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: {$this->basePath}/estudiante/seguimiento");
            exit();
        }

        $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
        $this->ensurePracticeEditableOrRedirect($practica, 'actividades', $activityPage);

        $requiredFields = ['id', 'actividad_realizada', 'fecha_actividad', 'hora_inicio', 'hora_fin', 'horas_invertidas'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios al actualizar.";
                header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
                exit();
            }
        }

        // Validar que la fecha no sea en el futuro
        $fecha_actividad = $_POST['fecha_actividad'];
        $fecha_hoy = date('Y-m-d');
        if ($fecha_actividad > $fecha_hoy) {
            $_SESSION['mensaje'] = "❌ Error: No puedes registrar actividades con fecha futura. Hoy es " . date('d/m/Y') . ".";
            header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
            exit();
        }

        // Validar que no haya otra actividad el mismo día (excluyendo la actual)
        $actividadId = (int) $_POST['id'];
        $existeActividadMismaFecha = $this->pasantiaModel->countActividadesByDateAndPractica($practica['id_practica'], $fecha_actividad, $actividadId);
        if ($existeActividadMismaFecha > 0) {
            $_SESSION['mensaje'] = "⚠️ Error: Ya existe otra actividad registrada para " . date('d/m/Y', strtotime($fecha_actividad)) . ". Solo puedes registrar una actividad por día.";
            header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
            exit();
        }

        $horas_invertidas = $this->calculateDurationHoursFromTime($_POST['hora_inicio'], $_POST['hora_fin']);
        if ($horas_invertidas <= 0 || $horas_invertidas > 12.00) {
            $_SESSION['mensaje'] = "Error: Las horas invertidas deben ser mayores a 0 y menores o iguales a 12.";
            header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
            exit();
        }

        $data = [
            'id'                 => $_POST['id'],
            'practica_id'        => $practica['id_practica'],
            'actividad_realizada' => trim($_POST['actividad_realizada']),
            'horas_invertidas'   => $horas_invertidas,
            'fecha_actividad'    => $_POST['fecha_actividad'],
            'hora_inicio'        => $_POST['hora_inicio'],
            'hora_fin'           => $_POST['hora_fin']
        ];

        if ($this->pasantiaModel->updateActividadDiaria($data)) {
            $_SESSION['mensaje'] = "Actividad diaria actualizada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la actividad diaria.";
        }

        header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
        exit();
    }

    public function deleteActividadDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            $_SESSION['mensaje'] = "Error: Solicitud inválida para eliminar.";
            header("Location: {$this->basePath}/estudiante/actividades_diarias");
            exit();
        }

        if (!AuthSecurity::validateCsrfToken('student_actividad_delete', $_POST['csrf_token'] ?? '')) {
            $_SESSION['mensaje'] = "Error: token de seguridad inválido. Intenta nuevamente.";
            $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
            header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: {$this->basePath}/estudiante/seguimiento");
            exit();
        }

        $activityPage = max(1, (int) ($_POST['activity_page'] ?? 1));
        $this->ensurePracticeEditableOrRedirect($practica, 'actividades', $activityPage);

        $id = (int) $_POST['id'];

        if ($this->pasantiaModel->deleteActividadDiaria($id, $practica['id_practica'])) {
            $_SESSION['mensaje'] = "Actividad diaria eliminada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar la actividad diaria.";
        }

        header("Location: {$this->basePath}/estudiante/informacion?module=pasantias&tab=actividades&activity_page={$activityPage}");
        exit();
    }

    public function generatePdf(int $id_practica)
    {
        // 1. Obtener la data del Modelo
        $data = $this->pasantiaModel->getPracticeFullData($id_practica);

        if (empty($data) || empty($data['infoPractica']['ruc'])) {
            // Manejo de error si la práctica no está registrada o no se encuentra
            // Redirigir o mostrar error 404
            http_response_code(404);
            die('Práctica o datos incompletos no encontrados.');
        }

        // 2. Generar el Contenido HTML (Usando tu vista limpia)
        $html = $this->renderPdfHtmlView('pasantias_pdf_fase1', $data);

        // 3. CONFIGURACIÓN Y GENERACIÓN DEL PDF CON DOMPDF

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true); // Necesario si usas imágenes externas o CSS remoto

        // ¡Aquí se crea la variable $dompdf que te faltaba!
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // 4. Enviar el archivo para descarga

        // Asegúrate de que infoPersonal exista antes de intentar acceder a codigo_matricula
        $codigo = $data['infoPersonal']['codigo_matricula'] ?? $id_practica;
        $filename = 'Registro_Practica_' . $codigo . '.pdf';

        if (ob_get_level() > 0) {
            ob_end_clean(); // Limpiar buffer de salida
        }

        // El método stream fuerza la descarga del PDF al navegador
        $dompdf->stream($filename, array("Attachment" => true));
        exit;
    }

    // Asegúrate de tener esta función auxiliar para cargar la vista de PDF
    protected function renderPdfHtmlView(string $viewName, array $data): string
    {
        extract($data);
        // Ajusta la ruta para que apunte correctamente a tu archivo Views/pasantias_pdf_fase1.php
        $path = __DIR__ . "/../Views/pasantias/{$viewName}.php";

        if (!file_exists($path)) {
            return "<h1>Error: Vista de PDF '{$viewName}' no encontrada.</h1>";
        }

        ob_start();
        include $path;
        return ob_get_clean();
    }

    public function generateActividadesPdf(int $id_practica)
    {
        // Obtener información de la práctica
        $practica = $this->pasantiaModel->getPracticaById($id_practica);

        if (!$practica) {
            http_response_code(404);
            die('Práctica no encontrada.');
        }

        // Obtener actividades diarias
        $actividades = $this->pasantiaModel->getActividadesDiarias($id_practica);

        // Organizar actividades por semana
        $actividadesPorSemana = [];
        foreach ($actividades as $actividad) {
            $fecha = new DateTime($actividad['fecha_actividad']);
            $semana = $fecha->format('W'); // Número de semana del año
            $anio = $fecha->format('Y');
            $claveSemana = $anio . '-S' . $semana;

            if (!isset($actividadesPorSemana[$claveSemana])) {
                // Calcular primer y último día de la semana
                $primerDia = clone $fecha;
                $primerDia->modify('monday this week');
                $ultimoDia = clone $primerDia;
                $ultimoDia->modify('+6 days');

                $actividadesPorSemana[$claveSemana] = [
                    'semana' => $semana,
                    'anio' => $anio,
                    'fecha_inicio' => $primerDia->format('Y-m-d'),
                    'fecha_fin' => $ultimoDia->format('Y-m-d'),
                    'actividades' => [],
                    'total_horas' => 0
                ];
            }

            $actividadesPorSemana[$claveSemana]['actividades'][] = $actividad;
            $actividadesPorSemana[$claveSemana]['total_horas'] += floatval($actividad['horas_invertidas']);
        }

        // Ordenar por semana
        ksort($actividadesPorSemana);

        // Obtener información del estudiante (compatible para estudiante y admin)
        $estudiante = null;
        $identificacionSesion = $_SESSION['identificacion'] ?? null;
        if (!empty($identificacionSesion)) {
            $estudiante = $this->userModel->getUserInfoByIdentificacion($identificacionSesion);
        }

        if (!$estudiante) {
            $nombreCompleto = trim((string) ($practica['estudiante_nombre'] ?? ''));
            $estudiante = [
                'primer_nombre' => $nombreCompleto,
                'segundo_nombre' => '',
                'primer_apellido' => '',
                'segundo_apellido' => '',
                'numero_identificacion' => $practica['numero_identificacion'] ?? 'N/A',
                'programa' => $practica['programa'] ?? 'N/A',
                'codigo_matricula' => $practica['codigo_matricula'] ?? $id_practica,
            ];
        }

        // Obtener información del tutor académico
        $tutorAcademico = null;
        if (!empty($estudiante['programa'])) {
            $tutoresAcademicos = $this->userModel->getTutoresAcademicosByPrograma($estudiante['programa']);
            $tutorAcademico = !empty($tutoresAcademicos) ? $tutoresAcademicos[0] : null;
        }

        $data = [
            'practica' => $practica,
            'actividadesPorSemana' => $actividadesPorSemana,
            'estudiante' => $estudiante,
            'tutorAcademico' => $tutorAcademico
        ];

        // Generar HTML
        $html = $this->renderPdfHtmlView('actividades_diarias_pdf', $data);

        // Configurar DOMPDF
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Descargar PDF
        $codigo = $estudiante['codigo_matricula'] ?? ($practica['codigo_matricula'] ?? $id_practica);
        $filename = 'Actividades_Diarias_' . $codigo . '_' . date('Y-m-d') . '.pdf';

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, array("Attachment" => true));
        exit;
    }

    public function generateProgramaTrabajoPdf(int $id_practica)
    {
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $permissionState = $_SESSION['admin_permissions'] ?? ['enabled' => false, 'matrix' => []];
        if (!empty($permissionState['enabled']) && empty($permissionState['matrix']['practicas']['view'])) {
            $_SESSION['error'] = 'No tienes permisos para ver prácticas.';
            header("Location: " . $this->basePath . "/admin/practicas");
            exit();
        }

        $practica = $this->pasantiaModel->getPracticaById($id_practica);
        if (!$practica) {
            http_response_code(404);
            die('Práctica no encontrada.');
        }

        $programaTrabajo = $this->pasantiaModel->getProgramaTrabajo((int) $id_practica, 1000, 0);

        $data = [
            'practica' => $practica,
            'programaTrabajo' => $programaTrabajo,
            'fechaEmision' => date('d/m/Y H:i'),
        ];

        $html = $this->renderPdfHtmlView('programa_trabajo_pdf', $data);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $codigo = $practica['codigo_matricula'] ?? $id_practica;
        $filename = 'Plan_Aprendizaje_' . $codigo . '_' . date('Y-m-d') . '.pdf';

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, array('Attachment' => true));
        exit;
    }

    // Alias de compatibilidad para evitar errores por diferencias en el nombre del método.
    public function generarProgramaTrabajoPdf(int $id_practica)
    {
        $this->generateProgramaTrabajoPdf($id_practica);
    }

    public function editarPasantia($id_practica)
    {
        // Verificar que sea administrador
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $permissionState = $_SESSION['admin_permissions'] ?? ['enabled' => false, 'matrix' => []];
        if (!empty($permissionState['enabled']) && empty($permissionState['matrix']['practicas']['edit'])) {
            $_SESSION['error'] = 'No tienes permisos para editar prácticas.';
            header("Location: " . $this->basePath . "/admin/practicas");
            exit();
        }

        $basePath = $this->basePath;
        $practica = $this->pasantiaModel->getPracticaById($id_practica);

        if (!$practica) {
            $_SESSION['error'] = "Practicas Pre Profesionales no encontradas";
            header("Location: " . $basePath . "/admin/dashboard");
            exit();
        }

        $tab = $_GET['tab'] ?? 'datos';
        $allowedTabs = ['datos', 'fase1', 'actividades'];
        $activeTab = in_array($tab, $allowedTabs, true) ? $tab : 'datos';
        $activityPage = max(1, (int) ($_GET['activity_page'] ?? 1));
        $activityLimit = 10;
        $activityOffset = ($activityPage - 1) * $activityLimit;

        $totalActividadesDiarias = $this->pasantiaModel->countActividadesDiarias((int) $id_practica);
        $totalHorasActividades = $this->pasantiaModel->getTotalHorasActividades((int) $id_practica);
        $totalActivityPages = max(1, (int) ceil($totalActividadesDiarias / $activityLimit));
        $activityPage = min($activityPage, $totalActivityPages);
        $activityOffset = ($activityPage - 1) * $activityLimit;
        $actividadesDiarias = $this->pasantiaModel->getActividadesDiariasPaginated(
            practicaId: (int) $id_practica,
            offset: $activityOffset,
            limit: $activityLimit,
            sortBy: 'fecha_actividad',
            sortDir: 'DESC'
        );

        // Si es POST, actualizar los datos
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modalidadRaw = (string) ($practica['modalidad'] ?? '');
            $modalidadUpper = strtoupper(strtr($modalidadRaw, [
                'á' => 'A',
                'é' => 'E',
                'í' => 'I',
                'ó' => 'O',
                'ú' => 'U',
                'Á' => 'A',
                'É' => 'E',
                'Í' => 'I',
                'Ó' => 'O',
                'Ú' => 'U',
            ]));
            $esHomologableLaboral = strpos($modalidadUpper, 'HOMOLOGABLES LABORALES') !== false;

            $datos = [
                'estado_fase_uno_completado' => $_POST['estado_fase_uno_completado'] ?? $practica['estado_fase_uno_completado'],
                'entidad_id' => $practica['entidad_id'] ?? null,
                'tutor_empresarial_id' => $practica['tutor_empresarial_id'] ?? null,
                'entidad_nombre_empresa' => $_POST['entidad_nombre_empresa'] ?? $practica['entidad_nombre_empresa'],
                'entidad_ruc' => $_POST['entidad_ruc'] ?? $practica['ruc'],
                'entidad_razon_social' => $_POST['entidad_razon_social'] ?? $practica['razon_social'],
                'entidad_persona_contacto' => $_POST['entidad_persona_contacto'] ?? $practica['persona_contacto'],
                'entidad_telefono_contacto' => $_POST['entidad_telefono_contacto'] ?? $practica['telefono_contacto'],
                'entidad_email_contacto' => $_POST['entidad_email_contacto'] ?? $practica['email_contacto'],
                'entidad_direccion' => $_POST['entidad_direccion'] ?? $practica['direccion'],
                'plazas_disponibles' => $_POST['plazas_disponibles'] ?? $practica['plazas_disponibles'],
                'afiliacion_iess' => $practica['afiliacion_iess'] ?? null,
                'estado' => $_POST['estado'] ?? ($practica['estado'] ?? 'ACTIVA'),
                'estado_actual' => $practica['estado'] ?? 'ACTIVA',
                'fecha_fin_actual' => $practica['fecha_fin'] ?? null,
                'observacion' => $_POST['observacion'] ?? ($practica['observacion'] ?? ''),
                'tutor_emp_nombre_completo' => $esHomologableLaboral ? ($practica['tutor_emp_nombre_completo'] ?? null) : ($_POST['tutor_emp_nombre_completo'] ?? $practica['tutor_emp_nombre_completo']),
                'tutor_emp_cedula' => $esHomologableLaboral ? ($practica['tutor_emp_cedula'] ?? null) : ($_POST['tutor_emp_cedula'] ?? $practica['tutor_emp_cedula']),
                'tutor_emp_funcion' => $esHomologableLaboral ? ($practica['tutor_emp_funcion'] ?? null) : ($_POST['tutor_emp_funcion'] ?? $practica['tutor_emp_funcion']),
                'tutor_emp_email' => $esHomologableLaboral ? ($practica['tutor_emp_email'] ?? null) : ($_POST['tutor_emp_email'] ?? $practica['tutor_emp_email']),
                'tutor_emp_telefono' => $esHomologableLaboral ? ($practica['tutor_emp_telefono'] ?? null) : ($_POST['tutor_emp_telefono'] ?? $practica['tutor_emp_telefono']),
                'tutor_emp_departamento' => $esHomologableLaboral ? ($practica['tutor_emp_departamento'] ?? null) : ($_POST['tutor_emp_departamento'] ?? $practica['tutor_emp_departamento']),
                'cambiar_tutor' => (!$esHomologableLaboral && !empty($_POST['cambiar_tutor'])),
            ];

            $resultado = $this->pasantiaModel->actualizarPasantia($id_practica, $datos);

            if ($resultado) {
                $_SESSION['success'] = "Estado de Practicas Pre Profesionales actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar el estado de Practicas Pre Profesionales";
            }

            header("Location: " . $basePath . "/admin/practicas");
            exit();
        }

        // Cargar vista de edición (ya no necesitamos cargar docentes)
        //require_once __DIR__ . '/../Views/admin/practicas/editar_pasantia.php';
        $this->renderAdmin('admin/practicas/editar_pasantia', [
            'title' => 'Editar Practicas Pre Profesionales',
            'practica' => $practica,
            'activeTab' => $activeTab,
            'actividadesDiarias' => $actividadesDiarias,
            'totalActividadesDiarias' => $totalActividadesDiarias,
            'totalHorasActividades' => $totalHorasActividades,
            'activityPage' => $activityPage,
            'activityLimit' => $activityLimit,
            'totalActivityPages' => $totalActivityPages,
        ]);
    }

    public function eliminarPasantia($id_practica)
    {
        error_log("=== INICIO ELIMINACIÓN ===");
        error_log("ID práctica: " . $id_practica);
        error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);
        error_log("Sesión is_admin: " . (isset($_SESSION['is_admin']) ? json_encode($_SESSION['is_admin']) : 'NO DEFINIDA'));
        error_log("Sesión completa: " . json_encode($_SESSION));

        // Verificar que sea administrador
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            error_log("❌ ACCESO DENEGADO: Usuario no es admin");
            $_SESSION['error'] = 'No autorizado para eliminar Practicas Pre Profesionales. Debes iniciar sesion como administrador.';
            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        $permissionState = $_SESSION['admin_permissions'] ?? ['enabled' => false, 'matrix' => []];
        if (!empty($permissionState['enabled']) && empty($permissionState['matrix']['practicas']['delete'])) {
            $_SESSION['error'] = 'No tienes permisos para eliminar prácticas.';
            header("Location: " . $this->basePath . "/admin/practicas");
            exit();
        }

        error_log("✓ Usuario admin verificado");

        try {
            $resultado = $this->pasantiaModel->eliminarPasantia($id_practica);
            error_log("Resultado eliminación: " . ($resultado ? '✓ SUCCESS' : '✗ FAILED'));

            if ($resultado) {
                $_SESSION['success'] = 'Practicas Pre Profesionales #' . $id_practica . ' eliminadas correctamente';
                error_log("✓ Practicas Pre Profesionales eliminadas exitosamente");
            } else {
                $_SESSION['error'] = 'Error al eliminar Practicas Pre Profesionales #' . $id_practica;
                error_log("✗ Error en la eliminación");
            }
        } catch (Exception $e) {
            error_log("✗ EXCEPCIÓN: " . $e->getMessage());
            $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
        }

        error_log("=== FIN ELIMINACIÓN ===");
        header("Location: " . $this->basePath . "/admin/dashboard");
        exit();
    }

    public function showPlanDeAprendizaje($carrera = null)
    {
        // Verificar que el estudiante esté logueado
        $userId = $_SESSION['id_usuario'];

        // Obtener la práctica activa del estudiante
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica) {
            $_SESSION['mensaje'] = "Debes registrar una práctica antes de acceder al Plan de Aprendizaje.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        if (!$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "La Fase 1 debe estar completa y aprobada para acceder al Plan de Aprendizaje.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'programa');

        // Obtener información completa del estudiante
        $estudiante = $this->userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);

        // Obtener información del tutor académico
        $tutorAcademico = null;
        if (!empty($estudiante['programa'])) {
            $tutoresAcademicos = $this->userModel->getTutoresAcademicosByPrograma($estudiante['programa']);
            $tutorAcademico = !empty($tutoresAcademicos) ? $tutoresAcademicos[0] : null;
        }

        // Preparar datos para la vista
        $data = [
            'basePath' => $this->basePath,
            'estudiante' => $estudiante,
            'practica' => $practica,
            'tutorAcademico' => $tutorAcademico,
            'mensaje' => $_SESSION['mensaje'] ?? null
        ];

        unset($_SESSION['mensaje']);

        // Determinar qué vista cargar según la carrera (normalización robusta)
        $programa = $carrera ?? ($estudiante['programa'] ?? '');
        $viewFile = $this->resolvePlanViewByProgram($programa);
        $viewPath = __DIR__ . '/../Views/estudiantes/' . $viewFile . '.php';

        $viewToRender = file_exists($viewPath)
            ? 'estudiantes/' . $viewFile
            : 'estudiantes/plan_de_aprendizaje';

        $this->renderStudent($viewToRender, $data, ['plan-aprendizaje.css'], []);
    }

    private function normalizeProgramText($text)
    {
        $text = mb_strtoupper(trim((string) $text), 'UTF-8');
        $text = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
            $text
        );
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }

    private function resolvePlanViewByProgram($program)
    {
        $normalized = $this->normalizeProgramText($program);

        if ($normalized === '') {
            return 'plan_de_aprendizaje';
        }

        if (strpos($normalized, 'ADMINISTRACION') !== false) {
            return 'plan_de_aprendizaje_administracion';
        }

        if (strpos($normalized, 'EDUCACION BASICA') !== false) {
            return 'plan_de_aprendizaje_educacion_basica';
        }

        if (strpos($normalized, 'ENFERMERIA') !== false || strpos($normalized, 'VETERINARIA') !== false) {
            return 'plan_de_aprendizaje_enfermeria';
        }

        if (strpos($normalized, 'MARKETING') !== false) {
            return 'plan_de_aprendizaje_marketing';
        }

        if (strpos($normalized, 'PRODUCCION ANIMAL') !== false) {
            return 'plan_de_aprendizaje_produccion_animal';
        }

        if (strpos($normalized, 'SEGURIDAD') !== false || strpos($normalized, 'PREVENCION') !== false) {
            return 'plan_de_aprendizaje_seguridad_prevencion';
        }

        if (strpos($normalized, 'TOPOGRAFIA') !== false) {
            return 'plan_de_aprendizaje_topografia';
        }

        return 'plan_de_aprendizaje';
    }

    public function generarPlanAprendizajePdf()
    {
        // Verificar que el estudiante esté logueado
        if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
            header("Location: " . $this->basePath . "/login");
            exit();
        }

        // Verificar que sea una petición POST con datos del formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['mensaje'] = "Debe enviar el formulario del plan de aprendizaje.";
            header("Location: " . $this->basePath . "/estudiante/plan-aprendizaje");
            exit();
        }

        $userId = $_SESSION['id_usuario'] ?? 0;
        $practica = $this->pasantiaModel->getActivePracticaByUserId((int) $userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "La Fase 1 debe estar completa y aprobada para acceder al Plan de Aprendizaje.";
            header("Location: " . $this->basePath . "/estudiante/informacion?module=pasantias&tab=programa");
            exit();
        }

        $this->ensurePracticeEditableOrRedirect($practica, 'programa');

        // Incluir el archivo que genera el PDF con los datos del formulario
        require_once __DIR__ . '/../Views/estudiantes/generar_plan_pdf.php';
        exit;
    }

    private function renderStudent($view, $data = [], $moduleCss = ['tab-pasantias.css'], $moduleJs = ['tab-pasantias.js'])
    {
        $vista_contenido = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($vista_contenido)) {
            http_response_code(404);
            die('Vista no encontrada: ' . $vista_contenido);
        }

        $data['basePath'] = $data['basePath'] ?? $this->basePath;
        $data['nombreCompleto'] = $data['nombreCompleto'] ?? ($_SESSION['nombres_completos'] ?? 'Estudiante');
        $data['moduleCss'] = $data['moduleCss'] ?? $moduleCss;
        $data['moduleJs'] = $data['moduleJs'] ?? $moduleJs;

        require __DIR__ . '/../Views/Layouts/main_layout.php';
    }

    private function renderAdmin($view, $data = [])
    {
        extract($data);

        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $basePath = '';
        } else {
            $basePath = '/superarseconectadosv2/public';
        }

        $nombreCompleto = $_SESSION['nombres_completos'] ?? 'Administrador';

        $content = __DIR__ . '/../Views/' . $view . '.php';

        if (!file_exists($content)) {
            die("Vista no encontrada: " . $content);
        }

        require __DIR__ . '/../Views/Layouts/admin_layout.php';
    }

    private function calculateDurationHoursFromTime(string $horaInicio, string $horaFin): float
    {
        $horaInicio = substr($horaInicio, 0, 5);
        $horaFin    = substr($horaFin, 0, 5);

        if (!preg_match('/^\d{2}:\d{2}$/', $horaInicio) || !preg_match('/^\d{2}:\d{2}$/', $horaFin)) {
            return 0.0;
        }

        [$h1, $m1] = array_map('intval', explode(':', $horaInicio));
        [$h2, $m2] = array_map('intval', explode(':', $horaFin));

        if ($h1 > 23 || $h2 > 23 || $m1 > 59 || $m2 > 59) {
            return 0.0;
        }

        $inicioMin = ($h1 * 60) + $m1;
        $finMin = ($h2 * 60) + $m2;
        $diffMin = $finMin - $inicioMin;

        if ($diffMin < 0) {
            $diffMin += 24 * 60;
        }

        return round($diffMin / 60, 4);
    }
}
