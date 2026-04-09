<div id="pasantias" class="tab-pane hidden">
    <h2 class="text-3xl font-bold text-superarse-morado-oscuro mb-6 border-b pb-2">Gestión de Prácticas
        Pre-Profesionales</h2>

    <div class="flex justify-between items-center mb-6 border-b pb-2">

        <?php if (!empty($data['infoPractica']['ruc'])): // Solo si la Fase 1 está completa
        ?>
            <?php $id_practica = htmlspecialchars($data['infoPractica']['id_practica'] ?? ''); ?>

            <a href="<?php echo $this->basePath; ?>/pasantias/generatePdf/<?php echo $id_practica; ?>"
                target="_blank"
                class="ml-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-superarse-morado-oscuro hover:bg-superarse-rosa focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-superarse-morado-oscuro transition duration-300">
                <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
            </a>
        <?php endif; ?>
    </div>

    <!-- ###################################################################### -->
    <!--                                 FASE 1: REGISTRO INICIAL               -->
    <!-- ###################################################################### -->
    <div style="<?php
                if (isset($data['infoPractica'])) {
                    echo 'display:none;';
                } else {
                    echo 'display:block;';
                }
                ?>">
        <div class="bg-yellow-100 border-l-4 border-superarse-rosa text-gray-800 p-4 mb-6 rounded-md">
            <p class="font-bold">FASE 1: REGISTRO PENDIENTE</p>
            <p class="text-sm">Completa el formulario de registro y asignación para desbloquear la Fase 2.
            </p>
        </div>
    </div>

    <form action="<?php echo $this->basePath; ?>/pasantias/saveFaseOne" method="POST"
        class="space-y-8 p-6 bg-white rounded-xl shadow-lg border border-gray-100">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($data['csrfTokenFaseOne'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <h3 class="text-xl font-semibold text-superarse-morado-medio">1. Información del Estudiante</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm bg-gray-50 p-4 rounded-lg">
            <p><strong>Código:</strong>
                <?php echo htmlspecialchars($data['infoPersonal']['codigo_matricula'] ?? 'N/D'); ?></p>
            <p><strong>Número de Identificación:</strong>
                <?php echo htmlspecialchars($data['infoPersonal']['numero_identificacion'] ?? 'N/D'); ?>
            </p>
            <p class="md:col-span-3"><strong>Nombre:</strong>
                <?php echo htmlspecialchars($data['nombreCompleto']); ?></p>
            <p><strong>Correo Inst.:</strong>
                <?php echo htmlspecialchars($data['infoPersonal']['usuario'] ?? 'N/D'); ?>
            </p>
            <p><strong>Nivel:</strong>
                <?php echo htmlspecialchars($data['infoPersonal']['nivel'] ?? 'N/D'); ?></p>
            <p><strong>Carrera/Campus:</strong>
                <?php echo htmlspecialchars($data['infoPersonal']['programa'] ?? 'N/D'); ?>
                <?php echo htmlspecialchars($data['infoPersonal']['sede'] ?? 'N/D'); ?></p>
        </div>

        <hr class="border-superarse-morado-medio/20">

        <h3 class="text-xl font-semibold text-superarse-morado-medio">2. Selección de Práctica y
            Asignaciones</h3>

        <!-- Docente Asignado -->
        <div>
            <label for="tutor_academico" class="block text-gray-700 font-medium mb-2">
                Su Tutor Académico es <span class="text-superarse-rosa">*</span>
            </label>

            <?php if ($data['cantidadTutores'] > 1): ?>
                <!-- cuando hay múltiples tutores -->
                <select
                    id="tutor_academico"
                    name="tutor_academico"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-superarse-rosa focus:border-transparent"
                    required
                    onchange="actualizarInfoTutor()">
                    <option value="" data-email="" data-telefono="">Seleccione un tutor académico</option>
                    <?php foreach ($data['tutoresAcademicos'] as $tutor): ?>
                        <option
                            value="<?php echo htmlspecialchars($tutor['id'] ?? ''); ?>"
                            data-email="<?php echo htmlspecialchars($tutor['email'] ?? ''); ?>"
                            data-telefono="<?php echo htmlspecialchars($tutor['telefono'] ?? ''); ?>"
                            data-nombre="<?php echo htmlspecialchars($tutor['nombre_completo'] ?? ''); ?>">
                            <?php echo htmlspecialchars($tutor['nombre_completo'] ?? 'Sin nombre'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php elseif ($data['cantidadTutores'] == 1): ?>
                <!-- Input readonly cuando hay solo un tutor -->
                <input
                    type="text"
                    id="tutor_academico"
                    name="tutor_academico"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                    readonly
                    value="<?php echo htmlspecialchars($data['tutoresAcademicos'][0]['nombre_completo'] ?? 'N/D'); ?>">
                <input type="hidden" name="tutor_academico_id" value="<?php echo htmlspecialchars($data['tutoresAcademicos'][0]['id'] ?? ''); ?>">
            <?php else: ?>
                <!-- Input cuando no hay tutores -->
                <input
                    type="text"
                    id="tutor_academico"
                    name="tutor_academico"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                    readonly
                    value="N/D - Sin tutor asignado">
            <?php endif; ?>
        </div>

        <div>
            <label for="correo_tutor" class="block text-gray-700 font-medium mb-2">
                Correo del Tutor Académico
            </label>
            <input
                type="email"
                id="correo_tutor"
                name="correo_tutor"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50"
                readonly
                value="<?php
                        if ($data['cantidadTutores'] == 1) {
                            echo htmlspecialchars($data['tutoresAcademicos'][0]['email'] ?? 'N/D');
                        } else {
                            echo 'N/D';
                        }
                        ?>">
        </div>

        <!-- Modalidad -->
        <div>
            <label for="modalidad" class="block text-gray-700 font-medium mb-2">
                Escoja la Modalidad de Práctica <span class="text-superarse-rosa">*</span>
            </label>
            <select id="modalidad" name="modalidad" required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                <?php echo !empty($data['infoPractica']['modalidad']) ? 'disabled' : ''; ?>>

                <?php if (!empty($data['infoPractica']['id_practica_modalidad'])): ?>
                    <!-- Si ya hay una práctica registrada, mostrar la modalidad seleccionada -->
                    <option value="<?php echo htmlspecialchars($data['infoPractica']['id_practica_modalidad']); ?>" selected>
                        <?php echo htmlspecialchars($data['infoPractica']['modalidad']); ?>
                    </option>
                <?php else: ?>
                    <!-- Si no hay práctica, mostrar opción de placeholder -->
                    <option value="">-- Seleccione una opción --</option>
                <?php endif; ?>

                <?php if (!empty($data['modalidades']) && is_array($data['modalidades'])): ?>
                    <?php foreach ($data['modalidades'] as $modalidad): ?>
                        <?php
                        // No duplicar la modalidad ya seleccionada
                        $yaSeleccionada = !empty($data['infoPractica']['id_practica_modalidad']) &&
                            $data['infoPractica']['id_practica_modalidad'] == $modalidad['id_practica_modalidad'];
                        if ($yaSeleccionada) continue;
                        ?>
                        <option value="<?php echo htmlspecialchars($modalidad['id_practica_modalidad']); ?>">
                            <?php echo htmlspecialchars($modalidad['modalidad']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <?php if (!empty($data['infoPractica']['modalidad'])): ?>
                <!-- Campo hidden para enviar la modalidad si el select está disabled -->
                <input type="hidden" name="modalidad" value="<?php echo htmlspecialchars($data['infoPractica']['id_practica_modalidad']); ?>">
            <?php endif; ?>
        </div>

        <hr class="border-superarse-morado-medio/20">

        <!-- Registro de Empresa (Entidad) -->
        <div id="seccion-empresa" class="hidden">
            <h3 class="text-xl font-semibold text-superarse-morado-medio">3. Registro de Empresa / Institución</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- RUC de la Empresa -->
                <div class="md:col-span-2">
                    <label for="entidad_ruc" class="block text-gray-700 font-medium mb-2">
                        RUC de la Empresa <span class="text-superarse-rosa">*</span>
                    </label>
                    <div class="flex gap-2 relative">
                        <input type="text" id="entidad_ruc" name="entidad_ruc" required
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                            placeholder="Ingrese el RUC de la empresa"
                            value="<?php echo htmlspecialchars($data['infoPractica']['ruc'] ?? ''); ?>"
                            <?php echo !empty($data['infoPractica']['ruc']) ? 'disabled' : ''; ?>>

                        <?php if (empty($data['infoPractica']['ruc'])): ?>
                            <button type="button" id="btn_buscar_ruc"
                                class="px-6 py-2 bg-superarse-morado-medio text-white rounded-lg hover:bg-superarse-morado-oscuro transition duration-300 flex items-center gap-2">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        <?php endif; ?>
                    </div>
                    <div id="mensaje_busqueda_ruc" class="mt-2 text-sm"></div>
                    <!-- Resultado de la búsqueda -->
                    <div id="entidad_resultado" class="mt-3 p-3 rounded-lg hidden animate-slide-in-up">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-building text-superarse-morado-medio text-lg"></i>
                            <span class="text-sm font-semibold text-gray-700">Empresa Encontrada:</span>
                        </div>
                        <p id="entidad_nombre_resultado" class="text-sm font-bold text-superarse-morado-medio ml-6"></p>
                    </div>
                    <small class="text-gray-500 text-xs mt-1 block">
                        <i class="fas fa-info-circle"></i> Busque la empresa en nuestra base de datos o ingrese los datos manualmente
                    </small>
                </div>

                <!-- Nombre de la Empresa -->
                <div>
                    <label for="entidad_nombre_empresa" class="block text-gray-700 font-medium mb-2">
                        Nombre de la Empresa <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="entidad_nombre_empresa" name="entidad_nombre_empresa" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Nombre comercial de la empresa" value="<?php echo htmlspecialchars($data['infoPractica']['nombre_empresa'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['nombre_empresa']) ? 'disabled' : ''; ?>>
                </div>

                <!-- Razón Social -->
                <div>
                    <label for="entidad_razon_social" class="block text-gray-700 font-medium mb-2">
                        Razón Social <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="entidad_razon_social" name="entidad_razon_social" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Razón social de la empresa" value="<?php echo htmlspecialchars($data['infoPractica']['razon_social'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['razon_social']) ? 'disabled' : ''; ?>>
                </div>

                <!-- Persona de Contacto -->
                <div id="EntidadPersonaContacto">
                    <label for="entidad_persona_contacto" class="block text-gray-700 font-medium mb-2">
                        Persona de Contacto <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="entidad_persona_contacto" name="entidad_persona_contacto" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Nombre completo del contacto" value="<?php echo htmlspecialchars($data['infoPractica']['persona_contacto'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['persona_contacto']) ? 'disabled' : ''; ?>>
                </div>

                <!-- Teléfono de Contacto -->
                <div id="EntidadTelefonoContacto">
                    <label for="entidad_telefono_contacto" class="block text-gray-700 font-medium mb-2">
                        Teléfono de Contacto <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="entidad_telefono_contacto" name="entidad_telefono_contacto" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Teléfono de la empresa" value="<?php echo htmlspecialchars($data['infoPractica']['telefono_contacto'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['telefono_contacto']) ? 'disabled' : ''; ?>>
                </div>

                <!-- Email de Contacto -->
                <div id="EntidadEmailContacto">
                    <label for="entidad_email_contacto" class="block text-gray-700 font-medium mb-2">
                        Email de Contacto <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="email" id="entidad_email_contacto" name="entidad_email_contacto" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="email@empresa.com" value="<?php echo htmlspecialchars($data['infoPractica']['email_contacto'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['email_contacto']) ? 'disabled' : ''; ?>>
                </div>


                <div id="EntidadPlazasDisponibles">
                    <label for="plazas_disponibles" class="block text-gray-700 font-medium mb-2">
                        Vacantes Disponibles <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="number" id="plazas_disponibles" name="plazas_disponibles"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Ingrese el número de vacantes"
                        value="<?php echo htmlspecialchars($data['infoPractica']['plazas_disponibles'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['plazas_disponibles']) ? 'readonly' : ''; ?>>
                </div>

                <div id="EntidadAfiliacionIESS" style="display: none;">
                    <label for="afiliacion_iees" class="block text-gray-700 font-medium mb-2">
                        Años o meses de afiliación al IESS <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="afiliacion_iees" name="afiliacion_iees"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Ej: 1 año, 6 meses, etc."
                        value="<?php echo htmlspecialchars($data['infoPractica']['afiliacion_iess'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['afiliacion_iess']) ? 'readonly' : ''; ?>>
                </div>

                <!-- Dirección (opcional) -->
                <div class="md:col-span-2">
                    <label for="entidad_direccion" class="block text-gray-700 font-medium mb-2">
                        Dirección
                    </label>
                    <input type="text" id="entidad_direccion" name="entidad_direccion"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Dirección completa de la empresa" value="<?php echo htmlspecialchars($data['infoPractica']['direccion'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['direccion']) ? 'disabled' : ''; ?>>
                </div>

                <div id="TablaProyectos" class="md:col-span-2 overflow-x-auto">
                    <table style="width: 100%; min-width: 760px; border-collapse: collapse; font-family: Arial, sans-serif;">
                        <thead>
                            <tr>
                                <th style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">Proyecto</th>
                                <th style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">Carreras</th>
                                <th style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">Lugar</th>
                                <th style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">Seleccionar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($data['infoProyectos'])): ?>
                                <?php foreach ($data['infoProyectos'] as $key => $proyecto): ?>
                                    <tr>
                                        <td style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">
                                            <?php echo htmlspecialchars($proyecto['descripcion']); ?>
                                        </td>
                                        <td style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">
                                            <?php echo htmlspecialchars($proyecto['carreras']); ?>
                                        </td>
                                        <td style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">
                                            <?php echo htmlspecialchars($proyecto['lugar']); ?>
                                        </td>
                                        <td style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">
                                            <input
                                                type="radio"
                                                name="proyecto_seleccionado"
                                                value="<?php echo htmlspecialchars($proyecto['id']); ?>"
                                                required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="border: 2px solid #000; padding: 10px; text-align: left; vertical-align: top; background-color: #ffffffff; color: #000000ff;">No hay proyectos disponibles.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <hr class="border-superarse-morado-medio/20">

        <!-- Registro del Tutor Empresarial -->
        <div id="seccion-tutor-empresa" class="hidden">
            <h3 id="labelInfoTutor" class="text-xl font-semibold text-superarse-morado-medio">4. Información del Tutor Empresarial</h3>
            <div id="informacion-tutor-empresarial" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tutor_emp_nombre_completo" class="block text-gray-700 font-medium mb-2">
                        Nombre Completo del Tutor <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="tutor_emp_nombre_completo" name="tutor_emp_nombre_completo" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Nombre completo del tutor" value="<?php echo htmlspecialchars($data['infoPractica']['nombre_completo'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['nombre_completo']) ? 'disabled' : ''; ?>>
                </div>

                <div>
                    <label for="tutor_emp_cedula" class="block text-gray-700 font-medium mb-2">
                        Cédula del Tutor <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="tutor_emp_cedula" name="tutor_emp_cedula" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Número de cédula" value="<?php echo htmlspecialchars($data['infoPractica']['cedula'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['cedula']) ? 'disabled' : ''; ?>>
                </div>

                <div>
                    <label for="tutor_emp_funcion" class="block text-gray-700 font-medium mb-2">
                        Función / Cargo <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="tutor_emp_funcion" name="tutor_emp_funcion" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Cargo del tutor" value="<?php echo htmlspecialchars($data['infoPractica']['funcion'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['funcion']) ? 'disabled' : ''; ?>>
                </div>

                <div>
                    <label for="tutor_emp_email" class="block text-gray-700 font-medium mb-2">
                        Email del Tutor <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="email" id="tutor_emp_email" name="tutor_emp_email" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="email@tutor.com" value="<?php echo htmlspecialchars($data['infoPractica']['email'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['email']) ? 'disabled' : ''; ?>>
                </div>

                <div>
                    <label for="tutor_emp_telefono" class="block text-gray-700 font-medium mb-2">
                        Teléfono <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="tutor_emp_telefono" name="tutor_emp_telefono" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Teléfono del tutor" value="<?php echo htmlspecialchars($data['infoPractica']['telefono'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['telefono']) ? 'disabled' : ''; ?>>
                </div>

                <div>
                    <label for="tutor_emp_departamento" class="block text-gray-700 font-medium mb-2">
                        Departamento/Área <span class="text-superarse-rosa">*</span>
                    </label>
                    <input type="text" id="tutor_emp_departamento" name="tutor_emp_departamento" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"
                        placeholder="Departamento del tutor" value="<?php echo htmlspecialchars($data['infoPractica']['departamento'] ?? ''); ?>"
                        <?php echo !empty($data['infoPractica']['departamento']) ? 'disabled' : ''; ?>>
                </div>
            </div>
        </div>

        <?php
        // Verificamos si la práctica ya tiene el RUC de la empresa asociado.
        // Asumimos que si tiene RUC, la Fase 1 está completa y ya NO se debe guardar, sino descargar.
        $fase_uno_completada = !empty($data['infoPractica']['ruc']);
        ?>

        <?php if (!$fase_uno_completada): ?>
            <button type="submit"
                class="w-full bg-superarse-rosa hover:bg-superarse-morado-medio text-white font-bold py-3 rounded-lg transition duration-300 mt-6">
                Guardar Registro e Iniciar Práctica (Fase 1 Completa)
            </button>

        <?php else: ?>
            <?php
            // Obtenemos el ID de la práctica, necesario para la URL del controlador
            $id_practica = htmlspecialchars($data['infoPractica']['id_practica'] ?? '');

            // Verificación de seguridad: solo mostrar si tenemos un ID
            if (!empty($id_practica)):
            ?>
                <a href="<?php echo $this->basePath; ?>/pasantias/generatePdf/<?php echo $id_practica; ?>"
                    target="_blank"
                    class="inline-block w-full text-center bg-superarse-morado-oscuro hover:bg-superarse-rosa text-white font-bold py-3 rounded-lg transition duration-300 mt-6">
                    <i class="fas fa-file-pdf mr-2"></i> Descargar Registro de Práctica (Fase 1)
                </a>
            <?php endif; ?>

        <?php endif; ?>

    </form>

    <!-- ###################################################################### -->
    <!--                               FASE 2: SEGUIMIENTO Y GESTIÓN                         -->
    <!-- ###################################################################### -->
    <div style="<?php
                if (isset($data['infoPractica']) && isset($data['infoStatusPractica']['estado_fase_uno_completado']) && $data['infoStatusPractica']['estado_fase_uno_completado'] == 1) {
                    echo 'display:block;';
                } else {
                    echo 'display:none;';
                }
                ?>">
        <div class="bg-green-100 border-l-4 border-green-500 text-gray-800 p-4 mb-6 rounded-lg">
            <p class="font-bold">FASE 2: EN EJECUCIÓN</p>
            <p class="text-sm">Tu registro ha sido aprobado. Gestiona tu práctica a continuación.</p>
        </div>
    </div>

    <?php
    $activeTab = $_GET['tab'] ?? 'programa';
    $allowedTabs = ['programa', 'actividades', 'calificaciones', 'manual'];
    if (!in_array($activeTab, $allowedTabs, true)) {
        $activeTab = 'programa';
    }
    ?>

    <div style="<?php
                if (isset($data['infoPractica']) && isset($data['infoStatusPractica']['estado_fase_uno_completado']) && $data['infoStatusPractica']['estado_fase_uno_completado'] == 1) {
                    echo 'display:block;';
                } else {
                    echo 'display:none;';
                }
                ?>" x-data="{ 
        currentTab: '<?php echo htmlspecialchars($activeTab, ENT_QUOTES, 'UTF-8'); ?>',
        scrollToActividades() {
            if (this.currentTab === 'actividades') {
                this.$nextTick(() => {
                    const section = document.getElementById('section-actividades-diarias');
                    if (section) {
                        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            }
        }
    }">

        <!-- Menú de pestañas -->
        <div class="flex border-b border-gray-200 overflow-x-auto">
            <button @click="currentTab = 'programa'"
                :class="{ 'border-superarse-morado-oscuro text-superarse-morado-oscuro font-bold': currentTab === 'programa' }"
                class="flex-shrink-0 py-2 px-4 text-gray-600 border-b-2 border-transparent hover:border-superarse-morado-medio hover:text-superarse-morado-medio transition duration-150 rounded-t-lg">
                <i class="fas fa-list-check mr-2"></i> Plan de Aprendizaje
            </button>

            <button @click="currentTab = 'actividades'; scrollToActividades()"
                :class="{ 'border-superarse-morado-oscuro text-superarse-morado-oscuro font-bold': currentTab === 'actividades' }"
                class="flex-shrink-0 py-2 px-4 text-gray-600 border-b-2 border-transparent hover:border-superarse-morado-medio hover:text-superarse-morado-medio transition duration-150 rounded-t-lg">
                <i class="fas fa-calendar-check mr-2"></i> Actividades Diarias
            </button>

            <button @click="currentTab = 'calificaciones'"
                :class="{ 'border-superarse-morado-oscuro text-superarse-morado-oscuro font-bold': currentTab === 'calificaciones' }"
                class="flex-shrink-0 py-2 px-4 text-gray-600 border-b-2 border-transparent hover:border-superarse-morado-medio hover:text-superarse-morado-medio transition duration-150 rounded-t-lg">
                <i class="fas fa-graduation-cap mr-2"></i> Calificaciones
            </button>

            <button @click="currentTab = 'manual'"
                :class="{ 'border-superarse-morado-oscuro text-superarse-morado-oscuro font-bold': currentTab === 'manual' }"
                class="flex-shrink-0 py-2 px-4 text-gray-600 border-b-2 border-transparent hover:border-superarse-morado-medio hover:text-superarse-morado-medio transition duration-150 rounded-t-lg">
                <i class="fas fa-book-open mr-2"></i> Documentación
            </button>
        </div>

        <div class="p-4 bg-gray-50 border border-gray-200 rounded-b-lg">
            <div x-show="currentTab === 'programa'">
                <?php
                // Preparar datos para programa_trabajo.php
                $practicaId = $data['infoPractica']['id_practica'] ?? 0;
                $programaTrabajo = $data['programaTrabajo'] ?? [];
                $basePath = $data['basePath'] ?? '';
                include __DIR__ . '/../estudiantes/programa_trabajo.php';
                ?>
            </div>


            <div x-show="currentTab === 'actividades'" id="section-actividades-diarias">
                <?php
                // Preparar datos para actividades_diarias.php
                $practicaId = $data['infoPractica']['id_practica'] ?? 0;
                $actividadesDiarias = $data['actividadesDiarias'] ?? [];
                $basePath = $data['basePath'] ?? '';
                $totalRegistros = (int) ($data['totalActividadesDiarias'] ?? count($actividadesDiarias));
                $activityPage = (int) ($data['activityPage'] ?? 1);
                $totalPages = (int) ($data['totalActivityPages'] ?? 1);
                $limit = (int) ($data['activityLimit'] ?? 10);
                $offset = max(0, ($activityPage - 1) * $limit);
                $search = '';
                $mensaje = $_SESSION['mensaje'] ?? null;
                unset($_SESSION['mensaje']);
                include __DIR__ . '/../estudiantes/actividades_diarias.php';
                ?>
            </div>

            <div x-show="currentTab === 'calificaciones'">
                <p>Aquí verás tus <strong>Calificaciones</strong>.
                    <a href="https://site2.q10.com/login?ReturnUrl=%2F&aplentId=610f5afd-3e65-4c60-9932-bff02c235882"
                        target="_blank"
                        class="text-blue-600 hover:underline font-semibold">
                        Acceder a Q10 →
                    </a>
                </p>
            </div>

            <div x-show="currentTab === 'manual'">
                <div style="font-size: 80%;">
                    <h2 class="text-2xl font-extrabold text-purple-800 mb-4">Manuales y Video Tutoriales</h2>
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Manual Usuario -->
                        <div class="flex-1 bg-gray-50 border border-gray-200 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center gap-2 mb-2">
                                <!-- Icono PDF -->
                                <svg class="text-red-500 w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6zm7 1.5V9a1 1 0 0 0 1 1h5.5L13 3.5z" />
                                </svg>
                                <span class="text-lg md:text-xl font-semibold text-purple-700">Manual de Usuario del Proceso</span>
                            </div>
                            <p class="text-gray-600 mb-4 text-sm md:text-base">
                                Descarga el manual completo para conocer los lineamientos de las prácticas.
                            </p>
                            <a href="URL_DEL_MANUAL.pdf" target="_blank" class="inline-flex items-center gap-1 text-pink-600 font-bold text-base hover:underline">
                                Abrir Manual (PDF)
                                <!-- Icono Descarga -->
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 3a1 1 0 1 0 2 0v7.586l2.293-2.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 1.414-1.414L9 10.586V3z" />
                                    <path d="M5 18a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-2a1 1 0 1 0-2 0v1H7v-1a1 1 0 1 0-2 0v2z" />
                                </svg>
                            </a>
                        </div>
                        <!-- Video Tutorial -->
                        <div class="flex-1 bg-gray-50 border border-gray-200 rounded-xl p-6 shadow-sm">
                            <div class="flex items-center gap-2 mb-2">
                                <!-- Icono Video -->
                                <svg class="text-blue-500 w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M21 7l-5.197 2.598A1 1 0 0 1 14 10.382V13.618a1 1 0 0 1 1.803.784L21 17M5 7h12a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z" />
                                </svg>
                                <span class="text-lg md:text-xl font-semibold text-purple-700">Video Tutorial (Registro de Actividades)</span>
                            </div>
                            <p class="text-gray-600 mb-4 text-sm md:text-base">
                                Mira el video que explica cómo registrar tus actividades diarias correctamente.
                            </p>
                            <a href="URL_DEL_VIDEO" target="_blank" class="inline-flex items-center gap-1 text-pink-600 font-bold text-base hover:underline">
                                Ver Video Ahora
                                <!-- Icono Play -->
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" fill="currentColor" />
                                    <polygon points="10,8 16,12 10,16" fill="#fff" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="mt-4 p-3 border rounded-lg
            <?php echo strpos($_SESSION['mensaje'], 'Error') !== false ? 'bg-red-100 border-red-300 text-red-700' : 'bg-green-100 border-green-300 text-green-700'; ?>">
                    <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
                </div>
                <?php unset($_SESSION['mensaje']); ?>
            <?php endif; ?> -->
        </div>
    </div>
</div>