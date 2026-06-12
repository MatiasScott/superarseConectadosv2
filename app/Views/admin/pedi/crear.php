<h2 class="text-2xl font-bold mb-6">Crear PEDI</h2>

<form method="POST" action="<?= $basePath ?>/admin/pedi/store" class="max-w-4xl mx-auto bg-white shadow-lg rounded-2xl p-6 space-y-5">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Eje</span>
            <input type="text" name="eje" required class="w-full mt-1 border rounded-lg px-4 py-2">
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Objetivo Estratégico</span>
            <textarea name="objetivo_estrategico" required rows="2" class="w-full mt-1 border rounded-lg px-4 py-2"></textarea>
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Estrategia</span>
            <textarea name="objetivo_estrategia" required rows="2" class="w-full mt-1 border rounded-lg px-4 py-2"></textarea>
        </label>

        <label class="block">
            <span class="text-sm text-gray-700 font-medium">Línea Base</span>
            <input type="text" name="linea_base" class="w-full mt-1 border rounded-lg px-4 py-2">
        </label>
    </div>

    <div class="border-t pt-4">
        <p class="text-sm font-semibold text-gray-700 mb-3">Metas por año</p>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <?php foreach ([2024, 2025, 2026, 2027, 2028] as $anio): ?>
                <label class="block">
                    <span class="text-xs text-gray-500 font-medium"><?= $anio ?></span>
                    <input type="text" name="meta_<?= $anio ?>" class="w-full mt-1 border rounded-lg px-3 py-2 text-sm">
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="bg-purple-700 hover:bg-purple-800 text-white px-6 py-2 rounded-lg font-semibold text-sm">Guardar PEDI</button>
        <a href="<?= $basePath ?>/admin/pedi" class="px-5 py-2 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold hover:bg-gray-50">Cancelar</a>
    </div>
</form>
