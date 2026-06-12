<?php
$meses = ['ene_pct','feb_pct','mar_pct','abr_pct','may_pct','jun_pct','jul_pct','ago_pct','sep_pct','oct_pct','nov_pct','dic_pct'];
$mesesLabel = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$canCreatePoa = isset($canCreatePoa) ? (bool)$canCreatePoa : false;
$canEditPoa = isset($canEditPoa) ? (bool)$canEditPoa : false;
$canDeletePoa = isset($canDeletePoa) ? (bool)$canDeletePoa : false;
$basePath = isset($basePath) ? (string)$basePath : '';
?>
<div class="max-w-7xl mx-auto">

    <div class="bg-white shadow-lg rounded-2xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Gestión POA</h2>
            <?php if ($canCreatePoa): ?>
                <a href="<?= $basePath ?>/admin/actividad/create" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nueva Actividad
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION['success'])): ?>
            <div class="mx-5 mt-5 rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-green-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['success']) ?></span>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="mx-5 mt-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-sm font-medium"><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="p-5">
            <div class="relative mb-4">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="buscadorPoa" placeholder="Buscar por actividad, eje, objetivo..." class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-500 transition">
            </div>

            <div class="overflow-x-auto">
                <table id="tablaPoa" class="min-w-full text-sm" style="table-layout:fixed;border-collapse:collapse;">
                    <colgroup>
                        <col span="8">
                        <?php foreach ($mesesLabel as $ml): ?>
                        <col style="width:40px;">
                        <?php endforeach; ?>
                        <col span="10">
                    </colgroup>
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Eje Estratégico (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Objetivo Estratégico (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Estrategia (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Nombre del Proyecto/Actividad</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Descripción</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Meta (PEDI)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Sede</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Laboratorio</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" colspan="<?= count($mesesLabel) ?>">Cronograma</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Avance Planificado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Avance Ejecutado (%)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Observación de Avance</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Presupuesto Planificado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Presupuesto Ejecutado</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Ejecución Presupuestaria (%)</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">PROCESOS</th>
                                <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Observaciones</th>
                            <th class="px-2 py-3 text-center border-r border-gray-200" rowspan="2">Estado</th>
                            <th class="px-2 py-3 text-center" rowspan="2">Acciones</th>
                        </tr>
                        <tr class="bg-gray-50 text-gray-500 text-xs">
                            <?php foreach ($mesesLabel as $ml): ?>
                            <th class="px-1 py-2 text-center font-semibold border-r border-gray-200"><?= $ml ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (!empty($poa)): ?>
                            <?php foreach ($poa as $p): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                            <?= htmlspecialchars($p['eje'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 max-w-[160px] truncate border-r border-gray-100 text-gray-800" title="<?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>">
                                        <?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 max-w-[140px] truncate border-r border-gray-100 text-gray-600" title="<?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>">
                                        <?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 font-medium border-r border-gray-100 max-w-[180px] truncate" title="<?= htmlspecialchars($p['nombre_actividad'] ?? '') ?>">
                                        <?= htmlspecialchars($p['nombre_actividad'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 max-w-[160px] truncate border-r border-gray-100 text-gray-500" title="<?= htmlspecialchars($p['observacion_actividad'] ?? '') ?>">
                                        <?= htmlspecialchars($p['observacion_actividad'] ?? '') ?>
                                    </td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <?php
                                        $metaPedi = $p['meta_pedi'] ?? '';
                                        $pctPedi = $p['meta_pedi_pct'] ?? null;

                                        $metaNumero = null;
                                        if ($pctPedi !== null && $pctPedi !== '' && is_numeric($pctPedi)) {
                                            $metaNumero = (float)$pctPedi;
                                        } elseif ($metaPedi !== '' && is_numeric($metaPedi)) {
                                            $metaNumero = (float)$metaPedi;
                                        }

                                        if ($metaNumero !== null):
                                            $num = $metaNumero;
                                            $displayNum = rtrim(rtrim(number_format($num, 2, '.', ''), '0'), '.');
                                            $color = $num >= 70 ? 'bg-green-100 text-green-800' : ($num >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                                        ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $color ?>">
                                            <?= htmlspecialchars($displayNum) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-gray-400 text-xs">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-500"><?= htmlspecialchars($p['nombre_sede'] ?? $p['sede'] ?? '') ?></td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-500"><?= htmlspecialchars($p['laboratorio'] ?? '') ?></td>
                                    <?php foreach ($meses as $m): ?>
                                    <td class="px-1 py-3 text-center border-r border-gray-100 last:border-r-0">
                                        <?php
                                        $val = $p[$m] ?? null;
                                        $checked = ($val !== null && $val !== '' && (float)$val > 0);
                                        ?>
                                        <?php if ($checked): ?>
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                            <svg class="w-3.5 h-3.5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <?php else: ?>
                                        <div class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                            <span class="text-gray-300">—</span>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <?php
                                    $mesActual = (int)date('n');
                                    $seleccionados = 0;
                                    $cumplidos = 0;
                                    foreach ($meses as $i => $m) {
                                        if (empty($p[$m]) || (float)$p[$m] <= 0) continue;
                                        $seleccionados++;
                                        if ($i < $mesActual) {
                                            $cumplidos++;
                                        }
                                    }
                                    $avancePlanif = $seleccionados > 0 ? round(($cumplidos / $seleccionados) * 100) : 0;
                                    $avanceEjecutado = isset($p['avance_ejecutado']) ? (float)$p['avance_ejecutado'] : 0.0;
                                    $aeColor = $avanceEjecutado >= 70 ? 'text-green-700' : ($avanceEjecutado >= 50 ? 'text-yellow-600' : 'text-red-600');
                                    $observacionAvance = $p['observaciones_avance'] ?? ($p['obeservaciones_avance'] ?? ($p['observaciones'] ?? ''));
                                    ?>
                                    <td class="px-2 py-3 text-center font-semibold border-r border-gray-100 whitespace-nowrap text-gray-700"><?= $avancePlanif ?>%</td>
                                    <td class="px-2 py-3 text-center font-semibold border-r border-gray-100 whitespace-nowrap <?= $avanceEjecutado > 0 ? $aeColor : 'text-gray-400' ?>"><?= number_format($avanceEjecutado, 2) ?>%</td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <span class="text-gray-600"><?= htmlspecialchars((string)($observacionAvance !== '' ? $observacionAvance : '—')) ?></span>
                                    </td>
                                    <?php
                                    $planif = (float)($p['presupuesto_planificado'] ?? 0);
                                    $ejec = (float)($p['presupuesto_ejecutado'] ?? 0);
                                    $pct = ($planif > 0)
                                        ? max(0, min(100, round((($planif - $ejec) / $planif) * 100, 2)))
                                        : 0;
                                    ?>
                                    <td class="px-2 py-3 text-right font-medium border-r border-gray-100 whitespace-nowrap">$<?= number_format($planif, 2) ?></td>
                                    <td class="px-2 py-3 text-right border-r border-gray-100 whitespace-nowrap">$<?= number_format($ejec, 2) ?></td>
                                    <td class="px-2 py-3 text-right font-semibold border-r border-gray-100 whitespace-nowrap <?= $pct >= 70 ? 'text-green-700' : ($pct >= 50 ? 'text-yellow-600' : 'text-red-600') ?>"><?= $pct ?>%</td>
                                    <td class="px-2 py-3 border-r border-gray-100 text-gray-600"><?= htmlspecialchars($p['nombre_area'] ?? '') ?></td>
                                        <td class="px-2 py-3 max-w-[180px] truncate border-r border-gray-100 text-gray-500" title="<?= htmlspecialchars($p['observaciones'] ?? '') ?>">
                                            <?= htmlspecialchars($p['observaciones'] ?? '') ?>
                                        </td>
                                    <td class="px-2 py-3 border-r border-gray-100">
                                        <?php
                                        $estado = trim((string)($p['estado'] ?? ''));
                                        $estadoNormalizado = function_exists('mb_strtolower')
                                            ? mb_strtolower($estado, 'UTF-8')
                                            : strtolower($estado);

                                        $mapaEstadoColor = [
                                            'activo' => 'bg-green-100 text-green-800',
                                            'inactivo' => 'bg-yellow-100 text-yellow-800',
                                            'in activo' => 'bg-yellow-100 text-yellow-800',
                                            'in-activo' => 'bg-yellow-100 text-yellow-800',
                                            'caducado' => 'bg-red-100 text-red-800',
                                        ];

                                        $colorEstado = $mapaEstadoColor[$estadoNormalizado] ?? 'bg-gray-100 text-gray-500';
                                        ?>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $colorEstado ?>">
                                            <?= htmlspecialchars($estado !== '' ? $estado : 'Sin estado') ?>
                                        </span>
                                    </td>
                                    <td class="px-2 py-3 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <?php if ($canEditPoa): ?>
                                                <a href="<?= $basePath ?>/admin/actividad/edit/<?= $p['id_actividad'] ?>" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($canDeletePoa): ?>
                                                <form action="<?= $basePath ?>/admin/actividad/eliminar/<?= $p['id_actividad'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta actividad?');">
                                                    <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (!$canEditPoa && !$canDeletePoa): ?>
                                                <span class="text-gray-400 text-xs">Sin acciones</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= 18 + count($meses) ?>" class="px-4 py-12 text-center">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-gray-400 text-lg font-medium">No hay actividades POA registradas</p>
                                    <p class="text-gray-300 text-sm mt-1">Presione "Nueva Actividad" para crear el primer registro</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="paginacionPoa" class="mt-6 flex justify-center gap-1"></div>
        </div>
    </div>

    <a href="<?= $basePath ?>/admin/plan-estrategico" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-900 to-purple-700 hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm transition text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver a Planificación
    </a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tabla = document.getElementById("tablaPoa");
        const buscador = document.getElementById("buscadorPoa");
        const paginacion = document.getElementById("paginacionPoa");
        if (!tabla || !buscador || !paginacion) return;

        const filasPorPagina = 10;
        const todasFilas = Array.from(tabla.querySelectorAll("tbody tr")).filter(fila => fila.querySelectorAll("td").length > 0);
        let filasFiltradas = [...todasFilas];
        let paginaActual = 1;

        const mostrarPagina = (pagina) => {
            paginaActual = pagina;
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            const filasPagina = filasFiltradas.slice(inicio, fin);
            todasFilas.forEach((fila) => { fila.style.display = "none"; });
            filasPagina.forEach((fila) => { fila.style.display = ""; });
            renderPaginacion();
        };

        const renderPaginacion = () => {
            const totalPaginas = Math.ceil(filasFiltradas.length / filasPorPagina);
            paginacion.innerHTML = "";
            if (totalPaginas <= 1) return;
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement("button");
                btn.innerText = i;
                btn.className = "px-3 py-1 mx-0.5 rounded-lg border text-sm font-medium transition " + (i === paginaActual ? "bg-purple-700 text-white border-purple-700" : "bg-white text-gray-600 border-gray-200 hover:bg-gray-50");
                btn.addEventListener("click", () => mostrarPagina(i));
                paginacion.appendChild(btn);
            }
        };

        buscador.addEventListener("input", function() {
            const valor = this.value.toLowerCase();
            filasFiltradas = todasFilas.filter(fila => (fila.textContent || "").toLowerCase().includes(valor));
            mostrarPagina(1);
        });

        mostrarPagina(1);
    });
</script>