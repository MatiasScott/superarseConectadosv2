<h2 class="text-2xl font-bold mb-6">Planificación Estratégica</h2>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
        <?= htmlspecialchars((string) $_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        <?= htmlspecialchars((string) $_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php
$selectedPoaProcesos = array_map('intval', (array) ($selectedPoa['procesos_ids'] ?? []));
$presupuestoUsadoSeleccionado = 0.0;
foreach (($selectedPoaActividades ?? []) as $actividadSel) {
    $presupuestoUsadoSeleccionado += (float) ($actividadSel['presupuesto_asignado'] ?? 0);
}
$presupuestoTotalSeleccionado = (float) ($selectedPoa['presupuesto_total_aprobado'] ?? 0);
$presupuestoDisponibleSeleccionado = max(0, $presupuestoTotalSeleccionado - $presupuestoUsadoSeleccionado);
?>

<div class="space-y-8">
    <section class="bg-white shadow-lg rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-1">Paso 1: Cabecera POA</h3>
        <p class="text-sm text-gray-500 mb-5">Seleccione estrategia, sede, año, procesos responsables y presupuesto total aprobado.</p>

        <form method="POST" action="<?= $basePath . ($selectedPoa ? '/admin/poa/update' : '/admin/poa/store') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if ($selectedPoa): ?>
                <input type="hidden" name="id" value="<?= (int) ($selectedPoa['id'] ?? 0) ?>">
            <?php endif; ?>

            <label class="block">
                <span class="text-sm text-gray-700 font-medium">Estrategia PEDI</span>
                <select name="estrategia_id" required class="w-full mt-1 border rounded-lg px-3 py-2">
                    <option value="">Seleccione...</option>
                    <?php foreach (($estrategias ?? []) as $estrategia): ?>
                        <?php $idEstrategia = (int) ($estrategia['id'] ?? 0); ?>
                        <option value="<?= $idEstrategia ?>" <?= ((int) ($selectedPoa['estrategia_id'] ?? 0) === $idEstrategia) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) (($estrategia['codigo'] ?? '') . ' - ' . ($estrategia['nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="text-sm text-gray-700 font-medium">Sede</span>
                <select name="sede_id" required class="w-full mt-1 border rounded-lg px-3 py-2">
                    <option value="">Seleccione...</option>
                    <?php foreach (($sedes ?? []) as $sede): ?>
                        <?php $idSede = (int) ($sede['id'] ?? 0); ?>
                        <option value="<?= $idSede ?>" <?= ((int) ($selectedPoa['sede_id'] ?? 0) === $idSede) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($sede['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="block">
                <span class="text-sm text-gray-700 font-medium">Año de planificación</span>
                <input type="number" name="anio_planificacion" min="2020" max="2100" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= (int) ($selectedPoa['anio_planificacion'] ?? date('Y')) ?>">
            </label>

            <label class="block">
                <span class="text-sm text-gray-700 font-medium">Presupuesto total aprobado</span>
                <input type="number" step="0.01" min="0" name="presupuesto_total_aprobado" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($selectedPoa['presupuesto_total_aprobado'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm text-gray-700 font-medium">Areas / Procesos responsables</span>
                <select name="procesos_ids[]" multiple required class="w-full mt-1 border rounded-lg px-3 py-2 h-40">
                    <?php foreach (($procesos ?? []) as $proceso): ?>
                        <?php $idProceso = (int) ($proceso['id'] ?? 0); ?>
                        <option value="<?= $idProceso ?>" <?= in_array($idProceso, $selectedPoaProcesos, true) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($proceso['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-xs text-gray-500 mt-1 block">Use Ctrl/Cmd + clic para seleccionar múltiples procesos.</span>
            </label>

            <label class="block">
                <span class="text-sm text-gray-700 font-medium">Estado de aprobación</span>
                <?php $estadoAprobacion = (string) ($selectedPoa['estado_aprobacion'] ?? 'borrador'); ?>
                <select name="estado_aprobacion" class="w-full mt-1 border rounded-lg px-3 py-2">
                    <option value="borrador" <?= $estadoAprobacion === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                    <option value="aprobado" <?= $estadoAprobacion === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                    <option value="cerrado" <?= $estadoAprobacion === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                </select>
            </label>

            <label class="block md:col-span-2">
                <span class="text-sm text-gray-700 font-medium">Observaciones</span>
                <textarea name="observaciones" rows="3" class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($selectedPoa['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </label>

            <div class="md:col-span-2 flex flex-wrap gap-3">
                <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">
                    <?= $selectedPoa ? 'Actualizar cabecera POA' : 'Crear cabecera POA' ?>
                </button>
                <?php if ($selectedPoa): ?>
                    <a href="<?= $basePath ?>/admin/plan-estrategico" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Nueva cabecera</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="bg-white shadow-lg rounded-2xl p-6">
        <h3 class="text-xl font-bold mb-4">Cabeceras POA registradas</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Estrategia</th>
                        <th class="px-4 py-3">Sede</th>
                        <th class="px-4 py-3">Año</th>
                        <th class="px-4 py-3">Procesos</th>
                        <th class="px-4 py-3">Presupuesto</th>
                        <th class="px-4 py-3">Asignado</th>
                        <th class="px-4 py-3">Disponible</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!empty($poa)): ?>
                        <?php foreach ($poa as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3"><?= (int) ($item['id'] ?? 0) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) (($item['estrategia_codigo'] ?? '') . ' - ' . ($item['estrategia_nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($item['sede_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3"><?= (int) ($item['anio_planificacion'] ?? 0) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($item['procesos_nombres'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 py-3">$<?= number_format((float) ($item['presupuesto_total_aprobado'] ?? 0), 2) ?></td>
                                <td class="px-4 py-3">$<?= number_format((float) ($item['presupuesto_asignado'] ?? 0), 2) ?></td>
                                <td class="px-4 py-3">$<?= number_format((float) ($item['presupuesto_disponible'] ?? 0), 2) ?></td>
                                <td class="px-4 py-3 text-center space-x-2">
                                    <a href="<?= $basePath ?>/admin/plan-estrategico?poa=<?= (int) ($item['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Gestionar</a>
                                    <form action="<?= $basePath ?>/admin/poa/eliminar/<?= (int) ($item['id'] ?? 0) ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta cabecera POA con sus actividades?');">
                                        <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-4 py-6 text-center text-gray-400">No hay cabeceras POA registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($selectedPoa):
        $meses = ['ene_pct','feb_pct','mar_pct','abr_pct','may_pct','jun_pct','jul_pct','ago_pct','sep_pct','oct_pct','nov_pct','dic_pct'];
        $mesesLabel = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    ?>
        <section class="bg-white shadow-lg rounded-2xl p-6">
            <h3 class="text-xl font-bold mb-1">Paso 2: Actividades y Proyectos Asociados</h3>
            <p class="text-sm text-gray-500 mb-4">POA #<?= (int) ($selectedPoa['id'] ?? 0) ?> | Presupuesto total: $<?= number_format($presupuestoTotalSeleccionado, 2) ?> | Disponible: $<span><?= number_format($presupuestoDisponibleSeleccionado, 2) ?></span></p>

            <form id="formActividad" method="POST" action="<?= $basePath ?>/admin/actividad/store" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="hidden" name="poa_id" value="<?= (int) ($selectedPoa['id'] ?? 0) ?>">
                <input type="hidden" id="presupuestoDisponibleValor" value="<?= htmlspecialchars((string) $presupuestoDisponibleSeleccionado, ENT_QUOTES, 'UTF-8') ?>">

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Tipo</span>
                    <select name="tipo_registro" required class="w-full mt-1 border rounded-lg px-3 py-2">
                        <option value="Actividad">Actividad</option>
                        <option value="Proyecto">Proyecto</option>
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Nombre</span>
                    <input type="text" name="nombre" required class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-gray-700 font-medium">Descripción</span>
                    <textarea name="descripcion" rows="2" class="w-full mt-1 border rounded-lg px-3 py-2"></textarea>
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Laboratorio</span>
                    <input type="text" name="laboratorio" class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Meta específica</span>
                    <input type="text" name="meta" required class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Presupuesto asignado</span>
                    <input id="presupuestoAsignado" type="number" step="0.01" min="0" name="presupuesto_asignado" required class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Presupuesto ejecutado</span>
                    <input type="number" step="0.01" min="0" name="presupuesto_ejecutado" value="0" class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block">
                    <span class="text-sm text-gray-700 font-medium">Avance (%)</span>
                    <input type="number" step="0.01" min="0" max="100" name="avance_actividad" value="0" class="w-full mt-1 border rounded-lg px-3 py-2">
                </label>

                <label class="block md:col-span-2">
                    <span class="text-sm text-gray-700 font-medium">Observaciones</span>
                    <textarea name="observaciones" rows="2" class="w-full mt-1 border rounded-lg px-3 py-2"></textarea>
                </label>

                <div class="md:col-span-2">
                    <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">Agregar Actividad/Proyecto</button>
                </div>
            </form>

            <div class="mt-6 overflow-x-auto">
                <div class="relative mb-4">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" id="buscadorPoa" placeholder="Buscar por actividad, eje, objetivo..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-500 transition">
                </div>

                <table id="tablaPoa" class="min-w-full text-sm" style="table-layout:fixed;border-collapse:collapse;">
                    <colgroup>
                        <col span="8">
                        <?php foreach ($mesesLabel as $ml): ?>
                        <col style="width:40px;">
                        <?php endforeach; ?>
                        <col span="10">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Eje Estratégico (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Objetivo Estratégico (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Estrategia (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Nombre del Proyecto/Actividad</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Descripción</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Meta (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Sede</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Laboratorio</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" colspan="<?= count($mesesLabel) ?>">Cronograma</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Avance Planificado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Avance Ejecutado (%)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Observación de Avance</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Presupuesto Planificado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Presupuesto Ejecutado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Ejecución Presupuestaria (%)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">PROCESOS</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Observaciones</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Estado</th>
                            <th class="px-2 py-3 text-center" rowspan="2">Acciones</th>
                        </tr>
                        <tr class="bg-gray-50 text-gray-500 text-xs">
                            <?php foreach ($mesesLabel as $ml): ?>
                            <th class="px-1 py-2 text-center font-semibold border-r border-gray-200"><?= $ml ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($selectedPoaActividades)): ?>
                            <?php foreach ($selectedPoaActividades as $p): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <?= htmlspecialchars($p['eje'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 max-w-[160px] truncate border-r border-gray-100 text-gray-800" title="<?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>">
                                        <?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 max-w-[140px] truncate border-r border-gray-100 text-gray-600" title="<?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>">
                                        <?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 font-medium border-r border-gray-100 max-w-[180px] truncate" title="<?= htmlspecialchars($p['nombre_actividad'] ?? '') ?>">
                                        <?= htmlspecialchars($p['nombre_actividad'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 max-w-[160px] truncate border-r border-gray-100 text-gray-500" title="<?= htmlspecialchars($p['observacion_actividad'] ?? '') ?>">
                                        <?= htmlspecialchars($p['observacion_actividad'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <?php
                                        $metaPedi = $p['meta_pedi'] ?? '';
                                        $pctPedi = $p['meta_pedi_pct'] ?? null;
                                        $hasMeta = ($metaPedi !== '');
                                        $hasPct = ($pctPedi !== null && $pctPedi !== '' && (float)$pctPedi > 0);
                                        $fallbackPct = false;
                                        if (!$hasPct && $hasMeta && is_numeric($metaPedi)) {
                                            $num = (float)$metaPedi;
                                            $hasPct = ($num > 0);
                                            $fallbackPct = true;
                                        } else {
                                            $num = $hasPct ? (float)$pctPedi : 0;
                                        }
                                        if ($hasMeta || $hasPct):
                                            $display = $hasMeta ? htmlspecialchars($metaPedi) : '-';
                                            $color = $num >= 70 ? 'bg-green-100 text-green-800' : ($num >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $hasPct ? $color : 'bg-gray-100 text-gray-500' ?>">
                                            <?= $display ?>
                                            <?php if ($hasPct && !$fallbackPct): ?> (<?= $num ?>%)<?php endif; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-500"><?= htmlspecialchars($p['sede'] ?? ($p['nombre_sede'] ?? '')) ?></td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-500"><?= htmlspecialchars($p['laboratorio'] ?? '') ?></td>
                                    <?php foreach ($meses as $m): ?>
                                    <td class="px-1 py-3 text-center border-r border-gray-100 last:border-r-0">
                                        <?php
                                        $val = $p[$m] ?? null;
                                        $checked = ($val !== null && $val !== '' && (float)$val > 0);
                                        ?>
                                        <?php if ($checked): ?>
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                            <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <?php else: ?>
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                            <span class="text-gray-300">—</span>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <?php
                                    $mesActual = (int)date('n');
                                    $seleccionados = 0;
                                    $cumplidos = 0;
                                    foreach ($meses as $i => $m) {
                                        if (empty($p[$m]) || (float)$p[$m] <= 0) continue;
                                        $seleccionados++;
                                        if ($i < $mesActual) {
                                            $cumplidos++;
                                        }
                                    }
                                    $avancePlanif = $seleccionados > 0 ? round(($cumplidos / $seleccionados) * 100) : 0;
                                    $avanceEjecutado = isset($p['avance_ejecutado']) ? (float)$p['avance_ejecutado'] : 0.0;
                                    $aeColor = $avanceEjecutado >= 70 ? 'text-green-700' : ($avanceEjecutado >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    $observacionAvance = $p['observaciones_avance'] ?? ($p['obeservaciones_avance'] ?? '');
                                    ?>
                                    <td class="px-2 py-3 text-center font-semibold border-r border-gray-100 whitespace-nowrap text-gray-700"><?= $avancePlanif ?>%</td>
                                    <td class="px-2 py-3 text-center font-semibold border-r border-gray-100 whitespace-nowrap <?= $avanceEjecutado > 0 ? $aeColor : 'text-gray-400' ?>"><?= number_format($avanceEjecutado, 2) ?>%</td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <span class="text-gray-600"><?= htmlspecialchars((string)($observacionAvance !== '' ? $observacionAvance : '—')) ?></span>
                                    </td>
                                    <?php
                                    $planif = (float)($p['presupuesto_planificado'] ?? 0);
                                    $ejec = (float)($p['presupuesto_ejecutado'] ?? 0);
                                    $pct = ($planif > 0)
                                        ? max(0, min(100, round((($planif - $ejec) / $planif) * 100, 2)))
                                        : 0;
                                    ?>
                                    <td class="px-2 py-3 text-right font-medium border-r border-gray-100 whitespace-nowrap">$<?= number_format($planif, 2) ?></td>
                                    <td class="px-2 py-3 text-right border-r border-gray-100 whitespace-nowrap">$<?= number_format($ejec, 2) ?></td>
                                    <td class="px-2 py-3 text-right font-semibold border-r border-gray-100 whitespace-nowrap <?= $pct >= 70 ? 'text-green-700' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $pct ?>%</td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-600"><?= htmlspecialchars($p['nombre_area'] ?? '') ?></td>
                                    <td class="px-2 py-3 max-w-[180px] truncate border-r border-gray-100 text-gray-500" title="<?= htmlspecialchars($p['observaciones'] ?? '') ?>">
                                        <?= htmlspecialchars($p['observaciones'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <?php
                                        $estado = trim((string)($p['estado'] ?? ''));
                                        $estadoNormalizado = function_exists('mb_strtolower')
                                            ? mb_strtolower($estado, 'UTF-8')
                                            : strtolower($estado);
                                        $mapaEstadoColor = [
                                            'activo' => 'bg-green-100 text-green-800',
                                            'inactivo' => 'bg-yellow-100 text-yellow-800',
                                            'caducado' => 'bg-red-100 text-red-800',
                                        ];
                                        $colorEstado = $mapaEstadoColor[$estadoNormalizado] ?? 'bg-gray-100 text-gray-500';
                                        ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $colorEstado ?>">
                                            <?= htmlspecialchars($estado !== '' ? $estado : 'Sin estado') ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <?php if ($canEditPoa): ?>
                                                <a href="<?= $basePath ?>/admin/actividad/edit/<?= $p['id_actividad'] ?>" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($canDeletePoa): ?>
                                                <form action="<?= $basePath ?>/admin/actividad/eliminar/<?= $p['id_actividad'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta actividad?');">
                                                    <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!$canEditPoa && !$canDeletePoa): ?>
                                                <span class="text-gray-400 text-xs">Sin acciones</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= 18 + count($meses) ?>" class="px-4 py-12 text-center">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-gray-400 text-lg font-medium">No hay actividades POA registradas</p>
                                    <p class="text-gray-300 text-sm mt-1">Presione "Nueva Actividad" para crear el primer registro</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="paginacionPoa" class="mt-6 flex justify-center gap-1"></div>
        </section>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tabla = document.getElementById("tablaPoa");
        const buscador = document.getElementById("buscadorPoa");
        const paginacion = document.getElementById("paginacionPoa");
        if (!tabla || !buscador || !paginacion) return;

        const filasPorPagina = 10;
        const todasFilas = Array.from(tabla.querySelectorAll("tbody tr")).filter(fila => fila.querySelectorAll("td").length > 0);
        let filasFiltradas = [...todasFilas];
        let paginaActual = 1;

        const mostrarPagina = (pagina) => {
            paginaActual = pagina;
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            const filasPagina = filasFiltradas.slice(inicio, fin);
            todasFilas.forEach((fila) => { fila.style.display = "none"; });
            filasPagina.forEach((fila) => { fila.style.display = ""; });
            renderPaginacion();
        };

        const renderPaginacion = () => {
            const totalPaginas = Math.ceil(filasFiltradas.length / filasPorPagina);
            paginacion.innerHTML = "";
            if (totalPaginas <= 1) return;
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement("button");
                btn.innerText = i;
                btn.className = "px-3 py-1 mx-0.5 rounded-lg border text-sm font-medium transition " + (i === paginaActual ? "bg-purple-700 text-white border-purple-700" : "bg-white text-gray-600 border-gray-200 hover:bg-gray-50");
                btn.addEventListener("click", () => mostrarPagina(i));
                paginacion.appendChild(btn);
            }
        };

        buscador.addEventListener("input", function() {
            const valor = this.value.toLowerCase();
            filasFiltradas = todasFilas.filter(fila => (fila.textContent || "").toLowerCase().includes(valor));
            mostrarPagina(1);
        });

        mostrarPagina(1);
    });
</script>
