<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-4xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">👨‍🎓 Agregar Estudiantes de Vinculacion por Carrera</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/guardar-carrera-proyectoV" class="space-y-6">

        <!-- Proyecto -->
        <div>
            <label class="block text-sm font-medium mb-1">Proyecto</label>
            <select name="id_proyecto" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">

                <option value="">Seleccione un proyecto</option>

                <?php foreach ($proyectos as $proyecto): ?>
                    <option value="<?= $proyecto['id_proyecto'] ?>">
                        <?= htmlspecialchars($proyecto['nombre_proyecto']) ?>
                    </option>
                <?php endforeach; ?>

            </select>
        </div>

        <!-- Carrera -->
        <div>
            <label class="block text-sm font-medium mb-1">Carrera</label>
            <input type="text" name="carrera" required
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
        </div>

        <!-- Número de Estudiantes -->
        <div>
            <label class="block text-sm font-medium mb-1">Número de Estudiantes</label>
            <input type="number" name="nro_estudiantes" required
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
                💾 Guardar
            </button>
        </div>

    </form>

</div>