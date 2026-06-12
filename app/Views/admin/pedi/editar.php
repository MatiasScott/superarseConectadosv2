<h2 class="text-2xl font-bold mb-6">Editar PEDI</h2>

<form method="POST" action="<?= $basePath ?>/admin/pedi/update" class="max-w-4xl mx-auto bg-white shadow-lg rounded-2xl p-6 space-y-5">
    <input type="hidden" name="id_pedi" value="<?= (int)($pedi['id_pedi'] ?? 0) ?>">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Eje</span>
            <input type="text" name="eje" required class="w-full mt-1 border rounded-lg px-4 py-2" value="<?= htmlspecialchars($pedi['eje'] ?? '') ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Objetivo Estratégico</span>
            <textarea name="objetivo_estrategico" required rows="2" class="w-full mt-1 border rounded-lg px-4 py-2"><?= htmlspecialchars($pedi['objetivo_estrategico'] ?? '') ?></textarea>
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estrategia</span>
            <textarea name="objetivo_estrategia" required rows="2" class="w-full mt-1 border rounded-lg px-4 py-2"><?= htmlspecialchars($pedi['objetivo_estrategia'] ?? '') ?></textarea>
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Línea Base</span>
            <input type="text" name="linea_base" class="w-full mt-1 border rounded-lg px-4 py-2" value="<?= htmlspecialchars($pedi['linea_base'] ?? '') ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Avance (%)</span>
            <input type="number" step="0.01" min="0" max="100" name="avance" class="w-full mt-1 border rounded-lg px-4 py-2" value="<?= htmlspecialchars((string)($pedi['avance'] ?? '0')) ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Avance Estrategia (%)</span>
            <input type="number" step="0.01" min="0" max="100" name="avance_estrategia" class="w-full mt-1 border rounded-lg px-4 py-2" value="<?= htmlspecialchars((string)($pedi['avance_estrategia'] ?? '0')) ?>">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estado</span>
            <select name="estado" class="w-full mt-1 border rounded-lg px-4 py-2">
                <option value="activo" <?= ($pedi['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                <option value="inactivo" <?= ($pedi['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </label>
    </div>

    <div class="border-t pt-4">
        <p class="text-sm font-semibold text-gray-700 mb-3">Metas por año</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <?php foreach ([2024, 2025, 2026, 2027, 2028] as $anio): ?>
                <label class="block">
                    <span class="text-xs text-gray-500 font-medium"><?= $anio ?></span>
                    <input type="text" name="meta_<?= $anio ?>" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm" value="<?= htmlspecialchars($pedi['meta_' . $anio] ?? '') ?>">
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg font-semibold text-sm">Actualizar PEDI</button>
        <a href="<?= $basePath ?>/admin/pedi" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Cancelar</a>
    </div>
</form>
