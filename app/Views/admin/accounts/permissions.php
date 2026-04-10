<?php
$account = $account ?? [];
$modules = $modules ?? [];
$actions = $actions ?? ['view', 'create', 'edit', 'delete'];
$permissionsMatrix = $permissionsMatrix ?? [];
$returnQuery = $returnQuery ?? '';

$actionLabels = [
    'view' => 'Ver',
    'create' => 'Crear',
    'edit' => 'Editar',
    'delete' => 'Eliminar',
];
?>

<div class="bg-white shadow-md rounded-xl p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Permisos por módulo</h2>
            <p class="text-sm text-gray-500 mt-1">
                Administrador: <span class="font-semibold text-gray-700"><?= htmlspecialchars($account['display_name'] ?? 'N/A') ?></span>
                (<?= htmlspecialchars($account['email'] ?? 'sin correo') ?>)
            </p>
        </div>

        <a href="<?= $basePath ?>/admin/accounts<?= $returnQuery !== '' ? '?' . htmlspecialchars($returnQuery) : '' ?>"
            class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm transition">
            Volver
        </a>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/accounts/permissions/update" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenPermissions) ?>">
        <input type="hidden" name="account_id" value="<?= (int) ($account['id'] ?? 0) ?>">
        <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery) ?>">

        <div class="overflow-x-auto border border-gray-200 rounded-xl">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Módulo</th>
                        <?php foreach ($actions as $action): ?>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700"><?= htmlspecialchars($actionLabels[$action] ?? ucfirst($action)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($modules as $moduleKey => $moduleLabel): ?>
                        <?php
                        $values = $permissionsMatrix[$moduleKey] ?? [];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($moduleLabel) ?></td>
                            <?php foreach ($actions as $action): ?>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox"
                                        name="permissions[<?= htmlspecialchars($moduleKey) ?>][<?= htmlspecialchars($action) ?>]"
                                        value="1"
                                        class="h-4 w-4 text-purple-700 border-gray-300 rounded"
                                        <?= !empty($values[$action]) ? 'checked' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-3">
            <button type="submit"
                class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-purple-700 hover:bg-purple-800 text-white font-semibold text-sm transition">
                Guardar permisos
            </button>
        </div>
    </form>
</div>
