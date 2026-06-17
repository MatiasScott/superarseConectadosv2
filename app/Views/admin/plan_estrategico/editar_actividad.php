<?php
$isEdit = !empty($actividad['id']);
$cardTitle = $isEdit ? 'Editar Actividad' : 'Nueva Actividad';
$submitLabel = $isEdit ? 'Actualizar' : 'Guardar';
$basePath = isset($basePath) ? (string)$basePath : '';
$formAction = $basePath . '/admin/actividad/' . ($isEdit ? 'update' : 'store');

$fechaFinActual = trim((string)($actividad['fecha_fin'] ?? ''));
$hoy = date('Y-m-d');
$estaCaducado = ($fechaFinActual !== '' && $fechaFinActual < $hoy);
$estadoInicial = $estaCaducado
    ? 'CADUCADO'
    : (!empty($actividad['estado']) ? 'ACTIVO' : 'INACTIVO');

$meses = [
    1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
];

$procesosSeleccionados = [];
if (!empty($actividad['proceso_ids'])) {
    $procesosSeleccionados = array_values(array_filter(array_map('trim', explode(',', (string)$actividad['proceso_ids']))));
}

$metasPedi = isset($metasPedi) && is_array($metasPedi) ? $metasPedi : [];
$responsables = isset($responsables) && is_array($responsables) ? $responsables : ($procesos ?? []);
$procesoActividadSeleccionado = (string) ($actividad['proceso_id'] ?? $actividad['procesos_institucionales_id'] ?? '');
$gestionActividadSeleccionada = (string) ($actividad['gestion_id'] ?? '');
?>

<div class="max-w-5xl mx-auto">
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-5 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white"><?= $cardTitle ?></h2>
            <a href="<?= $basePath ?>/admin/poa" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-4 py-2 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver
            </a>
        </div>

        <div class="p-6">
            <form method="POST" action="<?= $formAction ?>" class="space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Proceso</label>
                        <select id="procesoActividadSelect" name="proceso_id" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 text-gray-600 cursor-not-allowed" required disabled>
                            <option value="">Seleccione un proceso</option>
                            <?php foreach (($procesos ?? []) as $proc): ?>
                            <option value="<?= (int) $proc['id'] ?>" <?= $procesoActividadSeleccionado === (string) $proc['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $proc['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gestión</label>
                        <select id="gestionActividadSelect" name="gestion_id" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 text-gray-600 cursor-not-allowed" required disabled>
                            <option value="">Seleccione un proceso primero</option>
                            <?php foreach (($gestiones ?? []) as $gestion): ?>
                            <option value="<?= (int) $gestion['id'] ?>" data-proceso-id="<?= (int) $gestion['procesos_institucionales_id'] ?>" <?= $gestionActividadSeleccionada === (string) $gestion['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $gestion['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php if ($isEdit): ?>
                <input type="hidden" name="id_actividad" value="<?= (int)$actividad['id'] ?>">
                <?php endif; ?>

                <div class="flex items-start gap-2 p-3 rounded-xl bg-blue-50 border border-blue-200">
                    <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs text-blue-700 leading-relaxed">Al seleccionar un <strong>Eje Estratégico</strong>, los campos de <strong>Objetivo Estratégico</strong> y <strong>Estrategia</strong> se filtrarán automáticamente para mostrar solo las opciones vinculadas a dicho eje.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Eje Estratégico (PEDI)</label>
                        <select id="ejeSelect" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 text-gray-600 cursor-not-allowed" disabled>
                            <option value="">Seleccione un eje</option>
                            <?php foreach ($ejes as $e): ?>
                            <option value="<?= (int)$e['id'] ?>" <?= (string)($actividad['eje_id'] ?? '') === (string)$e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Objetivo Estratégico (PEDI)</label>
                        <select id="objetivoSelect" name="objetivo_id" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 text-gray-600 cursor-not-allowed" disabled>
                            <option value="">Seleccione un objetivo</option>
                            <?php foreach ($objetivos as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" data-eje-id="<?= (int)$o['eje_id'] ?>" <?= (string)($actividad['objetivo_id'] ?? '') === (string)$o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Estrategia (PEDI)</label>
                        <select id="estrategiaSelect" name="estrategia_id" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 text-gray-600 cursor-not-allowed" required disabled>
                            <option value="">Seleccione una estrategia</option>
                            <?php foreach ($estrategias as $es): ?>
                            <option value="<?= (int)$es['id'] ?>" data-objetivo-id="<?= (int)$es['objetivo_estrategico_id'] ?>" data-meta-pedi="<?= htmlspecialchars((string)($metasPedi[(int)$es['id']] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= (string)($actividad['estrategia_id'] ?? '') === (string)$es['id'] ? 'selected' : '' ?>><?= htmlspecialchars($es['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre del Proyecto/Actividad</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars((string)($actividad['nombre'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripcion/Insumos para la Actividad</label>
                    <textarea name="descripcion" rows="3" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese descripcion e insumos..."><?= htmlspecialchars((string)($actividad['descripcion'] ?? '')) ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meta (PEDI)</label>
                        <input type="text" id="metaPediInput" name="meta" value="<?= htmlspecialchars((string)($actividad['meta'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm bg-gray-100 focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Auto-asignada al seleccionar estrategia" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sede</label>
                        <select name="sede_id" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                            <option value="">Seleccione una sede</option>
                            <?php foreach ($sedes as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= (string)($actividad['sede_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Laboratorio</label>
                        <input type="text" name="laboratorio" value="<?= htmlspecialchars((string)($actividad['laboratorio'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Nombre del laboratorio">
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 p-4 bg-gray-50/70">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <label class="block text-sm font-semibold text-gray-800">RESPONSABLES</label>
                        <span id="procesoCounter" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-700">0/3 seleccionados</span>
                    </div>

                    <div id="procesoChecklist" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                        <?php foreach ($responsables as $proc):
                            $isChecked = in_array((string)$proc['id'], $procesosSeleccionados, true);
                        ?>
                        <label class="proceso-item flex items-start gap-2.5 p-3 rounded-xl border transition cursor-pointer <?= $isChecked ? 'bg-purple-100 border-purple-400' : 'bg-white border-gray-200 hover:border-purple-300 hover:bg-purple-50/40' ?>">
                            <input type="checkbox" name="proceso_ids[]" value="<?= (int)$proc['id'] ?>" class="proceso-checkbox mt-0.5 w-4 h-4 text-purple-700 rounded border-gray-300 focus:ring-purple-500" <?= $isChecked ? 'checked' : '' ?>>
                            <span class="text-sm text-gray-700 leading-tight"><?= htmlspecialchars($proc['nombre']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <p class="mt-2 text-xs text-gray-500">Seleccione hasta 3 procesos.</p>
                    <p id="procesoValidationMessage" class="mt-1 text-xs font-medium text-red-600 hidden"></p>
                </div>

                <div class="flex items-start gap-2 p-3 rounded-xl bg-amber-50 border border-amber-200">
                    <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-xs text-amber-700 leading-relaxed">Una vez guardada la actividad, el cronograma no podra ser modificado. Cualquier cambio debe ser notificado al departamento de <strong>TICS</strong>.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Cronograma</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <?php foreach ($meses as $numMes => $labelMes):
                            $checked = ((float)($cronograma[$numMes]['avance'] ?? 0) > 0);
                        ?>
                        <label class="mes-checkbox flex flex-col items-center gap-1.5 p-3 border border-gray-200 rounded-2xl cursor-pointer transition <?= $checked ? 'bg-purple-100 border-purple-500' : 'hover:bg-purple-50 hover:border-purple-300' ?>">
                            <input type="checkbox" name="cronograma[<?= $numMes ?>]" value="1" class="w-4 h-4 text-purple-700 rounded border-gray-300 focus:ring-purple-500" <?= $checked ? 'checked' : '' ?>>
                            <span class="text-sm font-semibold text-gray-600"><?= $labelMes ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Presupuesto Planificado</label>
                        <input type="number" step="0.01" name="presupuesto_asignado" id="presupuestoPlanificado" value="<?= htmlspecialchars((string)($actividad['presupuesto_asignado'] ?? '0')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Presupuesto Ejecutado</label>
                        <input type="number" step="0.01" name="presupuesto_ejecutado" id="presupuestoEjecutado" value="<?= htmlspecialchars((string)($actividad['presupuesto_ejecutado'] ?? '0')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    </div>

                    <div id="porcentajeEjecucionContainer" class="md:col-span-2 hidden">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold"></span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Avance Ejecutado (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="avance_actividad" value="<?= htmlspecialchars((string)($actividad['avance_actividad'] ?? '0')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observacion de Avance</label>
                        <input type="text" name="observaciones_avance" value="<?= htmlspecialchars((string)($actividad['observaciones'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese observacion del avance ejecutado...">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="editFechaInicio" value="<?= htmlspecialchars((string)($actividad['fecha_inicio'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="editFechaFin" value="<?= htmlspecialchars((string)($actividad['fecha_fin'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                    </div>

                    <div id="editEstadoBadge" class="md:col-span-2 hidden">
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                    <input type="text" name="observaciones" value="<?= htmlspecialchars((string)($actividad['observaciones'] ?? '')) ?>" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="Ingrese observaciones...">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Estado</label>
                    <select name="estado" id="editEstado" class="w-full mt-1 border rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                        <option value="ACTIVO" <?= $estadoInicial === 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                        <option value="INACTIVO" <?= $estadoInicial === 'INACTIVO' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="CADUCADO" <?= $estadoInicial === 'CADUCADO' ? 'selected' : '' ?>>Caducado</option>
                    </select>
                </div>

                <input type="hidden" name="tipo_registro" value="Actividad">

                <div class="flex gap-4 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-6 py-2.5 rounded-2xl text-sm font-medium transition"><?= $submitLabel ?></button>
                    <a href="<?= $basePath ?>/admin/poa" class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2.5 rounded-2xl text-sm font-medium transition">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ejeSelect = document.getElementById('ejeSelect');
    const objetivoSelect = document.getElementById('objetivoSelect');
    const estrategiaSelect = document.getElementById('estrategiaSelect');
    const procesoActividadSelect = document.getElementById('procesoActividadSelect');
    const gestionActividadSelect = document.getElementById('gestionActividadSelect');

    function filtrarGestionesActividad() {
        if (!procesoActividadSelect || !gestionActividadSelect) {
            return;
        }

        const procesoId = procesoActividadSelect.value;
        let seleccionValida = false;

        Array.from(gestionActividadSelect.querySelectorAll('option[data-proceso-id]')).forEach(function(option) {
            const coincide = !procesoId || option.getAttribute('data-proceso-id') === procesoId;
            option.hidden = !coincide;
            option.disabled = !coincide;
            if (option.selected && coincide) {
                seleccionValida = true;
            }
        });

        if (!seleccionValida) {
            gestionActividadSelect.value = '';
        }
    }

    function filterOptions(select, filterAttr, filterVal) {
        const options = select.querySelectorAll('option');
        options.forEach(function(opt) {
            if (!opt.value) return;
            const val = opt.getAttribute(filterAttr);
            opt.style.display = (!filterVal || val === filterVal) ? '' : 'none';
        });

        if (select.selectedOptions[0] && select.selectedOptions[0].style.display === 'none') {
            select.value = '';
        }
    }

    function onEjeChange() {
        filterOptions(objetivoSelect, 'data-eje-id', ejeSelect.value);
        onObjetivoChange();
    }

    function onObjetivoChange() {
        filterOptions(estrategiaSelect, 'data-objetivo-id', objetivoSelect.value);
        syncMetaPedi();
    }

    function syncMetaPedi() {
        const metaInput = document.getElementById('metaPediInput');
        if (!metaInput || !estrategiaSelect) return;
        const selectedOption = estrategiaSelect.options[estrategiaSelect.selectedIndex];
        const meta = selectedOption ? (selectedOption.getAttribute('data-meta-pedi') || '') : '';
        metaInput.value = meta;
        metaInput.placeholder = meta ? 'Meta asignada desde PEDI' : 'Sin meta registrada para la estrategia seleccionada';
    }

    ejeSelect.addEventListener('change', onEjeChange);
    objetivoSelect.addEventListener('change', onObjetivoChange);
    estrategiaSelect.addEventListener('change', syncMetaPedi);
    if (procesoActividadSelect && gestionActividadSelect) {
        procesoActividadSelect.addEventListener('change', filtrarGestionesActividad);
        filtrarGestionesActividad();
    }
    onEjeChange();
    syncMetaPedi();

    const procesoChecklist = document.getElementById('procesoChecklist');
    const procesoCheckboxes = document.querySelectorAll('.proceso-checkbox');
    const procesoValidationMessage = document.getElementById('procesoValidationMessage');
    const procesoCounter = document.getElementById('procesoCounter');

    function selectedProcesosCount() {
        return Array.from(procesoCheckboxes).filter(function(cb) { return cb.checked; }).length;
    }

    function syncProcesoVisualState() {
        const selected = selectedProcesosCount();

        Array.from(procesoCheckboxes).forEach(function(cb) {
            const item = cb.closest('.proceso-item');
            if (!item) return;
            item.classList.toggle('bg-purple-100', cb.checked);
            item.classList.toggle('border-purple-400', cb.checked);
            item.classList.toggle('bg-white', !cb.checked);
            item.classList.toggle('border-gray-200', !cb.checked);
        });

        if (procesoCounter) {
            procesoCounter.textContent = selected + '/3 seleccionados';
            procesoCounter.classList.toggle('bg-red-100', selected > 3 || selected === 0);
            procesoCounter.classList.toggle('text-red-700', selected > 3 || selected === 0);
            procesoCounter.classList.toggle('bg-purple-100', selected > 0 && selected <= 3);
            procesoCounter.classList.toggle('text-purple-700', selected > 0 && selected <= 3);
        }

        if (!procesoValidationMessage) return;

        if (selected === 0) {
            procesoValidationMessage.textContent = 'Debe seleccionar al menos 1 proceso.';
            procesoValidationMessage.classList.remove('hidden');
        } else if (selected > 3) {
            procesoValidationMessage.textContent = 'Solo puede seleccionar hasta 3 procesos.';
            procesoValidationMessage.classList.remove('hidden');
        } else {
            procesoValidationMessage.textContent = '';
            procesoValidationMessage.classList.add('hidden');
        }
    }

    if (procesoChecklist) {
        Array.from(procesoCheckboxes).forEach(function(cb) {
            cb.addEventListener('change', function() {
                const selected = selectedProcesosCount();
                if (selected > 3) {
                    cb.checked = false;
                }
                syncProcesoVisualState();
            });
        });

        const form = procesoChecklist.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const selected = selectedProcesosCount();
                if (selected < 1 || selected > 3) {
                    e.preventDefault();
                    syncProcesoVisualState();
                    procesoChecklist.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }

        syncProcesoVisualState();
    }

    const mesChecks = document.querySelectorAll('.mes-checkbox input[type="checkbox"]');
    mesChecks.forEach(function(input) {
        const card = input.closest('.mes-checkbox');
        const syncCard = function() {
            card.classList.toggle('bg-purple-100', input.checked);
            card.classList.toggle('border-purple-500', input.checked);
            card.classList.toggle('hover:bg-purple-50', !input.checked);
            card.classList.toggle('hover:border-purple-300', !input.checked);
        };
        input.addEventListener('change', syncCard);
        syncCard();
    });

    const planif = document.getElementById('presupuestoPlanificado');
    const ejec = document.getElementById('presupuestoEjecutado');
    const ejecWrap = document.getElementById('porcentajeEjecucionContainer');
    const ejecBadge = ejecWrap.querySelector('span');

    function calcEjecucion() {
        const p = parseFloat(planif.value) || 0;
        const e = parseFloat(ejec.value) || 0;

        if (p > 0 && e > p) {
            ejec.setCustomValidity('El presupuesto ejecutado no puede ser mayor al planificado.');
        } else {
            ejec.setCustomValidity('');
        }

        if (p <= 0) {
            ejecWrap.classList.add('hidden');
            return;
        }

        const pct = Math.max(0, Math.min(100, (e / p) * 100));
        const color = pct >= 70
            ? 'bg-green-100 text-green-800'
            : (pct >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
        const pctText = pct.toFixed(2).replace(/\.00$/, '').replace(/(\.\d)0$/, '$1');

        ejecBadge.className = 'inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold ' + color;
        ejecBadge.textContent = '% Ejecucion Presupuestaria: ' + pctText + '%';
        ejecWrap.classList.remove('hidden');
    }

    planif.addEventListener('input', calcEjecucion);
    ejec.addEventListener('input', calcEjecucion);
    calcEjecucion();

    const fechaFin = document.getElementById('editFechaFin');
    const estadoSel = document.getElementById('editEstado');
    const estadoWrap = document.getElementById('editEstadoBadge');
    const estadoBadge = estadoWrap.querySelector('span');

    const estadoManual = {
        ultimoNoCaducado: estadoSel.value === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO'
    };

    estadoSel.addEventListener('change', function() {
        if (estadoSel.value !== 'CADUCADO') {
            estadoManual.ultimoNoCaducado = estadoSel.value;
        }
    });

    function calcEstadoFecha() {
        if (!fechaFin.value) {
            estadoWrap.classList.add('hidden');
            return;
        }

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        const fin = new Date(fechaFin.value + 'T00:00:00');
        const diffDays = Math.round((fin - hoy) / (1000 * 60 * 60 * 24));

        if (diffDays < 0) {
            const dias = Math.abs(diffDays);
            estadoBadge.className = 'inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold bg-red-100 text-red-800';
            estadoBadge.innerHTML = 'Caducado hace ' + dias + ' dia' + (dias !== 1 ? 's' : '');
            estadoSel.value = 'CADUCADO';
        } else {
            estadoBadge.className = 'inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-semibold bg-green-100 text-green-800';
            estadoBadge.innerHTML = 'Faltan ' + diffDays + ' dia' + (diffDays !== 1 ? 's' : '');
            if (estadoSel.value === 'CADUCADO') {
                estadoSel.value = estadoManual.ultimoNoCaducado;
            }
        }

        estadoWrap.classList.remove('hidden');
    }

    fechaFin.addEventListener('change', calcEstadoFecha);
    fechaFin.addEventListener('input', calcEstadoFecha);
    calcEstadoFecha();
});
</script>
