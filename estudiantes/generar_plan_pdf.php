<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar opciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('chroot', realpath(__DIR__ . '/../../../'));
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

// Cargar logo y convertirlo a base64
$logoPath = __DIR__ . '/../../../public/Assets/img/LOGO SUPERARSE PNG-02.png';
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoSrc = 'file:///' . str_replace('\\', '/', realpath($logoPath));
}

// Obtener datos del formulario
$apellidos_nombres = $_POST['apellidos_nombres'] ?? '';
$carrera = $_POST['carrera'] ?? '';
$nivel = $_POST['nivel'] ?? '';
$cedula = $_POST['cedula'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';

$nombre_empresa = $_POST['nombre_empresa'] ?? '';
$ruc = $_POST['ruc'] ?? '';
$tipo_entidad = $_POST['tipo_entidad'] ?? '';
$actividad_economica = $_POST['actividad_economica'] ?? '';
$ubicacion = $_POST['ubicacion'] ?? '';
$area_departamento = $_POST['area_departamento'] ?? '';
$nombre_tutor_empresarial = $_POST['nombre_tutor_empresarial'] ?? '';
$telefono_tutor_empresarial = $_POST['telefono_tutor_empresarial'] ?? '';
$correo_tutor_empresarial = $_POST['correo_tutor_empresarial'] ?? '';
$descripcion_empresa = $_POST['descripcion_empresa'] ?? '';

$periodo_academico = $_POST['periodo_academico'] ?? '';
$fecha_inicio = $_POST['fecha_inicio'] ?? '';
$fecha_fin = $_POST['fecha_fin'] ?? '';
$horario = $_POST['horario'] ?? '';
$total_horas = $_POST['total_horas'] ?? '240';
$modalidad = $_POST['modalidad'] ?? '';
$nombre_tutor_academico = $_POST['nombre_tutor_academico'] ?? '';
$correo_tutor_academico = $_POST['correo_tutor_academico'] ?? '';

// Resultados de Aprendizaje
$ra1 = isset($_POST['ra1']) ? 'X' : '';
$ra2 = isset($_POST['ra2']) ? 'X' : '';
$ra3 = isset($_POST['ra3']) ? 'X' : '';
$ra4 = isset($_POST['ra4']) ? 'X' : '';
$ra5 = isset($_POST['ra5']) ? 'X' : '';

// Firmas
$signature_tutor_empresarial = $_POST['signature_tutor_empresarial'] ?? '';
$signature_tutor_academico = $_POST['signature_tutor_academico'] ?? '';
$nombre_firma_empresarial = $_POST['nombre_firma_empresarial'] ?? '';
$nombre_firma_academico = $_POST['nombre_firma_academico'] ?? '';

// Crear HTML del PDF
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            padding: 10px;
            margin: 0;
        }
        
        .document-header {
            width: 100%;
            border: 2px solid #333;
            margin-bottom: 15px;
        }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .header-table td {
            border-right: 2px solid #333;
            padding: 10px;
            vertical-align: middle;
        }
        
        .header-table td:last-child {
            border-right: none;
        }
        
        .header-logo {
            text-align: center;
        }
        
        .header-logo strong {
            color: #5B21B6;
            font-size: 14px;
        }
        
        .header-title {
            text-align: center;
        }
        
        .header-title h1 {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .header-title h2 {
            font-size: 11px;
            font-weight: 600;
        }
        
        .header-info {
            font-size: 9px;
            line-height: 1.6;
            padding: 5px !important;
        }
        
        .header-info div {
            margin-bottom: 1px;
        }
        
        .section-title {
            background-color: #f0f0f0;
            padding: 6px;
            font-size: 11px;
            font-weight: 700;
            margin: 10px 0 5px 0;
            border: 1px solid #999;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table td {
            border: 1px solid #999;
            padding: 5px;
            font-size: 10px;
        }
        
        table td:first-child {
            font-weight: 600;
            background-color: #f9f9f9;
            width: 180px;
        }
        
        .objective-text {
            margin: 10px 0;
            padding: 8px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            text-align: justify;
            font-size: 10px;
            line-height: 1.5;
        }
        
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .results-table th {
            background-color: #e0e0e0;
            border: 1px solid #999;
            padding: 6px;
            font-size: 10px;
            text-align: center;
            font-weight: 600;
        }
        
        .results-table td {
            border: 1px solid #999;
            padding: 5px;
            font-size: 9px;
            vertical-align: top;
        }
        
        .results-table td:first-child {
            text-align: center;
            width: 30px;
        }
        
        .activities-list {
            margin: 10px 0;
            padding-left: 15px;
        }
        
        .activities-list li {
            margin-bottom: 5px;
            line-height: 1.4;
            font-size: 10px;
        }
        
        .note-box {
            background-color: #fff9e6;
            border: 1px solid #f0c419;
            padding: 8px;
            margin: 10px 0;
            font-size: 10px;
            line-height: 1.4;
        }
        
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .signatures-table th {
            background-color: #f0f0f0;
            border: 1px solid #999;
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            text-align: center;
            width: 50%;
        }
        
        .signatures-table td {
            border: 1px solid #999;
            padding: 10px;
            height: 120px;
            vertical-align: bottom;
            text-align: center;
            width: 50%;
        }
        
        .signature-image {
            max-width: 250px;
            max-height: 70px;
            margin-bottom: 5px;
        }
        
        .signature-name {
            margin-top: 5px;
            padding-top: 5px;
            border-top: 1px solid #999;
            font-size: 9px;
        }
        
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Encabezado del documento -->
    <div class="document-header">
        <table class="header-table">
            <tr>
                <td class="logo-cell" style="width: 160px; text-align: center;">
                    ' . ($logoSrc ? '<img src="' . $logoSrc . '" alt="Superarse Tecnológico" style="width: 140px; height: auto;">' : '<strong>SUPERARSE</strong><br><span style="font-size: 9px;">Tecnológico de Formación Superior</span>') . '
                </td>
                <td class="header-title">
                    <h1>Gestión de Prácticas Pre Profesionales laborales</h1>
                    <h2>Plan de Aprendizaje Práctico</h2>
                </td>
                <td class="header-info" style="width: 160px;">
                    <div><strong>VERSIÓN:</strong> 002</div>
                    <div><strong>CÓDIGO:</strong> ISTS-GIDIVS-05-004</div>
                    <div><strong>FECHA:</strong> 22/11/2025</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Sección 1: Datos del estudiante -->
    <div class="section-title">1. Datos del estudiante:</div>
    <table>
        <tr>
            <td>Apellidos y nombres</td>
            <td>' . htmlspecialchars($apellidos_nombres) . '</td>
        </tr>
        <tr>
            <td>Carrera</td>
            <td>' . htmlspecialchars($carrera) . '</td>
        </tr>
        <tr>
            <td>Nivel</td>
            <td>' . htmlspecialchars($nivel) . '</td>
        </tr>
        <tr>
            <td>Cédula</td>
            <td>' . htmlspecialchars($cedula) . '</td>
        </tr>
        <tr>
            <td>Correo electrónico</td>
            <td>' . htmlspecialchars($correo) . '</td>
        </tr>
        <tr>
            <td>Teléfono</td>
            <td>' . htmlspecialchars($telefono) . '</td>
        </tr>
    </table>

    <!-- Sección 2: Datos de la empresa -->
    <div class="section-title">2. Datos de la empresa:</div>
    <table>
        <tr>
            <td>Nombre legal de la entidad formadora</td>
            <td>' . htmlspecialchars($nombre_empresa) . '</td>
        </tr>
        <tr>
            <td>RUC</td>
            <td>' . htmlspecialchars($ruc) . '</td>
        </tr>
        <tr>
            <td>Tipo de entidad</td>
            <td>' . htmlspecialchars($tipo_entidad) . '</td>
        </tr>
        <tr>
            <td>Actividad económica principal</td>
            <td>' . htmlspecialchars($actividad_economica) . '</td>
        </tr>
        <tr>
            <td>Ubicación</td>
            <td>' . htmlspecialchars($ubicacion) . '</td>
        </tr>
        <tr>
            <td>Área/departamento donde realizará la práctica</td>
            <td>' . htmlspecialchars($area_departamento) . '</td>
        </tr>
        <tr>
            <td>Nombre del tutor empresarial</td>
            <td>' . htmlspecialchars($nombre_tutor_empresarial) . '</td>
        </tr>
        <tr>
            <td>Teléfono</td>
            <td>' . htmlspecialchars($telefono_tutor_empresarial) . '</td>
        </tr>
        <tr>
            <td>Correo electrónico tutor</td>
            <td>' . htmlspecialchars($correo_tutor_empresarial) . '</td>
        </tr>
        <tr>
            <td>Descripción general de la empresa</td>
            <td>' . nl2br(htmlspecialchars($descripcion_empresa)) . '</td>
        </tr>
    </table>

    <!-- Sección 3: Datos del periodo de prácticas -->
    <div class="section-title">3. Datos del periodo de prácticas</div>
    <table>
        <tr>
            <td>Periodo Académico</td>
            <td>' . htmlspecialchars($periodo_academico) . '</td>
        </tr>
        <tr>
            <td>Fecha de inicio</td>
            <td>' . htmlspecialchars($fecha_inicio) . '</td>
        </tr>
        <tr>
            <td>Fecha de fin</td>
            <td>' . htmlspecialchars($fecha_fin) . '</td>
        </tr>
        <tr>
            <td>Horario</td>
            <td>' . htmlspecialchars($horario) . ' | <strong>Número de total de horas:</strong> ' . htmlspecialchars($total_horas) . '</td>
        </tr>
        <tr>
            <td>Modalidad</td>
            <td>' . htmlspecialchars($modalidad) . '</td>
        </tr>
        <tr>
            <td>Nombre del tutor académico</td>
            <td>' . htmlspecialchars($nombre_tutor_academico) . '</td>
        </tr>
        <tr>
            <td>Correo tutor académico institucional</td>
            <td>' . htmlspecialchars($correo_tutor_academico) . '</td>
        </tr>
    </table>

    <!-- Sección 4: Objetivo de las prácticas preprofesionales -->
    <div class="section-title">4. Objetivo de las prácticas preprofesionales</div>
    <div class="objective-text">';

// Definir objetivo según la carrera

$carreraNormalizada = strtoupper(trim($carrera));
// Palabras clave para las 7 carreras
$carreras = [
    'ADMINISTRACION' => ['ADMINISTRACION', 'ADMINISTRACIÓN'],
    'EDUCACION_BASICA' => ['EDUCACION BASICA', 'EDUCACIÓN BÁSICA'],
    'ENFERMERIA' => ['ENFERMERIA', 'ENFERMERÍA'],
    'MARKETING' => ['MARKETING'],
    'PRODUCCION_ANIMAL' => ['PRODUCCION ANIMAL', 'PRODUCCIÓN ANIMAL'],
    'SEGURIDAD_PREVENCION' => ['SEGURIDAD', 'PREVENCION', 'PREVENCIÓN'],
    'TOPOGRAFIA' => ['TOPOGRAFIA', 'TOPOGRAFÍA']
];

function carrera_es($carreraNormalizada, $palabras)
{
    foreach ($palabras as $palabra) {
        if (strpos($carreraNormalizada, $palabra) !== false) return true;
    }
    return false;
}

if (carrera_es($carreraNormalizada, $carreras['ADMINISTRACION'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Administración en un entorno organizacional real, participando en procesos administrativos, gestión de recursos, planificación y toma de decisiones, fortaleciendo competencias técnicas, éticas y de liderazgo, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['EDUCACION_BASICA'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Educación Básica en un entorno educativo real, apoyando los procesos de enseñanza-aprendizaje, la planificación y gestión didáctica, la evaluación de los aprendizajes, y la atención a la diversidad e inclusión, actuando con responsabilidad social, ética y enfoque humanista, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['ENFERMERIA'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Enfermería en la atención integral de la salud, participando en la promoción, prevención, recuperación y rehabilitación, bajo principios éticos y humanísticos, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['MARKETING'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Marketing en la investigación de mercados, desarrollo de estrategias comerciales, gestión de ventas y comunicación, fortaleciendo competencias creativas, analíticas y éticas, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['PRODUCCION_ANIMAL'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Producción Animal mediante la ejecución de actividades técnicas en sistemas de producción pecuaria en un entorno laboral real, bajo supervisión, fortaleciendo competencias profesionales, éticas, ambientales y de bienestar animal, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['SEGURIDAD_PREVENCION'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Seguridad y Prevención en la gestión de riesgos, implementación de planes de emergencia, promoción de la cultura preventiva y protección de personas y bienes, en coherencia con el perfil de egreso.';
} elseif (carrera_es($carreraNormalizada, $carreras['TOPOGRAFIA'])) {
    $html .= 'Aplicar los conocimientos de la carrera de Topografía en un entorno de campo y proyectos reales, mediante la ejecución de levantamientos topográficos, procesamiento de datos, elaboración de planos y apoyo en proyectos de georreferenciación, bajo supervisión, fortaleciendo competencias técnicas, éticas y de seguridad en obra, en coherencia con el perfil de egreso.';
} else {
    $html .= 'Aplicar los conocimientos de la carrera de ' . htmlspecialchars($carrera) . ' en un entorno real, fortaleciendo competencias profesionales y éticas, en coherencia con el perfil de egreso.';
}

$html .= '</div>

    <!-- Sección 5: Resultados de Aprendizaje -->
    <div class="section-title">5. Resultados de Aprendizaje</div>
    <p style="margin-bottom: 8px; font-size: 10px;">Al finalizar las prácticas preprofesionales, el estudiante será capaz de:</p>
    
    <table class="results-table">
        <thead>
            <tr>
                <th>Selección</th>
                <th>Resultados de Aprendizaje</th>
                <th>Actividades relacionadas</th>
            </tr>
        </thead>
        <tbody>';

// Resultados de Aprendizaje según la carrera
if (carrera_es($carreraNormalizada, $carreras['ADMINISTRACION'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Aplicar procesos administrativos en la gestión de recursos humanos, materiales y financieros.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Participar en la planificación y organización de actividades empresariales.</td><td>A3, A4</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Utilizar herramientas tecnológicas para la gestión administrativa.</td><td>A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Aplicar principios éticos y legales en la administración.</td><td>A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Elaborar informes y reportes administrativos.</td><td>A7</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['EDUCACION_BASICA'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Reconocer el currículo, planes y programas oficiales aplicados en las instituciones educativas.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Estructurar procesos de enseñanza-aprendizaje en la práctica pedagógica.</td><td>A1, A2, A3</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Determinar el nivel de aprendizaje de los estudiantes utilizando estrategias de evaluación.</td><td>A4, A6</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Identificar elementos del proceso didáctico en el aula.</td><td>A3, A2</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Establecer implicaciones socioeducativas de la diversidad cultural.</td><td>A5, A2, A6</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['ENFERMERIA'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Aplicar técnicas básicas de enfermería en la atención integral del paciente.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Participar en la promoción y prevención de la salud.</td><td>A3, A4</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Gestionar el cuidado y la seguridad del paciente.</td><td>A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Aplicar principios éticos y legales en la atención de enfermería.</td><td>A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Elaborar registros y reportes de enfermería.</td><td>A7</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['MARKETING'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Investigar mercados y analizar información relevante.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Desarrollar estrategias de marketing y ventas.</td><td>A3, A4</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Gestionar la comunicación y promoción de productos o servicios.</td><td>A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Utilizar herramientas digitales para el marketing.</td><td>A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Elaborar reportes y análisis de resultados.</td><td>A7</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['PRODUCCION_ANIMAL'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Ejecutar actividades técnicas de manejo productivo y reproductivo.</td><td>A1, A2, A6</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Implementar acciones preventivas de sanidad animal y bioseguridad.</td><td>A3, A8, A7</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Aplicar estrategias de alimentación y nutrición animal.</td><td>A4, A1, A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Aplicar protocolos de buenas prácticas pecuarias y sostenibilidad ambiental.</td><td>A6, A3, A8</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Registrar y comunicar información técnica de la operación.</td><td>A5, A7</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['SEGURIDAD_PREVENCION'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Identificar riesgos y proponer medidas preventivas en el entorno laboral.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Implementar planes de emergencia y evacuación.</td><td>A3, A4</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Promover la cultura preventiva y la seguridad ocupacional.</td><td>A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Aplicar normativas legales de seguridad y salud en el trabajo.</td><td>A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Elaborar informes y reportes de seguridad.</td><td>A7</td></tr>';
} elseif (carrera_es($carreraNormalizada, $carreras['TOPOGRAFIA'])) {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Realizar levantamientos y replanteos topográficos.</td><td>A2, A3, A4</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Procesar información topográfica según la precisión requerida.</td><td>A5, A8</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Representar información topográfica en planos.</td><td>A6, A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Utilizar herramientas tecnológicas y software para representaciones gráficas digitales.</td><td>A2, A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Interpretar cartas topográficas y mapas temáticos.</td><td>A7</td></tr>';
} else {
    $html .= '
        <tr><td>' . $ra1 . '</td><td><strong>RA1.</strong> Aplicar conocimientos profesionales en el entorno laboral.</td><td>A1, A2</td></tr>
        <tr><td>' . $ra2 . '</td><td><strong>RA2.</strong> Participar en procesos de mejora continua.</td><td>A3, A4</td></tr>
        <tr><td>' . $ra3 . '</td><td><strong>RA3.</strong> Utilizar herramientas tecnológicas y de gestión.</td><td>A5</td></tr>
        <tr><td>' . $ra4 . '</td><td><strong>RA4.</strong> Aplicar principios éticos y legales en su profesión.</td><td>A6</td></tr>
        <tr><td>' . $ra5 . '</td><td><strong>RA5.</strong> Elaborar informes y reportes profesionales.</td><td>A7</td></tr>';
}

$html .= '
        </tbody>
    </table>
    
    <div class="note-box">
        <strong>Nota.</strong> Los RA marcados con X son aplicables a este periodo, según el área asignada. Marque <strong>3-5 RA.</strong> Las actividades y la evaluación se alinean a los <strong>RA</strong> marcados.
    </div>

    <!-- Sección 6: Actividades prácticas esenciales -->
    <div class="section-title">6. Actividades prácticas esenciales</div>
    <ul class="activities-list">';


// Actividades según la carrera
if (carrera_es($carreraNormalizada, $carreras['ADMINISTRACION'])) {
    $html .= '
        <li><strong>A1.</strong> Apoyar en la gestión de recursos humanos, materiales y financieros.</li>
        <li><strong>A2.</strong> Participar en la planificación y organización de actividades administrativas.</li>
        <li><strong>A3.</strong> Colaborar en la elaboración de presupuestos y control de gastos.</li>
        <li><strong>A4.</strong> Apoyar en la gestión documental y archivo.</li>
        <li><strong>A5.</strong> Utilizar software administrativo para la gestión de información.</li>
        <li><strong>A6.</strong> Aplicar principios éticos y legales en la administración.</li>
        <li><strong>A7.</strong> Elaborar informes y reportes administrativos.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['EDUCACION_BASICA'])) {
    $html .= '
        <li><strong>A1.</strong> Apoyar en la planificación microcurricular conforme al currículo institucional.</li>
        <li><strong>A2.</strong> Colaborar en la ejecución de actividades de aula bajo supervisión.</li>
        <li><strong>A3.</strong> Elaborar y/o adaptar recursos didácticos y estrategias de aprendizaje.</li>
        <li><strong>A4.</strong> Apoyar en la aplicación de instrumentos de evaluación y registro de resultados.</li>
        <li><strong>A5.</strong> Participar en acciones de atención a la diversidad e inclusión.</li>
        <li><strong>A6.</strong> Registrar evidencias y avances en bitácora, y elaborar reportes breves.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['ENFERMERIA'])) {
    $html .= '
        <li><strong>A1.</strong> Apoyar en la atención básica de pacientes bajo supervisión.</li>
        <li><strong>A2.</strong> Colaborar en la promoción y prevención de la salud.</li>
        <li><strong>A3.</strong> Participar en la administración de medicamentos y tratamientos.</li>
        <li><strong>A4.</strong> Apoyar en la gestión de insumos y recursos de enfermería.</li>
        <li><strong>A5.</strong> Registrar información clínica y reportes de enfermería.</li>
        <li><strong>A6.</strong> Aplicar normas de bioseguridad y ética profesional.</li>
        <li><strong>A7.</strong> Elaborar informes y reportes de enfermería.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['MARKETING'])) {
    $html .= '
        <li><strong>A1.</strong> Apoyar en la investigación de mercados y análisis de datos.</li>
        <li><strong>A2.</strong> Colaborar en el desarrollo de campañas de marketing.</li>
        <li><strong>A3.</strong> Participar en la gestión de ventas y atención al cliente.</li>
        <li><strong>A4.</strong> Apoyar en la elaboración de materiales promocionales.</li>
        <li><strong>A5.</strong> Utilizar herramientas digitales para la promoción de productos o servicios.</li>
        <li><strong>A6.</strong> Aplicar principios éticos y legales en el marketing.</li>
        <li><strong>A7.</strong> Elaborar reportes y análisis de resultados.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['PRODUCCION_ANIMAL'])) {
    $html .= '
        <li><strong>A1.</strong> Ejecutar actividades de manejo y cuidado de animales bajo supervisión.</li>
        <li><strong>A2.</strong> Participar en labores de reproducción y manejo productivo.</li>
        <li><strong>A3.</strong> Apoyar en la aplicación de medidas preventivas de sanidad y bioseguridad.</li>
        <li><strong>A4.</strong> Colaborar en la formulación o ajuste de raciones y manejo de pastos.</li>
        <li><strong>A5.</strong> Realizar registros técnicos en formatos internos.</li>
        <li><strong>A6.</strong> Aplicar buenas prácticas pecuarias y criterios de sostenibilidad.</li>
        <li><strong>A7.</strong> Elaborar reportes simples de actividades y hallazgos.</li>
        <li><strong>A8.</strong> Aplicar normas básicas de seguridad y salud ocupacional.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['SEGURIDAD_PREVENCION'])) {
    $html .= '
        <li><strong>A1.</strong> Identificar riesgos y proponer medidas preventivas en el entorno laboral.</li>
        <li><strong>A2.</strong> Participar en la elaboración e implementación de planes de emergencia.</li>
        <li><strong>A3.</strong> Colaborar en la capacitación sobre seguridad y prevención.</li>
        <li><strong>A4.</strong> Apoyar en la gestión de incidentes y accidentes laborales.</li>
        <li><strong>A5.</strong> Elaborar reportes y registros de seguridad.</li>
        <li><strong>A6.</strong> Aplicar normativas legales de seguridad y salud en el trabajo.</li>
        <li><strong>A7.</strong> Promover la cultura preventiva en la organización.</li>';
} elseif (carrera_es($carreraNormalizada, $carreras['TOPOGRAFIA'])) {
    $html .= '
        <li><strong>A1.</strong> Apoyar en la planificación del levantamiento topográfico.</li>
        <li><strong>A2.</strong> Instalar, operar y verificar equipos topográficos.</li>
        <li><strong>A3.</strong> Ejecutar levantamientos topográficos y registrar datos.</li>
        <li><strong>A4.</strong> Participar en replanteos y documentar tolerancias.</li>
        <li><strong>A5.</strong> Descargar y procesar datos para planos.</li>
        <li><strong>A6.</strong> Elaborar/actualizar planos y productos gráficos digitales.</li>
        <li><strong>A7.</strong> Apoyar en georreferenciación e interpretación cartográfica.</li>
        <li><strong>A8.</strong> Mantener bitácora y evidencias de actividades.</li>';
} else {
    $html .= '
        <li><strong>A1.</strong> Apoyar en actividades profesionales según el área.</li>
        <li><strong>A2.</strong> Participar en procesos de mejora continua.</li>
        <li><strong>A3.</strong> Utilizar herramientas tecnológicas y de gestión.</li>
        <li><strong>A4.</strong> Aplicar principios éticos y legales en su profesión.</li>
        <li><strong>A5.</strong> Elaborar informes y reportes profesionales.</li>';
}

$html .= '
    </ul>

    <!-- Sección 7: Nota de Flexibilidad -->
    <div class="section-title">7. Nota de flexibilidad</div>
    <div class="note-box">';

if (strpos($carreraNormalizada, 'PRODUCCION') !== false || strpos($carreraNormalizada, 'PRODUCCIÓN') !== false) {
    $html .= 'Las actividades descritas en el presente plan son de carácter referencial y podrán adaptarse según la naturaleza, tamaño, especie(s) y procesos de la entidad formadora, siempre que mantengan coherencia con el perfil de egreso de la carrera de Producción Animal y cuenten con la validación del tutor académico.';
} else {
    $html .= 'Las actividades son referenciales y podrán ajustarse según el nivel, curso, asignatura y planificación de la institución receptora, manteniendo coherencia con los resultados de aprendizaje seleccionados y con validación del tutor académico y tutor institucional.';
}

$html .= '</div>

    <!-- Sección 8: Seguimiento -->
    <div class="section-title">8. Seguimiento</div>
    <ul class="activities-list">
        <li>Registro semanal en bitácora individual del estudiante.</li>
        <li>Validación del tutor empresarial.</li>
        <li>Revisión y acompañamiento del tutor académico.</li>
    </ul>

    <!-- Sección 9: Evidencias -->
    <div class="section-title">9. Evidencias</div>
    <ul class="activities-list">
        <li>Bitácora de prácticas preprofesionales.</li>
        <li>Planificación(es) y/o recursos didácticos elaborados/adaptados.</li>
        <li>Instrumentos de evaluación aplicados y registros de resultados (según corresponda).</li>
        <li>Informe final con descripción de actividades y propuesta breve de mejora.</li>
    </ul>

    <!-- Sección 10: Evaluación -->
    <div class="section-title">10. Evaluación</div>
    <div style="padding: 8px; font-size: 10px; line-height: 1.5; text-align: justify;">
        La evaluación del desempeño será integral. El Tutor Empresarial valorará cualitativamente el cumplimiento de las actividades y el comportamiento profesional mediante una rúbrica institucional. Con base en dicha rúbrica y en las evidencias presentadas, el Tutor Académico consolidará la valoración y asignará la calificación final en el sistema institucional, conforme a la normativa de evaluación estudiantil vigente en el Instituto.
    </div>

    <!-- Sección 11: Responsables -->
    <div class="section-title">11. Responsables</div>
    <table class="signatures-table">
        <thead>
            <tr>
                <th style="width: 50%;">Tutor empresarial</th>
                <th style="width: 50%;">Tutor Académico</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="width: 50%;">
                    <div style="margin-top: 50px; padding-top: 8px; border-top: 1px solid #333; display: inline-block; min-width: 70%;">
                        <strong style="font-size: 10px;">' . htmlspecialchars($nombre_tutor_empresarial) . '</strong><br>
                        <span style="font-size: 9px;">Tutor Empresarial</span>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div style="margin-top: 50px; padding-top: 8px; border-top: 1px solid #333; display: inline-block; min-width: 70%;">
                        <strong style="font-size: 10px;">' . htmlspecialchars($nombre_tutor_academico) . '</strong><br>
                        <span style="font-size: 9px;">Tutor Académico</span>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Dirección: Av. General Rumiñahui e Isla Pinta 1111, a media cuadra del San Luis Shopping</p>
        <p>Teléfono: (02) 393-0980</p>
        <p>www.superarse.edu.ec</p>
        <p style="margin-top: 8px;">Página 1 de 3</p>
    </div>
</body>
</html>
';

// Cargar HTML en Dompdf
$dompdf->loadHtml($html);

// Configurar tamaño y orientación del papel
$dompdf->setPaper('A4', 'portrait');

// Renderizar el PDF
try {
    $dompdf->render();
} catch (Exception $e) {
    error_log('Error generando PDF: ' . $e->getMessage());
    die('Error al generar el PDF. Por favor, intente nuevamente.');
}

// Generar nombre de archivo
$filename = 'Plan_Aprendizaje_' . preg_replace('/[^a-zA-Z0-9]/', '_', $apellidos_nombres) . '_' . date('Y-m-d') . '.pdf';

// En modo admin el controlador gestiona el stream; el render ya se hizo arriba.
if (!empty($adminPdfMode)) {
    return;
}

// Limpiar el buffer de salida antes de enviar el PDF
if (ob_get_level()) {
    ob_end_clean();
}

// Enviar el PDF al navegador
$dompdf->stream($filename, array('Attachment' => 1));
