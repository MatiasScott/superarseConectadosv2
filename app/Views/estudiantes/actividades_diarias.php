<?php
// Soportar tanto el formato de array $data como variables directas
$basePath = $basePath ?? $data['basePath'] ?? '';
$practicaId = $practicaId ?? $data['practicaId'] ?? 0;

// Debug temporal - eliminar después
if ($practicaId == 0 && isset($data['infoPractica']['id_practica'])) {
    $practicaId = $data['infoPractica']['id_practica'];
}

$actividadesDiarias = $actividadesDiarias ?? $data['actividadesDiarias'] ?? [];
$totalRegistros = $totalRegistros ?? $data['totalRegistros'] ?? 0;
$offset = $offset ?? $data['offset'] ?? 0;
$limit = $limit ?? $data['limit'] ?? 10;
$search = $search ?? $data['search'] ?? '';
$mensaje = $mensaje ?? $data['mensaje'] ?? null;
$activityPage = $activityPage ?? $data['activityPage'] ?? (int) floor(($offset / max(1, $limit)) + 1);
$totalPages = $totalPages ?? $data['totalActivityPages'] ?? (int) max(1, ceil(($totalRegistros ?: 0) / max(1, $limit)));

// Debug - comentar después de verificar
// echo "<!-- DEBUG: practicaId = " . $practicaId . " -->";
?>

<div x-data="{
    mostrarFormulario: false,
    modoEdicion: false,
    actividadEditando: null,
    horasCalculadas: '',
    advertenciaFecha: '',
    actividadesExistentes: [],

    obtenerActividadesExistentes() {
        return this.$el.getAttribute('data-actividades-existentes') ? 
            JSON.parse(this.$el.getAttribute('data-actividades-existentes')) : [];
    },

    validarFecha() {
        const fechaInput = document.getElementById('fecha_actividad').value;
        const hoy = new Date().toISOString().split('T')[0];
        this.advertenciaFecha = '';

        if (fechaInput > hoy) {
            this.advertenciaFecha = '❌ No puedes registrar actividades futuras';
            return false;
        }

        if (!this.modoEdicion && this.obtenerActividadesExistentes().includes(fechaInput)) {
            this.advertenciaFecha = '⚠️ Ya existe una actividad para este día';
            return false;
        }

        return true;
    },

    nuevaActividad() {
        this.modoEdicion = false;
        this.actividadEditando = null;
        this.horasCalculadas = '';
        this.advertenciaFecha = '';
        this.limpiarFormulario();
        this.mostrarFormulario = true;
    },

    editarActividad(actividad) {
        this.modoEdicion = true;
        this.actividadEditando = actividad;
        this.mostrarFormulario = true;
        this.advertenciaFecha = '';

        this.$nextTick(() => {
            document.getElementById('id_actividad').value = actividad.id_actividad_diaria;
            document.getElementById('actividad_realizada').value = actividad.actividad_realizada;
            document.getElementById('fecha_actividad').value = actividad.fecha_actividad;
            document.getElementById('hora_inicio').value = actividad.hora_inicio;
            document.getElementById('hora_fin').value = actividad.hora_fin;
            this.calcularHoras();
        });
    },

    limpiarFormulario() {
        document.getElementById('form_actividad').reset();
        document.getElementById('id_actividad').value = '';
        document.getElementById('fecha_actividad').value = '<?php echo date('Y-m-d'); ?>';
        document.getElementById('horas_invertidas').value = '';
        this.horasCalculadas = '';
        this.advertenciaFecha = '';
    },

    cancelar() {
        this.mostrarFormulario = false;
        this.modoEdicion = false;
        this.actividadEditando = null;
        this.limpiarFormulario();
    },

    calcularHoras() {
    let inicio = document.getElementById('hora_inicio').value;
    let fin = document.getElementById('hora_fin').value;
    let horasInput = document.getElementById('horas_invertidas');

    if (inicio && fin) {
        const [h1, m1] = inicio.split(':').map(Number);
        const [h2, m2] = fin.split(':').map(Number);
        let inicioMin = h1 * 60 + m1;
        let finMin = h2 * 60 + m2;

        // Diferencia en minutos
        let diffMin = finMin - inicioMin;
        if (diffMin < 0) diffMin += 24 * 60; // si pasa medianoche

        // Conversion a horas y minutos exactos
        const horas = Math.floor(diffMin / 60);
        const minutos = diffMin % 60;

        if (horas + minutos / 60 > 6) {
            alert('La duración no puede exceder las 6 horas.');
            horasInput.value = '';
            this.horasCalculadas = '';
            return;
        }

        // Mostrar formato legible sin redondear
        this.horasCalculadas = `${horas}h ${minutos}m`;

        // Guardar valor decimal para el backend (ej: 5.05)
        const horasDecimal = (diffMin / 60);
        horasInput.value = horasDecimal;
    } else {
        horasInput.value = '';
        this.horasCalculadas = '';
    }
}
}" 
data-actividades-existentes="<?php echo htmlspecialchars(json_encode(array_map(function($a) { return $a['fecha_actividad']; }, $actividadesDiarias)), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-700">🗓️ Actividades Diarias</h2>
            <div class="mt-2 flex gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-superarse-morado-claro text-superarse-morado-oscuro">
                    ⏱️ Total de horas: <strong class="ml-1"><?php 
                        $totalHoras = $totalHorasActividades ?? $data['totalHorasActividades'] ?? 0;
                        echo number_format($totalHoras, 2, '.', '');
                    ?> horas</strong>
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">
                    📝 Registradas: <strong class="ml-1"><?php 
                        $totalRegistradas = $totalRegistros ?? $data['totalActividadesDiarias'] ?? 0;
                        echo $totalRegistradas;
                    ?></strong>
                </span>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="<?php echo $basePath; ?>/pasantias/generateActividadesPdf/<?php echo $practicaId; ?>" 
                target="_blank"
                class="bg-red-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-red-700 transition duration-150 flex items-center gap-2">
                📄 Descargar PDF
            </a>
            <button @click="mostrarFormulario ? cancelar() : nuevaActividad()" type="button"
                class="bg-superarse-morado-medio text-white font-semibold py-2 px-4 rounded-lg hover:bg-superarse-morado-oscuro transition duration-150 flex items-center gap-2">
                <span x-show="!mostrarFormulario">Nueva Actividad</span>
                <span x-show="mostrarFormulario">Cancelar</span>
            </button>
        </div>
    </div>

    <?php if (isset($mensaje) && !empty($mensaje)): ?>
        <div class="mb-4 p-3 border rounded-lg
            <?php echo strpos($mensaje, 'Error') !== false || strpos($mensaje, '❌') !== false
                ? 'bg-red-100 border-red-300 text-red-700'
                : 'bg-green-100 border-green-300 text-green-700'; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    <?php endif; ?>

    <div x-show="mostrarFormulario"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="mb-6">

        <form id="form_actividad"
            action="<?php echo $basePath; ?>/pasantias/addActividadDiaria"
            method="POST"
            class="space-y-4 bg-white p-6 border border-gray-200 rounded-lg shadow-sm"
            @submit="if (modoEdicion) { this.action = '<?php echo $basePath; ?>/pasantias/updateActividadDiaria'; }">

            <div class="border-b pb-2 mb-4">
                <h3 class="text-lg font-semibold text-gray-700"
                    x-text="modoEdicion ? '✏️ Editar Actividad' : '➕ Nueva Actividad'">
                </h3>
            </div>

            <input type="hidden" name="practica_id" value="<?php echo $practicaId; ?>">
            <input type="hidden" id="id_actividad" name="id" value="">
            <input type="hidden" id="horas_invertidas" name="horas_invertidas" value="">
            <input type="hidden" name="tab" value="actividades">
            <input type="hidden" name="activity_page" value="<?php echo (int) $activityPage; ?>">

            <div>
                <label for="actividad_realizada" class="block text-gray-700 font-medium mb-1">
                    Actividad Realizada/Descripción <span class="text-red-500">*</span>
                </label>
                <textarea id="actividad_realizada" name="actividad_realizada" required rows="3"
                    placeholder="Describe brevemente la actividad realizada..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio"></textarea>
            </div>

            <div>
                <label for="fecha_actividad" class="block text-gray-700 font-medium mb-1">
                    Fecha de la Actividad <span class="text-red-500">*</span>
                </label>
                <input type="date" id="fecha_actividad" name="fecha_actividad" required
                    value="<?php echo date('Y-m-d'); ?>"
                    max="<?php echo date('Y-m-d'); ?>"
                    @change="validarFecha()"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio">
                <p class="text-xs text-gray-500 mt-1">Solo hasta hoy. Una actividad por día.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="hora_inicio" class="block text-gray-700 font-medium mb-1">
                        Hora de Inicio <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required
                        @change="calcularHoras()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio">
                </div>

                <div>
                    <label for="hora_fin" class="block text-gray-700 font-medium mb-1">
                        Hora de Fin <span class="text-red-500">*</span>
                    </label>
                    <input type="time" id="hora_fin" name="hora_fin" required
                        @change="calcularHoras()"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-superarse-morado-medio">
                </div>
            </div>

            <!-- Campo visible de horas calculadas dinámicamente -->
            <div>
                <label class="block text-gray-700 font-medium mb-1">
                    Horas Invertidas
                </label>
                <input type="text"
                    readonly
                    x-model="horasCalculadas"
                    placeholder="—"
                    class="w-full px-4 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-600 cursor-not-allowed">
                <p class="text-sm text-gray-500 mt-1">Se calcula automáticamente según las horas de inicio y fin.</p>
            </div>

            <!-- Advertencia de validación de fecha -->
            <div x-show="advertenciaFecha" class="p-3 rounded-lg" :class="advertenciaFecha.includes('❌') ? 'bg-red-100 border border-red-300 text-red-700' : 'bg-yellow-100 border border-yellow-300 text-yellow-700'">
                <span x-text="advertenciaFecha"></span>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit"
                    :disabled="advertenciaFecha !== ''"
                    :class="advertenciaFecha !== '' ? 'opacity-50 cursor-not-allowed' : ''"
                    class="bg-superarse-morado-medio text-white font-semibold py-2 px-6 rounded-lg hover:bg-superarse-morado-oscuro transition duration-150"
                    x-text="modoEdicion ? 'Actualizar' : 'Guardar'">
                </button>
                <button type="button" @click="cancelar()"
                    class="bg-gray-200 text-gray-700 font-semibold py-2 px-6 rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla -->
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
                                    ⏰ <?php echo htmlspecialchars($actividad['hora_inicio']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                    ⏰ <?php echo htmlspecialchars($actividad['hora_fin']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo number_format($actividad['horas_invertidas'], 2); ?>h
                                    </span>
                                </td>

                                <!-- ACCIONES -->
                                <td class="px-4 py-3 text-sm text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">

                                        <!-- ✏️ BOTÓN EDITAR -->
                                        <button type="button"
                                            @click='editarActividad(<?php echo json_encode($actividad, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                            class="text-blue-600 hover:text-blue-800 font-medium transition duration-150"
                                            title="Editar actividad">
                                            ✏️ Editar
                                        </button>

                                        <span class="text-gray-300">|</span>

                                        <!-- 🗑️ BOTÓN ELIMINAR -->
                                        <form
                                            action="<?php echo $basePath; ?>/pasantias/deleteActividadDiaria"
                                            method="POST"
                                            onsubmit="return confirm('⚠️ ¿Estás seguro de eliminar esta actividad?\n\nFecha: <?php echo date('d/m/Y', strtotime($actividad['fecha_actividad'])); ?>\nActividad: <?php echo htmlspecialchars($actividad['actividad_realizada']); ?>\n\nEsta acción no se puede deshacer.');"
                                            class="inline">
                                            <input type="hidden" name="id" value="<?php echo $actividad['id_actividad_diaria']; ?>">
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 font-medium transition duration-150"
                                                title="Eliminar actividad">
                                                🗑️ Eliminar
                                            </button>
                                                <input type="hidden" name="tab" value="actividades">
                                                <input type="hidden" name="activity_page" value="<?php echo (int) $activityPage; ?>">
                                        </form>

                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-4xl">📋</span>
                                    <p class="text-sm">No hay actividades registradas aún.</p>
                                    <button @click="nuevaActividad()" type="button"
                                        class="mt-2 text-superarse-morado-medio hover:text-superarse-morado-oscuro font-medium">
                                        Registra tu primera actividad →
                                    </button>
                                </div>
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
                    <a href="<?php echo $basePath; ?>/estudiante/informacion?module=pasantias&tab=actividades&activity_page=<?php echo (int) ($activityPage - 1); ?>"
                        class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">Anterior</a>
                <?php endif; ?>

                <?php if ($activityPage < $totalPages): ?>
                    <a href="<?php echo $basePath; ?>/estudiante/informacion?module=pasantias&tab=actividades&activity_page=<?php echo (int) ($activityPage + 1); ?>"
                        class="px-3 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700">Siguiente</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>