<h2 class="text-2xl font-bold mb-6">Crear PEDI</h2>

<div class="bg-white shadow-lg rounded-2xl p-6">

    <form method="POST" action="<?= $basePath ?>/admin/pedi/store" class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Objetivo Estratégico
            </label>

            <textarea name="objetivo_estrategico"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"
                required></textarea>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Avance del Objetivo Estratégico %
            </label>

            <input type="number"
                step="0.01"
                name="avance"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"
                required>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Estrategia
            </label>

            <textarea name="objetivo_estrategia"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"></textarea>
        </div>


        <div>
            <label class="block text-sm font-medium text-gray-700">
                Avance de la Estrategia %
            </label>

            <input type="number"
                step="0.01"
                name="avance_estrategia"
                class="w-full mt-1 border rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-400"
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