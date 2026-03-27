<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">🎤 Editar Ponencia</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/ponencia/actualizar" class="space-y-6">

        <!-- ID oculto -->
        <input type="hidden" name="id_ponencia" value="<?= $ponencia['id_ponencia'] ?>">

        <!-- Nombre -->
        <div>
            <label class="block text-sm font-medium mb-1">Nombre Ponencia</label>
            <input type="text" name="nombre_ponencia" required
                value="<?= htmlspecialchars($ponencia['nombre_ponencia'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Autor -->
        <div>
            <label class="block text-sm font-medium mb-1">Autor</label>
            <input type="text" name="autor"
                value="<?= htmlspecialchars($ponencia['autor'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Número Acta -->
        <div>
            <label class="block text-sm font-medium mb-1">Número Acta</label>
            <input type="text" name="nro_acta" readonly
                value="<?= htmlspecialchars($ponencia['nro_acta'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Fecha -->
        <div>
            <label class="block text-sm font-medium mb-1">Fecha Realización</label>
            <input type="date" name="fecha_realizacion"
                value="<?= $ponencia['fecha_realizacion'] ?? '' ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Organizador -->
        <div>
            <label class="block text-sm font-medium mb-1">Organizador</label>
            <input type="text" name="nombre_organizador"
                value="<?= htmlspecialchars($ponencia['nombre_organizador'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Periodo -->
        <div>
            <label class="block text-sm font-medium mb-1">Periodo Académico</label>
            <input type="text" name="periodo_academico"
                value="<?= htmlspecialchars($ponencia['periodo_academico'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-3 pt-4">
            <a href="<?= $basePath ?>/admin/investigacion"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Actualizar Ponencia
            </button>
        </div>

    </form>

</div>