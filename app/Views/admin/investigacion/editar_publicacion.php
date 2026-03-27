<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">✏️ Editar Publicación</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/publicacion/actualizar" class="space-y-6">

        <!-- ID oculto -->
        <input type="hidden" name="id_publicacion"
            value="<?= $publicacion['id_publicacion'] ?>">

        <!-- Nombre -->
        <div>
            <label class="block text-sm font-medium mb-1">Nombre Publicación</label>
            <input type="text" name="nombre_publicacion" required
                value="<?= htmlspecialchars($publicacion['nombre_publicacion']) ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Año -->
        <div>
            <label class="block text-sm font-medium mb-1">Año</label>
            <input type="number" name="anio" required
                value="<?= $publicacion['anio'] ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Tipo -->
        <div>
            <label class="block text-sm font-medium mb-1">Tipo</label>
            <select name="tipo" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">

                <option value="">Seleccione tipo</option>

                <?php
                $tipos = [
                    "ARTICULO_CIENTIFICO" => "Artículo Científico",
                    "LIBRO" => "Libro",
                    "CAPITULO_LIBRO" => "Capítulo de Libro",
                    "PUBLICACION_DOCENTE" => "Publicación Docente",
                    "PROYECTO" => "Proyecto",
                    "GUIA" => "Guía"
                ];

                foreach ($tipos as $valor => $texto):
                ?>
                    <option value="<?= $valor ?>"
                        <?= ($publicacion['tipo'] === $valor) ? 'selected' : '' ?>>
                        <?= $texto ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <!-- URL -->
        <div>
            <label class="block text-sm font-medium mb-1">URL</label>
            <input type="text" name="url"
                value="<?= htmlspecialchars($publicacion['url'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Periodo -->
        <div>
            <label class="block text-sm font-medium mb-1">Periodo Académico</label>
            <input type="text" name="periodo_academico"
                value="<?= htmlspecialchars($publicacion['periodo_academico']) ?>"
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
                💾 Actualizar Publicación
            </button>
        </div>

    </form>

</div>