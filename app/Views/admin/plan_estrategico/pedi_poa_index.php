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

    <?php if ($selectedPoa): ?>
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
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Meta</th>
                            <th class="px-4 py-3">Asignado</th>
                            <th class="px-4 py-3">Ejecutado</th>
                            <th class="px-4 py-3">Avance</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($selectedPoaActividades)): ?>
                            <?php foreach ($selectedPoaActividades as $actividad): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><?= (int) ($actividad['id'] ?? 0) ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($actividad['tipo_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($actividad['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3"><?= htmlspecialchars((string) ($actividad['meta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="px-4 py-3">$<?= number_format((float) ($actividad['presupuesto_asignado'] ?? 0), 2) ?></td>
                                    <td class="px-4 py-3">$<?= number_format((float) ($actividad['presupuesto_ejecutado'] ?? 0), 2) ?></td>
                                    <td class="px-4 py-3"><?= number_format((float) ($actividad['avance_actividad'] ?? 0), 2) ?>%</td>
                                    <td class="px-4 py-3 text-center space-x-2">
                                        <a href="<?= $basePath ?>/admin/actividad/cronograma/<?= (int) ($actividad['id'] ?? 0) ?>" class="text-indigo-600 hover:text-indigo-800 font-semibold">Planificar meses</a>
                                        <form action="<?= $basePath ?>/admin/actividad/eliminar/<?= (int) ($actividad['id'] ?? 0) ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta actividad/proyecto?');">
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-400">Aún no hay actividades/proyectos para esta cabecera POA.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    (function () {
        const form = document.getElementById('formActividad');
        const presupuestoInput = document.getElementById('presupuestoAsignado');
        const disponibleInput = document.getElementById('presupuestoDisponibleValor');

        if (!form || !presupuestoInput || !disponibleInput) {
            return;
        }

        form.addEventListener('submit', function (event) {
            const disponible = Number(disponibleInput.value || 0);
            const asignado = Number(presupuestoInput.value || 0);

            if (asignado > disponible) {
                event.preventDefault();
                alert('El presupuesto asignado no puede superar el presupuesto disponible del POA.');
            }
        });
    })();
</script>
