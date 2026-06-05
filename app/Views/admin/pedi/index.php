<?php
function renderMetaCell($meta, $pct) {
    $hasMeta = ($meta !== null && $meta !== '');
    $num = (float)$pct;
    $hasPct = ($pct !== null && $pct !== '' && $num > 0);

    if (!$hasMeta && !$hasPct) {
        return '';
    }

    $display = $hasMeta ? htmlspecialchars($meta) : '-';

    if (!$hasPct && $hasMeta && is_numeric($meta)) {
        $num = (float)$meta;
        $hasPct = ($num > 0);
    }

    if ($hasPct) {
        if ($num >= 70) {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#16a34a;color:#ffffff;">' . $display . '</span>';
        } elseif ($num >= 50) {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#ca8a04;color:#ffffff;">' . $display . '</span>';
        } else {
            return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;font-weight:700;background:#dc2626;color:#ffffff;">' . $display . '</span>';
        }
    }

    return '<span style="display:inline-block;width:100%;padding:4px 8px;border-radius:6px;font-size:13px;color:#9ca3af;">' . $display . '</span>';
}

$canCreatePedi = isset($canCreatePedi) ? (bool)$canCreatePedi : false;
$canEditPedi = isset($canEditPedi) ? (bool)$canEditPedi : false;
$canDeletePedi = isset($canDeletePedi) ? (bool)$canDeletePedi : false;
$basePath = isset($basePath) ? (string)$basePath : '';
?>

<div class="max-w-7xl mx-auto">

    <div class="bg-white shadow-lg rounded-2xl overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-purple-900 to-purple-700 px-6 py-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Plan Estratégico de Desarrollo Institucional</h2>
            <?php if ($canCreatePedi): ?>
                <a href="<?= $basePath ?>/admin/pedi/create" class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-sm font-medium px-3 py-1.5 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nuevo PEDI
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
                <input type="text"
                    id="buscadorPedi"
                    placeholder="Buscar por eje, objetivo, estrategia..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-500 transition">
            </div>

            <table id="tablaPedi" class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                        <th class="px-4 py-3 text-left rounded-l-lg">N</th>
                        <th class="px-4 py-3 text-left">Eje</th>
                        <th class="px-4 py-3 text-left">Objetivo Estratégico</th>
                        <th class="px-4 py-3 text-left">Estrategia</th>
                        <th class="px-4 py-3 text-center">Línea Base</th>
                        <th class="px-4 py-3 text-center">2024</th>
                        <th class="px-4 py-3 text-center">2025</th>
                        <th class="px-4 py-3 text-center">2026</th>
                        <th class="px-4 py-3 text-center">2027</th>
                        <th class="px-4 py-3 text-center">2028</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-center rounded-r-lg">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($pedi)): ?>
                        <?php $n = count($pedi); foreach ($pedi as $p): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3.5 text-gray-500 font-medium"><?= $n-- ?></td>
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-700">
                                        <?= htmlspecialchars($p['eje'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 max-w-[200px] truncate" title="<?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>">
                                    <?= htmlspecialchars($p['objetivo_estrategico'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3.5 max-w-[180px] truncate text-gray-500" title="<?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>">
                                    <?= htmlspecialchars($p['objetivo_estrategia'] ?? '') ?>
                                </td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['linea_base'] ?? '', $p['linea_base'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['meta_2024'] ?? '', $p['meta_2024_pct'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['meta_2025'] ?? '', $p['meta_2025_pct'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['meta_2026'] ?? '', $p['meta_2026_pct'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['meta_2027'] ?? '', $p['meta_2027_pct'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center"><?= renderMetaCell($p['meta_2028'] ?? '', $p['meta_2028_pct'] ?? null) ?></td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= ($p['estado'] ?? '') === 'activo' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
                                        <span class="w-1.5 h-1.5 rounded-full <?= ($p['estado'] ?? '') === 'activo' ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                                        <?= htmlspecialchars($p['estado'] ?? 'activo') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1">
                                        <?php if ($canEditPedi): ?>
                                            <a href="<?= $basePath ?>/admin/pedi/edit/<?= $p['id_pedi'] ?>" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($canDeletePedi): ?>
                                            <form action="<?= $basePath ?>/admin/pedi/eliminar/<?= $p['id_pedi'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este PEDI y todas sus metas?');">
                                                <button type="submit" class="p-1.5 rounded-lg text-red-600 hover:bg-red-50 hover:text-red-800 transition" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (!$canEditPedi && !$canDeletePedi): ?>
                                            <span class="text-gray-400 text-xs">Sin acciones</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-4 py-12 text-center">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-gray-400 text-lg font-medium">No hay registros PEDI</p>
                                <p class="text-gray-300 text-sm mt-1">Presione "Nuevo PEDI" para crear el primer registro</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div id="paginacionPedi" class="mt-6 flex justify-center gap-1"></div>
        </div>
    </div>

    <a href="<?= $basePath ?>/admin/plan-estrategico" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-900 to-purple-700 hover:opacity-90 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm transition text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Volver a Planificación
    </a>
</div>

<script>
    function activarTabla(tablaId, buscadorId, paginacionId) {
        const filasPorPagina = 10;
        const tabla = document.getElementById(tablaId);
        const buscador = document.getElementById(buscadorId);
        const paginacion = document.getElementById(paginacionId);
        if (!tabla || !buscador || !paginacion) return;

        const todasFilas = Array.from(tabla.querySelectorAll("tbody tr")).filter(fila => fila.querySelectorAll("td").length > 0);
        let filasFiltradas = [...todasFilas];
        let paginaActual = 1;

        function mostrarPagina(pagina) {
            paginaActual = pagina;
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            const filasPagina = filasFiltradas.slice(inicio, fin);
            todasFilas.forEach((fila) => { fila.style.display = "none"; });
            filasPagina.forEach((fila) => { fila.style.display = ""; });
            renderPaginacion();
        }

        function renderPaginacion() {
            const totalPaginas = Math.ceil(filasFiltradas.length / filasPorPagina);
            paginacion.innerHTML = "";
            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement("button");
                btn.innerText = i;
                btn.className = "px-3 py-1 mx-0.5 rounded-lg border text-sm font-medium transition " + (i === paginaActual ? "bg-purple-700 text-white border-purple-700" : "bg-white text-gray-600 border-gray-200 hover:bg-gray-50");
                btn.addEventListener("click", () => mostrarPagina(i));
                paginacion.appendChild(btn);
            }
        }

        buscador.addEventListener("input", function() {
            const valor = this.value.toLowerCase();
            filasFiltradas = todasFilas.filter(fila => (fila.textContent || "").toLowerCase().includes(valor));
            mostrarPagina(1);
        });

        mostrarPagina(1);
    }

    document.addEventListener("DOMContentLoaded", function() {
        activarTabla("tablaPedi", "buscadorPedi", "paginacionPedi");
    });
</script>