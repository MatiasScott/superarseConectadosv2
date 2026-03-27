<?php
class InvestigacionController
{

    private $proyectoModel;
    private $carreraModel;
    private $ponenciaModel;
    private $publicacionModel;

    public function __construct($conexion)
    {
        $this->proyectoModel = new ProyectoAdministracion($conexion);
        $this->carreraModel = new ProyectoEstudianteCarrera($conexion);
        $this->ponenciaModel = new Ponencia($conexion);
        $this->publicacionModel = new Publicacion($conexion);
    }

    public function index()
    {
        $proyectos = $this->proyectoModel
            ->obtenerActivosInvestigacion() ?? [];

        $publicaciones = $this->publicacionModel
            ->obtenerTodas() ?? [];

        $ponencias = $this->ponenciaModel
            ->obtenerTodas() ?? [];

        $carreras = $this->carreraModel
            ->obtenerTodas() ?? [];

        require 'app/Views/admin/investigacion/index.php';
    }

    public function crear()
    {
        require 'views/investigacion/crear.php';
    }

    public function guardar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'nombre_proyecto' => $_POST['nombre_proyecto'] ?? '',
                'codigo_proyecto' => $_POST['codigo_proyecto'] ?? '',
                'responsable' => $_POST['responsable'] ?? '',
                'correo_responsable' => $_POST['correo_responsable'] ?? '',
                'objetivo' => $_POST['objetivo'] ?? '',
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'porcentaje_avance' => $_POST['porcentaje_avance'] ?? 0,
                'localizacion' => $_POST['localizacion'] ?? '',
                'convenio' => $_POST['convenio'] ?? '',
                'linea_investigacion' => $_POST['linea_investigacion'] ?? '',
                'alcance_proyecto' => $_POST['alcance_proyecto'] ?? '',
                'presupuesto' => $_POST['presupuesto'] ?? 0,
                'beneficiarios' => $_POST['beneficiarios'] ?? 0,
                'periodo_academico' => $_POST['periodo_academico'] ?? '',
                'estado' => $_POST['estado'] ?? 'ACTIVO'
            ];

            // 🔥 1. Crear proyecto y obtener ID
            $idProyecto = $this->proyectoModel->crearInvestigacion($data);

            // 🔥 2. Guardar carreras (si vienen del formulario)
            if (!empty($_POST['carrera']) && !empty($_POST['nro_estudiantes'])) {

                foreach ($_POST['carrera'] as $index => $carrera) {

                    $nroEstudiantes = $_POST['nro_estudiantes'][$index] ?? 0;

                    if (!empty($carrera)) {
                        $this->carreraModel->agregarCarrera(
                            $idProyecto,
                            $carrera,
                            $nroEstudiantes
                        );
                    }
                }
            }

            header('Location: index.php?controller=investigacion&action=index');
            exit();
        }
    }

    public function eliminar($id)
    {
        $this->proyectoModel->eliminar($id);

        header('Location: index.php?controller=investigacion&action=index');
        exit();
    }

    public function eliminarPublicacion($id)
    {
        $this->publicacionModel->eliminar($id);
        header('Location: index.php?controller=investigacion&action=index');
        exit();
    }

    public function eliminarPonencia($id)
    {
        $this->ponenciaModel->eliminar($id);
        header('Location: index.php?controller=investigacion&action=index');
        exit();
    }

    public function eliminarCarrera($id)
    {
        $this->carreraModel->eliminar($id);
        header('Location: index.php?controller=investigacion&action=index');
        exit();
    }

    public function editar($id)
    {
        $proyecto = $this->proyectoModel->obtenerPorId($id);

        if (!$proyecto) {
            header('Location: index.php?controller=investigacion&action=index');
            exit();
        }

        require 'views/investigacion/editar.php';
    }

    public function actualizar($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';

            $this->proyectoModel->actualizarInvestigacion($id, $nombre, $descripcion);

            header('Location: index.php?controller=investigacion&action=index');
            exit();
        }
    }

    public function guardarPublicacion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'nombre_publicacion' => $_POST['nombre_publicacion'] ?? '',
                'anio' => $_POST['anio'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'url' => $_POST['url'] ?? null,
                'periodo_academico' => $_POST['periodo_academico'] ?? ''
            ];

            $this->publicacionModel->crear($data);

            header('Location: index.php?controller=investigacion&action=index');
            exit();
        }
    }

    public function guardarPonencia()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $data = [
                'nombre_ponencia' => $_POST['nombre_ponencia'] ?? '',
                'autor' => $_POST['autor'] ?? '',
                'nro_acta' => $_POST['nro_acta'] ?? null,
                'fecha_realizacion' => $_POST['fecha_realizacion'] ?? '',
                'nombre_organizador' => $_POST['nombre_organizador'] ?? '',
                'periodo_academico' => $_POST['periodo_academico'] ?? ''
            ];

            $this->ponenciaModel->crear($data);

            header('Location: index.php?controller=investigacion&action=index');
            exit();
        }
    }

    public function proyectos()
    {
        $proyectos = $this->proyectoModel
            ->obtenerPorTipo('INVESTIGACION');

        require 'views/investigacion/proyectos.php';
    }

    public function carreras()
    {
        $carreras = $this->carreraModel->obtenerTodas();

        require 'views/investigacion/carreras.php';
    }

    public function carrerasPorProyecto($idProyecto)
    {
        $proyecto = $this->proyectoModel->obtenerPorId($idProyecto);

        $carreras = $this->carreraModel
            ->obtenerPorProyecto($idProyecto);

        require 'views/investigacion/carreras_proyecto.php';
    }

    public function publicaciones()
    {
        $publicaciones = $this->publicacionModel->obtenerTodas();

        require 'views/investigacion/publicaciones.php';
    }

    public function ponencias()
    {
        $ponencias = $this->ponenciaModel->obtenerTodas();

        require 'views/investigacion/ponencias.php';
    }
}
