<?php
$activeTab = (string) ($activeTab ?? 'procesos');
if (!in_array($activeTab, ['procesos', 'procesos_institucionales', 'gestiones', 'ejes', 'objetivos', 'estrategias'], true)) {
    $activeTab = 'procesos';
}

$editProceso = is_array($editProceso ?? null) ? $editProceso : null;
$editProcesoInstitucional = is_array($editProcesoInstitucional ?? null) ? $editProcesoInstitucional : null;
$editGestion = is_array($editGestion ?? null) ? $editGestion : null;
$editEje = is_array($editEje ?? null) ? $editEje : null;
$editObjetivo = is_array($editObjetivo ?? null) ? $editObjetivo : null;
$editEstrategia = is_array($editEstrategia ?? null) ? $editEstrategia : null;
$metasEdit = (array) ($editEstrategia['metas'] ?? []);
if (empty($metasEdit)) {
    $metasEdit = [['anio' => '', 'porcentaje_esperado' => '', 'observaciones' => '']];
}
?>

<h2 class="text-2xl font-bold mb-6">Administracion - Configuracion</h2>

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

<div class="mb-6 flex flex-wrap gap-2">
    <a href="<?= $basePath ?>/admin/configuracion?tab=procesos" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'procesos' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Procesos</a>
    <a href="<?= $basePath ?>/admin/configuracion?tab=procesos_institucionales" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'procesos_institucionales' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Procesos Institucionales</a>
    <a href="<?= $basePath ?>/admin/configuracion?tab=gestiones" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'gestiones' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Gestiones Institucionales</a>
    <a href="<?= $basePath ?>/admin/configuracion?tab=ejes" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'ejes' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Ejes Estrategicos</a>
    <a href="<?= $basePath ?>/admin/configuracion?tab=objetivos" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'objetivos' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Objetivos Estrategicos</a>
    <a href="<?= $basePath ?>/admin/configuracion?tab=estrategias" class="px-4 py-2 rounded-lg text-sm font-semibold <?= $activeTab === 'estrategias' ? 'bg-superarse-morado-medio text-white' : 'bg-white border border-gray-300 text-gray-700' ?>">Estrategias + Linea Base + Metas</a>
</div>

<?php if ($activeTab === 'procesos'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Seccion A: Procesos</h3>
    <p class="text-sm text-gray-500 mb-5">Estos procesos se usan en la cabecera del POA.</p>

    <form method="POST" action="<?= $basePath . ($editProceso ? '/admin/configuracion/proceso/update' : '/admin/configuracion/proceso/store') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if ($editProceso): ?>
            <input type="hidden" name="id" value="<?= (int) ($editProceso['id'] ?? 0) ?>">
        <?php endif; ?>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700 font-medium">Nombre del proceso</span>
            <input type="text" name="nombre" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editProceso['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estado</span>
            <?php $estadoProceso = (string) ($editProceso['estado'] ?? '1'); ?>
            <select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2">
                <option value="1" <?= $estadoProceso === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $estadoProceso === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </label>

        <div class="md:col-span-3 flex flex-wrap gap-3">
            <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">
                <?= $editProceso ? 'Actualizar proceso' : 'Registrar proceso' ?>
            </button>
            <?php if ($editProceso): ?>
                <a href="<?= $basePath ?>/admin/configuracion?tab=procesos" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Nuevo registro</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Procesos registrados</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($procesos ?? []) as $proceso): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= (int) ($proceso['id'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($proceso['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= (int) ($proceso['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=procesos&edit_proceso=<?= (int) ($proceso['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/proceso/eliminar/<?= (int) ($proceso['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php endif; ?>

<?php if ($activeTab === 'procesos_institucionales'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Seccion B: Procesos Institucionales</h3>
    <p class="text-sm text-gray-500 mb-5">Estos procesos institucionales clasifican las actividades del POA.</p>

    <form method="POST" action="<?= $basePath . ($editProcesoInstitucional ? '/admin/configuracion/proceso-institucional/update' : '/admin/configuracion/proceso-institucional/store') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if ($editProcesoInstitucional): ?>
            <input type="hidden" name="id" value="<?= (int) ($editProcesoInstitucional['id'] ?? 0) ?>">
        <?php endif; ?>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700 font-medium">Nombre del proceso institucional</span>
            <input type="text" name="nombre" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editProcesoInstitucional['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estado</span>
            <?php $estadoProcesoInstitucional = (string) ($editProcesoInstitucional['estado'] ?? '1'); ?>
            <select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2">
                <option value="1" <?= $estadoProcesoInstitucional === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $estadoProcesoInstitucional === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </label>

        <div class="md:col-span-3 flex flex-wrap gap-3">
            <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">
                <?= $editProcesoInstitucional ? 'Actualizar proceso institucional' : 'Registrar proceso institucional' ?>
            </button>
            <?php if ($editProcesoInstitucional): ?>
                <a href="<?= $basePath ?>/admin/configuracion?tab=procesos_institucionales" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Nuevo registro</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Procesos institucionales registrados</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($procesosInstitucionales ?? []) as $procesoInstitucional): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= (int) ($procesoInstitucional['id'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($procesoInstitucional['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= (int) ($procesoInstitucional['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=procesos_institucionales&edit_proceso_institucional=<?= (int) ($procesoInstitucional['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/proceso-institucional/eliminar/<?= (int) ($procesoInstitucional['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php endif; ?>

<?php if ($activeTab === 'gestiones'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Seccion B: Gestiones Institucionales</h3>
    <p class="text-sm text-gray-500 mb-5">Cada gestión institucional queda asociada a un proceso institucional para usarla en actividades POA.</p>

    <form method="POST" action="<?= $basePath . ($editGestion ? '/admin/configuracion/gestion/update' : '/admin/configuracion/gestion/store') ?>" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if ($editGestion): ?>
            <input type="hidden" name="id" value="<?= (int) ($editGestion['id'] ?? 0) ?>">
        <?php endif; ?>

        <label class="block md:col-span-2">
            <span class="text-sm text-gray-700 font-medium">Nombre de la gestión</span>
            <input type="text" name="nombre" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editGestion['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estado</span>
            <?php $estadoGestion = (string) ($editGestion['estado'] ?? '1'); ?>
            <select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2">
                <option value="1" <?= $estadoGestion === '1' ? 'selected' : '' ?>>Activo</option>
                <option value="0" <?= $estadoGestion === '0' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </label>

        <label class="block md:col-span-3">
            <span class="text-sm text-gray-700 font-medium">Proceso</span>
            <?php $procesoGestion = (int) ($editGestion['procesos_institucionales_id'] ?? 0); ?>
            <select name="procesos_institucionales_id" required class="w-full mt-1 border rounded-lg px-3 py-2">
                <option value="">Seleccione...</option>
                <?php foreach (($procesosInstitucionalesActivos ?? []) as $proceso): ?>
                    <option value="<?= (int) ($proceso['id'] ?? 0) ?>" <?= (int) ($proceso['id'] ?? 0) === $procesoGestion ? 'selected' : '' ?>><?= htmlspecialchars((string) ($proceso['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="md:col-span-3 flex flex-wrap gap-3">
            <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm">
                <?= $editGestion ? 'Actualizar gestion' : 'Registrar gestion' ?>
            </button>
            <?php if ($editGestion): ?>
                <a href="<?= $basePath ?>/admin/configuracion?tab=gestiones" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Nuevo registro</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Gestiones registradas</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Proceso</th><th class="px-4 py-3">Gestion</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($gestiones ?? []) as $gestion): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= (int) ($gestion['id'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($gestion['proceso_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($gestion['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= (int) ($gestion['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=gestiones&edit_gestion=<?= (int) ($gestion['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/gestion/eliminar/<?= (int) ($gestion['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php endif; ?>

<?php if ($activeTab === 'ejes'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Pestana 1: Ejes Estrategicos</h3>
    <form method="POST" action="<?= $basePath . ($editEje ? '/admin/configuracion/eje/update' : '/admin/configuracion/eje/store') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($editEje): ?><input type="hidden" name="id" value="<?= (int) ($editEje['id'] ?? 0) ?>"><?php endif; ?>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Nombre</span><input type="text" name="nombre" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editEje['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Estado</span><?php $estadoEje = (string) ($editEje['estado'] ?? '1'); ?><select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2"><option value="1" <?= $estadoEje === '1' ? 'selected' : '' ?>>Activo</option><option value="0" <?= $estadoEje === '0' ? 'selected' : '' ?>>Inactivo</option></select></label>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Avance (solo lectura)</span><input type="text" readonly class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100" value="<?= number_format((float) ($editEje['avance'] ?? 0), 2) ?>%"></label>
        <label class="block md:col-span-2"><span class="text-sm text-gray-700 font-medium">Observaciones</span><textarea name="observaciones" rows="3" class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editEje['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <div class="md:col-span-2"><button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm"><?= $editEje ? 'Actualizar eje' : 'Registrar eje' ?></button></div>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Ejes registrados</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Avance</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Objetivos</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($ejes ?? []) as $eje): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= (int) ($eje['id'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($eje['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= number_format((float) ($eje['avance'] ?? 0), 2) ?>%</td>
                        <td class="px-4 py-3"><?= (int) ($eje['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3"><?= (int) ($eje['total_objetivos'] ?? 0) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=ejes&edit_eje=<?= (int) ($eje['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/eje/eliminar/<?= (int) ($eje['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso por POA activo debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($activeTab === 'objetivos'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Pestana 2: Objetivos Estrategicos</h3>
    <form method="POST" action="<?= $basePath . ($editObjetivo ? '/admin/configuracion/objetivo/update' : '/admin/configuracion/objetivo/store') ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php if ($editObjetivo): ?><input type="hidden" name="id" value="<?= (int) ($editObjetivo['id'] ?? 0) ?>"><?php endif; ?>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Codigo</span><input type="text" name="codigo" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editObjetivo['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Estado</span><?php $estadoObjetivo = (string) ($editObjetivo['estado'] ?? '1'); ?><select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2"><option value="1" <?= $estadoObjetivo === '1' ? 'selected' : '' ?>>Activo</option><option value="0" <?= $estadoObjetivo === '0' ? 'selected' : '' ?>>Inactivo</option></select></label>
        <label class="block md:col-span-2"><span class="text-sm text-gray-700 font-medium">Nombre</span><textarea name="nombre" rows="2" required class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editObjetivo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Eje estrategico</span><?php $ejeObjetivo = (int) ($editObjetivo['eje_id'] ?? 0); ?><select name="eje_id" required class="w-full mt-1 border rounded-lg px-3 py-2"><option value="">Seleccione...</option><?php foreach (($ejes ?? []) as $eje): $idEje = (int) ($eje['id'] ?? 0); ?><option value="<?= $idEje ?>" <?= $idEje === $ejeObjetivo ? 'selected' : '' ?>><?= htmlspecialchars((string) ($eje['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
        <label class="block"><span class="text-sm text-gray-700 font-medium">Avance (solo lectura)</span><input type="text" readonly class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100" value="<?= number_format((float) ($editObjetivo['avance'] ?? 0), 2) ?>%"></label>
        <label class="block md:col-span-2"><span class="text-sm text-gray-700 font-medium">Observaciones</span><textarea name="observaciones" rows="3" class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editObjetivo['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
        <div class="md:col-span-2"><button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm"><?= $editObjetivo ? 'Actualizar objetivo' : 'Registrar objetivo' ?></button></div>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Objetivos registrados</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">ID</th><th class="px-4 py-3">Codigo</th><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Eje</th><th class="px-4 py-3">Avance</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($objetivos ?? []) as $objetivo): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= (int) ($objetivo['id'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($objetivo['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($objetivo['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($objetivo['eje_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= number_format((float) ($objetivo['avance'] ?? 0), 2) ?>%</td>
                        <td class="px-4 py-3"><?= (int) ($objetivo['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=objetivos&edit_objetivo=<?= (int) ($objetivo['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/objetivo/eliminar/<?= (int) ($objetivo['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso por POA activo debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>

<?php if ($activeTab === 'estrategias'): ?>
<section class="bg-white shadow-lg rounded-2xl p-6 mb-6">
    <h3 class="text-xl font-bold mb-1">Pestana 3: Estrategias + Linea Base + Metas</h3>

    <form id="formEstrategia" method="POST" action="<?= $basePath . ($editEstrategia ? '/admin/configuracion/estrategia/update' : '/admin/configuracion/estrategia/store') ?>" class="space-y-4">
        <?php if ($editEstrategia): ?><input type="hidden" name="id" value="<?= (int) ($editEstrategia['id'] ?? 0) ?>"><?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <label class="block"><span class="text-sm text-gray-700 font-medium">Objetivo estrategico</span><?php $objEstrategia = (int) ($editEstrategia['objetivo_estrategico_id'] ?? 0); ?><select name="objetivo_estrategico_id" required class="w-full mt-1 border rounded-lg px-3 py-2"><option value="">Seleccione...</option><?php foreach (($objetivos ?? []) as $objetivo): $objId = (int) ($objetivo['id'] ?? 0); ?><option value="<?= $objId ?>" <?= $objId === $objEstrategia ? 'selected' : '' ?>><?= htmlspecialchars((string) (($objetivo['codigo'] ?? '') . ' - ' . ($objetivo['nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
            <label class="block"><span class="text-sm text-gray-700 font-medium">Estado</span><?php $estadoEstrategia = (string) ($editEstrategia['estado'] ?? '1'); ?><select name="estado" class="w-full mt-1 border rounded-lg px-3 py-2"><option value="1" <?= $estadoEstrategia === '1' ? 'selected' : '' ?>>Activo</option><option value="0" <?= $estadoEstrategia === '0' ? 'selected' : '' ?>>Inactivo</option></select></label>
            <label class="block"><span class="text-sm text-gray-700 font-medium">Codigo de estrategia</span><input type="text" name="codigo" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editEstrategia['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="block"><span class="text-sm text-gray-700 font-medium">Avance (solo lectura)</span><input type="text" readonly class="w-full mt-1 border rounded-lg px-3 py-2 bg-gray-100" value="<?= number_format((float) ($editEstrategia['avance'] ?? 0), 2) ?>%"></label>
            <label class="block md:col-span-2"><span class="text-sm text-gray-700 font-medium">Nombre de la estrategia</span><textarea name="nombre" rows="2" required class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editEstrategia['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
            <label class="block md:col-span-2"><span class="text-sm text-gray-700 font-medium">Observaciones de estrategia</span><textarea name="observaciones" rows="2" class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editEstrategia['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-xl">
            <label class="block"><span class="text-sm text-gray-700 font-medium">Porcentaje de partida (Linea base)</span><input type="number" name="porcentaje_partida" step="0.01" min="0" max="100" required class="w-full mt-1 border rounded-lg px-3 py-2" value="<?= htmlspecialchars((string) ($editEstrategia['porcentaje_partida'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>"></label>
            <label class="block"><span class="text-sm text-gray-700 font-medium">Observaciones de linea base</span><textarea name="linea_base_observaciones" rows="2" class="w-full mt-1 border rounded-lg px-3 py-2"><?= htmlspecialchars((string) ($editEstrategia['linea_base_observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
        </div>

        <div class="border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-gray-800">Metas anuales</h4>
                <button type="button" id="btnAgregarMeta" class="px-3 py-1.5 rounded-lg border border-superarse-morado-medio text-superarse-morado-medio hover:bg-superarse-morado-medio hover:text-white text-sm font-semibold">+ Anadir Meta Anual</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="tablaMetas">
                    <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider"><tr><th class="px-3 py-2">Anio</th><th class="px-3 py-2">Porcentaje esperado</th><th class="px-3 py-2">Observaciones</th><th class="px-3 py-2">Accion</th></tr></thead>
                    <tbody>
                        <?php foreach ($metasEdit as $meta): ?>
                            <tr>
                                <td class="px-3 py-2"><input type="number" name="meta_anio[]" min="2000" max="2100" required class="w-full border rounded-lg px-2 py-1" value="<?= htmlspecialchars((string) ($meta['anio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="px-3 py-2"><input type="number" name="meta_porcentaje[]" step="0.01" min="0" max="100" required class="w-full border rounded-lg px-2 py-1" value="<?= htmlspecialchars((string) ($meta['porcentaje_esperado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="px-3 py-2"><input type="text" name="meta_observaciones[]" class="w-full border rounded-lg px-2 py-1" value="<?= htmlspecialchars((string) ($meta['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="px-3 py-2 text-center"><button type="button" class="text-red-600 hover:text-red-800 font-semibold btnEliminarMeta">Quitar</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white px-5 py-2 rounded-lg font-semibold text-sm"><?= $editEstrategia ? 'Actualizar estrategia y detalle' : 'Registrar estrategia y detalle' ?></button>
    </form>
</section>

<section class="bg-white shadow-lg rounded-2xl p-6">
    <h3 class="text-xl font-bold mb-4">Estrategias registradas</h3>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr><th class="px-4 py-3">Codigo</th><th class="px-4 py-3">Eje/Objetivo</th><th class="px-4 py-3">Estrategia</th><th class="px-4 py-3">Linea base</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-center">Acciones</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach (($estrategias ?? []) as $estrategia): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($estrategia['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><p class="font-semibold text-gray-700"><?= htmlspecialchars((string) ($estrategia['eje_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p><p class="text-gray-500 text-xs"><?= htmlspecialchars((string) (($estrategia['objetivo_codigo'] ?? '') . ' - ' . ($estrategia['objetivo_nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($estrategia['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3"><?= number_format((float) ($estrategia['porcentaje_partida'] ?? 0), 2) ?>%</td>
                        <td class="px-4 py-3"><?= (int) ($estrategia['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo' ?></td>
                        <td class="px-4 py-3 text-center space-x-2">
                            <a href="<?= $basePath ?>/admin/configuracion?tab=estrategias&edit_estrategia=<?= (int) ($estrategia['id'] ?? 0) ?>" class="text-blue-600 hover:text-blue-800 font-semibold">Editar</a>
                            <form method="POST" action="<?= $basePath ?>/admin/configuracion/estrategia/eliminar/<?= (int) ($estrategia['id'] ?? 0) ?>" class="inline" onsubmit="return confirm('Si esta en uso por POA activo debera inactivarlo. Continuar?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
(function () {
    const tablaMetas = document.getElementById('tablaMetas');
    const btnAgregarMeta = document.getElementById('btnAgregarMeta');
    const formEstrategia = document.getElementById('formEstrategia');
    if (!tablaMetas || !btnAgregarMeta || !formEstrategia) return;

    const tbody = tablaMetas.querySelector('tbody');

    btnAgregarMeta.addEventListener('click', function () {
        const row = document.createElement('tr');
        row.innerHTML = '<td class="px-3 py-2"><input type="number" name="meta_anio[]" min="2000" max="2100" required class="w-full border rounded-lg px-2 py-1"></td>' +
            '<td class="px-3 py-2"><input type="number" name="meta_porcentaje[]" step="0.01" min="0" max="100" required class="w-full border rounded-lg px-2 py-1"></td>' +
            '<td class="px-3 py-2"><input type="text" name="meta_observaciones[]" class="w-full border rounded-lg px-2 py-1"></td>' +
            '<td class="px-3 py-2 text-center"><button type="button" class="text-red-600 hover:text-red-800 font-semibold btnEliminarMeta">Quitar</button></td>';
        tbody.appendChild(row);
    });

    tbody.addEventListener('click', function (event) {
        const button = event.target.closest('.btnEliminarMeta');
        if (!button) return;
        const rows = tbody.querySelectorAll('tr');
        if (rows.length <= 1) return;
        const row = button.closest('tr');
        if (row) row.remove();
    });

    formEstrategia.addEventListener('submit', function (event) {
        const years = [];
        const inputs = formEstrategia.querySelectorAll('input[name="meta_anio[]"]');
        for (let i = 0; i < inputs.length; i += 1) {
            const value = Number(inputs[i].value || 0);
            if (!value) continue;
            if (years.indexOf(value) >= 0) {
                event.preventDefault();
                alert('No puede repetir el mismo anio en las metas anuales.');
                return;
            }
            years.push(value);
        }
    });
})();
</script>
<?php endif; ?>
