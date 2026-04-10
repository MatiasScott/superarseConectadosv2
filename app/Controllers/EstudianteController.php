<?php
// app/Controllers/EstudianteController.php

require_once '../app/Models/UserModel.php';
require_once '../app/Models/PagoModel.php';
require_once '../app/Models/AsignaturaModel.php';
require_once '../app/Models/CredencialModel.php';
require_once '../app/Models/BancoModel.php';
require_once '../app/Models/PasantiaModel.php';
require_once '../app/Helpers/AuthSecurity.php';

class EstudianteController
{
    private $basePath;

    public function __construct()
    {
        // Configurar basePath según el entorno
        if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
            $this->basePath = '';
        } else {
            $this->basePath = '/superarseconectadosv2/public';
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['authenticated']) && !empty($_SESSION['must_change_password'])) {
            header("Location: " . $this->basePath . "/password/change");
            exit();
        }
    }

    public function informacion()
    {
        if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['identificacion'])) {
            header("Location: " . $this->basePath . "/login");
            exit();
        }

        $idUsuario = $_SESSION['id_usuario'];
        $identificacion = $_SESSION['identificacion'];

        $data = [];

        try {
            $activityPage = max(1, (int) ($_GET['activity_page'] ?? 1));
            $activityLimit = 10;
            $activityOffset = ($activityPage - 1) * $activityLimit;

            $userModel = new UserModel();
            $pagoModel = new PagoModel();
            $asignaturaModel = new AsignaturaModel();
            $credencialModel = new CredencialModel();
            $bancoModel = new BancoModel();
            $pasantiaModel = new PasantiaModel();

            $infoPersonal = $userModel->getUserInfoByIdentificacion($identificacion);
            $infoPrograma = $userModel->getProgramaInfoByIdentificacion($identificacion);
            $infoPagos = $pagoModel->getPagosByIdentificacion($identificacion);
            $infoAsignaturas = $asignaturaModel->getAsignaturasByIdentificacion($identificacion);
            $infoCredenciales = $credencialModel->getCredencialesByUserId($idUsuario);
            $tutoresAcademicos = $userModel->getTutoresAcademicosByPrograma($infoPersonal['programa']);
            $infoProyectos = $pasantiaModel->getProyectos();
            $modalidades = $pasantiaModel->getPracticaModalidad();
            $infoPractica = $pasantiaModel->getActivePracticaByUserId($idUsuario);
            $infoStatusPractica = $pasantiaModel->getStatusPracticaByUserId($idUsuario);
            $infoBancos = $bancoModel->getAllBancosActivos();
            $totalActividadesDiarias = 0;
            $totalHorasActividades = 0;
            $totalActivityPages = 1;
            if (!empty($infoPractica['id_practica']) && is_numeric($infoPractica['id_practica'])) {
                $totalActividadesDiarias = $pasantiaModel->countActividadesDiarias((int) $infoPractica['id_practica']);
                $totalHorasActividades = $pasantiaModel->getTotalHorasActividades((int) $infoPractica['id_practica']);
                $totalActivityPages = max(1, (int) ceil($totalActividadesDiarias / $activityLimit));
                $activityPage = min($activityPage, $totalActivityPages);
                $activityOffset = ($activityPage - 1) * $activityLimit;

                $actividadesDiarias = $pasantiaModel->getActividadesDiariasPaginated(
                    practicaId: (int)$infoPractica['id_practica'],
                    offset: $activityOffset,
                    limit: $activityLimit,
                    sortBy: 'fecha_actividad',
                    sortDir: 'DESC'
                );
            } else {
                $actividadesDiarias = [];
            }
            if (!empty($infoPractica['id_practica']) && is_numeric($infoPractica['id_practica'])) {
                $programaTrabajo = $pasantiaModel->getProgramaTrabajo(
                    practicaId: (int)$infoPractica['id_practica'],
                    limit: 10,
                    offset: 0
                );
            } else {
                $programaTrabajo = [];
            }
            $data['nombreCompleto'] = $_SESSION['nombres_completos'] ?? 'Estudiante';
            $data['infoPagos'] = $infoPagos ?? [
                'abono_total' => 'N/D',
                'saldo_total' => 'N/D',
                'observacion' => 'N/D'
            ];
            $data['infoPersonal'] = $infoPersonal ?? [];
            $data['infoAsignaturas'] = $infoAsignaturas ?? [];
            $data['infoCredenciales'] = $infoCredenciales ?? [];
            $data['bancos'] = $infoBancos ?? [];
            $data['basePath'] = $this->basePath;
            $data['modalidades'] = $modalidades ?? [];
            $data['tutoresAcademicos'] = $tutoresAcademicos ?? [];
            $data['cantidadTutores'] = is_array($tutoresAcademicos) ? count($tutoresAcademicos) : 0;
            $data['infoPractica'] = $infoPractica ?? null;
            $data['infoStatusPractica'] = $infoStatusPractica ?? null;
            $data['actividadesDiarias'] = $actividadesDiarias ?? [];
            $data['totalActividadesDiarias'] = $totalActividadesDiarias;
            $data['totalHorasActividades'] = $totalHorasActividades;
            $data['activityPage'] = $activityPage;
            $data['activityLimit'] = $activityLimit;
            $data['totalActivityPages'] = $totalActivityPages;
            $data['programaTrabajo'] = $programaTrabajo ?? [];
            $data['infoPrograma'] = $infoPrograma ?? [];
            $data['infoProyectos'] = $infoProyectos ?? [];
            $data['moduleCss'] = ['tab-pasantias.css'];
            $data['moduleJs'] = ['tab-pasantias.js'];
            // Agregar practicaId directamente para facilitar el acceso
            $data['practicaId'] = $infoPractica['id_practica'] ?? 0;
            $data['csrfTokenFaseOne'] = AuthSecurity::generateCsrfToken('student_fase_one');
            $data['csrfTokenActividadForm'] = AuthSecurity::generateCsrfToken('student_actividad_form');
            $data['csrfTokenActividadDelete'] = AuthSecurity::generateCsrfToken('student_actividad_delete');
            $data['csrfTokenPagoUpload'] = AuthSecurity::generateCsrfToken('student_payment_upload');

            $vista_contenido = __DIR__ . '/../Views/dashboard/index.php';
            require_once __DIR__ . '/../Views/Layouts/main_layout.php';
        } catch (Exception $e) {
            error_log("Error en EstudianteController: " . $e->getMessage());
            header("Location: " . $this->basePath . "/login?error=error_sistema");
            exit();
        }
    }
}