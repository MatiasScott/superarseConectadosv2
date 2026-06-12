<h2 class="text-2xl font-bold mb-6">Editar PEDI</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">
    <form method="POST" action="<?= $basePath ?>/admin/pedi/update" class="space-y-4">
        <input type="hidden" name="id_pedi" value="<?= $pedi['id_pedi'] ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700">Eje</label>
            <select name="eje" id="pediEje" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
                <option value="">Seleccione un eje</option>
                <?php foreach ($ejes as $e): ?>
                    <option value="<?= htmlspecialchars($e['nombre']) ?>" <?= ($pedi['eje'] ?? '') === $e['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Objetivo Estratégico</label>
                <select name="objetivo_estrategico" id="pediObjetivo" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
                    <option value="">Seleccione un objetivo</option>
                    <?php
                    $ejeMapPedi = [];
                    foreach ($ejes as $e) $ejeMapPedi[$e['id']] = $e['nombre'];
                    foreach ($objetivos as $o):
                        $ejeNombre = $ejeMapPedi[$o['eje_id']] ?? '';
                    ?>
                    <option value="<?= htmlspecialchars($o['nombre']) ?>" data-eje-nombre="<?= htmlspecialchars($ejeNombre) ?>" <?= ($pedi['objetivo_estrategico'] ?? '') === $o['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($o['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Estrategia</label>
                <select name="objetivo_estrategia" id="pediEstrategia" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
                    <option value="">Seleccione una estrategia</option>
                    <?php
                    $objMapPedi = [];
                    foreach ($objetivos as $o) $objMapPedi[$o['id']] = $o['nombre'];
                    foreach ($estrategias as $s):
                        $objNombre = $objMapPedi[$s['objetivo_id']] ?? '';
                    ?>
                    <option value="<?= htmlspecialchars($s['nombre']) ?>" data-objetivo-nombre="<?= htmlspecialchars($objNombre) ?>" <?= ($pedi['objetivo_estrategia'] ?? '') === $s['nombre'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Línea Base</label>
            <input type="text" value="<?= htmlspecialchars($pedi['linea_base'] ?? '') ?>" class="w-full mt-1 border rounded-lg px-4 py-2 bg-gray-50 text-gray-500 cursor-not-allowed" readonly disabled>
            <p class="text-xs text-gray-400 mt-1">Editable desde Configuración del Sistema</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Metas Anuales</label>
            <p class="text-xs text-gray-400 mb-3">Las metas se gestionan desde Configuración del Sistema</p>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <?php for ($anio = 2024; $anio <= 2028; $anio++): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta <?= $anio ?></label>
                    <input type="text" value="<?= htmlspecialchars($pedi['meta_' . $anio] ?? '') ?>" class="w-full mt-1 border rounded-lg px-4 py-2 bg-gray-50 text-gray-500 cursor-not-allowed" readonly disabled>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <input type="text" value="Activo" class="w-full mt-1 border rounded-lg px-4 py-2 bg-gray-50 text-gray-500 cursor-not-allowed" readonly disabled>
        </div>

        <div class="flex gap-4 pt-4">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">Guardar</button>
            <a href="<?= $basePath ?>/admin/pedi" class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const pediEje = document.getElementById("pediEje");
    const pediObj = document.getElementById("pediObjetivo");
    const pediEst = document.getElementById("pediEstrategia");

    function cascadaObj() {
        const ejeSel = pediEje.value;
        const curObj = pediObj.value;
        for (const opt of pediObj.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.ejeNombre === ejeSel || !ejeSel) ? "" : "none";
        }
        if (curObj && [...pediObj.options].some(o => o.value === curObj && o.style.display === "none")) {
            pediObj.value = "";
        }
        cascadaEst();
    }

    function cascadaEst() {
        const objSel = pediObj.value;
        const curEst = pediEst.value;
        for (const opt of pediEst.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.objetivoNombre === objSel || !objSel) ? "" : "none";
        }
        if (curEst && [...pediEst.options].some(o => o.value === curEst && o.style.display === "none")) {
            pediEst.value = "";
        }
    }

    pediEje.addEventListener("change", cascadaObj);
    pediObj.addEventListener("change", cascadaEst);
    cascadaObj();
});
</script>