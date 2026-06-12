<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">📚 Nueva Publicación</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/guardar-publicacion" class="space-y-6">

        <!-- Nombre -->
        <div>
            <label class="block text-sm font-medium mb-1">Nombre Publicación</label>
            <input type="text" name="nombre_publicacion" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Año -->
        <div>
            <label class="block text-sm font-medium mb-1">Año</label>
            <input type="number" name="anio" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Tipo -->
        <div>
            <label class="block text-sm font-medium mb-1">Tipo</label>
            <select name="tipo" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">

                <option value="">Seleccione tipo</option>

                <option value="ARTICULO_CIENTIFICO"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'ARTICULO_CIENTIFICO') ? 'selected' : '' ?>>
                    Artículo Científico
                </option>

                <option value="LIBRO"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'LIBRO') ? 'selected' : '' ?>>
                    Libro
                </option>

                <option value="CAPITULO_LIBRO"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'CAPITULO_LIBRO') ? 'selected' : '' ?>>
                    Capítulo de Libro
                </option>

                <option value="PUBLICACION_DOCENTE"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'PUBLICACION_DOCENTE') ? 'selected' : '' ?>>
                    Publicación Docente
                </option>

                <option value="PROYECTO"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'PROYECTO') ? 'selected' : '' ?>>
                    Proyecto
                </option>

                <option value="GUIA"
                    <?= (isset($pub['tipo']) && $pub['tipo'] === 'GUIA') ? 'selected' : '' ?>>
                    Guía
                </option>

            </select>
        </div>

        <!-- URL -->
        <div>
            <label class="block text-sm font-medium mb-1">URL (Opcional)</label>
            <input type="text" name="url"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Periodo -->
        <div>
            <label class="block text-sm font-medium mb-1">Periodo Académico</label>
            <input type="text" name="periodo_academico"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Botón -->
        <div class="flex justify-end space-x-3 pt-4">
            <a href="<?= $basePath ?>/admin/investigacion"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Guardar Publicación
            </button>
        </div>

    </form>

</div>