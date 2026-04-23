<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('chroot', realpath(__DIR__ . '/../../'));
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);

// Cargar logo
$logoPath = __DIR__ . '/../PDFS/superarse.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
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
$ra6 = isset($_POST['ra6']) ? 'X' : '';
$ra7 = isset($_POST['ra7']) ? 'X' : '';
$ra8 = isset($_POST['ra8']) ? 'X' : '';

// Crear HTML del PDF
$html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><style>body{font-family:Arial,sans-serif;font-size:10px;line-height:1.3;padding:10px;margin:0;}table{width:100%;border-collapse:collapse;}table td,table th{border:1px solid #999;padding:5px;}table th{background:#eee;}h1,h2{margin:0;}ul{margin:0 0 10px 20px;}li{margin-bottom:5px;} .section-title{background:#f0f0f0;padding:6px;font-size:11px;font-weight:700;margin:10px 0 5px 0;border:1px solid #999;} .note-box{background:#fff9e6;border:1px solid #f0c419;padding:8px;margin:10px 0;font-size:10px;line-height:1.4;} </style></head><body>';
$html .= '<table><tr><td style="width:160px;text-align:center;">' . ($logoBase64 ? '<img src="' . $logoBase64 . '" style="width:140px;">' : 'SUPERARSE') . '</td><td style="text-align:center;"><h1>Gestión de Prácticas Pre Profesionales laborales</h1><h2>Plan de Aprendizaje Práctico</h2></td><td style="width:160px;font-size:9px;"><div><strong>VERSIÓN:</strong> 002</div><div><strong>CÓDIGO:</strong> ISTS-GIDIVS-05-004</div><div><strong>FECHA:</strong> 22/11/2025</div></td></tr></table>';
$html .= '<div class="section-title">1. Datos del estudiante:</div><table><tr><td>Apellidos y nombres</td><td>' . htmlspecialchars($apellidos_nombres) . '</td></tr><tr><td>Carrera</td><td>' . htmlspecialchars($carrera) . '</td></tr><tr><td>Nivel</td><td>' . htmlspecialchars($nivel) . '</td></tr><tr><td>Cédula</td><td>' . htmlspecialchars($cedula) . '</td></tr><tr><td>Correo electrónico</td><td>' . htmlspecialchars($correo) . '</td></tr><tr><td>Teléfono</td><td>' . htmlspecialchars($telefono) . '</td></tr></table>';
$html .= '<div class="section-title">2. Datos de la empresa:</div><table><tr><td>Nombre legal de la entidad formadora</td><td>' . htmlspecialchars($nombre_empresa) . '</td></tr><tr><td>RUC</td><td>' . htmlspecialchars($ruc) . '</td></tr><tr><td>Tipo de entidad</td><td>' . htmlspecialchars($tipo_entidad) . '</td></tr><tr><td>Actividad económica principal</td><td>' . htmlspecialchars($actividad_economica) . '</td></tr><tr><td>Ubicación</td><td>' . htmlspecialchars($ubicacion) . '</td></tr><tr><td>Área/departamento donde realizará la práctica</td><td>' . htmlspecialchars($area_departamento) . '</td></tr><tr><td>Nombre del tutor empresarial</td><td>' . htmlspecialchars($nombre_tutor_empresarial) . '</td></tr><tr><td>Teléfono</td><td>' . htmlspecialchars($telefono_tutor_empresarial) . '</td></tr><tr><td>Correo electrónico tutor</td><td>' . htmlspecialchars($correo_tutor_empresarial) . '</td></tr><tr><td>Descripción general de la empresa</td><td>' . nl2br(htmlspecialchars($descripcion_empresa)) . '</td></tr></table>';
$html .= '<div class="section-title">3. Datos del periodo de prácticas</div><table><tr><td>Periodo Académico</td><td>' . htmlspecialchars($periodo_academico) . '</td></tr><tr><td>Fecha de inicio</td><td>' . htmlspecialchars($fecha_inicio) . '</td></tr><tr><td>Fecha de fin</td><td>' . htmlspecialchars($fecha_fin) . '</td></tr><tr><td>Horario</td><td>' . htmlspecialchars($horario) . ' | <strong>Número de total de horas:</strong> ' . htmlspecialchars($total_horas) . '</td></tr><tr><td>Modalidad</td><td>' . htmlspecialchars($modalidad) . '</td></tr><tr><td>Nombre del tutor académico</td><td>' . htmlspecialchars($nombre_tutor_academico) . '</td></tr><tr><td>Correo tutor académico institucional</td><td>' . htmlspecialchars($correo_tutor_academico) . '</td></tr></table>';
$html .= '<div class="section-title">4. Objetivo de las prácticas preprofesionales</div><div class="objective-text">Aplicar los conocimientos de la carrera de Topografía en un entorno de campo y proyectos reales, mediante la ejecución de levantamientos topográficos, procesamiento de datos, elaboración de planos y apoyo en proyectos de georreferenciación, bajo supervisión, fortaleciendo competencias técnicas, éticas y de seguridad en obra, en coherencia con el perfil de egreso.</div>';
$html .= '<div class="section-title">5. Resultados de Aprendizaje</div><table class="results-table"><thead><tr><th>Selección</th><th>Resultados de Aprendizaje</th><th>Actividades relacionadas</th></tr></thead><tbody>';
$html .= '<tr><td>' . $ra1 . '</td><td><strong>R1.</strong> Realizar levantamientos y replanteos topográficos (planimétricos y altimétricos) usando instrumentos y accesorios técnicos, conforme requerimientos y normas de obra.</td><td>A2, A3, A4</td></tr>';
$html .= '<tr><td>' . $ra2 . '</td><td><strong>R2.</strong> Procesar información topográfica según la precisión requerida del proyecto, aplicando criterios de control de datos (incluyendo análisis estadístico básico a partir de datos reales).</td><td>A5, A8</td></tr>';
$html .= '<tr><td>' . $ra3 . '</td><td><strong>R3.</strong> Representar información topográfica en planos: reconocer elementos gráficos y geométricos, calcular áreas y elaborar/leer planos en AutoCAD.</td><td>A6, A5</td></tr>';
$html .= '<tr><td>' . $ra4 . '</td><td><strong>R4.</strong> Utilizar herramientas tecnológicas y software (estación total, GNSS y SIG) para representaciones gráficas digitales e interpretación de planos topográficos.</td><td>A2, A6</td></tr>';
$html .= '<tr><td>' . $ra5 . '</td><td><strong>R5.</strong> Interpretar cartas topográficas y mapas temáticos para trabajos de georreferenciación.</td><td>A7</td></tr>';
$html .= '<tr><td>' . $ra6 . '</td><td><strong>R6.</strong> Aplicar seguridad en obra mediante la elaboración/implementación de planes de contingencia con medidas de prevención y protección técnica para evitar accidentes laborales.</td><td>A1, A2</td></tr>';
$html .= '<tr><td>' . $ra7 . '</td><td><strong>R7.</strong> Elaborar informes técnicos (planta y subterránea) sobre levantamientos e información, medir y representar en plano cumpliendo especificaciones técnicas.</td><td>A7, A6, A7</td></tr>';
$html .= '<tr><td>' . $ra8 . '</td><td><strong>R8.</strong> Representar fielmente el terreno (accidentes naturales y artificiales), sus límites y extensiones, mediante análisis para procesamiento y representación gráfica.</td><td>A3, A5, A6</td></tr>';
$html .= '</tbody></table><div class="note-box"><strong>Nota.</strong> Los RA marcados con X son aplicables a este periodo, según el área asignada. Marque <strong>3-8 RA.</strong> Las actividades y la evaluación se alinean a los <strong>RA</strong> marcados.</div>';
$html .= '<div class="section-title">6. Actividades prácticas esenciales</div><ul class="activities-list"><li><strong>A1.</strong> Apoyar en la planificación del levantamiento (reconocimiento, puntos de control, rutas, checklists de equipos y EPP).</li><li><strong>A2.</strong> Instalar, operar y verificar equipos (estación total, nivel, GNSS, accesorios), realizando controles básicos de precisión.</li><li><strong>A3.</strong> Ejecutar levantamientos topográficos (poligonales, radiaciones, nivelación u otros según proyecto) y registrar datos en libretas/formatos.</li><li><strong>A4.</strong> Participar en replanteos (ejes, cotas, alineamientos, estacas/puntos), documentando tolerancias y novedades para corrección.</li><li><strong>A5.</strong> Descargar, depurar y procesar datos (coordenadas, cotas, ajustes) y generar insumos técnicos para planos.</li><li><strong>A6.</strong> Elaborar/actualizar planos y productos gráficos (plantas, perfiles, secciones, curvas de nivel) en formato digital (CAD/SIG según aplique).</li><li><strong>A7.</strong> Apoyar en georreferenciación e interpretación cartográfica para ubicar elementos del proyecto y contrastar información.</li><li><strong>A8.</strong> Mantener bitácora y evidencias (archivos, capturas, reportes breves) y comunicar avances/incidencias al tutor empresarial y académico.</li></ul>';
$html .= '<div class="section-title">7. Nota de flexibilidad</div><div class="note-box">Las actividades del presente plan son referenciales y podrán ajustarse según la naturaleza y procesos de la entidad formadora, manteniendo coherencia con el perfil de egreso y los RA seleccionados, con validación del tutor académico.</div>';
$html .= '<div class="section-title">8. Seguimiento</div><ul class="activities-list"><li>Registro semanal en bitácora individual del estudiante.</li><li>Validación del tutor empresarial.</li><li>Revisión y acompañamiento del tutor académico.</li></ul>';
$html .= '<div class="section-title">9. Evidencias</div><ul class="activities-list"><li>Bitácora de prácticas preprofesionales.</li><li>Planificación(es) y/o recursos didácticos elaborados/adaptados.</li><li>Instrumentos de evaluación aplicados y registros de resultados (según corresponda).</li><li>Informe final con descripción de actividades y propuesta breve de mejora.</li></ul>';
$html .= '</body></html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('plan_aprendizaje_topografia.pdf', ['Attachment' => false]);
