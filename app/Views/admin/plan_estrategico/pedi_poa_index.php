<h2 class="text-2xl font-bold mb-4">Planificación Estratégica</h2>

<div class="mb-6 p-4 rounded-lg border border-blue-200 bg-blue-50 text-sm text-blue-800">
    <p class="font-semibold mb-1">Pasos recomendados:</p>
    <ol class="list-decimal list-inside space-y-1">
        <li>Ir a <strong>PEDI</strong> para gestionar los objetivos estratégicos y su avance.</li>
        <li>Ir a <strong>POA</strong> para gestionar cabeceras, presupuestos y actividades operativas.</li>
    </ol>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- PEDI -->
    <a href="<?= $basePath ?>/admin/pedi" class="block bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6 border-t-4 border-purple-600">
        <div class="text-4xl mb-4">
            <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Plan Estratégico de Desarrollo Institucional</h3>
        <p class="text-sm text-gray-500 mb-4">Plan Estratégico de Desarrollo Institucional. Gestiona los objetivos estratégicos y su avance.</p>
        <div class="text-purple-600 font-semibold text-sm flex items-center gap-1">
            Gestionar
            <span class="text-lg">&rarr;</span>
        </div>
    </a>

    <!-- POA -->
    <a href="<?= $basePath ?>/admin/poa" class="block bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6 border-t-4 border-blue-600">
        <div class="text-4xl mb-4">
            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Plan Operativo Anual</h3>
        <p class="text-sm text-gray-500 mb-4">Plan Operativo Anual. Administra cabeceras, presupuestos, procesos y actividades operativas.</p>
        <div class="text-blue-600 font-semibold text-sm flex items-center gap-1">
            Gestionar
            <span class="text-lg">&rarr;</span>
        </div>
    </a>

</div>
