<?php
class VinculacionController
{

    private $proyectoModel;

    public function __construct($conexion)
    {
        $this->proyectoModel = new ProyectoAdministracion($conexion);
    }

    public function index()
    {
        $totalProyectos = $this->proyectoModel
            ->contarActivosPorTipo('VINCULACION');

        $proyectos = $this->proyectoModel
            ->obtenerActivosVinculacion();

        require 'views/vinculacion/index.php';
    }

    public function crear()
    {
        require 'views/vinculacion/crear.php';
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

            $this->proyectoModel->crearVinculacion($data);

            header('Location: index.php?controller=vinculacion&action=index');
            exit();
        }
    }

    public function eliminar($id)
    {
        $this->proyectoModel->eliminar($id);

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
}
