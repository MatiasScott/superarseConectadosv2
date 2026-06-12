<div class="max-w-4xl mx-auto py-2 px-1">
    <div class="mb-4">
        <a href="<?php echo $basePath; ?>/admin/auditoria-fase-dos"
            class="inline-block text-white px-4 py-2 rounded-lg font-semibold transition hover:opacity-90"
            style="background-color: #4a2c5e;">
            ← Volver a Auditoría
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-8">
        <!-- Información del Registro -->
        <div class="mb-8 pb-8 border-b border-gray-200">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">ID Registro</p>
                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($datos['id']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Tipo</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold"
                        style="background-color: <?php echo $tipoRegistro === 'ACTIVIDAD' ? '#dbeafe' : '#f3e8ff'; ?>; color: <?php echo $tipoRegistro === 'ACTIVIDAD' ? '#1e40af' : '#6b21a8'; ?>;">
                        <?php echo htmlspecialchars($tipoRegistro); ?>
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Estudiante</p>
                    <p class="text-base text-gray-900"><?php echo htmlspecialchars($datos['estudiante_nombre']); ?></p>
                </div>
                <div>
                    <p class="text-xs text-gray-600 uppercase font-semibold">Empresa</p>
                    <p class="text-base text-gray-900"><?php echo htmlspecialchars($datos['nombre_empresa']); ?></p>
                </div>
            </div>
        </div>

        <!-- Formulario de Edición -->
        <form id="formularioEdicion" method="POST" action="<?php echo $basePath; ?>/admin/auditoria-guardar">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($datos['id']); ?>">
            <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipoRegistro); ?>">

            <div class="space-y-6">
                <!-- Fila 1: Estudiante y Documento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">👤 Estudiante</label>
                        <input type="text" value="<?php echo htmlspecialchars($datos['estudiante_nombre']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">📋 Identificación</label>
                        <input type="text" value="<?php echo htmlspecialchars($datos['numero_identificacion']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                    </div>
                </div>

                <!-- Fila 2: Empresa y Modalidad -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">🏢 Empresa</label>
                        <input type="text" value="<?php echo htmlspecialchars($datos['nombre_empresa']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-900 mb-2">📚 Modalidad</label>
                        <input type="text" value="<?php echo htmlspecialchars($datos['modalidad']); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                    </div>
                </div>

                <!-- Fila 3: Actividad/Descripción (EDITABLE) -->
                <div>
                    <label for="actividad" class="block text-sm font-semibold text-gray-900 mb-2">✍️ Actividad Realizada / Descripción</label>
                    <textarea id="actividad" name="actividad" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Describa la actividad realizada..."><?php echo htmlspecialchars($datos['actividad'] ?? ''); ?></textarea>
                </div>

                <!-- Fila 4: Horas, Fecha y Modalidad -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="horas" class="block text-sm font-semibold text-gray-900 mb-2">⏱️ Horas</label>
                        <input type="number" id="horas" name="horas" step="0.5" min="0"
                            value="<?php echo htmlspecialchars($datos['horas'] ?? 0); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="fecha" class="block text-sm font-semibold text-gray-900 mb-2">📅 Fecha</label>
                        <input type="date" id="fecha" name="fecha"
                            value="<?php echo htmlspecialchars($datos['fecha'] ?? date('Y-m-d')); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="departamento" class="block text-sm font-semibold text-gray-900 mb-2">🏷️ Departamento/Área</label>
                        <input type="text" id="departamento" name="departamento"
                            value="<?php echo htmlspecialchars($datos['departamento_area'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ej: Recursos Humanos">
                    </div>
                </div>

                <!-- Fila 5: Horas de inicio y fin -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="hora_inicio" class="block text-sm font-semibold text-gray-900 mb-2">🕐 Hora Inicio</label>
                        <input type="time" id="hora_inicio" name="hora_inicio"
                            value="<?php echo htmlspecialchars(substr($datos['hora_inicio'] ?? '', 0, 5)); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label for="hora_fin" class="block text-sm font-semibold text-gray-900 mb-2">🕕 Hora Fin</label>
                        <input type="time" id="hora_fin" name="hora_fin"
                            value="<?php echo htmlspecialchars(substr($datos['hora_fin'] ?? '', 0, 5)); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <?php if ($tipoRegistro === 'PLAN'): ?>
                    <!-- Fila 6: Función Asignada (solo para planes) -->
                    <div>
                        <label for="funcion" class="block text-sm font-semibold text-gray-900 mb-2">💼 Función Asignada</label>
                        <input type="text" id="funcion" name="funcion_asignada"
                            value="<?php echo htmlspecialchars($datos['funcion_asignada'] ?? ''); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Ej: Soporte técnico">
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botones de Acción -->
            <div class="mt-8 pt-8 border-t border-gray-200 flex gap-4">
                <button type="submit" class="flex-1 px-6 py-3 rounded-lg font-semibold text-white transition"
                    style="background-color: #10b981;">
                    ✓ Guardar Cambios
                </button>
                <button type="button" onclick="window.history.back()"
                    class="flex-1 px-6 py-3 rounded-lg font-semibold text-gray-700 border-2 border-gray-300 transition hover:bg-gray-50">
                    ✕ Cancelar
                </button>
            </div>
        </form>

        <!-- Información Adicional -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <p class="text-xs text-gray-600">
                ℹ️ <strong>Nota:</strong> Los datos marcados como deshabilitados (grisados) no pueden ser editados desde esta interfaz.
                Si necesitas cambiar estudiante, empresa o modalidad, contacta al administrador.
            </p>
        </div>
    </div>
</div>