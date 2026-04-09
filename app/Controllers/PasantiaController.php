<?php
// app/Controllers/PasantiaController.php

use Dompdf\Dompdf;
use Dompdf\Options;

require_once __DIR__ . '/../Models/PasantiaModel.php';
require_once __DIR__ . '/../Models/UserModel.php';

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
            $_SESSION['mensaje'] = 'El formulario de registro de pasantía está integrado en el panel principal.';
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

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: " . $this->basePath . "/estudiante/seguimiento");
            exit();
        }

        $requiredFields = ['actividad_realizada', 'fecha_actividad', 'hora_inicio', 'hora_fin', 'horas_invertidas'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios para el reporte diario.";
                header("Location: " . $this->basePath . "/estudiante/informacion");
                exit();
            }
        }

        $horas_invertidas = (float) $_POST['horas_invertidas'];
        if ($horas_invertidas <= 0 || $horas_invertidas > 12.00) {
            $_SESSION['mensaje'] = "Error: Las horas invertidas deben ser mayores a 0 y menores o iguales a 12.";
            header("Location: " . $this->basePath . "/estudiante/informacion");
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

        header("Location: " . $this->basePath . "/estudiante/informacion");
        exit();
    }

    public function buscarEntidadPorRUC()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $ruc = trim($_POST['ruc'] ?? '');
        $idPrograma = trim($_POST['idPrograma'] ?? '');

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

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: {$this->basePath}/estudiante/seguimiento");
            exit();
        }

        $requiredFields = ['id', 'actividad_realizada', 'fecha_actividad', 'hora_inicio', 'hora_fin', 'horas_invertidas'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['mensaje'] = "Error: Faltan campos obligatorios al actualizar.";
                header("Location: {$this->basePath}/estudiante/informacion");
                exit();
            }
        }

        $data = [
            'id'                 => $_POST['id'],
            'practica_id'        => $practica['id_practica'],
            'actividad_realizada' => trim($_POST['actividad_realizada']),
            'horas_invertidas'   => (float) $_POST['horas_invertidas'],
            'fecha_actividad'    => $_POST['fecha_actividad'],
            'hora_inicio'        => $_POST['hora_inicio'],
            'hora_fin'           => $_POST['hora_fin']
        ];

        if ($this->pasantiaModel->updateActividadDiaria($data)) {
            $_SESSION['mensaje'] = "Actividad diaria actualizada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al actualizar la actividad diaria.";
        }

        header("Location: {$this->basePath}/estudiante/informacion");
        exit();
    }

    public function deleteActividadDiaria()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
            $_SESSION['mensaje'] = "Error: Solicitud inválida para eliminar.";
            header("Location: {$this->basePath}/estudiante/actividades_diarias");
            exit();
        }

        $userId = $_SESSION['id_usuario'];
        $practica = $this->pasantiaModel->getActivePracticaByUserId($userId);

        if (!$practica || !$practica['estado_fase_uno_completado']) {
            $_SESSION['mensaje'] = "Acceso denegado: Práctica no aprobada.";
            header("Location: {$this->basePath}/estudiante/seguimiento");
            exit();
        }

        $id = (int) $_POST['id'];

        if ($this->pasantiaModel->deleteActividadDiaria($id, $practica['id_practica'])) {
            $_SESSION['mensaje'] = "Actividad diaria eliminada con éxito.";
        } else {
            $_SESSION['mensaje'] = "Error al eliminar la actividad diaria.";
        }

        header("Location: {$this->basePath}/estudiante/informacion");
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

        // Obtener información del estudiante
        $estudiante = $this->userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);

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
        $codigo = $estudiante['codigo_matricula'] ?? $id_practica;
        $filename = 'Actividades_Diarias_' . $codigo . '_' . date('Y-m-d') . '.pdf';

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $dompdf->stream($filename, array("Attachment" => true));
        exit;
    }

    public function editarPasantia($id_practica)
    {
        // Verificar que sea administrador
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header("Location: " . $this->basePath . "/admin/login");
            exit();
        }

        $basePath = $this->basePath;
        $practica = $this->pasantiaModel->getPracticaById($id_practica);

        if (!$practica) {
            $_SESSION['error'] = "Pasantía no encontrada";
            header("Location: " . $basePath . "/admin/dashboard");
            exit();
        }

        // Si es POST, actualizar los datos
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = [
                'estado_fase_uno_completado' => $_POST['estado_fase_uno_completado'] ?? $practica['estado_fase_uno_completado']
            ];

            $resultado = $this->pasantiaModel->actualizarPasantia($id_practica, $datos);

            if ($resultado) {
                $_SESSION['success'] = "Estado de pasantía actualizado correctamente";
            } else {
                $_SESSION['error'] = "Error al actualizar el estado de la pasantía";
            }

            header("Location: " . $basePath . "/admin/practicas");
            exit();
        }

        // Cargar vista de edición (ya no necesitamos cargar docentes)
        //require_once __DIR__ . '/../Views/admin/practicas/editar_pasantia.php';
        $this->renderAdmin('admin/practicas/editar_pasantia', [
            'title' => 'Editar Pasantía',
            'practica' => $practica
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
            $_SESSION['error'] = 'No autorizado para eliminar pasantías. Debes iniciar sesión como administrador.';
            header("Location: " . $this->basePath . "/admin/dashboard");
            exit();
        }

        error_log("✓ Usuario admin verificado");

        try {
            $resultado = $this->pasantiaModel->eliminarPasantia($id_practica);
            error_log("Resultado eliminación: " . ($resultado ? '✓ SUCCESS' : '✗ FAILED'));

            if ($resultado) {
                $_SESSION['success'] = 'Pasantía #' . $id_practica . ' eliminada correctamente';
                error_log("✓ Pasantía eliminada exitosamente");
            } else {
                $_SESSION['error'] = 'Error al eliminar la pasantía #' . $id_practica;
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

        // Determinar qué vista cargar según la carrera
        $carreraEstudiante = $carrera ?? strtolower(str_replace(' ', '_', $estudiante['programa'] ?? ''));

        $viewsMap = [
            'educación_básica' => 'plan_de_aprendizaje_educacion_basica',
            'administración' => 'plan_de_aprendizaje_administracion',
            'marketing' => 'plan_de_aprendizaje_marketing',
            'enfermería' => 'plan_de_aprendizaje_enfermeria',
            'producción_animal' => 'plan_de_aprendizaje_produccion_animal',
            'seguridad_y_prevención' => 'plan_de_aprendizaje_seguridad_prevencion',
            'topografía' => 'plan_de_aprendizaje_topografia'
        ];

        $viewFile = $viewsMap[$carreraEstudiante] ?? 'plan_de_aprendizaje';
        $viewPath = __DIR__ . '/../Views/estudiantes/' . $viewFile . '.php';

        $viewToRender = file_exists($viewPath)
            ? 'estudiantes/' . $viewFile
            : 'estudiantes/plan_de_aprendizaje';

        $this->renderStudent($viewToRender, $data, ['plan-aprendizaje.css'], []);
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
}
