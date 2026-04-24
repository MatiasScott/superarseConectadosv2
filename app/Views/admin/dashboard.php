<?php
$resumen = $resumen ?? [];
$alertas = $alertas ?? [];
$recientes = $recientes ?? [];
$porMes = $porMes ?? [];
$topEmpresas = $topEmpresas ?? [];
$porCarrera = $porCarrera ?? [];
$porModalidad = $porModalidad ?? [];
$resumenInstitucional = $resumenInstitucional ?? [];

$cumplimiento = (float)($resumen['cumplimiento'] ?? 0);
$cumplimientoClass = $cumplimiento >= 80 ? 'ok' : ($cumplimiento >= 50 ? 'warn' : 'bad');
?>

<section class="dash-hero">
    <h2>Tablero Gerencial de Practicas</h2>
    <p>Vision ejecutiva para seguimiento academico, operativo y de cumplimiento institucional.</p>
</section>

<section class="table-wrap" style="margin-bottom: 16px;">
    <h3>Resumen Institucional Integrado</h3>
    <div class="kpi-grid" style="margin-bottom: 0;">
        <article class="kpi-card">
            <div class="kpi-label">Vinculacion activa</div>
            <div class="kpi-value"><?= (int)($resumenInstitucional['vinculacion_activos'] ?? 0); ?></div>
            <div class="kpi-help">Proyectos activos en modulo Vinculacion</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Investigacion activa</div>
            <div class="kpi-value"><?= (int)($resumenInstitucional['investigacion_activos'] ?? 0); ?></div>
            <div class="kpi-help">Proyectos activos en modulo Investigacion</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Publicaciones</div>
            <div class="kpi-value"><?= (int)($resumenInstitucional['publicaciones_total'] ?? 0); ?></div>
            <div class="kpi-help">Registros totales publicados</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Ponencias</div>
            <div class="kpi-value"><?= (int)($resumenInstitucional['ponencias_total'] ?? 0); ?></div>
            <div class="kpi-help">Eventos y ponencias registradas</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Convenios (total)</div>
            <div class="kpi-value"><?= (int)($resumenInstitucional['convenios_total'] ?? 0); ?></div>
            <div class="kpi-help">Convenios acumulados</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Convenios activos</div>
            <div class="kpi-value ok"><?= (int)($resumenInstitucional['convenios_activos'] ?? 0); ?></div>
            <div class="kpi-help">Estado Activo en el modulo Convenios</div>
        </article>

        <article class="kpi-card">
            <div class="kpi-label">Convenios por vencer (30 dias)</div>
            <div class="kpi-value warn"><?= (int)($resumenInstitucional['convenios_por_vencer_30_dias'] ?? 0); ?></div>
            <div class="kpi-help">Riesgo de continuidad institucional</div>
        </article>
    </div>
</section>

<section class="kpi-grid">
    <article class="kpi-card">
        <div class="kpi-label">Practicas activas</div>
        <div class="kpi-value"><?= (int)($resumen['total'] ?? 0); ?></div>
        <div class="kpi-help">Registros con estado ACTIVA</div>
    </article>

    <article class="kpi-card">
        <div class="kpi-label">Cumplimiento global</div>
        <div class="kpi-value <?= $cumplimientoClass; ?>"><?= number_format($cumplimiento, 1); ?>%</div>
        <div class="kpi-help">Activas / Total de todas las practicas</div>
    </article>

    <article class="kpi-card">
        <div class="kpi-label">Carreras activas</div>
        <div class="kpi-value"><?= (int)($resumen['carreras_activas'] ?? 0); ?></div>
        <div class="kpi-help">Programas con participacion</div>
    </article>
</section>

<section class="admin-panels">
    <article class="panel">
        <h3>Tendencia de registros (12 meses)</h3>
        <canvas id="chartPorMes"></canvas>
    </article>

    <article class="panel">
        <h3>Alertas operativas</h3>
        <div class="alert-list">
            <?php
            $pendientesAntiguos = (int)($alertas['pendientes_mayores_15_dias'] ?? 0);
            $sinActividades = (int)($alertas['completadas_sin_actividades'] ?? 0);
            ?>

            <div class="alert-box <?= $pendientesAntiguos > 0 ? 'danger' : 'ok'; ?>">
                <strong>Pendientes mayores a 15 dias</strong>
                <span><?= $pendientesAntiguos; ?> practicas</span>
            </div>

            <div class="alert-box <?= $sinActividades > 0 ? 'warning' : 'ok'; ?>">
                <strong>Completadas sin actividades registradas</strong>
                <span><?= $sinActividades; ?> practicas</span>
            </div>
        </div>
    </article>
</section>

<section class="admin-panels">
    <article class="panel">
        <h3>Top empresas por volumen</h3>
        <canvas id="chartEmpresas"></canvas>
    </article>

    <article class="panel">
        <h3>Distribucion por carrera</h3>
        <canvas id="chartCarrera"></canvas>
    </article>
</section>

<section class="table-wrap" style="margin-bottom: 16px;">
    <h3>Distribucion por modalidad</h3>
    <table class="dashboard-table" style="min-width: 420px;">
        <thead>
            <tr>
                <th>Modalidad</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($porModalidad)): ?>
                <?php foreach ($porModalidad as $fila): ?>
                    <tr>
                        <td><?= htmlspecialchars($fila['etiqueta'] ?? 'Sin modalidad'); ?></td>
                        <td><?= (int)($fila['total'] ?? 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">Sin datos de modalidad.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<section class="table-wrap">
    <h3>Practicas recientes</h3>
    <table class="dashboard-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Estudiante</th>
                <th>Carrera</th>
                <th>Empresa</th>
                <th>Modalidad</th>
                <th>Estado</th>
                <th>Fecha registro</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($recientes)): ?>
                <?php foreach ($recientes as $r): ?>
                    <?php $ok = (int)($r['estado_fase_uno_completado'] ?? 0) === 1; ?>
                    <tr>
                        <td>#<?= (int)($r['id_practica'] ?? 0); ?></td>
                        <td><?= htmlspecialchars(trim((string)($r['estudiante'] ?? 'N/D')) ?: 'N/D'); ?></td>
                        <td><?= htmlspecialchars($r['programa'] ?? 'N/D'); ?></td>
                        <td><?= htmlspecialchars($r['empresa'] ?? 'N/D'); ?></td>
                        <td><?= htmlspecialchars($r['modalidad'] ?? 'N/D'); ?></td>
                        <td>
                            <span class="badge <?= $ok ? 'ok' : 'pending'; ?>">
                                <?= $ok ? 'Completada' : 'Pendiente'; ?>
                            </span>
                        </td>
                        <td><?= !empty($r['fecha_registro']) ? date('d/m/Y H:i', strtotime($r['fecha_registro'])) : 'N/D'; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">Sin registros recientes.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<script>
    window.ADMIN_DASHBOARD_DATA = {
        porMes: <?= json_encode($porMes, JSON_UNESCAPED_UNICODE); ?>,
        topEmpresas: <?= json_encode($topEmpresas, JSON_UNESCAPED_UNICODE); ?>,
        porCarrera: <?= json_encode($porCarrera, JSON_UNESCAPED_UNICODE); ?>
    };
</script>