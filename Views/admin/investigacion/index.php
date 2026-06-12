<h2 class="text-2xl font-bold mb-6">Investigación, Desarrollo, Innovación</h2>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Proyectos de Investigación</h2>

        <a href="<?= $basePath ?>/admin/proyecto/crear"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nuevo Proyecto
        </a>
    </div>

    <input type="text" id="buscadorProyectos"
        placeholder="Buscar proyecto..."
        class="w-full mb-4 px-4 py-2 border rounded-lg">

    <div class="overflow-x-auto">
        <table id="tablaProyectos" class="min-w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Responsable</th>
                    <th class="px-4 py-3">Periodo</th>
                    <th class="px-4 py-3">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y">

                <?php foreach ($proyectos as $proyecto): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><?= $proyecto['id_proyecto'] ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($proyecto['nombre_proyecto']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($proyecto['responsable']) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($proyecto['periodo_academico']) ?></td>
                        <td class="px-4 py-3 text-center space-x-2">

                            <a href="<?= $basePath ?>/admin/proyecto/editar/<?= $proyecto['id_proyecto'] ?>"
                                class="text-blue-600 hover:text-blue-800 font-medium"
                                title="Editar">
                                ✏️
                            </a>

                            <form action="<?= $basePath ?>/admin/proyecto/eliminar/<?= $proyecto['id_proyecto'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar este proyecto de investigación?');">
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                    🗑️
                                </button>
                            </form>

                        </td>
                    </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <div id="paginacionProyectos" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Publicaciones</h2>

        <a href="<?= $basePath ?>/admin/publicacion/crear"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            ➕ Nueva Publicación
        </a>
    </div>

    <!-- Buscador -->
    <input type="text"
        id="buscadorPublicaciones"
        placeholder="Buscar publicación..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400 focus:outline-none">

    <div class="overflow-x-auto">
        <table id="tablaPublicaciones" class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Año</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Periodo</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($publicaciones)): ?>
                    <?php foreach ($publicaciones as $pub): ?>
                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $pub['id_publicacion'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($pub['nombre_publicacion']) ?>
                            </td>

                            <td class="px-4 py-3"><?= $pub['anio'] ?></td>

                            <td class="px-4 py-3">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                    <?= str_replace('_', ' ', $pub['tipo']) ?>
                                </span>
                            </td>

                            <td class="px-4 py-3"><?= htmlspecialchars($pub['periodo_academico']) ?></td>

                            <td class="px-4 py-3 text-center space-x-3">

                                <a href="<?= $basePath ?>/admin/publicacion/editar/<?= $pub['id_publicacion'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/publicacion/eliminar/<?= $pub['id_publicacion'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar esta publicación?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

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
    <div id="paginacionPublicaciones" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Ponencias</h2>

        <a href="<?= $basePath ?>/admin/ponencia/crear"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            ➕ Nueva Ponencia
        </a>
    </div>

    <!-- Buscador -->
    <input type="text"
        id="buscadorPonencias"
        placeholder="Buscar ponencia..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-400 focus:outline-none">

    <div class="overflow-x-auto">
        <table id="tablaPonencias" class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Autor</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Periodo</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($ponencias)): ?>
                    <?php foreach ($ponencias as $pon): ?>
                        <tr class="hover:bg-gray-50 transition">

                            <td class="px-4 py-3"><?= $pon['id_ponencia'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($pon['nombre_ponencia']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($pon['autor']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= date("d/m/Y", strtotime($pon['fecha_realizacion'])) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($pon['periodo_academico']) ?>
                            </td>

                            <td class="px-4 py-3 text-center space-x-3">

                                <a href="<?= $basePath ?>/admin/ponencia/editar/<?= $pon['id_ponencia'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/ponencia/eliminar/<?= $pon['id_ponencia'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar esta ponencia?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

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
    <div id="paginacionPonencias" class="mt-4 flex justify-center"></div>

</div>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">Proyectos de Investigación e Innovacion por Carrera</h2>

        <a href="<?= $basePath ?>/admin/carrera/crear"
            class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            ➕ Agregar Carrera
        </a>
    </div>

    <!-- Buscador -->
    <input type="text"
        id="buscadorCarreras"
        placeholder="Buscar por proyecto o carrera..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-400 focus:outline-none">

    <div class="overflow-x-auto">
        <table id="tablaCarreras" class="min-w-full text-sm text-left">
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

                                <a href="<?= $basePath ?>/admin/carrera/editar/<?= $car['id'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/carrera/eliminar/<?= $car['id'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar este registro de carrera?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

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
    <div id="paginacionCarreras" class="mt-4 flex justify-center"></div>

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
        activarTabla("tablaProyectos", "buscadorProyectos", "paginacionProyectos");
        activarTabla("tablaPublicaciones", "buscadorPublicaciones", "paginacionPublicaciones");
        activarTabla("tablaPonencias", "buscadorPonencias", "paginacionPonencias");
        activarTabla("tablaCarreras", "buscadorCarreras", "paginacionCarreras");
    });
</script>