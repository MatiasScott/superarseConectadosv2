<?php
$logs = $logs ?? [];
$totalLogs = (int) ($totalLogs ?? 0);
$totalPages = (int) ($totalPages ?? 1);
$page = (int) ($page ?? 1);
$search = $search ?? '';
$module = $module ?? '';
$table = $table ?? '';
$action = $action ?? '';
$availableTables = $availableTables ?? [];
$moduleTableGroups = $moduleTableGroups ?? [];

$buildQuery = function (array $overrides = []) use ($search, $module, $table, $action, $page) {
    $params = [
        'search' => $search,
        'module' => $module,
        'table' => $table,
        'action' => $action,
        'page' => $page,
    ];

    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }

    return http_build_query(array_filter($params, static function ($value) {
        return !($value === '' || $value === null);
    }));
};

$resolveModuleLabel = static function ($tableName) use ($moduleTableGroups) {
    foreach ($moduleTableGroups as $group) {
        $tables = $group['tables'] ?? [];
        if (in_array($tableName, $tables, true)) {
            return (string) ($group['label'] ?? 'Módulo');
        }
    }

    return 'Otros';
};

$decodeJson = static function ($jsonText) {
    if (!is_string($jsonText) || trim($jsonText) === '') {
        return [];
    }

    $decoded = json_decode($jsonText, true);
    return is_array($decoded) ? $decoded : [];
};

$normalizeValue = static function ($value) {
    if ($value === null) {
        return 'NULL';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if (is_scalar($value)) {
        return (string) $value;
    }

    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
};

$isSensitiveField = static function ($fieldName) {
    return preg_match('/(estado|monto|fecha|presupuesto|pago|valor|saldo|total|costo|precio|importe|anio|año|vencimiento|inicio|fin)/i', (string) $fieldName) === 1;
};

$buildFieldDiff = static function ($actionType, array $before, array $after) use ($normalizeValue, $isSensitiveField) {
    $rows = [];

    if ($actionType === 'INSERT') {
        foreach ($after as $field => $newValue) {
            $rows[] = [
                'field' => (string) $field,
                'old' => '',
                'new' => $normalizeValue($newValue),
                'line' => $field . ': ' . $normalizeValue($newValue),
                'sensitive' => $isSensitiveField($field),
            ];
        }
        return $rows;
    }

    if ($actionType === 'DELETE') {
        foreach ($before as $field => $oldValue) {
            $rows[] = [
                'field' => (string) $field,
                'old' => $normalizeValue($oldValue),
                'new' => '',
                'line' => $field . ': ' . $normalizeValue($oldValue),
                'sensitive' => $isSensitiveField($field),
            ];
        }
        return $rows;
    }

    $allFields = array_unique(array_merge(array_keys($before), array_keys($after)));
    foreach ($allFields as $field) {
        $oldExists = array_key_exists($field, $before);
        $newExists = array_key_exists($field, $after);

        $oldValue = $oldExists ? $before[$field] : null;
        $newValue = $newExists ? $after[$field] : null;

        if ($oldValue !== $newValue) {
            $rows[] = [
                'field' => (string) $field,
                'old' => $normalizeValue($oldValue),
                'new' => $normalizeValue($newValue),
                'line' => $field . ': ' . $normalizeValue($oldValue) . ' -> ' . $normalizeValue($newValue),
                'sensitive' => $isSensitiveField($field),
            ];
        }
    }

    return $rows;
};
?>

<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-800">Auditoría General</h2>
    <p class="text-sm text-gray-500 mt-1">Registro de inserciones, modificaciones y eliminaciones en todos los módulos (admin y estudiantes).</p>
</div>

<div class="bg-white shadow rounded-xl p-6 mb-6">
    <form method="GET" action="<?= $basePath ?>/admin/auditoria-general" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <input type="text"
            name="search"
            value="<?= htmlspecialchars($search) ?>"
            placeholder="Buscar por actor, tabla o contenido..."
            class="md:col-span-2 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">

        <select name="module" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">
            <option value="">Todos los módulos</option>
            <?php foreach ($moduleTableGroups as $moduleKey => $group): ?>
                <option value="<?= htmlspecialchars((string) $moduleKey) ?>" <?= $moduleKey === $module ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($group['label'] ?? $moduleKey)) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="table" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">
            <option value="">Todas las tablas</option>
            <?php foreach ($availableTables as $tableName): ?>
                <option value="<?= htmlspecialchars($tableName) ?>" <?= $tableName === $table ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tableName) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="action" class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">
            <option value="">Todas las acciones</option>
            <option value="INSERT" <?= $action === 'INSERT' ? 'selected' : '' ?>>INSERT</option>
            <option value="UPDATE" <?= $action === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
            <option value="DELETE" <?= $action === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
        </select>

        <div class="md:col-span-4 flex gap-2 justify-end">
            <button type="submit" class="bg-purple-700 text-white px-4 py-2 rounded-lg hover:bg-purple-800 transition font-semibold">Filtrar</button>
            <a href="<?= $basePath ?>/admin/auditoria-general" class="bg-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 transition font-semibold">Limpiar</a>
            <a href="<?= $basePath ?>/admin/auditoria-general/export/csv?<?= htmlspecialchars($buildQuery(['page' => null])) ?>"
                class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition font-semibold">
                Exportar CSV
            </a>
            <a href="<?= $basePath ?>/admin/auditoria-general/export/excel?<?= htmlspecialchars($buildQuery(['page' => null])) ?>"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-semibold">
                Exportar Excel
            </a>
        </div>
    </form>
</div>

<div class="bg-white shadow rounded-xl overflow-hidden">
    <div class="px-6 py-4 border-b bg-gray-50">
        <p class="text-sm text-gray-600">Total de eventos: <span class="font-semibold"><?= $totalLogs ?></span></p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-100 border-b text-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Módulo</th>
                    <th class="px-4 py-3 text-left">Actor</th>
                    <th class="px-4 py-3 text-left">Tabla</th>
                    <th class="px-4 py-3 text-left">Acción</th>
                    <th class="px-4 py-3 text-left">Registro</th>
                    <th class="px-4 py-3 text-left">Antes</th>
                    <th class="px-4 py-3 text-left">Después</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">No hay eventos de auditoría para los filtros seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $badgeClass = 'bg-gray-200 text-gray-700';
                        if (($log['action_type'] ?? '') === 'INSERT') {
                            $badgeClass = 'bg-green-100 text-green-700';
                        } elseif (($log['action_type'] ?? '') === 'UPDATE') {
                            $badgeClass = 'bg-blue-100 text-blue-700';
                        } elseif (($log['action_type'] ?? '') === 'DELETE') {
                            $badgeClass = 'bg-red-100 text-red-700';
                        }

                        $beforeArr = $decodeJson((string) ($log['before_data'] ?? ''));
                        $afterArr = $decodeJson((string) ($log['after_data'] ?? ''));
                        $diffRows = $buildFieldDiff((string) ($log['action_type'] ?? ''), $beforeArr, $afterArr);

                        $diffLines = array_map(static function ($item) {
                            return $item['line'];
                        }, $diffRows);

                        $previewRows = array_slice($diffRows, 0, 8);
                        $restCount = max(0, count($diffLines) - count($previewRows));

                        $modalPayload = [
                            'before' => (string) ($log['before_data'] ?? ''),
                            'after' => (string) ($log['after_data'] ?? ''),
                            'diff' => $diffRows,
                        ];
                        ?>
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars((string) ($log['event_time'] ?? '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700">
                                    <?= htmlspecialchars($resolveModuleLabel((string) ($log['table_name'] ?? ''))) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800"><?= htmlspecialchars((string) ($log['actor_name'] ?? 'Sistema')) ?></div>
                                <div class="text-gray-500"><?= htmlspecialchars((string) ($log['actor_type'] ?? 'unknown')) ?></div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars((string) ($log['table_name'] ?? '')) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded-full text-[11px] font-semibold <?= $badgeClass ?>">
                                    <?= htmlspecialchars((string) ($log['action_type'] ?? '')) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= htmlspecialchars((string) ($log['record_pk'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-gray-700 max-w-md">
                                <pre class="whitespace-pre-wrap break-words"><?= htmlspecialchars((string) ($log['before_data'] ?? '')) ?></pre>
                            </td>
                            <td class="px-4 py-3 text-gray-700 max-w-md">
                                <pre class="whitespace-pre-wrap break-words"><?= htmlspecialchars((string) ($log['after_data'] ?? '')) ?></pre>
                                <div class="mt-2 p-2 bg-gray-50 border rounded-md">
                                    <div class="font-semibold text-gray-700 mb-1">Cambios campo por campo</div>
                                    <?php if (empty($previewRows)): ?>
                                        <div class="text-gray-500">Sin diferencias detectables.</div>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($previewRows as $item): ?>
                                                <div class="text-xs px-2 py-1 rounded <?= !empty($item['sensitive']) ? 'bg-yellow-100 border border-yellow-300 text-yellow-900 font-semibold' : 'bg-white border border-gray-200 text-gray-700' ?>">
                                                    <?= htmlspecialchars((string) $item['line']) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($restCount > 0): ?>
                                            <div class="text-gray-500 mt-1">... y <?= $restCount ?> campo(s) más</div>
                                        <?php endif; ?>

                                        <button type="button"
                                            class="mt-2 text-xs px-3 py-1.5 rounded bg-indigo-600 hover:bg-indigo-700 text-white font-semibold"
                                            data-audit-open-modal="1"
                                            data-audit-title="<?= htmlspecialchars((string) (($log['table_name'] ?? '') . ' [' . ($log['action_type'] ?? '') . '] ' . ($log['record_pk'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                            data-audit-payload="<?= htmlspecialchars(json_encode($modalPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>">
                                            Ver detalle completo
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-600">Página <?= $page ?> de <?= $totalPages ?></div>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
                <a href="<?= $basePath ?>/admin/auditoria-general?<?= htmlspecialchars($buildQuery(['page' => $page - 1])) ?>" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-100">Anterior</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a href="<?= $basePath ?>/admin/auditoria-general?<?= htmlspecialchars($buildQuery(['page' => $page + 1])) ?>" class="px-4 py-2 border rounded-lg text-sm hover:bg-gray-100">Siguiente</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<div id="auditDetailModal" class="hidden fixed inset-0 z-50 bg-black/50 items-center justify-center p-4">
    <div class="bg-white w-full max-w-5xl rounded-xl shadow-2xl overflow-hidden">
        <div class="px-5 py-4 border-b bg-gray-50 flex items-center justify-between">
            <h3 id="auditModalTitle" class="text-lg font-bold text-gray-800">Detalle de auditoría</h3>
            <button type="button" id="auditModalClose" class="text-gray-500 hover:text-gray-700 text-xl leading-none">&times;</button>
        </div>

        <div class="p-5 grid grid-cols-1 lg:grid-cols-2 gap-4 max-h-[75vh] overflow-y-auto">
            <div>
                <h4 class="font-semibold text-gray-700 mb-2">Diff completo</h4>
                <div id="auditModalDiff" class="text-xs bg-gray-50 border rounded p-3 space-y-2"></div>
            </div>
            <div class="space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Antes (JSON)</h4>
                    <pre id="auditModalBefore" class="text-xs bg-gray-50 border rounded p-3 whitespace-pre-wrap break-words"></pre>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-700 mb-2">Después (JSON)</h4>
                    <pre id="auditModalAfter" class="text-xs bg-gray-50 border rounded p-3 whitespace-pre-wrap break-words"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const modal = document.getElementById('auditDetailModal');
        const modalTitle = document.getElementById('auditModalTitle');
        const modalDiff = document.getElementById('auditModalDiff');
        const modalBefore = document.getElementById('auditModalBefore');
        const modalAfter = document.getElementById('auditModalAfter');
        const closeBtn = document.getElementById('auditModalClose');

        if (!modal || !modalTitle || !modalDiff || !modalBefore || !modalAfter || !closeBtn) {
            return;
        }

        const escapeHtml = (str) => {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        const renderDiffHtml = (diffItems) => {
            if (!Array.isArray(diffItems) || diffItems.length === 0) {
                return '<div class="text-gray-500">Sin cambios detectables.</div>';
            }

            return diffItems.map((item) => {
                const field = escapeHtml(item?.field || 'campo');
                const oldValue = escapeHtml(item?.old ?? '');
                const newValue = escapeHtml(item?.new ?? '');
                const isSensitive = !!item?.sensitive;

                const oldIsEmpty = oldValue === '' || oldValue === 'NULL';
                const newIsEmpty = newValue === '' || newValue === 'NULL';

                let changeIcon = '~';
                let changeLabel = 'Modificado';
                let changeClass = 'text-blue-700 bg-blue-100 border-blue-200';

                if (oldIsEmpty && !newIsEmpty) {
                    changeIcon = '+';
                    changeLabel = 'Nuevo';
                    changeClass = 'text-green-700 bg-green-100 border-green-200';
                } else if (!oldIsEmpty && newIsEmpty) {
                    changeIcon = '-';
                    changeLabel = 'Eliminado';
                    changeClass = 'text-red-700 bg-red-100 border-red-200';
                }

                const wrapperClass = isSensitive
                    ? 'p-2 rounded border border-yellow-300 bg-yellow-50'
                    : 'p-2 rounded border border-gray-200 bg-white';

                return `
                    <div class="${wrapperClass}">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="font-semibold text-gray-700">${field}</div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 border rounded text-[11px] font-semibold ${changeClass}">
                                <span>${changeIcon}</span>
                                <span>${changeLabel}</span>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-start gap-2">
                            <span class="text-red-700 bg-red-100 border border-red-200 rounded px-2 py-0.5">${oldValue || 'NULL'}</span>
                            <span class="text-gray-500 font-semibold">→</span>
                            <span class="text-green-700 bg-green-100 border border-green-200 rounded px-2 py-0.5">${newValue || 'NULL'}</span>
                        </div>
                    </div>
                `;
            }).join('');
        };

        const openModal = (title, payloadText) => {
            let payload = {};
            try {
                payload = JSON.parse(payloadText || '{}');
            } catch (e) {
                payload = {};
            }

            const diffItems = Array.isArray(payload.diff) ? payload.diff : [];

            modalTitle.textContent = title || 'Detalle de auditoría';
            modalDiff.innerHTML = renderDiffHtml(diffItems);
            modalBefore.textContent = payload.before || '';
            modalAfter.textContent = payload.after || '';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const closeModal = () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.querySelectorAll('[data-audit-open-modal="1"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                openModal(btn.getAttribute('data-audit-title'), btn.getAttribute('data-audit-payload'));
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
