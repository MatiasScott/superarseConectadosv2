<h2 class="text-2xl font-bold mb-6">Gestión de Convenios</h2>

<div class="bg-white shadow-lg rounded-2xl p-6 mb-8">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Convenios Institucionales</h2>

        <a href="<?= $basePath ?>/admin/convenio/crear"
            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">
            ➕ Nuevo Convenio
        </a>
    </div>

    <!-- Buscador -->
    <input type="text"
        id="buscadorConvenios"
        placeholder="Buscar empresa, ciudad o carrera..."
        class="w-full mb-4 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-purple-400 focus:outline-none">

    <div class="overflow-x-auto">

        <table id="tablaConvenios" class="min-w-full text-sm text-left">

            <thead class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Empresa</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Institución</th>
                    <th class="px-4 py-3">Ciudad</th>
                    <th class="px-4 py-3">Fecha Inicio</th>
                    <th class="px-4 py-3">Fecha Fin</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">

                <?php if (!empty($convenios)): ?>
                    <?php foreach ($convenios as $convenio): ?>

                        <tr class="hover:bg-gray-50">

                            <td class="px-4 py-3"><?= $convenio['id_convenio'] ?></td>

                            <td class="px-4 py-3 font-medium text-gray-800">
                                <?= htmlspecialchars($convenio['nombre_empresa']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($convenio['tipo_convenio_acuerdo']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($convenio['tipo_institucion']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($convenio['ciudad']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($convenio['fecha_inicio']) ?>
                            </td>

                            <td class="px-4 py-3">
                                <?= htmlspecialchars($convenio['fecha_fin']) ?>
                            </td>

                            <td class="px-4 py-3">

                                <?php if ($convenio['estado'] == 'Activo'): ?>

                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                        Activo
                                    </span>

                                <?php elseif ($convenio['estado_convenio'] == 'Caducado'): ?>

                                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">
                                        Caducado
                                    </span>

                                <?php else: ?>

                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">
                                        Inactivo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="px-4 py-3 text-center">

                                <a href="<?= $basePath ?>/admin/convenio/editar/<?= $convenio['id_convenio'] ?>"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                    title="Editar">
                                    ✏️
                                </a>

                                <form action="<?= $basePath ?>/admin/convenio/eliminar/<?= $convenio['id_convenio'] ?>" method="POST" class="inline" onsubmit="return confirm('¿Deseas eliminar este convenio?');">
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                        🗑️
                                    </button>
                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>
                <?php else: ?>

                    <tr>
                        <td colspan="9" class="text-center py-6 text-gray-400">
                            No hay convenios registrados
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <div id="paginacionConvenios" class="mt-4 flex justify-center"></div>

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
        activarTabla("tablaConvenios", "buscadorConvenios", "paginacionConvenios");
    });
</script>