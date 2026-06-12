<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-6xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">✏️ Editar Proyecto de Vinculación</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/proyecto/actualizarVinculacion" class="space-y-6">

        <!-- ID oculto -->
        <input type="hidden" name="id_proyecto" value="<?= $proyecto['id_proyecto'] ?>">

        <!-- INFORMACIÓN GENERAL -->
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre del Proyecto *</label>
                <input type="text" name="nombre_proyecto" required
                    value="<?= htmlspecialchars($proyecto['nombre_proyecto']) ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Código *</label>
                <input type="text" name="codigo_proyecto" required
                    value="<?= htmlspecialchars($proyecto['codigo_proyecto']) ?>"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Responsable *</label>
                <input type="text" name="responsable" required
                    value="<?= htmlspecialchars($proyecto['responsable']) ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Correo Responsable</label>
                <input type="email" name="correo_responsable"
                    value="<?= htmlspecialchars($proyecto['correo_responsable'] ?? '') ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Objetivo</label>
            <textarea name="objetivo" rows="3"
                class="w-full px-4 py-2 border rounded-lg"><?= htmlspecialchars($proyecto['objetivo'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Alcance</label>
            <textarea name="alcance_proyecto" rows="3"
                class="w-full px-4 py-2 border rounded-lg"><?= htmlspecialchars($proyecto['alcance_proyecto'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Localización</label>
            <input type="text"
                name="localizacion"
                value="<?= htmlspecialchars($proyecto['localizacion'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Convenio</label>
            <input type="text"
                name="convenio"
                value="<?= htmlspecialchars($proyecto['convenio'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Línea de Investigación</label>
            <input type="text"
                name="linea_investigacion"
                value="<?= htmlspecialchars($proyecto['linea_investigacion'] ?? '') ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio"
                    value="<?= $proyecto['fecha_inicio'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin"
                    value="<?= $proyecto['fecha_fin'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Periodo Académico</label>
                <input type="text" name="periodo_academico"
                    value="<?= htmlspecialchars($proyecto['periodo_academico']) ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Porcentaje Avance</label>
                <input type="number" min="0" max="100" step="0.01" name="porcentaje_avance"
                    value="<?= $proyecto['porcentaje_avance'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Presupuesto</label>
                <input type="number" step="0.01" name="presupuesto"
                    value="<?= $proyecto['presupuesto'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Beneficiarios</label>
                <input type="number" name="beneficiarios"
                    value="<?= $proyecto['beneficiarios'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Estado</label>
                <select name="estado" class="w-full px-4 py-2 border rounded-lg">
                    <option value="ACTIVO" <?= $proyecto['estado'] == 'ACTIVO' ? 'selected' : '' ?>>ACTIVO</option>
                    <option value="INACTIVO" <?= $proyecto['estado'] == 'INACTIVO' ? 'selected' : '' ?>>INACTIVO</option>
                </select>
            </div>
        </div>

        <div class="flex justify-end space-x-3 pt-6">
            <a href="<?= $basePath ?>/admin/vinculacion"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Actualizar Proyecto
            </button>
        </div>

    </form>
</div>