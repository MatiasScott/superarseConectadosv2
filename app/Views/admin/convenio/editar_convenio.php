<div class="bg-white shadow-lg rounded-2xl p-6 mb-8 max-w-5xl mx-auto">

    <h2 class="text-xl font-bold mb-6">✏️ Editar Convenio</h2>

    <form method="POST" action="<?= $basePath ?>/admin/convenio/actualizar" class="space-y-6">

        <input type="hidden" name="id_convenio" value="<?= $convenio['id_convenio'] ?>">

        <!-- EMPRESA -->
        <div>
            <label class="block text-sm font-medium mb-1">Empresa</label>
            <input type="text"
                name="nombre_empresa"
                value="<?= htmlspecialchars($convenio['nombre_empresa']) ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <!-- FECHAS -->
        <div class="grid md:grid-cols-2 gap-4">

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Inicio</label>
                <input type="date"
                    name="fecha_inicio"
                    value="<?= $convenio['fecha_inicio'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Fecha Fin</label>
                <input type="date"
                    name="fecha_fin"
                    value="<?= $convenio['fecha_fin'] ?>"
                    class="w-full px-4 py-2 border rounded-lg">
            </div>

        </div>

        <!-- ESTADO CONVENIO -->
        <div>
            <label class="block text-sm font-medium mb-1">Estado del Convenio</label>
            <input type="hidden" name="estado_convenio" value="<?= htmlspecialchars($convenio['estado_convenio']) ?>">
            <input type="text"
                value="<?= htmlspecialchars(ucfirst($convenio['estado_convenio'])) ?>"
                readonly
                class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed">
        </div>

        <!-- TIPO ACUERDO -->
        <div>
            <label class="block text-sm font-medium mb-1">Tipo de Convenio / Acuerdo</label>
            <select name="tipo_convenio_acuerdo" class="w-full px-4 py-2 border rounded-lg">

                <option value="marco" <?= $convenio['tipo_convenio_acuerdo'] == 'marco' ? 'selected' : '' ?>>Marco</option>
                <option value="especifico" <?= $convenio['tipo_convenio_acuerdo'] == 'especifico' ? 'selected' : '' ?>>Específico</option>

            </select>
        </div>

        <!-- TIPO INSTITUCION -->
        <div>
            <label class="block text-sm font-medium mb-1">Tipo de Institución</label>
            <select name="tipo_institucion" class="w-full px-4 py-2 border rounded-lg">

                <option value="Publico" <?= $convenio['tipo_institucion'] == 'Publico' ? 'selected' : '' ?>>Público</option>
                <option value="Privado" <?= $convenio['tipo_institucion'] == 'Privado' ? 'selected' : '' ?>>Privado</option>
                <option value="Educacion" <?= $convenio['tipo_institucion'] == 'Educacion' ? 'selected' : '' ?>>Educación</option>
                <option value="ONG" <?= $convenio['tipo_institucion'] == 'ONG' ? 'selected' : '' ?>>ONG</option>
                <option value="Redes" <?= $convenio['tipo_institucion'] == 'Redes' ? 'selected' : '' ?>>Redes</option>
                <option value="Otros" <?= $convenio['tipo_institucion'] == 'Otros' ? 'selected' : '' ?>>Otros</option>

            </select>
        </div>

        <!-- EN EJECUCION -->
        <div>
            <label class="block text-sm font-medium mb-1">En Ejecución</label>
            <select name="en_ejecucion" class="w-full px-4 py-2 border rounded-lg">

                <option value="si" <?= $convenio['en_ejecucion'] == 'si' ? 'selected' : '' ?>>Sí</option>
                <option value="no" <?= $convenio['en_ejecucion'] == 'no' ? 'selected' : '' ?>>No</option>

            </select>
        </div>

        <!-- TIPO CONVENIO -->
        <div>
            <label class="block text-sm font-medium mb-1">Tipo de Convenio</label>
            <select name="tipo_convenio" class="w-full px-4 py-2 border rounded-lg">

                <option value="practicas preprofesionales" <?= $convenio['tipo_convenio'] == 'practicas preprofesionales' ? 'selected' : '' ?>>Prácticas Preprofesionales</option>
                <option value="investigacion" <?= $convenio['tipo_convenio'] == 'investigacion' ? 'selected' : '' ?>>Investigación</option>
                <option value="vinculacion" <?= $convenio['tipo_convenio'] == 'vinculacion' ? 'selected' : '' ?>>Vinculación</option>
                <option value="comercial" <?= $convenio['tipo_convenio'] == 'comercial' ? 'selected' : '' ?>>Comercial</option>
                <option value="docencia" <?= $convenio['tipo_convenio'] == 'docencia' ? 'selected' : '' ?>>Docencia</option>
                <option value="educacion continua" <?= $convenio['tipo_convenio'] == 'educacion continua' ? 'selected' : '' ?>>Educación Continua</option>
                <option value="otros" <?= $convenio['tipo_convenio'] == 'otros' ? 'selected' : '' ?>>Otros</option>

            </select>
        </div>

        <!-- CARRERA -->
        <div>
            <label class="block text-sm font-medium mb-1">Carrera</label>
            <input type="text"
                name="carrera"
                value="<?= htmlspecialchars($convenio['carrera']) ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <!-- LOCALIZACION -->
        <div>
            <label class="block text-sm font-medium mb-1">Localización</label>
            <input type="text"
                name="localizacion"
                value="<?= htmlspecialchars($convenio['localizacion']) ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <!-- CIUDAD -->
        <div>
            <label class="block text-sm font-medium mb-1">Ciudad</label>
            <input type="text"
                name="ciudad"
                value="<?= htmlspecialchars($convenio['ciudad']) ?>"
                class="w-full px-4 py-2 border rounded-lg">
        </div>

        <!-- OBSERVACIONES -->
        <div>
            <label class="block text-sm font-medium mb-1">Observaciones</label>
            <textarea name="observaciones"
                rows="3"
                class="w-full px-4 py-2 border rounded-lg"><?= htmlspecialchars($convenio['observaciones']) ?></textarea>
        </div>

        <!-- ESTADO -->
        <div>
            <label class="block text-sm font-medium mb-1">Estado</label>
            <input type="hidden" name="estado" value="<?= htmlspecialchars($convenio['estado']) ?>">
            <input type="text"
                value="<?= htmlspecialchars($convenio['estado']) ?>"
                readonly
                class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-700 cursor-not-allowed">
        </div>

        <!-- BOTONES -->
        <div class="flex justify-end space-x-3 pt-4">

            <a href="<?= $basePath ?>/admin/convenio"
                class="bg-gray-500 hover:bg-gray-600 text-white px-5 py-2 rounded-lg text-sm">
                Cancelar
            </a>

            <button type="submit"
                class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-lg text-sm">
                💾 Actualizar Convenio
            </button>

        </div>

    </form>

</div>