<h2 class="text-2xl font-bold mb-6">Crear PEDI</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">
    <form method="POST" action="<?= $basePath ?>/admin/pedi/store" class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">Eje</label>
            <select name="eje" id="pediEje" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
                <option value="">Seleccione un eje</option>
                <?php foreach ($ejes as $e): ?>
                    <option value="<?= htmlspecialchars($e['nombre']) ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Objetivo Estratégico</label>
                <select name="objetivo_estrategico" id="pediObjetivo" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
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
                <label class="block text-sm font-medium text-gray-700">Estrategia</label>
                <select name="objetivo_estrategia" id="pediEstrategia" class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400" required>
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

        <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select name="estado" class="w-full mt-1 border rounded-lg px-4 py-2">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
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
        for (const opt of pediObj.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.ejeNombre === ejeSel || !ejeSel) ? "" : "none";
        }
        pediObj.value = "";
        cascadaEst();
    }

    function cascadaEst() {
        const objSel = pediObj.value;
        for (const opt of pediEst.options) {
            if (!opt.value) continue;
            opt.style.display = (opt.dataset.objetivoNombre === objSel || !objSel) ? "" : "none";
        }
        pediEst.value = "";
    }

    pediEje.addEventListener("change", cascadaObj);
    pediObj.addEventListener("change", cascadaEst);
});
</script>