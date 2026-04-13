<h2 class="text-2xl font-bold mb-6">Crear Actividad POA</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">

    <form method="POST" action="<?= $basePath ?>/admin/actividad/store" class="space-y-4">


        <div>
            <label class="block text-sm font-medium text-gray-700">
                POA
            </label>

            <select name="id_poa"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>

                <option value="">Seleccione</option>

                <?php foreach ($poa as $p): ?>

                    <option
                        value="<?= $p['id_poa'] ?>"
                        data-presupuesto-total="<?= (float) ($p['presupuesto_anual'] ?? 0) ?>"
                        data-presupuesto-usado="<?= (float) ($p['presupuesto_asignado'] ?? 0) ?>">
                        <?= $p['nombre_area'] ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Nombre Actividad
            </label>

            <input type="text"
                name="nombre_actividad"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Presupuesto Actividad
            </label>

            <input type="number"
                step="0.01"
                name="presupuesto_actividad"
                id="presupuestoActividad"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>
            <p id="presupuestoInfo" class="mt-1 text-xs text-gray-500"></p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Fecha Inicio
                </label>

                <input
                    type="date"
                    name="fecha_inicio"
                    class="w-full mt-1 border rounded-lg px-4 py-2"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Fecha Fin
                </label>

                <input
                    type="date"
                    name="fecha_fin"
                    class="w-full mt-1 border rounded-lg px-4 py-2"
                    required>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Avance %
                </label>

                <input type="number"
                    step="0.01"
                    name="avance"
                    class="w-full mt-1 border rounded-lg px-4 py-2"
                    required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    Estado
                </label>

                <select name="estado"
                    class="w-full mt-1 border rounded-lg px-4 py-2">

                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>

                </select>
            </div>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Observaciones
            </label>

            <textarea
                name="observacion_actividad"
                rows="3"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                placeholder="Ingrese observaciones..."></textarea>
        </div>

        <div class="flex gap-4 pt-4">

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                Guardar
            </button>

            <a href="<?= $basePath ?>/admin/plan-estrategico"
                class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2 rounded-lg">
                Cancelar
            </a>

        </div>

    </form>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const selectPoa = document.querySelector('select[name="id_poa"]');
        const presupuestoInput = document.getElementById("presupuestoActividad");
        const presupuestoInfo = document.getElementById("presupuestoInfo");

        if (!selectPoa || !presupuestoInput || !presupuestoInfo) {
            return;
        }

        const actualizarLimitePresupuesto = () => {
            const option = selectPoa.options[selectPoa.selectedIndex];
            if (!option || !option.value) {
                presupuestoInput.removeAttribute("max");
                presupuestoInfo.textContent = "";
                return;
            }

            const total = Number(option.dataset.presupuestoTotal || 0);
            const usado = Number(option.dataset.presupuestoUsado || 0);
            const disponible = Math.max(0, total - usado);

            presupuestoInput.max = disponible.toFixed(2);
            presupuestoInfo.textContent = `Disponible para esta actividad: $${disponible.toFixed(2)}`;
        };

        selectPoa.addEventListener("change", actualizarLimitePresupuesto);
        actualizarLimitePresupuesto();
    });
</script>