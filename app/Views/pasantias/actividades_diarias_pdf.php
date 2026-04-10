<?php
// Preparar logo para Dompdf
$logoPath = __DIR__ . '/../../../public/Assets/img/LOGO SUPERARSE PNG-02.png';
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoSrc = 'file:///' . str_replace('\\', '/', realpath($logoPath));
}

$logoHeaderHtml = '<div class="logo-text">SUPERARSE</div><div class="logo-subtext">Tecnológico de Formación Superior</div>';
if (!empty($logoSrc)) {
    $safeLogoSrc = htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8');
    $logoHeaderHtml = '<img src="' . $safeLogoSrc . '" alt="Logo Superarse" style="max-height: 80px; margin-bottom: 5px;">';
}

if (!function_exists('format_decimal_hours_hm_pdf')) {
    function format_decimal_hours_hm_pdf($decimalHours): string
    {
        $totalMinutes = (int) round(((float) $decimalHours) * 60);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return $hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Actividades Diarias</title>
    
    <?php
    // NOTA: Los estilos son generados por PHP para mantener el código limpio
    // Se inyectan inline porque DomPDF requiere estilos embebidos en el HTML
    include __DIR__ . '/pdf-styles-actividades.php';
    ?>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    <?php echo $logoHeaderHtml; ?>
                </td>
                <td class="title-cell">
                    <p class="title-text">Gestión de prácticas pre profesionales<br>laborales</p>
                    <p class="title-text" style="margin-top: 8px; font-size: 10pt;">Control de Avances de Actividades</p>
                </td>
                <td class="info-cell">
                    <p><strong>VERSION:</strong><br>002</p>
                    <p><strong>CODIGO:</strong><br>ISTS-GIDIVS-05-005</p>
                    <p><strong>FECHA:</strong><br>22/11/2025</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="info-estudiante">
        <p><strong>Estudiante:</strong> <?php 
            $nombreCompleto = trim(
                ($estudiante['primer_nombre'] ?? '') . ' ' . 
                ($estudiante['segundo_nombre'] ?? '') . ' ' . 
                ($estudiante['primer_apellido'] ?? '') . ' ' . 
                ($estudiante['segundo_apellido'] ?? '')
            );
            echo htmlspecialchars($nombreCompleto ?: 'N/A');
        ?></p>
        <p><strong>Cédula:</strong> <?php echo htmlspecialchars($estudiante['numero_identificacion'] ?? 'N/A'); ?></p>
        <p><strong>Programa:</strong> <?php echo htmlspecialchars($estudiante['programa'] ?? 'N/A'); ?></p>
        <p><strong>Entidad:</strong> <?php echo htmlspecialchars($practica['entidad_nombre_empresa'] ?? 'N/A'); ?></p>
    </div>

    <?php 
    $totalHorasGeneral = 0;
    $totalSemanas = count($actividadesPorSemana);
    ?>

    <?php foreach ($actividadesPorSemana as $clave => $semanaData): ?>
        <div class="semana-container">
            <div class="semana-header">
                <h3>Semana <?php echo $semanaData['semana']; ?> - <?php echo $semanaData['anio']; ?></h3>
                <p>
                    Del <?php echo date('d/m/Y', strtotime($semanaData['fecha_inicio'])); ?> 
                    al <?php echo date('d/m/Y', strtotime($semanaData['fecha_fin'])); ?>
                </p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 45%;">Actividad Realizada</th>
                        <th style="width: 12%;">Hora Inicio</th>
                        <th style="width: 12%;">Hora Fin</th>
                        <th style="width: 16%; text-align: center;">Horas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($semanaData['actividades'] as $actividad): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($actividad['fecha_actividad'])); ?></td>
                            <td><?php echo htmlspecialchars($actividad['actividad_realizada']); ?></td>
                            <td><?php echo htmlspecialchars($actividad['hora_inicio']); ?></td>
                            <td><?php echo htmlspecialchars($actividad['hora_fin']); ?></td>
                            <td style="text-align: center;"><?php echo number_format($actividad['horas_invertidas'], 2); ?>h (<?php echo htmlspecialchars(format_decimal_hours_hm_pdf($actividad['horas_invertidas']), ENT_QUOTES, 'UTF-8'); ?>)</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-horas">
                Total de horas esta semana: <?php echo number_format($semanaData['total_horas'], 2); ?> horas (<?php echo htmlspecialchars(format_decimal_hours_hm_pdf($semanaData['total_horas']), ENT_QUOTES, 'UTF-8'); ?>)
            </div>
        </div>
        <?php $totalHorasGeneral += $semanaData['total_horas']; ?>
    <?php endforeach; ?>

    <?php if (empty($actividadesPorSemana)): ?>
        <div style="text-align: center; padding: 40px; color: #999;">
            <p style="font-size: 12pt;">No hay actividades registradas aún.</p>
        </div>
    <?php endif; ?>

    <div class="firmas-container">
        <h3 style="text-align: center; color: #1E40AF; margin-bottom: 30px;">APROBADO POR</h3>
        
        <div class="firmas-grid">
            <div class="firma-box">
                <div class="firma-line"></div>
                <div class="firma-label">Tutor Empresarial</div>
                <div class="firma-info">
                    <?php if (!empty($practica['tutor_emp_nombre_completo'])): ?>
                        <?php echo htmlspecialchars($practica['tutor_emp_nombre_completo']); ?>
                    <?php else: ?>
                        Nombre y firma
                    <?php endif; ?>
                </div>
            </div>

            <div class="firma-box">
                <div class="firma-line"></div>
                <div class="firma-label">Tutor Académico</div>
                <div class="firma-info">
                    <?php if (!empty($tutorAcademico['nombre_completo'])): ?>
                        <?php echo htmlspecialchars($tutorAcademico['nombre_completo']); ?>
                    <?php else: ?>
                        Nombre y firma
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 9pt; color: #666;">
        <p style="margin: 3px 0;"><strong>Dirección:</strong> Av. General Rumiñahui e Isla Pinta 1111, a media cuadra del San Luis Shopping</p>
        <p style="margin: 3px 0;"><strong>Teléfono:</strong> (02) 393-0980</p>
        <p style="margin: 3px 0;"><strong>Web:</strong> www.superarse.edu.ec</p>
    </div>
</body>
</html>
