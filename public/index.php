<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../vendor/autoload.php';
require_once '../app/Controllers/LoginController.php';
require_once '../app/Controllers/EstudianteController.php';
require_once '../app/Controllers/PagoController.php';
require_once '../app/Controllers/PasantiaController.php';
require_once '../app/Controllers/AdminController.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/* =========================================================
   CONFIGURAR BASE PATH (LOCAL / PRODUCCIÓN)
========================================================= */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
    $basePath = '';
} else {
    $basePath = '/superarseconectadosv2/public';
}

/* Quitar basePath de la URI */
if ($basePath !== '' && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}

if ($uri === '' || $uri === false) {
    $uri = '/';
}

$method = $_SERVER['REQUEST_METHOD'];

/* =========================================================
   ================== RUTAS ADMIN ==========================
========================================================= */

if (preg_match('#^/admin#', $uri)) {

    $controller = new AdminController();

    switch (true) {

        case $uri === '/admin/login':
            $controller->loginForm();
            break;

        case $uri === '/admin/login/check' && $method === 'POST':
            $controller->checkLogin();
            break;

        case $uri === '/admin/dashboard':
            $controller->dashboard();
            break;

        case $uri === '/admin/practicas':
            $controller->practicas();
            break;

        case $uri === '/admin/vinculacion':
            $controller->vinculacion();
            break;

        case $uri === '/admin/investigacion':
            $controller->investigacion();
            break;

        case $uri === '/admin/proyecto/crear':
            $controller->mostrarCrearProyecto();
            break;

        case $uri === '/admin/proyecto/crear_vinculacion':
            $controller->mostrarCrearProyectoVinculacion();
            break;

        case $uri === '/admin/publicacion/crear':
            $controller->mostrarCrearPublicacion();
            break;

        case $uri === '/admin/ponencia/crear':
            $controller->mostrarCrearPonencia();
            break;

        case $uri === '/admin/carrera/crear':
            $controller->mostrarCrearCarrera();
            break;

        case $uri === '/admin/carrera/crearV':
            $controller->mostrarCrearCarreraV();
            break;

        case $uri === '/admin/guardar-proyecto-investigacion' && $method === 'POST':
            $controller->guardarProyectoInvestigacion();
            break;

        case $uri === '/admin/guardar-proyecto-vinculacion' && $method === 'POST':
            $controller->guardarProyectoVinculacion();
            break;

        case $uri === '/admin/guardar-publicacion' && $method === 'POST':
            $controller->guardarPublicacion();
            break;

        case $uri === '/admin/guardar-ponencia' && $method === 'POST':
            $controller->guardarPonencia();
            break;

        case $uri === '/admin/guardar-carrera-proyecto' && $method === 'POST':
            $controller->guardarCarreraProyecto();
            break;

        case $uri === '/admin/guardar-carrera-proyectoV' && $method === 'POST':
            $controller->guardarCarreraProyectoV();
            break;

        case preg_match('/^\/admin\/proyecto\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarProyecto((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/vinculacion\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarProyectoVinculacion((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/publicacion\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarPublicacion((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/ponencia\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarPonencia((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/carrera\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarCarrera((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/carrera\/editarV\/(\d+)$/', $uri, $matches):
            $controller->editarCarreraV((int)$matches[1]);
            break;

        case $uri === '/admin/proyecto/actualizarVinculacion' && $method === 'POST':
            $controller->actualizarProyectoVinculacion();
            break;

        case $uri === '/admin/proyecto/actualizar' && $method === 'POST':
            $controller->actualizarProyecto();
            break;

        case $uri === '/admin/publicacion/actualizar' && $method === 'POST':
            $controller->actualizarPublicacion();
            break;

        case $uri === '/admin/ponencia/actualizar' && $method === 'POST':
            $controller->actualizarPonencia();
            break;

        case $uri === '/admin/carrera/actualizar' && $method === 'POST':
            $controller->actualizarCarrera();
            break;

        case $uri === '/admin/carrera/actualizarV' && $method === 'POST':
            $controller->actualizarCarreraV();
            break;

        case $uri === '/admin/auditoria-fase-dos':
            $controller->auditoriaPhasTwo();
            break;

        case $uri === '/admin/plan-estrategico':
            $controller->planEstrategicoIndex();
            break;

        case $uri === '/admin/pedi/create':
            $controller->crearPedi();
            break;

        case $uri === '/admin/pedi/store' && $method === 'POST':
            $controller->guardarPedi();
            break;

        case preg_match('/^\/admin\/pedi\/edit\/(\d+)$/', $uri, $matches):
            $controller->editarPedi((int)$matches[1]);
            break;

        case $uri === '/admin/pedi/update' && $method === 'POST':
            $controller->actualizarPedi();
            break;

        case $uri === '/admin/poa/create':
            $controller->crearPoa();
            break;

        case $uri === '/admin/poa/store' && $method === 'POST':
            $controller->guardarPoa();
            break;

        case preg_match('/^\/admin\/poa\/edit\/(\d+)$/', $uri, $matches):
            $controller->editarPoa((int)$matches[1]);
            break;

        case $uri === '/admin/poa/update' && $method === 'POST':
            $controller->actualizarPoa();
            break;

        case $uri === '/admin/actividad/create':
            $controller->crearActividad();
            break;

        case $uri === '/admin/actividad/store' && $method === 'POST':
            $controller->guardarActividad();
            break;

        case preg_match('/^\/admin\/actividad\/edit\/(\d+)$/', $uri, $matches):
            $controller->editarActividad((int)$matches[1]);
            break;

        case $uri === '/admin/actividad/update' && $method === 'POST':
            $controller->actualizarActividad();
            break;

        case $uri === '/admin/logout':
            $controller->logout();
            break;

        case preg_match('/^\/admin\/practicas\/editar\/(\d+)$/', $uri, $matches):
            $pasantiaController = new PasantiaController();
            $pasantiaController->editarPasantia((int)$matches[1]);
            break;

        case preg_match('/^\/admin\/practicas\/eliminar\/(\d+)$/', $uri, $matches):
            if ($method !== 'POST') {
                $_SESSION['error'] = 'La eliminación debe hacerse mediante POST';
                header("Location: $basePath/admin/dashboard");
                exit();
            }
            $pasantiaController = new PasantiaController();
            $pasantiaController->eliminarPasantia((int)$matches[1]);
            break;

        case $uri === '/admin/convenio':
            $controller->convenio();
            break;

        case $uri === '/admin/convenio/crear':
            $controller->mostrarCrearConvenio();
            break;

        case $uri === '/admin/convenio/guardar' && $method === 'POST':
            $controller->guardarConvenio();
            break;

        case preg_match('/^\/admin\/convenio\/editar\/(\d+)$/', $uri, $matches):
            $controller->editarConvenio((int)$matches[1]);
            break;

        case $uri === '/admin/convenio/actualizar' && $method === 'POST':
            $controller->actualizarConvenio();
            break;

        default:
            http_response_code(404);
            echo "404 - Ruta admin no encontrada: " . htmlspecialchars($uri);
            break;
    }

    exit();
}

/* =========================================================
   ================== RUTAS PDF DINÁMICAS ==================
========================================================= */

if (preg_match('/^\/pasantias\/generatePdf\/(\d+)$/', $uri, $matches)) {
    $controller = new PasantiaController();
    $controller->generatePdf((int)$matches[1]);
    exit();
}

if (preg_match('/^\/pasantias\/generateActividadesPdf\/(\d+)$/', $uri, $matches)) {
    $controller = new PasantiaController();
    $controller->generateActividadesPdf((int)$matches[1]);
    exit();
}

if (preg_match('/^\/admin\/practicas\/editar\/(\d+)$/', $uri, $matches)) {
    $controller = new PasantiaController();
    $controller->editarPasantia((int)$matches[1]);
    exit();
}

if (preg_match('/^\/admin\/practicas\/eliminar\/(\d+)$/', $uri, $matches)) {
    if ($method !== 'POST') {
        $_SESSION['error'] = 'La eliminación debe hacerse mediante POST';
        header("Location: $basePath/admin/dashboard");
        exit();
    }
    $controller = new PasantiaController();
    $controller->eliminarPasantia((int)$matches[1]);
    exit();
}

/* =========================================================
   ================== RUTAS ESTUDIANTES ====================
========================================================= */

switch ($uri) {

    /* LOGIN */
    case '/':
    case '/login':
        if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
            header("Location: $basePath/estudiante/informacion");
            exit();
        }
        (new LoginController())->index();
        break;

    case '/login/check':
        if ($method === 'POST') {
            (new LoginController())->check();
        } else {
            header("Location: $basePath/login");
        }
        break;

    case '/logout':
    case '/login/logout':
        (new LoginController())->logout();
        header("Location: $basePath/login");
        exit();

        /* ESTUDIANTE */
    case '/estudiante/informacion':
        (new EstudianteController())->informacion();
        break;

    /* PAGOS */
    case '/pago':
        (new PagoController())->procesarPago();
        break;

    case '/pagos/upload-comprobante':
        if ($method === 'POST') {
            (new PagoController())->uploadComprobante();
        }
        break;

    /* PASANTÍAS */
    case '/pasantias/buscarEntidadPorRUC':
        (new PasantiaController())->buscarEntidadPorRUC();
        exit();

    case '/pasantias/saveFaseOne':
        if ($method === 'POST') {
            (new PasantiaController())->saveFaseOne();
        }
        break;

    case '/pasantias/addActividadDiaria':
        if ($method === 'POST') {
            (new PasantiaController())->addActividadDiaria();
        }
        break;

    case '/pasantias/updateActividadDiaria':
        if ($method === 'POST') {
            (new PasantiaController())->updateActividadDiaria();
        }
        break;

    case '/pasantias/deleteActividadDiaria':
        if ($method === 'POST') {
            $id = $_POST['id'] ?? 0;
            (new PasantiaController())->deleteActividadDiaria($id);
        }
        break;

    case '/pasantias/addProgramaTrabajo':
        if ($method === 'POST') {
            (new PasantiaController())->addProgramaTrabajo();
        }
        break;

    case '/pasantias/updateProgramaTrabajo':
        if ($method === 'POST') {
            (new PasantiaController())->updateProgramaTrabajo();
        }
        break;

    case '/pasantias/deleteProgramaTrabajo':
        if ($method === 'POST') {
            (new PasantiaController())->deleteProgramaTrabajo();
        }
        break;

    case '/estudiante/plan-aprendizaje':
        (new PasantiaController())->showPlanDeAprendizaje();
        break;

    case '/estudiante/generar-plan-aprendizaje-pdf':
        if ($method === 'POST') {
            (new PasantiaController())->generarPlanAprendizajePdf();
        }
        break;

    default:
        http_response_code(404);
        echo "404 - Página no encontrada: " . htmlspecialchars($uri);
        break;
}
