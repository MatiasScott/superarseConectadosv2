<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Gestión de Entidades</h2>
        <p class="text-sm text-gray-600">Administra empresas, relación con programas y tutor empresarial asociado.</p>
    </div>

    <div class="flex gap-2">
        <a href="<?= $basePath ?>/admin/practicas"
            class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 transition">
            Volver a Prácticas
        </a>
        <a href="<?= $basePath ?>/admin/entidades/crear"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
            Nueva Entidad
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg">
        <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="bg-white shadow rounded-xl p-6 mb-6">
    <form method="GET" action="<?= $basePath ?>/admin/entidades" class="flex flex-col md:flex-row gap-3">
        <input type="text"
            name="buscar"
            value="<?= htmlspecialchars($buscar ?? '', ENT_QUOTES, 'UTF-8') ?>"
            placeholder="Buscar por nombre, RUC, programa, tutor..."
            class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">

        <button type="submit" class="px-5 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition">
            Buscar
        </button>

        <a href="<?= $basePath ?>/admin/entidades" class="px-5 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 transition text-center">
            Limpiar
        </a>
    </form>
</div>

<div class="bg-white shadow rounded-xl overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">ID</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Entidad</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">RUC</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Programa</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tutor Empresarial</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Estado</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php if (!empty($entidades)): ?>
                <?php foreach ($entidades as $entidad): ?>
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3"><?= (int) ($entidad['id_entidad'] ?? 0) ?></td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800"><?= htmlspecialchars((string) ($entidad['nombre_empresa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($entidad['razon_social'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string) ($entidad['ruc'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-4 py-3">
                            <?= htmlspecialchars((string) ($entidad['programa_nombre'] ?? 'Sin programa'), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-gray-800"><?= htmlspecialchars((string) ($entidad['tutor_nombre'] ?? 'Sin tutor'), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars((string) ($entidad['tutor_cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="px-4 py-3">
                            <?php $estado = (string) ($entidad['estado'] ?? 'Disponible'); ?>
                            <?php if ($estado === 'Disponible'): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Disponible</span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">No Disponible</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <a href="<?= $basePath ?>/admin/entidades/editar/<?= (int) $entidad['id_entidad'] ?>"
                                class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-500">No existen entidades registradas con esos criterios.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>