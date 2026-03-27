<h2 class="text-2xl font-bold mb-6">Crear POA</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">

    <form method="POST" action="<?= $basePath ?>/admin/poa/store" class="space-y-4">


        <div>
            <label class="block text-sm font-medium text-gray-700">
                PEDI
            </label>

            <select name="id_pedi"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>

                <option value="">Seleccione</option>

                <?php foreach ($pedi as $p): ?>

                    <option value="<?= $p['id_pedi'] ?>">
                        <?= $p['objetivo_estrategia'] ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Nombre Área
            </label>

            <input type="text"
                name="nombre_area"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Presupuesto Anual
            </label>

            <input type="number"
                step="0.01"
                name="presupuesto_anual"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Estado Actividad
            </label>

            <select name="estado_actividad"
                class="w-full mt-1 border rounded-lg px-4 py-2">

                <option value="no ejecutada">No Ejecutada</option>
                <option value="en progreso">En Progreso</option>
                <option value="Ejecutada">Ejecutada</option>

            </select>

        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Observaciones
            </label>

            <textarea name="observaciones"
                class="w-full mt-1 border rounded-lg px-4 py-2"></textarea>
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