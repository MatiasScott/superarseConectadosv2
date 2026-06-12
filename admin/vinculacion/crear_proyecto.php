<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-6xl mx-auto">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">➕ Nuevo Proyecto de Vinculación</h2>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/guardar-proyecto-vinculacion" class="space-y-6">

        <!-- INFORMACIÓN GENERAL -->
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nombre del Proyecto *</label>
                <input type="text" name="nombre_proyecto" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Código *</label>
                <input type="text" name="codigo_proyecto" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Responsable *</label>
                <input type="text" name="responsable" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Correo Responsable</label>
                <input type="email" name="correo_responsable"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <!-- DESCRIPCIÓN -->
        <div>
            <label class="block text-sm font-medium mb-1">Objetivo</label>
            <textarea name="objetivo" rows="3"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Alcance del Proyecto</label>
            <textarea name="alcance_proyecto" rows="3"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500"></textarea>
        </div>

        <!-- UBICACIÓN Y CONVENIO -->
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Localización / Comunidad</label>
                <input type="text" name="localizacion"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Convenio</label>
                <select name="convenio"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="N/A">N/A</option>
                    <?php if (!empty($conveniosActivos)): ?>
                        <?php foreach ($conveniosActivos as $item): ?>
                            <option value="<?= htmlspecialchars($item['nombre_empresa']) ?>">
                                <?= htmlspecialchars($item['nombre_empresa']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Línea de Vinculación</label>
                <input type="text" name="linea_investigacion"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <!-- FECHAS -->
        <div class="grid md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Periodo Académico</label>
                <input type="text" name="periodo_academico"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Porcentaje de Avance</label>
                <input type="number" name="porcentaje_avance" min="0" max="100" step="0.01" value="0"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <!-- DATOS ADMINISTRATIVOS -->
        <div class="grid md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Presupuesto ($)</label>
                <input type="number" step="0.01" name="presupuesto" value="0"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Beneficiarios</label>
                <input type="number" name="beneficiarios" value="0"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Estado</label>
                <select name="estado"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="ACTIVO">ACTIVO</option>
                    <option value="INACTIVO">INACTIVO</option>
                </select>
            </div>
        </div>

        <!-- BOTONES -->
        <div class="flex justify-end space-x-3 pt-6">
            <a href="<?= $basePath ?>/admin/vinculacion"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Guardar Proyecto
            </button>
        </div>

    </form>
</div>