<!-- 🔷 HEADER -->
<div class="flex justify-between items-center mb-8">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Prácticas</h1>
        <p class="text-sm text-gray-500">
            Total de registros: <span class="font-semibold"><?= $totalRegistros ?></span>
        </p>
    </div>
</div>


<!-- 🔷 TARJETAS KPI -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">

    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-purple-700">
        <p class="text-sm text-gray-500">Total Registros</p>
        <p class="text-3xl font-bold text-purple-800 mt-2"><?= $totalRegistros ?></p>
    </div>

    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-red-500">
        <p class="text-sm text-gray-500">Fase 1 (Pendientes)</p>
        <p class="text-3xl font-bold text-red-600 mt-2"><?= $totalPendientes ?></p>
    </div>

    <div class="bg-white shadow-md rounded-xl p-6 border-l-4 border-green-600">
        <p class="text-sm text-gray-500">Fase 2 (Completadas)</p>
        <p class="text-3xl font-bold text-green-600 mt-2"><?= $totalCompletadas ?></p>
    </div>

</div>


<!-- 🔷 MENSAJES -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
        <?= htmlspecialchars($_SESSION['success']); ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        <?= htmlspecialchars($_SESSION['error']); ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<!-- 🔷 FILTROS -->
<div class="bg-white shadow rounded-xl p-6 mb-6">

    <form method="GET" action="<?= $basePath ?>/admin/practicas"
        class="flex flex-col md:flex-row md:items-center gap-4">

        <input type="text"
            name="buscar"
            value="<?= htmlspecialchars($buscar ?? '') ?>"
            placeholder="Buscar por estudiante o empresa..."
            class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">

        <select name="estado"
            class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-600 focus:outline-none">

            <option value="">Todos los estados</option>
            <option value="0" <?= ($estado === "0") ? 'selected' : '' ?>>Fase 1</option>
            <option value="1" <?= ($estado === "1") ? 'selected' : '' ?>>Fase 2</option>

        </select>

        <button type="submit"
            class="bg-purple-700 text-white px-5 py-2 rounded-lg hover:bg-purple-800 transition">
            Filtrar
        </button>

        <a href="<?= $basePath ?>/admin/practicas"
            class="bg-gray-200 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            Limpiar
        </a>

    </form>

</div>


<!-- 🔷 TABLA -->
<div class="bg-white shadow rounded-xl overflow-hidden">

    <table class="min-w-full text-sm">

        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">ID</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estudiante</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Empresa</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Estado</th>
                <th class="px-6 py-3 text-left font-semibold text-gray-600">Acciones</th>
            </tr>
        </thead>

        <tbody>

            <?php if (!empty($pasantias)): ?>
                <?php foreach ($pasantias as $p): ?>

                    <tr class="border-b hover:bg-gray-50 transition">

                        <td class="px-6 py-4"><?= $p['id_practica'] ?></td>

                        <td class="px-6 py-4 font-medium text-gray-800">
                            <?= htmlspecialchars($p['estudiante_nombre']) ?>
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            <?= htmlspecialchars($p['nombre_empresa']) ?>
                        </td>

                        <td class="px-6 py-4">
                            <?php if ($p['estado_fase_uno_completado'] == 1): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                    Fase 2
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                    Fase 1
                                </span>
                            <?php endif; ?>
                        </td>

                        <td class="px-6 py-4 text-center space-x-3">

                            <a href="<?= $basePath ?>/admin/practicas/editar/<?= $p['id_practica'] ?>"
                                class="text-blue-600 hover:text-blue-800 font-medium"
                                title="Editar">
                                ✏️
                            </a>

                            <form action="<?= $basePath ?>/admin/practicas/eliminar/<?= $p['id_practica'] ?>"
                                method="POST"
                                class="inline"
                                onsubmit="return confirm('¿Eliminar esta práctica?');">

                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium" title="Eliminar">
                                    🗑️
                                </button>
                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" class="text-center py-10 text-gray-500">
                        No hay registros disponibles.
                    </td>
                </tr>

            <?php endif; ?>

        </tbody>

    </table>

</div>


<!-- 🔷 PAGINACIÓN -->
<?php if ($totalPaginas > 1): ?>
    <div class="mt-6 flex justify-between items-center">

        <div class="text-sm text-gray-600">
            Página <?= $paginaActual ?> de <?= $totalPaginas ?>
        </div>

        <div class="flex gap-2">

            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a href="<?= $basePath ?>/admin/practicas?page=<?= $i ?>&estado=<?= $estado ?>&buscar=<?= urlencode($buscar) ?>"
                    class="px-4 py-2 rounded-lg border text-sm
                        <?= ($i == $paginaActual)
                            ? 'bg-purple-700 text-white border-purple-700'
                            : 'bg-white text-gray-600 hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

        </div>

    </div>
<?php endif; ?>