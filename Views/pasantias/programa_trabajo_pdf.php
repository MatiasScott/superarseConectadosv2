<?php
$logoPath = __DIR__ . '/../../../public/Assets/img/LOGO SUPERARSE PNG-02.png';
$logoSrc = '';
if (file_exists($logoPath)) {
    $logoSrc = 'file:///' . str_replace('\\', '/', realpath($logoPath));
}

$nombreEstudiante = trim((string)($practica['estudiante_nombre'] ?? 'N/D'));
$codigoMatricula = (string)($practica['codigo_matricula'] ?? 'N/D');
$identificacion = (string)($practica['numero_identificacion'] ?? 'N/D');
$carrera = (string)($practica['programa'] ?? 'N/D');
$empresa = (string)($practica['entidad_nombre_empresa'] ?? $practica['nombre_empresa'] ?? 'N/D');
$ruc = (string)($practica['ruc'] ?? 'N/D');
$modalidad = (string)($practica['modalidad'] ?? 'N/D');
$docente = (string)($practica['docente_nombre'] ?? 'N/D');
$fechaEmision = (string)($fechaEmision ?? date('d/m/Y H:i'));
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Plan de Aprendizaje</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 16px;
        }

        .header-table,
        .data-table,
        .plan-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 1px solid #cbd5e1;
            vertical-align: middle;
            padding: 8px;
        }

        .logo-cell {
            width: 34%;
            text-align: center;
        }

        .title-cell {
            width: 42%;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .meta-cell {
            width: 24%;
            font-size: 10px;
            line-height: 1.4;
        }

        .section-title {
            margin-top: 14px;
            margin-bottom: 6px;
            padding: 6px 8px;
            font-size: 12px;
            font-weight: bold;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #312e81;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        .data-label {
            width: 30%;
            font-weight: bold;
            background: #f9fafb;
        }

        .plan-table th,
        .plan-table td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            vertical-align: top;
        }

        .plan-table th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #374151;
        }

        .muted {
            color: #6b7280;
            font-size: 10px;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <?php if (!empty($logoSrc)): ?>
                    <img src="<?php echo htmlspecialchars($logoSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo" style="width: 180px; height: auto;">
                <?php else: ?>
                    SUPERARSE
                <?php endif; ?>
            </td>
            <td class="title-cell">
                Gestión de Prácticas Pre Profesionales<br>
                Plan de Aprendizaje
            </td>
            <td class="meta-cell">
                <strong>Versión:</strong> 002<br>
                <strong>Código:</strong> ISTS-GIDIVS-05-004<br>
                <strong>Fecha emisión:</strong> <?php echo htmlspecialchars($fechaEmision, ENT_QUOTES, 'UTF-8'); ?>
            </td>
        </tr>
    </table>

    <div class="section-title">1. Datos de Practicas Pre Profesionales</div>
    <table class="data-table">
        <tr>
            <td class="data-label">Estudiante</td>
            <td><?php echo htmlspecialchars($nombreEstudiante, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Código matrícula</td>
            <td><?php echo htmlspecialchars($codigoMatricula, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Identificación</td>
            <td><?php echo htmlspecialchars($identificacion, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Carrera</td>
            <td><?php echo htmlspecialchars($carrera, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Empresa</td>
            <td><?php echo htmlspecialchars($empresa, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">RUC</td>
            <td><?php echo htmlspecialchars($ruc, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Modalidad</td>
            <td><?php echo htmlspecialchars($modalidad, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="data-label">Tutor académico</td>
            <td><?php echo htmlspecialchars($docente, ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </table>

    <div class="section-title">2. Actividades del plan de aprendizaje</div>
    <table class="plan-table">
        <thead>
            <tr>
                <th style="width: 7%;">#</th>
                <th style="width: 48%;">Actividad planificada</th>
                <th style="width: 20%;">Departamento / Área</th>
                <th style="width: 15%;">Función asignada</th>
                <th style="width: 10%;">Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($programaTrabajo ?? []) as $idx => $item): ?>
                <?php
                $actividad = (string)($item['actividad_planificada'] ?? $item['nombre_actividad'] ?? 'N/D');
                $departamento = (string)($item['departamento_area'] ?? 'N/D');
                $funcion = (string)($item['funcion_asignada'] ?? 'N/D');
                $fechaRaw = (string)($item['fecha_planificada'] ?? $item['fecha_actividad'] ?? $item['fecha_inicio'] ?? '');
                $fecha = 'N/D';
                if (!empty($fechaRaw)) {
                    $ts = strtotime($fechaRaw);
                    if ($ts !== false) {
                        $fecha = date('d/m/Y', $ts);
                    }
                }
                ?>
                <tr>
                    <td style="text-align: center;"><?php echo (int)$idx + 1; ?></td>
                    <td><?php echo htmlspecialchars($actividad, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($departamento, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($funcion, ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p class="muted">Documento generado desde el modulo administrativo de edicion de Practicas Pre Profesionales.</p>
</body>

</html>