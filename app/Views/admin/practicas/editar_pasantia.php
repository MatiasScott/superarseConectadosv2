<!-- Main Content -->
<main class="flex-grow max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 w-full">
    <?php
    $modalidadRaw = (string) ($practica['modalidad'] ?? '');
    $modalidadUpper = strtoupper(strtr($modalidadRaw, [
        'á' => 'A',
        'é' => 'E',
        'í' => 'I',
        'ó' => 'O',
        'ú' => 'U',
        'Á' => 'A',
        'É' => 'E',
        'Í' => 'I',
        'Ó' => 'O',
        'Ú' => 'U',
    ]));
    $esHomologableLaboral = strpos($modalidadUpper, 'HOMOLOGABLES LABORALES') !== false;
    $esAyudantiaInvestigacion = strpos($modalidadUpper, 'AYUDANTIAS EN INVESTIGACION') !== false;
    ?>
    <div class="bg-white shadow-2xl rounded-2xl p-6 sm:p-8 border border-gray-100">

        <h2 class="text-2xl font-bold text-gray-900 mb-2">
            Editar Pasantía
        </h2>

        <p class="text-gray-600 mb-8">
            Puedes modificar entidad y estado. Los datos bloqueados se muestran solo para consulta.
        </p>

        <form method="POST" class="space-y-8">

            <!-- SOLO LECTURA -->
            <section class="bg-gray-50 border border-gray-200 rounded-xl p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Información Bloqueada (solo lectura)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <label class="block text-gray-600 mb-1">Estudiante</label>
                        <input type="text" value="<?php echo htmlspecialchars($practica['estudiante_nombre'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Código Matrícula</label>
                        <input type="text" value="<?php echo htmlspecialchars($practica['codigo_matricula'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Carrera</label>
                        <input type="text" value="<?php echo htmlspecialchars($practica['programa'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Modalidad</label>
                        <input type="text" value="<?php echo htmlspecialchars($practica['modalidad'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                    <div>
                        <label class="block text-gray-600 mb-1">Docente Asignado</label>
                        <input type="text" value="<?php echo htmlspecialchars($practica['docente_nombre'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                    </div>
                </div>
            </section>

            <?php if ($esHomologableLaboral): ?>
                <section class="border border-amber-200 bg-amber-50 rounded-xl p-4 sm:p-5">
                    <h3 class="text-lg font-semibold text-amber-900 mb-4">Datos adicionales de Homologables Laborales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Afiliación al IESS</label>
                            <input type="text" value="<?php echo htmlspecialchars($practica['afiliacion_iess'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($esAyudantiaInvestigacion): ?>
                <section class="border border-blue-200 bg-blue-50 rounded-xl p-4 sm:p-5">
                    <h3 class="text-lg font-semibold text-blue-900 mb-4">Datos del Proyecto (Ayudantías en Investigación)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <label class="block text-gray-700 mb-1">Proyecto</label>
                            <input type="text" value="<?php echo htmlspecialchars($practica['proyecto_descripcion'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-1">Lugar</label>
                            <input type="text" value="<?php echo htmlspecialchars($practica['proyecto_lugar'] ?? 'N/A'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" readonly>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 mb-1">Carreras</label>
                            <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100" rows="2" readonly><?php echo htmlspecialchars($practica['proyecto_carreras'] ?? 'N/A'); ?></textarea>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ENTIDAD EDITABLE -->
            <section class="border border-gray-200 rounded-xl p-4 sm:p-5">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Datos de la Entidad</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="entidad_nombre_empresa" class="block text-sm font-semibold text-gray-700 mb-1">Nombre Empresa</label>
                        <input type="text" id="entidad_nombre_empresa" name="entidad_nombre_empresa" value="<?php echo htmlspecialchars($practica['entidad_nombre_empresa'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="entidad_ruc" class="block text-sm font-semibold text-gray-700 mb-1">RUC</label>
                        <input type="text" id="entidad_ruc" name="entidad_ruc" value="<?php echo htmlspecialchars($practica['ruc'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="entidad_razon_social" class="block text-sm font-semibold text-gray-700 mb-1">Razón Social</label>
                        <input type="text" id="entidad_razon_social" name="entidad_razon_social" value="<?php echo htmlspecialchars($practica['razon_social'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="entidad_persona_contacto" class="block text-sm font-semibold text-gray-700 mb-1">Persona Contacto</label>
                        <input type="text" id="entidad_persona_contacto" name="entidad_persona_contacto" value="<?php echo htmlspecialchars($practica['persona_contacto'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="entidad_telefono_contacto" class="block text-sm font-semibold text-gray-700 mb-1">Teléfono Contacto</label>
                        <input type="text" id="entidad_telefono_contacto" name="entidad_telefono_contacto" value="<?php echo htmlspecialchars($practica['telefono_contacto'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="entidad_email_contacto" class="block text-sm font-semibold text-gray-700 mb-1">Email Contacto</label>
                        <input type="email" id="entidad_email_contacto" name="entidad_email_contacto" value="<?php echo htmlspecialchars($practica['email_contacto'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="plazas_disponibles" class="block text-sm font-semibold text-gray-700 mb-1">Vacantes</label>
                        <input type="number" min="0" id="plazas_disponibles" name="plazas_disponibles" value="<?php echo (int) ($practica['plazas_disponibles'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div class="md:col-span-2">
                        <label for="entidad_direccion" class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                        <input type="text" id="entidad_direccion" name="entidad_direccion" value="<?php echo htmlspecialchars($practica['direccion'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
            </section>

            <?php if (!$esHomologableLaboral): ?>
                <!-- TUTOR EDITABLE -->
                <section class="border border-gray-200 rounded-xl p-4 sm:p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Tutor Empresarial</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="tutor_emp_nombre_completo" class="block text-sm font-semibold text-gray-700 mb-1">Nombre Completo</label>
                            <input type="text" id="tutor_emp_nombre_completo" name="tutor_emp_nombre_completo" value="<?php echo htmlspecialchars($practica['tutor_emp_nombre_completo'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="tutor_emp_cedula" class="block text-sm font-semibold text-gray-700 mb-1">Cédula</label>
                            <input type="text" id="tutor_emp_cedula" name="tutor_emp_cedula" value="<?php echo htmlspecialchars($practica['tutor_emp_cedula'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="tutor_emp_funcion" class="block text-sm font-semibold text-gray-700 mb-1">Función/Cargo</label>
                            <input type="text" id="tutor_emp_funcion" name="tutor_emp_funcion" value="<?php echo htmlspecialchars($practica['tutor_emp_funcion'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="tutor_emp_telefono" class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                            <input type="text" id="tutor_emp_telefono" name="tutor_emp_telefono" value="<?php echo htmlspecialchars($practica['tutor_emp_telefono'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="tutor_emp_email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                            <input type="email" id="tutor_emp_email" name="tutor_emp_email" value="<?php echo htmlspecialchars($practica['tutor_emp_email'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label for="tutor_emp_departamento" class="block text-sm font-semibold text-gray-700 mb-1">Departamento</label>
                            <input type="text" id="tutor_emp_departamento" name="tutor_emp_departamento" value="<?php echo htmlspecialchars($practica['tutor_emp_departamento'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ESTADO EDITABLE -->
            <section class="border border-gray-200 rounded-xl p-4 sm:p-5">
                <label for="estado_fase_uno_completado" class="block text-base font-semibold text-gray-900 mb-3">
                    Estado Fase Uno
                </label>

                <select id="estado_fase_uno_completado"
                    name="estado_fase_uno_completado"
                    required
                    class="block w-full md:w-80 px-4 py-3 text-base border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                    <option value="0" <?php echo ((int)$practica['estado_fase_uno_completado'] === 0) ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="1" <?php echo ((int)$practica['estado_fase_uno_completado'] === 1) ? 'selected' : ''; ?>>Completado</option>
                </select>
            </section>

            <!-- Botones -->
            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-6 border-t border-gray-200">
                <a href="<?php echo $basePath; ?>/admin/practicas"
                    class="w-full sm:w-auto text-center px-6 py-3 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-100 font-semibold transition">
                    Cancelar
                </a>

                <button type="submit"
                    class="w-full sm:w-auto px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold shadow-md hover:shadow-lg transition duration-300">
                    Guardar Cambios
                </button>
            </div>

        </form>
    </div>
</main>