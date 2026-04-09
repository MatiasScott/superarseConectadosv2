<h2 class="text-2xl font-bold mb-6">Planificación Estratégica</h2>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Plan Estratégico de Desarrollo Institucional</h2>

        <a href="<?= $basePath ?>/admin/pedi/create"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nuevo PEDI
        </a>
    </div>

    <input type="text"
        id="buscadorPedi"
        placeholder="Buscar objetivo..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">

    <div class="overflow-x-auto">
        <table id="tablaPedi" class="min-w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Objetivo Estratégico</th>
                    <th class="px-4 py-3">Avance del Obj. Estratégico</th>
                    <th class="px-4 py-3">Estrategia</th>
                    <th class="px-4 py-3">Avance de la Estrategia</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($pedi)): ?>
                    <?php foreach ($pedi as $p): ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $p['id_pedi'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($p['objetivo_estrategico']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $p['avance'] ?> %
                            </td>

                            <td class="px-4 py-3">
                                <?= $p['objetivo_estrategia'] ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $p['avance_estrategia'] ?> %
                            </td>

                            <td class="px-4 py-3 text-center">

                                <a href="<?= $basePath ?>/admin/pedi/edit/<?= $p['id_pedi'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/pedi/eliminar/<?= $p['id_pedi'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar este PEDI?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                            No hay registros disponibles
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacionPedi" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Plan Operativo Anual</h2>

        <a href="<?= $basePath ?>/admin/poa/create"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nuevo POA
        </a>
    </div>

    <input type="text"
        id="buscadorPoa"
        placeholder="Buscar POA..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">

    <div class="overflow-x-auto">
        <table id="tablaPoa" class="min-w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Estrategia</th>
                    <th class="px-4 py-3">Área</th>
                    <th class="px-4 py-3">Presupuesto</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($poa)): ?>
                    <?php foreach ($poa as $p): ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $p['id_poa'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($p['objetivo_estrategia']) ?>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($p['nombre_area']) ?>
                            </td>

                            <td class="px-4 py-3">
                                $<?= number_format($p['presupuesto_anual'], 2) ?>
                            </td>

                            <td class="px-4 py-3 text-center">

                                <a href="<?= $basePath ?>/admin/poa/edit/<?= $p['id_poa'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/poa/eliminar/<?= $p['id_poa'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar este POA?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                            No hay registros disponibles
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacionPoa" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Actividades POA</h2>

        <a href="<?= $basePath ?>/admin/actividad/create"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nueva Actividad
        </a>
    </div>

    <input type="text"
        id="buscadorActividades"
        placeholder="Buscar actividad..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">

    <div class="overflow-x-auto">
        <table id="tablaActividades" class="min-w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Area</th>
                    <th class="px-4 py-3">Actividad</th>
                    <th class="px-4 py-3">Presupuesto</th>
                    <th class="px-4 py-3">Fecha Inicio</th>
                    <th class="px-4 py-3">Fecha Fin</th>
                    <th class="px-4 py-3">Avance</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($actividades)): ?>
                    <?php foreach ($actividades as $a): ?>

                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $a['id_actividad'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($a['nombre_area']) ?>
                            </td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($a['nombre_actividad']) ?>
                            </td>

                            <td class="px-4 py-3">
                                $<?= number_format($a['presupuesto_actividad'], 2) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $a['fecha_inicio'] ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $a['fecha_fin'] ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= $a['avance'] ?> %
                            </td>

                            <td class="px-4 py-3 text-center">

                                <a href="<?= $basePath ?>/admin/actividad/edit/<?= $a['id_actividad'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/actividad/eliminar/<?= $a['id_actividad'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar esta actividad?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-gray-400">
                            No hay registros disponibles
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacionActividades" class="mt-4 flex justify-center"></div>

</div>

<script>
    function activarTabla(tablaId, buscadorId, paginacionId) {
        const filasPorPagina = 10;
        const tabla = document.getElementById(tablaId);
        const buscador = document.getElementById(buscadorId);
        const paginacion = document.getElementById(paginacionId);

        if (!tabla || !buscador || !paginacion) {
            return;
        }

        const todasFilas = Array.from(tabla.querySelectorAll("tbody tr"))
            .filter(fila => fila.querySelectorAll("td").length > 0);
        let filasFiltradas = [...todasFilas];
        let paginaActual = 1;

        function mostrarPagina(pagina) {
            paginaActual = pagina;
            const inicio = (pagina - 1) * filasPorPagina;
            const fin = inicio + filasPorPagina;
            const filasPagina = filasFiltradas.slice(inicio, fin);

            // Ocultar todas y mostrar solo coincidencias de la página actual
            todasFilas.forEach((fila) => {
                fila.style.display = "none";
            });

            filasPagina.forEach((fila) => {
                fila.style.display = "";
            });

            renderPaginacion();
        }

        function renderPaginacion() {
            const totalPaginas = Math.ceil(filasFiltradas.length / filasPorPagina);
            paginacion.innerHTML = "";

            for (let i = 1; i <= totalPaginas; i++) {
                const btn = document.createElement("button");
                btn.innerText = i;
                btn.className = "px-3 py-1 mx-1 rounded border text-sm " +
                    (i === paginaActual ? "bg-purple-600 text-white" : "bg-white hover:bg-gray-100");

                btn.addEventListener("click", () => mostrarPagina(i));
                paginacion.appendChild(btn);
            }
        }

        // Filtrar en vivo mientras escribes
        buscador.addEventListener("input", function() {
            const valor = this.value.toLowerCase();

            filasFiltradas = todasFilas.filter(fila =>
                (fila.textContent || "").toLowerCase().includes(valor)
            );

            mostrarPagina(1); // siempre mostrar la primera página del filtrado
        });

        mostrarPagina(1); // mostrar primera página al cargar
    }

    // Activar buscador + paginación en todas tus tablas
    document.addEventListener("DOMContentLoaded", function() {

        activarTabla("tablaPedi", "buscadorPedi", "paginacionPedi");
        activarTabla("tablaPoa", "buscadorPoa", "paginacionPoa");
        activarTabla("tablaActividades", "buscadorActividades", "paginacionActividades");

    });
</script>