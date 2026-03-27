<?php
// Contar actividades y planes para estadísticas
$actividadesCount = 0;
$planesCount = 0;
foreach ($registros as $registro) {
    if ($registro['tipo_registro'] === 'ACTIVIDAD') {
        $actividadesCount++;
    } else {
        $planesCount++;
    }
}
?>
<div class="max-w-7xl mx-auto py-2 px-1 sm:px-2 lg:px-3" data-basepath="<?php echo htmlspecialchars($basePath); ?>">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Registros -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition" style="border-left: 4px solid #3b82f6;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Registros</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $totalRegistros; ?></p>
                        <p class="text-xs text-gray-500 mt-2">Planes + Actividades</p>
                    </div>
                    <div style="color: #3b82f6; opacity: 0.2;" class="text-4xl">
                        📊
                    </div>
                </div>
            </div>

            <!-- Actividades -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition" style="border-left: 4px solid #10b981;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Actividades Realizadas</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $actividadesCount; ?></p>
                        <p class="text-xs text-gray-500 mt-2">En esta página</p>
                    </div>
                    <div style="color: #10b981; opacity: 0.2;" class="text-4xl">
                        ✓
                    </div>
                </div>
            </div>

            <!-- Planes -->
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition" style="border-left: 4px solid #a855f7;">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Planes Programados</p>
                        <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo $planesCount; ?></p>
                        <p class="text-xs text-gray-500 mt-2">En esta página</p>
                    </div>
                    <div style="color: #a855f7; opacity: 0.2;" class="text-4xl">
                        📋
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <span style="color: #e75ba8;">⚙️</span> Filtros y Búsqueda
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <form method="GET" class="lg:col-span-2 flex gap-2">
                    <div class="flex-1 relative">
                        <span class="absolute left-3 top-3 text-gray-400">🔍</span>
                        <input 
                            type="text" 
                            name="search" 
                            placeholder="Nombre, cédula, empresa..." 
                            value="<?php echo htmlspecialchars($search ?? ''); ?>"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent"
                        />
                    </div>
                    <button 
                        type="submit"
                        class="text-white px-6 py-2 rounded-lg font-semibold transition duration-200 flex items-center gap-2"
                        style="background-color: #e75ba8;">
                        🔍 Buscar
                    </button>
                    <?php if ($search): ?>
                        <a href="?page=1&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>" 
                        class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg transition duration-200 flex items-center gap-2">
                            ✕ Limpiar
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Sort -->
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <input type="hidden" name="limit" value="<?php echo $limit; ?>">
                    <div class="flex-1 relative">
                        <span class="absolute left-3 top-3 text-gray-400">⬍</span>
                        <select 
                            name="sortBy"
                            onchange="this.form.submit()"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent appearance-none bg-white cursor-pointer">
                            <option value="fecha" <?php echo $sortBy === 'fecha' ? 'selected' : ''; ?>>Fecha (Reciente)</option>
                            <option value="estudiante" <?php echo $sortBy === 'estudiante' ? 'selected' : ''; ?>>Estudiante (A-Z)</option>
                            <option value="empresa" <?php echo $sortBy === 'empresa' ? 'selected' : ''; ?>>Empresa (A-Z)</option>
                            <option value="modalidad" <?php echo $sortBy === 'modalidad' ? 'selected' : ''; ?>>Modalidad (A-Z)</option>
                        </select>
                    </div>
                </form>

                <!-- Limit -->
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <input type="hidden" name="sortBy" value="<?php echo htmlspecialchars($sortBy); ?>">
                    <div class="flex-1 relative">
                        <span class="absolute left-3 top-3 text-gray-400">☰</span>
                        <select 
                            name="limit"
                            onchange="this.form.submit()"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:border-transparent appearance-none bg-white cursor-pointer">
                            <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 por página</option>
                            <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 por página</option>
                            <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 por página</option>
                            <option value="250" <?php echo $limit == 250 ? 'selected' : ''; ?>>250 por página</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Search Summary -->
            <?php if ($search): ?>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-600">
                        ℹ️ Mostrando resultados para: <span class="font-semibold text-gray-900">"<?php echo htmlspecialchars($search); ?>"</span>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Table Section -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Table Wrapper for horizontal scroll on mobile -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white text-sm" style="background: linear-gradient(to right, #4a2c5e, #663d8a);">
                            <th class="px-4 py-4 text-left font-semibold">ID</th>
                            <th class="px-4 py-4 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-4 text-left font-semibold">Estudiante</th>
                            <th class="px-4 py-4 text-left font-semibold">Cédula</th>
                            <th class="px-4 py-4 text-left font-semibold">Matrícula</th>
                            <th class="px-4 py-4 text-left font-semibold">Programa</th>
                            <th class="px-4 py-4 text-left font-semibold">Empresa</th>
                            <th class="px-4 py-4 text-left font-semibold">Modalidad</th>
                            <th class="px-4 py-4 text-left font-semibold">Actividad / Plan</th>
                            <th class="px-4 py-4 text-left font-semibold">Dept/Área</th>
                            <th class="px-4 py-4 text-left font-semibold">Función</th>
                            <th class="px-4 py-4 text-center font-semibold">Horas</th>
                            <th class="px-4 py-4 text-left font-semibold">Inicio</th>
                            <th class="px-4 py-4 text-left font-semibold">Fin</th>
                            <th class="px-4 py-4 text-left font-semibold">Fecha</th>

                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $idx => $registro): 
                                $bgClass = $idx % 2 === 0 ? 'bg-white' : 'bg-gray-50';
                            ?>
                                <tr class="<?php echo $bgClass; ?> hover:bg-blue-50 transition text-sm">
                                    <!-- ID Badge -->
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold font-mono" style="background-color: #e5e7eb; color: #374151;">
                                            #<?php echo str_pad($registro['id'], 4, '0', STR_PAD_LEFT); ?>
                                        </span>
                                    </td>

                                    <!-- Type Badge -->
                                    <td class="px-4 py-3">
                                        <?php if ($registro['tipo_registro'] === 'PLAN'): ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold" style="background-color: #dbeafe; color: #075985;">
                                                📋 PLAN
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold" style="background-color: #dcfce7; color: #166534;">
                                                ✓ ACTIVIDAD
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Student with Avatar -->
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full text-white flex items-center justify-center text-xs font-bold" style="background: linear-gradient(to br, #e75ba8, #663d8a);">
                                                <?php echo strtoupper(substr($registro['estudiante_nombre'], 0, 1)); ?>
                                            </div>
                                            <span class="font-medium text-gray-900"><?php echo htmlspecialchars(substr($registro['estudiante_nombre'], 0, 20)); ?></span>
                                        </div>
                                    </td>

                                    <!-- ID -->
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                        <?php echo htmlspecialchars($registro['numero_identificacion']); ?>
                                    </td>

                                    <!-- Enrollment Code -->
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                        <?php echo htmlspecialchars($registro['codigo_matricula']); ?>
                                    </td>

                                    <!-- Program -->
                                    <td class="px-4 py-3 text-gray-700 text-xs max-w-xs truncate">
                                        <?php echo htmlspecialchars($registro['programa'] ?? 'N/A'); ?>
                                    </td>

                                    <!-- Company -->
                                    <td class="px-4 py-3 text-gray-700 text-xs max-w-xs truncate">
                                        <?php echo htmlspecialchars($registro['nombre_empresa']); ?>
                                    </td>

                                    <!-- Modality -->
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold" style="background-color: #f3e8ff; color: #6b21a8;">
                                            <?php echo htmlspecialchars($registro['modalidad']); ?>
                                        </span>
                                    </td>

                                    <!-- Activity/Plan -->
                                    <td class="px-4 py-3 text-gray-700 text-xs max-w-xs truncate" title="<?php echo htmlspecialchars($registro['actividad']); ?>">
                                        <?php echo htmlspecialchars(substr($registro['actividad'], 0, 40)); ?><?php echo strlen($registro['actividad']) > 40 ? '...' : ''; ?>
                                    </td>

                                    <!-- Department/Area -->
                                    <td class="px-4 py-3 text-gray-700 text-xs">
                                        <?php echo htmlspecialchars($registro['departamento_area'] ?? '-'); ?>
                                    </td>

                                    <!-- Function -->
                                    <td class="px-4 py-3 text-gray-700 text-xs max-w-xs truncate">
                                        <?php echo htmlspecialchars($registro['funcion_asignada'] ?? '-'); ?>
                                    </td>

                                    <!-- Hours -->
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($registro['horas']): ?>
                                            <span class="inline-block px-2 py-1 rounded text-xs font-bold" style="background-color: #fed7aa; color: #92400e;">
                                                <?php echo number_format($registro['horas'], 1); ?>h
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Start Time -->
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                        <?php echo $registro['hora_inicio'] ? date('H:i', strtotime($registro['hora_inicio'])) : '-'; ?>
                                    </td>

                                    <!-- End Time -->
                                    <td class="px-4 py-3 text-gray-700 font-mono text-xs">
                                        <?php echo $registro['hora_fin'] ? date('H:i', strtotime($registro['hora_fin'])) : '-'; ?>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-4 py-3 text-gray-700 font-medium text-xs">
                                        📅 <?php echo date('d/m/Y', strtotime($registro['fecha'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="15" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <i class="fas fa-inbox text-4xl mb-4"></i>
                                        <p class="text-lg font-medium text-gray-700 mt-4">No hay registros disponibles</p>
                                        <?php if ($search): ?>
                                            <p class="text-sm text-gray-500 mt-2">Intenta con otros términos de búsqueda para: "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-500 mt-2">No hay planes o actividades registradas aún.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
            <div class="mt-6 flex justify-center items-center gap-1 flex-wrap">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>"
                       class="px-3 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition flex items-center gap-1 text-sm font-medium"
                       title="Primera página">
                        ⏮
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>"
                       class="px-3 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition flex items-center gap-1 text-sm font-medium">
                        ◀ Anterior
                    </a>
                <?php endif; ?>

                <div class="flex gap-1 items-center">
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    
                    if ($start > 1): ?>
                        <span class="px-2 py-2 text-gray-500 text-sm">...</span>
                    <?php endif;
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>"
                           class="px-3 py-2 <?php echo $i === $page ? 'bg-superarse-rosa text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-100'; ?> rounded-lg transition text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; 
                    
                    if ($end < $totalPages): ?>
                        <span class="px-2 py-2 text-gray-500 text-sm">...</span>
                    <?php endif; ?>
                </div>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>"
                       class="px-3 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition flex items-center gap-1 text-sm font-medium">
                        Siguiente ▶
                    </a>
                    <a href="?page=<?php echo $totalPages; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&sortBy=<?php echo urlencode($sortBy); ?>&limit=<?php echo $limit; ?>"
                       class="px-3 py-2 bg-white text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-100 transition flex items-center gap-1 text-sm font-medium"
                       title="Última página">
                        ⏭
                    </a>
                <?php endif; ?>
            </div>

            <div class="mt-4 text-center text-sm text-gray-600">
                Página <span class="font-bold text-gray-900"><?php echo $page; ?></span> de 
                <span class="font-bold text-gray-900"><?php echo $totalPages; ?></span> 
                <span class="text-gray-500 ml-2">
                    💾 <?php 
                    $start_record = ($page - 1) * $limit + 1;
                    $end_record = min($page * $limit, $totalRegistros);
                    echo "Mostrando " . $start_record . " a " . $end_record . " de " . $totalRegistros . " registros";
                    ?>
                </span>
            </div>
    <?php endif; ?>

    <!-- Tabla de Registros Eliminados -->
    <div class="max-w-7xl mx-auto px-0 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-600 to-red-800 rounded-t">
                <h3 class="text-lg font-semibold text-white">🗑️ Registros Eliminados por Estudiantes</h3>
                <p class="text-xs text-red-100 mt-1">Total de registros eliminados: <strong><?php echo $totalEliminados; ?></strong></p>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-white text-sm" style="background: linear-gradient(to right, #dc2626, #991b1b);">
                            <th class="px-4 py-4 text-left font-semibold">Tipo</th>
                            <th class="px-4 py-4 text-left font-semibold">Estudiante</th>
                            <th class="px-4 py-4 text-left font-semibold">Programa</th>
                            <th class="px-4 py-4 text-left font-semibold">Empresa</th>
                            <th class="px-4 py-4 text-left font-semibold">Actividad / Plan</th>
                            <th class="px-4 py-4 text-center font-semibold">Horas</th>
                            <th class="px-4 py-4 text-left font-semibold">Inicio</th>
                            <th class="px-4 py-4 text-left font-semibold">Fin</th>
                            <th class="px-4 py-4 text-left font-semibold">Fecha Eliminación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($registrosEliminados)): ?>
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    📭 No hay registros de eliminación en el sistema
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registrosEliminados as $registro): ?>
                                <tr class="hover:bg-red-50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <?php 
                                        if ($registro['tipo_registro'] === 'ACTIVIDAD') {
                                            echo '<span style="background-color: #3B82F6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">ACTIVIDAD</span>';
                                        } else {
                                            echo '<span style="background-color: #8B5CF6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold;">PLAN</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($registro['estudiante_nombre'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        <?php echo htmlspecialchars(substr($registro['programa'] ?? '', 0, 30)); ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($registro['empresa_nombre'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        <?php 
                                        $desc = $registro['descripcion'] ?? '';
                                        echo htmlspecialchars(strlen($desc) > 35 ? substr($desc, 0, 35) . '...' : $desc);
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 text-center text-sm font-semibold text-gray-900">
                                        <?php 
                                        if ($registro['horas_cumplidas']) {
                                            echo number_format($registro['horas_cumplidas'], 1) . 'h';
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php 
                                        if ($registro['fecha_inicio']) {
                                            echo '📅 ' . date('d/m/Y', strtotime($registro['fecha_inicio']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?php 
                                        if ($registro['fecha_fin']) {
                                            echo '📅 ' . date('d/m/Y', strtotime($registro['fecha_fin']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                                        <?php 
                                        try {
                                            $fecha = new DateTime($registro['fecha_eliminacion']);
                                            echo '🗑️ ' . $fecha->format('d/m/Y H:i');
                                        } catch (Exception $e) {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="text-gray-600 py-6 mt-8 text-center text-sm">
        <p>Sistema de Auditoría | Última actualización: <span class="font-semibold"><?php echo date('d/m/Y H:i'); ?></span></p>
    </div>
</div>
