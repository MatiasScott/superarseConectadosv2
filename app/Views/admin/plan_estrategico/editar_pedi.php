<h2 class="text-2xl font-bold mb-6">Editar PEDI</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">

    <form method="POST" action="<?= $basePath ?>/admin/pedi/update" class="space-y-4">

        <input type="hidden" name="id_pedi" value="<?= $pedi['id_pedi'] ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Objetivo Estratégico
            </label>

            <textarea name="objetivo_estrategico"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"
                required><?= htmlspecialchars($pedi['objetivo_estrategico']) ?></textarea>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Avance del Objetivo Estratégico %
            </label>

            <input type="number"
                step="0.01"
                value="<?= $pedi['avance'] ?>"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                readonly>
            <p class="text-xs text-gray-500 mt-1">Se calcula automáticamente a partir del avance de sus estrategias.</p>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Avance de la Estrategia %
            </label>

            <textarea name="objetivo_estrategia"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"><?= htmlspecialchars($pedi['objetivo_estrategia']) ?></textarea>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Avance %
            </label>

            <input type="number"
                step="0.01"
                name="avance_estrategia"
                value="<?= $pedi['avance_estrategia'] ?>"
                class="w-full mt-1 border rounded-lg px-4 py-2"
                required>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Estado
            </label>

            <select name="estado" class="w-full mt-1 border rounded-lg px-4 py-2">

                <option value="ACTIVO" <?= $pedi['estado'] == "ACTIVO" ? 'selected' : '' ?>>Activo</option>
                <option value="INACTIVO" <?= $pedi['estado'] == "INACTIVO" ? 'selected' : '' ?>>Inactivo</option>

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