<h2 class="text-2xl font-bold mb-6">Vinculación con la Sociedad</h2>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Proyectos de Vinculación</h2>

        <a href="<?= $basePath ?>/admin/proyecto/crear_vinculacion"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nuevo Proyecto
        </a>
    </div>

    <!-- Buscador -->
    <input type="text" id="buscadorVinculacion"
        placeholder="Buscar proyecto..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-400">

    <div class="overflow-x-auto">
        <table id="tablaVinculacion" class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Nombre del Proyecto</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Localizacion</th>
                    <th class="px-4 py-3">Periodo</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($proyectosVinculacion)): ?>
                    <?php foreach ($proyectosVinculacion as $proyecto): ?>
                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $proyecto['id_proyecto'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($proyecto['nombre_proyecto']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($proyecto['responsable']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($proyecto['localizacion'] ?? '') ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($proyecto['periodo_academico']) ?>
                            </td>

                            <td class="px-4 py-3 text-center space-x-2">

                                <a href="<?= $basePath ?>/admin/vinculacion/editar/<?= $proyecto['id_proyecto'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    ✏️
                                </a>

                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                            No hay registros disponibles
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacionVinculacion" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Proyectos de Vinculacion por Carrera</h2>

        <a href="<?= $basePath ?>/admin/carrera/crearV"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            ➕ Agregar Carrera
        </a>
    </div>

    <!-- Buscador -->
    <input type="text"
        id="buscadorVinculacionCarreras"
        placeholder="Buscar por proyecto o carrera..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-400 focus:outline-none">

    <div class="overflow-x-auto">
        <table id="tablaCarrerasVinculacion" class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Nombre del Proyecto</th>
                    <th class="px-4 py-3">Carrera</th>
                    <th class="px-4 py-3 text-center">N° Estudiantes</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($carreras)): ?>
                    <?php foreach ($carreras as $car): ?>
                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $car['id'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($car['nombre_proyecto']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($car['carrera']) ?>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700">
                                    <?= $car['nro_estudiantes'] ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center space-x-3">

                                <a href="<?= $basePath ?>/admin/carrera/editarV/<?= $car['id'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                    ✏️
                                </a>

                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                            No hay registros disponibles
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div id="paginacionVinculacionCarreras" class="mt-4 flex justify-center"></div>

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
        activarTabla("tablaVinculacion", "buscadorVinculacion", "paginacionVinculacion");
        activarTabla("tablaCarrerasVinculacion", "buscadorVinculacionCarreras", "paginacionVinculacionCarreras");
    });
</script>