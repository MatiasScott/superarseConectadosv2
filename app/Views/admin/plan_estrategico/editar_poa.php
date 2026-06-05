<h2 class="text-2xl font-bold mb-6">Editar POA</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">

    <form method="POST" action="<?= $basePath ?>/admin/poa/update" class="space-y-4">

        <input type="hidden" name="id_poa" value="<?= $poa['id_poa'] ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                PEDI
            </label>

            <select name="id_pedi"
                class="w-full mt-1 border rounded-lg px-4 py-2">

                <?php foreach ($pedi as $p): ?>

                    <option value="<?= $p['id_pedi'] ?>"
                        <?= $p['id_pedi'] == $poa['id_pedi'] ? 'selected' : '' ?>>

                        <?= $p['objetivo_estrategico'] ?>

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
                value="<?= htmlspecialchars($poa['nombre_area']) ?>"
                class="w-full mt-1 border rounded-lg px-4 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Presupuesto Anual
            </label>

            <input type="number"
                step="0.01"
                name="presupuesto_anual"
                value="<?= $poa['presupuesto_anual'] ?>"
                class="w-full mt-1 border rounded-lg px-4 py-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Estado Actividad
            </label>

            <select name="estado_actividad"
                class="w-full mt-1 border rounded-lg px-4 py-2">

                <option value="no ejecutada" <?= $poa['estado_actividad'] == "no ejecutada" ? 'selected' : '' ?>>
                    No ejecutada
                </option>

                <option value="en progreso" <?= $poa['estado_actividad'] == "en progreso" ? 'selected' : '' ?>>
                    En progreso
                </option>

                <option value="Ejecutada" <?= $poa['estado_actividad'] == "Ejecutada" ? 'selected' : '' ?>>
                    Ejecutada
                </option>

            </select>

        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Observaciones
            </label>

            <textarea name="observaciones"
                class="w-full mt-1 border rounded-lg px-4 py-2"><?= htmlspecialchars($poa['observaciones']) ?></textarea>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">
                Estado
            </label>

            <select name="estado"
                class="w-full mt-1 border rounded-lg px-4 py-2">

                <option value="ACTIVO" <?= $poa['estado'] == "ACTIVO" ? 'selected' : '' ?>>Activo</option>

                <option value="INACTIVO" <?= $poa['estado'] == "INACTIVO" ? 'selected' : '' ?>>Inactivo</option>

            </select>

        </div>


        <div class="flex gap-4 pt-4">

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                Actualizar
            </button>

            <a href="<?= $basePath ?>/admin/plan-estrategico"
                class="bg-gray-400 hover:bg-gray-500 text-white px-6 py-2 rounded-lg">
                Cancelar
            </a>

        </div>

    </form>

</div>