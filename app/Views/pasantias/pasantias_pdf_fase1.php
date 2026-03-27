<?php
$logoPath = __DIR__ . '/../../../public/Assets/img/LOGO SUPERARSE PNG-02.png';
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoSrc = 'file:///' . str_replace('\\', '/', realpath($logoPath));
}

$logoHeaderHtml = '<div class="logo-text">SUPERARSE</div><div class="logo-subtext">Tecnológico de Formación Superior</div>';
if (!empty($logoSrc)) {
    $safeLogoSrc = htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8');
    $logoHeaderHtml = '<img src="' . $safeLogoSrc . '" alt="Superarse Tecnológico" style="width: 200px; height: auto;">';
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Registro de Práctica Pre-Profesional (Fase 1)</title>
    
    <?php
    // NOTA: Los estilos son generados por PHP para mantener el código limpio
    // Se inyectan inline porque DomPDF requiere estilos embebidos en el HTML
    include __DIR__ . '/pdf-styles-pasantias.php';
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
                    <p class="title-text" style="margin-top: 8px; font-size: 10pt;">Solicitud de prácticas<br>preprofesionales</p>
                </td>
                <td class="info-cell">
                    <p><strong>VERSION:</strong><br>002</p>
                    <p><strong>CODIGO:</strong><br>ISTS-GIDIVS-05-002</p>
                    <p><strong>FECHA:</strong><br>22/11/2025</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3 class="section-title">1. Información del Estudiante</h3>

        <div class="data-row">
            <span class="data-label">Nombre Completo:</span>
            <span class="data-value"><?php echo htmlspecialchars($nombreCompleto ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Código Matrícula:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPersonal['codigo_matricula'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Identificación (Cédula):</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPersonal['numero_identificacion'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Programa/Carrera:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPersonal['programa'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Nivel:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPersonal['nivel'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Nivel:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPersonal['periodo'] ?? 'N/D'); ?></span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section">
        <h3 class="section-title">2. Detalles de la Práctica y Estado</h3>

        <div class="data-row">
            <span class="data-label">Modalidad:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['modalidad'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Afiliación IESS:</span>
            <span class="data-value"><?php echo htmlspecialchars(($infoPractica['afiliacion_iess'] == 1 ? 'Sí' : 'No') ?? 'N/D'); ?></span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section">
        <h3 class="section-title">3. Información de la Entidad/Empresa</h3>

        <div class="data-row">
            <span class="data-label">Razón Social:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['razon_social'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">RUC:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['ruc'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Dirección:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['direccion'] ?? 'N/D'); ?></span>
        </div>

        <br>
        <h4 style="font-size: 11pt; color: #5B21B6; margin-top: 0; border-bottom: 1px dotted #ccc; padding-bottom: 3px;">Contacto Empresarial</h4>
        <div class="data-row">
            <span class="data-label">Persona de Contacto:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['persona_contacto'] ?? 'N/D'); ?></span>
        </div>
        <div class="data-row">
            <span class="data-label">Email:</span>
            <span class="data-value"><?php echo htmlspecialchars($infoPractica['email_contacto'] ?? 'N/D'); ?></span>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section">
        <h3 class="section-title">4. Tutor Empresarial Asignado</h3>
        <?php
        // Asumimos que tutoresEmpresariales es un array que contiene al menos un elemento
        $tutorEmp = $tutoresEmpresariales[0] ?? null;
        ?>

        <?php if ($tutorEmp && !empty($tutorEmp['nombre_completo'])): ?>
            <div class="data-row">
                <span class="data-label">Nombre Completo:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['nombre_completo'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Cédula:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['cedula'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Función/Cargo:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['funcion'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Departamento:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['departamento'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Email:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['email'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Teléfono:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorEmp['telefono'] ?? 'N/D'); ?></span>
            </div>
        <?php else: ?>
            <p>No se ha asignado un Tutor Empresarial para esta práctica.</p>
        <?php endif; ?>
        <div class="clear"></div>
    </div>

    <div class="section">
        <h3 class="section-title">5. Tutor Académico (Docente) Asignado</h3>
        <?php
        // Asumimos que tutoresAcademicos es un array que contiene al menos un elemento
        $tutorAcademico = $tutoresAcademicos[0] ?? null;
        ?>

        <?php if ($tutorAcademico && !empty($tutorAcademico['nombre_completo'])): ?>
            <div class="data-row">
                <span class="data-label">Nombre Completo:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorAcademico['nombre_completo'] ?? 'N/D'); ?></span>
            </div>
            <div class="data-row">
                <span class="data-label">Email:</span>
                <span class="data-value"><?php echo htmlspecialchars($tutorAcademico['email'] ?? 'N/D'); ?></span>
            </div>
            <?php
            // Mostramos el teléfono solo si lo lograste incluir en la consulta del modelo
            if (!empty($tutorAcademico['telefono'])): ?>
                <div class="data-row">
                    <span class="data-label">Teléfono:</span>
                    <span class="data-value"><?php echo htmlspecialchars($tutorAcademico['telefono'] ?? 'N/D'); ?></span>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>No se ha asignado un Tutor Académico para esta práctica.</p>
        <?php endif; ?>
        <div class="clear"></div>
    </div>

    <div class="signature-area">
        <p style="margin-bottom: 50px;">Registro Aprobado y Formalizado</p>
        
        <table class="signature-table">
            <tr>
                <td style="border-top: 1px solid #000; padding-top: 5px;">
                    Estudiante: <?php echo htmlspecialchars($nombreCompleto ?? 'N/D'); ?>
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ccc; font-size: 9pt; color: #666;">
        <p style="margin: 3px 0;"><strong>Dirección:</strong> Av. General Rumiñahui e Isla Pinta 1111, a media cuadra del San Luis Shopping</p>
        <p style="margin: 3px 0;"><strong>Teléfono:</strong> (02) 393-0980</p>
        <p style="margin: 3px 0;"><strong>Web:</strong> www.superarse.edu.ec</p>
    </div>

</body>

</html>