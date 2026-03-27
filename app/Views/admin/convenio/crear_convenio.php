<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-6xl mx-auto">

    <h2 class="text-xl font-bold mb-6">➕ Crear Convenio</h2>

    <form method="POST" action="<?= $basePath ?>/admin/convenio/guardar" class="space-y-6">

        <div class="grid md:grid-cols-2 gap-4">

            <div>
                <label class="block text-sm font-medium mb-1">Empresa *</label>
                <input type="text" name="nombre_empresa" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Carrera</label>
                <input type="text" name="carrera"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>

        </div>

        <div class="grid md:grid-cols-3 gap-4">

            <div>
                <label class="block text-sm font-medium mb-1">Tipo de Convenio</label>

                <select name="tipo_convenio_acuerdo"
                    class="w-full px-4 py-2 border rounded-lg">

                    <option value="marco">Marco</option>
                    <option value="especifico">Específico</option>

                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tipo de Institución</label>

                <select name="tipo_institucion"
                    class="w-full px-4 py-2 border rounded-lg">

                    <option value="Publico">Público</option>
                    <option value="Privado">Privado</option>
                    <option value="Educacion">Educación</option>
                    <option value="ONG">ONG</option>
                    <option value="Redes">Redes</option>
                    <option value="Otros">Otros</option>

                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tipo de Convenio</label>

                <select name="tipo_convenio"
                    class="w-full px-4 py-2 border rounded-lg">

                    <option value="practicas preprofesionales">Prácticas Preprofesionales</option>
                    <option value="investigacion">Investigación</option>
                    <option value="vinculacion">Vinculación</option>
                    <option value="comercial">Comercial</option>
                    <option value="docencia">Docencia</option>
                    <option value="educacion continua">Educación Continua</option>
                    <option value="otros">Otros</option>

                </select>

            </div>

        </div>

        <div class="grid md:grid-cols-2 gap-4">

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Estado del Convenio</label>
                <input type="hidden" name="estado_convenio" value="vigente">
                <input type="text"
                    value="Vigente"
                    readonly
                    class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed">
            </div>

        </div>

        <div class="grid md:grid-cols-3 gap-4">

            <div>
                <label class="block text-sm font-medium mb-1">Ciudad</label>
                <input type="text" name="ciudad"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Localización</label>
                <input type="text" name="localizacion"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">En Ejecución</label>

                <select name="en_ejecucion"
                    class="w-full px-4 py-2 border rounded-lg">

                    <option value="si">Sí</option>
                    <option value="no">No</option>

                </select>

            </div>

        </div>

        <div>

            <label class="block text-sm font-medium mb-1">Observaciones</label>

            <textarea name="observaciones" rows="3"
                class="w-full px-4 py-2 border rounded-lg"></textarea>

        </div>

        <div>
            <label class="block text-sm font-medium mb-1">Estado</label>
            <input type="hidden" name="estado" value="Activo">
            <input type="text"
                value="Activo"
                readonly
                class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed">
        </div>

        <div class="flex justify-end space-x-3 pt-6">

            <a href="<?= $basePath ?>/admin/convenio"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Guardar Convenio
            </button>

        </div>

    </form>

</div>