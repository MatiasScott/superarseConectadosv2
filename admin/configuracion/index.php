<?php
function renderMetaCellCfg($meta, $pct) {
    $hasMeta = ($meta !== null && $meta !== '');
    $num = (float)$pct;
    $hasPct = ($pct !== null && $pct !== '' && $num > 0);

    if (!$hasMeta && !$hasPct) {
        return '';
    }

    $display = $hasMeta ? htmlspecialchars($meta) : '-';

    if (!$hasPct && $hasMeta && is_numeric($meta)) {
        $num = (float)$meta;
        $hasPct = ($num > 0);
    }

    if ($hasPct) {
        if ($num >= 70) {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#16a34a;color:#ffffff;">' . $display . '</span>';
        } elseif ($num >= 50) {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#ca8a04;color:#ffffff;">' . $display . '</span>';
        } else {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#dc2626;color:#ffffff;">' . $display . '</span>';
        }
    }

    return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;color:#9ca3af;">' . $display . '</span>';
}

$canCreateConfiguracion = isset($canCreateConfiguracion) ? (bool)$canCreateConfiguracion : false;
$canEditConfiguracion = isset($canEditConfiguracion) ? (bool)$canEditConfiguracion : false;
$canDeleteConfiguracion = isset($canDeleteConfiguracion) ? (bool)$canDeleteConfiguracion : false;
$basePath = isset($basePath) ? (string)$basePath : '';
$ejes = isset($ejes) && is_array($ejes) ? $ejes : [];
$objetivos = isset($objetivos) && is_array($objetivos) ? $objetivos : [];
$estrategias = isset($estrategias) && is_array($estrategias) ? $estrategias : [];
$areas = isset($areas) && is_array($areas) ? $areas : [];
$sedes = isset($sedes) && is_array($sedes) ? $sedes : [];
$pedi = isset($pedi) && is_array($pedi) ? $pedi : [];
?>
<div class="max-w-5xl mx-auto">

    <!-- Ejes Estratégicos -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white">Ejes Estratégicos</h2>
        </div>
        <div class="p-6">
            <?php if ($canCreateConfiguracion): ?>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-eje" class="flex gap-3 mb-4">
                <input type="text" name="nombre" placeholder="Nombre del eje" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                <input type="text" name="descripcion" placeholder="Descripción (opcional)" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">+ Agregar</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($ejes)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-left">Descripción</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($ejes as $e): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($e['nombre']) ?></td>
                            <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($e['descripcion'] ?? '') ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button onclick="editarEje(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['nombre'])) ?>', '<?= htmlspecialchars(addslashes($e['descripcion'] ?? '')) ?>')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-eje/<?= $e['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este eje estratégico?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center text-gray-400 py-6 text-sm">No hay ejes estratégicos registrados.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Objetivos Estratégicos -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white">Objetivos Estratégicos</h2>
        </div>
        <div class="p-6">
            <?php if ($canCreateConfiguracion): ?>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-objetivo" class="flex gap-3 mb-4">
                <select name="eje_id" class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    <option value="">Sin eje</option>
                    <?php foreach ($ejes as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="nombre" placeholder="Nombre del objetivo" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">+ Agregar</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($objetivos)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Eje</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($objetivos as $o): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($o['eje_nombre'] ?? '—') ?></td>
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($o['nombre']) ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button onclick="editarObjetivo(<?= $o['id'] ?>, <?= $o['eje_id'] ?: 'null' ?>, '<?= htmlspecialchars(addslashes($o['nombre'])) ?>')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-objetivo/<?= $o['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este objetivo estratégico?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center text-gray-400 py-6 text-sm">No hay objetivos estratégicos registrados.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estrategias -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white">Estrategias</h2>
        </div>
        <div class="p-6">
            <?php if ($canCreateConfiguracion): ?>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-estrategia" class="flex gap-3 mb-4">
                <select name="objetivo_id" class="border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                    <option value="">Sin objetivo</option>
                    <?php foreach ($objetivos as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="nombre" placeholder="Nombre de la estrategia" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">+ Agregar</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($estrategias)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Objetivo</th>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($estrategias as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($s['objetivo_nombre'] ?? '—') ?></td>
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($s['nombre']) ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button onclick="editarEstrategia(<?= $s['id'] ?>, <?= $s['objetivo_id'] ?: 'null' ?>, '<?= htmlspecialchars(addslashes($s['nombre'])) ?>')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-estrategia/<?= $s['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta estrategia?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center text-gray-400 py-6 text-sm">No hay estrategias registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modales Objetivo y Estrategia -->
    <?php if ($canEditConfiguracion): ?>
    <div id="modalObjetivo" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Editar Objetivo Estratégico</h3>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/actualizar-objetivo" class="space-y-4">
                <input type="hidden" name="id" id="editObjetivoId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Eje</label>
                    <select name="eje_id" id="editObjetivoEjeId" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                        <option value="">Sin eje</option>
                        <?php foreach ($ejes as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="editObjetivoNombre" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Guardar</button>
                    <button type="button" onclick="cerrarModalObjetivo()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEstrategia" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Editar Estrategia</h3>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/actualizar-estrategia" class="space-y-4">
                <input type="hidden" name="id" id="editEstrategiaId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Objetivo</label>
                    <select name="objetivo_id" id="editEstrategiaObjetivoId" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                        <option value="">Sin objetivo</option>
                        <?php foreach ($objetivos as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="editEstrategiaNombre" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Guardar</button>
                    <button type="button" onclick="cerrarModalEstrategia()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarObjetivo(id, ejeId, nombre) {
        document.getElementById('editObjetivoId').value = id;
        document.getElementById('editObjetivoEjeId').value = ejeId || '';
        document.getElementById('editObjetivoNombre').value = nombre;
        document.getElementById('modalObjetivo').style.display = 'flex';
    }
    function cerrarModalObjetivo() {
        document.getElementById('modalObjetivo').style.display = 'none';
    }
    document.getElementById('modalObjetivo').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalObjetivo();
    });
    function editarEstrategia(id, objetivoId, nombre) {
        document.getElementById('editEstrategiaId').value = id;
        document.getElementById('editEstrategiaObjetivoId').value = objetivoId || '';
        document.getElementById('editEstrategiaNombre').value = nombre;
        document.getElementById('modalEstrategia').style.display = 'flex';
    }
    function cerrarModalEstrategia() {
        document.getElementById('modalEstrategia').style.display = 'none';
    }
    document.getElementById('modalEstrategia').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalEstrategia();
    });
    </script>
    <?php endif; ?>

    <!-- Modal Editar Eje -->
    <?php if ($canEditConfiguracion): ?>
    <div id="modalEje" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Editar Eje Estratégico</h3>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/actualizar-eje" class="space-y-4">
                <input type="hidden" name="id_eje" id="editEjeId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="editEjeNombre" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Descripción</label>
                    <input type="text" name="descripcion" id="editEjeDescripcion" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Guardar</button>
                    <button type="button" onclick="cerrarModalEje()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarEje(id, nombre, descripcion) {
        document.getElementById('editEjeId').value = id;
        document.getElementById('editEjeNombre').value = nombre;
        document.getElementById('editEjeDescripcion').value = descripcion;
        document.getElementById('modalEje').style.display = 'flex';
    }
    function cerrarModalEje() {
        document.getElementById('modalEje').style.display = 'none';
    }
    </script>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success']) ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error']) ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Metas PEDI -->
    <div class="bg-white shadow-lg rounded-2xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Metas PEDI</h2>
            <?php if ($canCreateConfiguracion): ?>
            <button onclick="abrirModalPedi()" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Añadir PEDI
            </button>
            <?php endif; ?>
        </div>

        <?php if (!empty($pedi)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-center">Línea Base</th>
                            <th class="px-4 py-3 text-center">2024</th>
                            <th class="px-4 py-3 text-center">2025</th>
                            <th class="px-4 py-3 text-center">2026</th>
                            <th class="px-4 py-3 text-center">2027</th>
                            <th class="px-4 py-3 text-center">2028</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($pedi as $p): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 text-center"><?= renderMetaCellCfg($p['linea_base'] ?? '', $p['linea_base'] ?? null) ?></td>
                            <?php for ($anio = 2024; $anio <= 2028; $anio++): ?>
                            <td class="px-4 py-3 text-center"><?= renderMetaCellCfg($p['meta_' . $anio] ?? '', $p['meta_' . $anio . '_pct'] ?? null) ?></td>
                            <?php endfor; ?>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button type="button" data-pedi='<?= json_encode($p) ?>' onclick='editarPedi(JSON.parse(this.dataset.pedi))' class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-pedi/<?= $p['id_pedi'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este PEDI y todas sus metas?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
        <div class="p-16 text-center">
            <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-gray-400 text-lg font-medium">No hay registros PEDI</p>
            <p class="text-gray-300 text-sm mt-1">Haga clic en "Añadir PEDI" para comenzar</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Añadir / Editar PEDI -->
    <?php if ($canCreateConfiguracion || $canEditConfiguracion): ?>
    <div id="modalPedi" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900" id="modalPediTitle">Añadir PEDI</h3>
                <button type="button" onclick="cerrarModalPedi()" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-pedi-modal" class="space-y-4">
                <input type="hidden" name="id_pedi" id="editPediId" value="">
                <input type="hidden" name="eje_id" id="editPediEjeId" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Eje</label>
                        <select name="eje" id="editPediEje" class="w-full border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                            <option value="">Seleccione un eje</option>
                            <?php foreach ($ejes as $e): ?>
                            <option value="<?= htmlspecialchars($e['nombre']) ?>" data-eje-id="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                        <select name="estado" id="editPediEstado" class="w-full border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Objetivo Estratégico</label>
                        <select name="objetivo_estrategico" id="editPediObjetivo" class="w-full border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                            <option value="">Seleccione un objetivo</option>
                            <?php
                            $ejeMap = [];
                            foreach ($ejes as $e) $ejeMap[$e['id']] = $e['nombre'];
                            foreach ($objetivos as $o):
                                $ejeNombre = $ejeMap[$o['eje_id']] ?? '';
                            ?>
                            <option value="<?= htmlspecialchars($o['nombre']) ?>" data-eje-nombre="<?= htmlspecialchars($ejeNombre) ?>"><?= htmlspecialchars($o['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estrategia</label>
                        <select name="objetivo_estrategia" id="editPediEstrategia" class="w-full border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                            <option value="">Seleccione una estrategia</option>
                            <?php
                            $objMap = [];
                            foreach ($objetivos as $o) $objMap[$o['id']] = $o['nombre'];
                            foreach ($estrategias as $s):
                                $objNombre = $objMap[$s['objetivo_id']] ?? '';
                            ?>
                            <option value="<?= htmlspecialchars($s['nombre']) ?>" data-objetivo-nombre="<?= htmlspecialchars($objNombre) ?>"><?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Línea Base y Metas Anuales</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 text-center">Línea Base</label>
                            <input type="text" name="linea_base_modal" id="editPediLineaBase" class="w-full text-center border border-gray-200 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="—">
                        </div>
                        <?php for ($anio = 2024; $anio <= 2028; $anio++): ?>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1 text-center"><?= $anio ?></label>
                            <input type="text" name="meta_modal[<?= $anio ?>]" id="editPediMeta<?= $anio ?>" class="w-full text-center border border-gray-200 rounded-lg px-2 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" placeholder="—">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition">Guardar</button>
                    <button type="button" onclick="cerrarModalPedi()" class="bg-gray-400 hover:bg-gray-500 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalPedi(data) {
        document.getElementById('modalPediTitle').textContent = data ? 'Editar PEDI' : 'Añadir PEDI';
        document.getElementById('editPediId').value = data ? data.id_pedi : '';
        document.getElementById('editPediEje').value = data ? (data.eje || '') : '';
        document.getElementById('editPediEstado').value = data ? (data.estado || 'activo') : 'activo';
        document.getElementById('editPediLineaBase').value = data ? (data.linea_base || '') : '';
        <?php for ($anio = 2024; $anio <= 2028; $anio++): ?>
        document.getElementById('editPediMeta<?= $anio ?>').value = data ? (data.meta_<?= $anio ?> || '') : '';
        <?php endfor; ?>
        // Sync hidden eje_id
        const ejeSel = document.getElementById('editPediEje');
        const ejeOpt = ejeSel.options[ejeSel.selectedIndex];
        document.getElementById('editPediEjeId').value = ejeOpt && ejeOpt.dataset.ejeId ? ejeOpt.dataset.ejeId : '';
        // Set objetivos and estrategias after cascading
        cascadaObjPedi();
        document.getElementById('editPediObjetivo').value = data ? (data.objetivo_estrategico || '') : '';
        cascadaEstPedi();
        document.getElementById('editPediEstrategia').value = data ? (data.objetivo_estrategia || '') : '';
        document.getElementById('modalPedi').style.display = 'flex';
    }

    function editarPedi(data) {
        abrirModalPedi(data);
    }

    function cerrarModalPedi() {
        document.getElementById('modalPedi').style.display = 'none';
    }

    document.getElementById('modalPedi').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalPedi();
    });

    function cascadaObjPedi() {
        const eje = document.getElementById('editPediEje').value;
        const sel = document.getElementById('editPediObjetivo');
        for (const opt of sel.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.ejeNombre === eje || !eje) ? "" : "none";
        }
        if (sel.value && [...sel.options].some(o => o.value === sel.value && o.style.display === "none")) {
            sel.value = "";
        }
    }

    function cascadaEstPedi() {
        const obj = document.getElementById('editPediObjetivo').value;
        const sel = document.getElementById('editPediEstrategia');
        for (const opt of sel.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.objetivoNombre === obj || !obj) ? "" : "none";
        }
        if (sel.value && [...sel.options].some(o => o.value === sel.value && o.style.display === "none")) {
            sel.value = "";
        }
    }

    document.getElementById('editPediEje').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        document.getElementById('editPediEjeId').value = opt && opt.dataset.ejeId ? opt.dataset.ejeId : '';
        cascadaObjPedi();
        cascadaEstPedi();
    });
    document.getElementById('editPediObjetivo').addEventListener('change', cascadaEstPedi);
    </script>
    <?php endif; ?>

    <!-- PROCESOS -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white">PROCESOS</h2>
        </div>
        <div class="p-6">
            <?php if ($canCreateConfiguracion): ?>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-area" class="flex gap-3 mb-4">
                <input type="text" name="nombre" placeholder="Nombre del área" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">+ Agregar</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($areas)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($areas as $a): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($a['nombre']) ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button onclick="editarArea(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nombre'])) ?>')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-area/<?= $a['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta área?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center text-gray-400 py-6 text-sm">No hay áreas registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Editar Área -->
    <?php if ($canEditConfiguracion): ?>
    <div id="modalArea" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Editar Área</h3>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/actualizar-area" class="space-y-4">
                <input type="hidden" name="id" id="editAreaId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="editAreaNombre" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Guardar</button>
                    <button type="button" onclick="cerrarModalArea()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarArea(id, nombre) {
        document.getElementById('editAreaId').value = id;
        document.getElementById('editAreaNombre').value = nombre;
        document.getElementById('modalArea').style.display = 'flex';
    }
    function cerrarModalArea() {
        document.getElementById('modalArea').style.display = 'none';
    }
    document.getElementById('modalArea').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalArea();
    });
    </script>
    <?php endif; ?>

    <!-- Sedes -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4">
            <h2 class="text-lg font-bold text-white">Sedes</h2>
        </div>
        <div class="p-6">
            <?php if ($canCreateConfiguracion): ?>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/guardar-sede" class="flex gap-3 mb-4">
                <input type="text" name="nombre" placeholder="Nombre de la sede" class="flex-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">+ Agregar</button>
            </form>
            <?php endif; ?>

            <?php if (!empty($sedes)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3 text-left">Nombre</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($sedes as $s): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium"><?= htmlspecialchars($s['nombre']) ?></td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($canEditConfiguracion): ?>
                                    <button onclick="editarSede(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['nombre'])) ?>')" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <?php endif; ?>

                                    <?php if ($canDeleteConfiguracion): ?>
                                    <form action="<?= $basePath ?>/admin/configuracion/eliminar-sede/<?= $s['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta sede?');">
                                        <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                    <?php if (!$canEditConfiguracion && !$canDeleteConfiguracion): ?>
                                    <span class="text-gray-400 text-xs">Sin acciones</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-center text-gray-400 py-6 text-sm">No hay sedes registradas.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Editar Sede -->
    <?php if ($canEditConfiguracion): ?>
    <div id="modalSede" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50" style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Editar Sede</h3>
            <form method="POST" action="<?= $basePath ?>/admin/configuracion/actualizar-sede" class="space-y-4">
                <input type="hidden" name="id" id="editSedeId">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" name="nombre" id="editSedeNombre" class="w-full mt-1 border rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-purple-300 focus:border-purple-500" required>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-purple-700 hover:bg-purple-900 text-white px-4 py-2 rounded-lg text-sm font-medium">Guardar</button>
                    <button type="button" onclick="cerrarModalSede()" class="bg-gray-400 hover:bg-gray-500 text-white px-4 py-2 rounded-lg text-sm font-medium">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editarSede(id, nombre) {
        document.getElementById('editSedeId').value = id;
        document.getElementById('editSedeNombre').value = nombre;
        document.getElementById('modalSede').style.display = 'flex';
    }
    function cerrarModalSede() {
        document.getElementById('modalSede').style.display = 'none';
    }
    document.getElementById('modalSede').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalSede();
    });
    </script>
    <?php endif; ?>

    <a href="<?= $basePath ?>/admin/plan-estrategico" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-900 to-purple-700 hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm transition text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver a Planificación
    </a>
</div>