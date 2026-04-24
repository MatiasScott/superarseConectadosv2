<!-- Main Content -->
<main class="flex-grow py-8 w-full" style="padding-left:10%;padding-right:10%;">
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
    $activeTab = ($activeTab ?? 'datos') === 'actividades' ? 'actividades' : 'datos';
    $actividadesDiarias = $actividadesDiarias ?? [];
    $totalRegistros = (int) ($totalActividadesDiarias ?? count($actividadesDiarias));
    $totalHoras = (float) ($totalHorasActividades ?? 0);
    $activityPage = (int) ($activityPage ?? 1);
    $totalPages = (int) ($totalActivityPages ?? 1);
    $estadoPracticaActual = strtoupper(trim((string) ($practica['estado'] ?? 'ACTIVA')));
    if ($estadoPracticaActual === 'CANCELADA') {
        $estadoPracticaActual = 'NO FINALIZADO';
    }

    if (!function_exists('format_decimal_hours_hm_admin')) {
        function format_decimal_hours_hm_admin($decimalHours): string
        {
            $totalMinutes = (int) round(((float) $decimalHours) * 60);
            $hours = intdiv($totalMinutes, 60);
            $minutes = $totalMinutes % 60;
            return $hours . 'h ' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) . 'm';
        }
    }
    ?>
    <div class="bg-white shadow-2xl rounded-2xl p-6 sm:p-8 border border-gray-100">

        <div class="flex items-center justify-between gap-3 mb-2">
            <h2 class="text-2xl font-bold text-gray-900">
                Editar Pasantía
            </h2>
            <a href="<?php echo $basePath; ?>/admin/practicas"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100 font-semibold transition whitespace-nowrap">
                Volver
            </a>
        </div>

        <p class="text-gray-600 mb-8">
            Puedes modificar entidad y estado. Los datos bloqueados se muestran solo para consulta.
        </p>

        <section class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-800">Documento Fase 1</h3>
                    <p class="text-sm text-gray-600">Puedes revisar los datos mostrados en esta pantalla y descargar el PDF de Fase 1.</p>
                </div>

                <?php if (!empty($practica['ruc'])): ?>
                    <a href="<?php echo $basePath; ?>/pasantias/generatePdf/<?php echo (int) ($practica['id_practica'] ?? 0); ?>"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-superarse-morado-oscuro px-4 py-2 text-sm font-semibold text-white transition hover:bg-superarse-rosa">
                        Descargar Fase 1 (PDF)
                    </a>
                <?php else: ?>
                    <span class="inline-flex items-center rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-800">
                        Aún no hay datos completos de Fase 1 para descargar.
                    </span>
                <?php endif; ?>
            </div>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                <?php if (!empty($tienePlanAprendizaje)): ?>
                    <a href="<?php echo $basePath; ?>/admin/practicas/plan-aprendizaje/pdf/<?php echo (int) ($practica['id_practica'] ?? 0); ?>"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Descargar Plan de Aprendizaje (PDF)
                    </a>
                <?php else: ?>
                    <span class="inline-flex items-center rounded-lg bg-amber-100 px-3 py-2 text-sm font-medium text-amber-800">
                        Esta pasantía todavía no tiene registros guardados del plan de aprendizaje.
                    </span>
                <?php endif; ?>
            </div>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <aside class="lg:col-span-1">
                <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                    <button type="button" id="menuEditarPasantia"
                        class="w-full text-left px-4 py-3 transition <?php echo $activeTab === 'datos' ? 'bg-superarse-morado-claro text-superarse-morado-oscuro font-bold border-l-4 border-superarse-morado-oscuro' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        Editar pasantía
                    </button>
                    <button type="button" id="menuVerActividades"
                        class="w-full text-left px-4 py-3 border-t border-gray-200 transition <?php echo $activeTab === 'actividades' ? 'bg-superarse-morado-claro text-superarse-morado-oscuro font-bold border-l-4 border-superarse-morado-oscuro' : 'text-gray-700 hover:bg-gray-100'; ?>">
                        Ver actividades
                    </button>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <div id="panelEditarPasantia" class="<?php echo $activeTab === 'datos' ? '' : 'hidden'; ?>">
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
                                    <input type="text" id="entidad_ruc" name="entidad_ruc" value="<?php echo htmlspecialchars($practica['ruc'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
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
                                    <input type="number" id="plazas_disponibles" name="plazas_disponibles" value="<?php echo (int) ($practica['plazas_disponibles'] ?? 0); ?>" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="entidad_direccion" class="block text-sm font-semibold text-gray-700 mb-1">Dirección</label>
                                    <input type="text" id="entidad_direccion" name="entidad_direccion" value="<?php echo htmlspecialchars($practica['direccion'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                            </div>
                        </section>

                        <?php if (!$esHomologableLaboral): ?>
                            <!-- TUTOR EMPRESARIAL -->
                            <section class="border border-gray-200 rounded-xl p-4 sm:p-5">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800">Tutor Empresarial</h3>
                                    <button type="button" id="btnCambiarTutor"
                                        class="text-sm bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold px-3 py-1.5 rounded-lg transition">
                                        Cambiar tutor
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 italic mb-4">Si debe cambiar datos del nombre o cédula, póngase en contacto con el administrador del sistema.</p>

                                <!-- Tutor actual (solo lectura) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="tutorActualPanel">
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Nombre actual</label>
                                        <input type="text" value="<?php echo htmlspecialchars($practica['tutor_emp_nombre_completo'] ?? 'Sin tutor asignado'); ?>"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Cédula actual</label>
                                        <input type="text" value="<?php echo htmlspecialchars($practica['tutor_emp_cedula'] ?? ''); ?>"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Función</label>
                                        <input type="text" value="<?php echo htmlspecialchars($practica['tutor_emp_funcion'] ?? ''); ?>"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 mb-1">Email</label>
                                        <input type="text" value="<?php echo htmlspecialchars($practica['tutor_emp_email'] ?? ''); ?>"
                                            class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100" readonly>
                                    </div>
                                </div>

                                <!-- Panel de cambio (oculto por defecto) -->
                                <div id="panelCambioTutor" class="hidden border-t border-amber-200 pt-5 mt-5">
                                    <p class="text-sm text-amber-700 font-medium mb-3">
                                        Ingresa la cédula del nuevo tutor. Si ya existe en el sistema, sus datos se cargarán automáticamente.
                                    </p>

                                    <!-- Búsqueda por cédula -->
                                    <div class="flex gap-2 mb-4">
                                        <div class="flex-1">
                                            <label class="block text-sm font-semibold text-gray-700 mb-1">Cédula del nuevo tutor</label>
                                            <input type="text" id="cedulaBuscar" placeholder="Ej: 0912345678" maxlength="13"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" id="btnBuscarTutor"
                                                class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-semibold transition">
                                                Buscar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Feedback -->
                                    <div id="tutorSearchFeedback" class="hidden mb-4 text-sm rounded-lg px-3 py-2"></div>

                                    <!-- Formulario del nuevo tutor -->
                                    <div id="panelFormTutor" class="hidden">
                                        <input type="hidden" name="cambiar_tutor" value="1">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nombre Completo</label>
                                                <input type="text" id="tutor_emp_nombre_completo" name="tutor_emp_nombre_completo" readonly
                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Cédula</label>
                                                <input type="text" id="tutor_emp_cedula" name="tutor_emp_cedula" readonly
                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Función / Cargo</label>
                                                <input type="text" id="tutor_emp_funcion" name="tutor_emp_funcion"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                                                <input type="email" id="tutor_emp_email" name="tutor_emp_email"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Teléfono</label>
                                                <input type="text" id="tutor_emp_telefono" name="tutor_emp_telefono" readonly
                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-1">Departamento</label>
                                                <input type="text" id="tutor_emp_departamento" name="tutor_emp_departamento" readonly
                                                    class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <script>
                                (function() {
                                    const btnCambiar = document.getElementById('btnCambiarTutor');
                                    const panel = document.getElementById('panelCambioTutor');
                                    const btnBuscar = document.getElementById('btnBuscarTutor');
                                    const feedback = document.getElementById('tutorSearchFeedback');
                                    const panelForm = document.getElementById('panelFormTutor');

                                    btnCambiar.addEventListener('click', function() {
                                        const oculto = panel.classList.toggle('hidden');
                                        this.textContent = oculto ? 'Cambiar tutor' : 'Cancelar cambio';
                                        if (oculto) {
                                            panelForm.classList.add('hidden');
                                            feedback.classList.add('hidden');
                                            document.getElementById('cedulaBuscar').value = '';
                                        }
                                    });

                                    btnBuscar.addEventListener('click', function() {
                                        const cedula = document.getElementById('cedulaBuscar').value.trim();
                                        if (!cedula) {
                                            showFeedback('Ingresa una cédula para buscar.', 'warn');
                                            return;
                                        }

                                        showFeedback('Buscando...', 'info');
                                        panelForm.classList.add('hidden');

                                        const url = '<?= htmlspecialchars($basePath) ?>/admin/tutores/buscar-por-cedula?cedula=' + encodeURIComponent(cedula);
                                        fetch(url)
                                            .then(function(r) {
                                                return r.json();
                                            })
                                            .then(function(data) {
                                                if (data.found) {
                                                    showFeedback('Tutor encontrado. Puede ajustar sus datos antes de guardar.', 'ok');
                                                    fillForm(data.tutor);
                                                } else {
                                                    showFeedback('Tutor no encontrado. Complete los datos para registrarlo como nuevo.', 'warn');
                                                    fillForm({
                                                        cedula: cedula
                                                    });
                                                }
                                                panelForm.classList.remove('hidden');
                                            })
                                            .catch(function() {
                                                showFeedback('Error al conectar. Intente de nuevo.', 'err');
                                            });
                                    });

                                    function showFeedback(msg, type) {
                                        const classes = {
                                            info: 'bg-gray-100 text-gray-600',
                                            ok: 'bg-green-100 text-green-800',
                                            warn: 'bg-amber-100 text-amber-800',
                                            err: 'bg-red-100 text-red-700',
                                        };
                                        feedback.className = 'mb-4 text-sm rounded-lg px-3 py-2 ' + (classes[type] || classes.info);
                                        feedback.textContent = msg;
                                        feedback.classList.remove('hidden');
                                    }

                                    function fillForm(t) {
                                        document.getElementById('tutor_emp_cedula').value = t.cedula || '';
                                        document.getElementById('tutor_emp_nombre_completo').value = t.nombre_completo || '';
                                        document.getElementById('tutor_emp_funcion').value = t.funcion || '';
                                        document.getElementById('tutor_emp_telefono').value = t.telefono || '';
                                        document.getElementById('tutor_emp_email').value = t.email || '';
                                        document.getElementById('tutor_emp_departamento').value = t.departamento || '';
                                    }
                                }());
                            </script>
                        <?php endif; ?>

                        <!-- ESTADO EDITABLE -->
                        <section class="border border-gray-200 rounded-xl p-4 sm:p-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                <div>
                                    <label for="estado_fase_uno_completado" class="block text-base font-semibold text-gray-900 mb-3">
                                        Fase
                                    </label>

                                    <select id="estado_fase_uno_completado"
                                        name="estado_fase_uno_completado"
                                        required
                                        class="block w-full px-4 py-3 text-base border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                        <option value="0" <?php echo ((int)$practica['estado_fase_uno_completado'] === 0) ? 'selected' : ''; ?>>Fase 1</option>
                                        <option value="1" <?php echo ((int)$practica['estado_fase_uno_completado'] === 1) ? 'selected' : ''; ?>>Fase 2</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="estado" class="block text-base font-semibold text-gray-900 mb-3">
                                        Estado de práctica
                                    </label>
                                    <select id="estado" name="estado"
                                        class="block w-full px-4 py-3 text-base border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                                        <option value="ACTIVA" <?php echo $estadoPracticaActual === 'ACTIVA' ? 'selected' : ''; ?>>ACTIVA</option>
                                        <option value="FINALIZADA" <?php echo $estadoPracticaActual === 'FINALIZADA' ? 'selected' : ''; ?>>FINALIZADA</option>
                                        <option value="NO FINALIZADO" <?php echo $estadoPracticaActual === 'NO FINALIZADO' ? 'selected' : ''; ?>>NO FINALIZADO</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="fecha_fin_visual" class="block text-base font-semibold text-gray-900 mb-3">
                                        Fecha fin
                                    </label>
                                    <input type="text" id="fecha_fin_visual"
                                        value="<?php echo !empty($practica['fecha_fin']) ? htmlspecialchars(date('d/m/Y', strtotime($practica['fecha_fin']))) : 'No definida'; ?>"
                                        readonly
                                        class="block w-full px-4 py-3 text-base border-2 border-gray-200 bg-gray-100 rounded-xl text-gray-700">
                                </div>
                            </div>

                            <div class="mt-5">
                                <label for="observacion" class="block text-base font-semibold text-gray-900 mb-2">
                                    Observación
                                </label>
                                <textarea
                                    id="observacion"
                                    name="observacion"
                                    rows="4"
                                    placeholder="Escribe el motivo de no finalización/finalización o comentarios adicionales"
                                    class="block w-full px-4 py-3 text-base border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition"><?php echo htmlspecialchars((string) ($practica['observacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <p class="mt-2 text-xs text-gray-500">
                                    Puedes usar este campo para explicar por qué se finaliza o no se finaliza la práctica, o para dejar notas de seguimiento.
                                </p>
                            </div>
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

                <div id="panelVerActividades" class="space-y-6 <?php echo $activeTab === 'actividades' ? '' : 'hidden'; ?>">
                    <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700">Actividades Diarias</h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-superarse-morado-claro text-superarse-morado-oscuro">
                                    Total de horas: <strong class="ml-1"><?php echo htmlspecialchars(format_decimal_hours_hm_admin($totalHoras), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                                    Registradas: <strong class="ml-1"><?php echo (int) $totalRegistros; ?></strong>
                                </span>
                            </div>
                        </div>
                        <a href="<?php echo $basePath; ?>/pasantias/generateActividadesPdf/<?php echo (int) ($practica['id_practica'] ?? 0); ?>"
                            target="_blank"
                            class="bg-red-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-150 inline-flex items-center justify-center gap-2">
                            Descargar PDF
                        </a>
                    </div>

                    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fecha</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actividad</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Inicio</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fin</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Horas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php if (!empty($actividadesDiarias)): ?>
                                        <?php foreach ($actividadesDiarias as $actividad): ?>
                                            <tr class="hover:bg-gray-50 transition duration-150">
                                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                    <?php echo date('d/m/Y', strtotime($actividad['fecha_actividad'])); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700">
                                                    <div class="max-w-xs">
                                                        <?php echo htmlspecialchars($actividad['actividad_realizada']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($actividad['hora_inicio']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                    <?php echo htmlspecialchars($actividad['hora_fin']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap text-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars(format_decimal_hours_hm_admin($actividad['horas_invertidas']), ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                                No hay actividades registradas aún.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <div class="mt-4 flex items-center justify-between text-sm text-gray-600">
                            <span>Página <?php echo (int) $activityPage; ?> de <?php echo (int) $totalPages; ?></span>
                            <div class="flex items-center gap-2">
                                <?php if ($activityPage > 1): ?>
                                    <a href="<?php echo $basePath; ?>/admin/practicas/editar/<?php echo (int) ($practica['id_practica'] ?? 0); ?>?tab=actividades&activity_page=<?php echo (int) ($activityPage - 1); ?>"
                                        class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">Anterior</a>
                                <?php endif; ?>

                                <?php if ($activityPage < $totalPages): ?>
                                    <a href="<?php echo $basePath; ?>/admin/practicas/editar/<?php echo (int) ($practica['id_practica'] ?? 0); ?>?tab=actividades&activity_page=<?php echo (int) ($activityPage + 1); ?>"
                                        class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">Siguiente</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    (function() {
        var btnDatos = document.getElementById('menuEditarPasantia');
        var btnActividades = document.getElementById('menuVerActividades');
        var panelDatos = document.getElementById('panelEditarPasantia');
        var panelActividades = document.getElementById('panelVerActividades');

        if (!btnDatos || !btnActividades || !panelDatos || !panelActividades) {
            return;
        }

        function activarDatos() {
            panelDatos.classList.remove('hidden');
            panelActividades.classList.add('hidden');

            btnDatos.classList.add('bg-superarse-morado-claro', 'text-superarse-morado-oscuro', 'font-bold', 'border-l-4', 'border-superarse-morado-oscuro');
            btnDatos.classList.remove('text-gray-700', 'hover:bg-gray-100');

            btnActividades.classList.remove('bg-superarse-morado-claro', 'text-superarse-morado-oscuro', 'font-bold', 'border-l-4', 'border-superarse-morado-oscuro');
            btnActividades.classList.add('text-gray-700', 'hover:bg-gray-100');
        }

        function activarActividades() {
            panelActividades.classList.remove('hidden');
            panelDatos.classList.add('hidden');

            btnActividades.classList.add('bg-superarse-morado-claro', 'text-superarse-morado-oscuro', 'font-bold', 'border-l-4', 'border-superarse-morado-oscuro');
            btnActividades.classList.remove('text-gray-700', 'hover:bg-gray-100');

            btnDatos.classList.remove('bg-superarse-morado-claro', 'text-superarse-morado-oscuro', 'font-bold', 'border-l-4', 'border-superarse-morado-oscuro');
            btnDatos.classList.add('text-gray-700', 'hover:bg-gray-100');
        }

        btnDatos.addEventListener('click', activarDatos);
        btnActividades.addEventListener('click', activarActividades);
    }());
</script>