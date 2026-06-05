<h2 class="text-2xl font-bold mb-4">Planificación</h2>

<div class="mb-6 p-4 rounded-lg border border-blue-200 bg-blue-50 text-sm text-blue-800">
    <p class="font-semibold mb-1">Pasos recomendados:</p>
    <ol class="list-decimal list-inside space-y-1">
        <li>Ingresar a <strong>Configuración</strong> para hacer el llenado de las tablas (ejes, objetivos, estrategias, áreas, sedes).</li>
        <li>Ir a <strong>PEDI</strong> y seleccionar la o las actividades a desarrollar.</li>
        <li>Por último ir a <strong>POA</strong> para gestionar las actividades operativas.</li>
    </ol>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- PEDI -->
    <a href="<?= $basePath ?>/admin/pedi" class="block bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6 border-t-4 border-purple-600">
        <div class="text-4xl mb-4">📋</div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">PEDI</h3>
        <p class="text-sm text-gray-500 mb-4">Plan Estratégico de Desarrollo Institucional. Gestiona los objetivos estratégicos y su avance.</p>
        <div class="text-purple-600 font-semibold text-sm flex items-center gap-1">
            Gestionar
            <span class="text-lg">→</span>
        </div>
    </a>

    <!-- POA -->
    <a href="<?= $basePath ?>/admin/poa" class="block bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6 border-t-4 border-blue-600">
        <div class="text-4xl mb-4">📊</div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">POA</h3>
        <p class="text-sm text-gray-500 mb-4">Plan Operativo Anual. Administra presupuestos, áreas y actividades operativas.</p>
        <div class="text-blue-600 font-semibold text-sm flex items-center gap-1">
            Gestionar
            <span class="text-lg">→</span>
        </div>
    </a>

    <!-- Configuración -->
    <a href="<?= $basePath ?>/admin/configuracion" class="block bg-white rounded-xl shadow-lg hover:shadow-xl transition p-6 border-t-4 border-gray-600">
        <div class="text-4xl mb-4">⚙️</div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Configuración</h3>
        <p class="text-sm text-gray-500 mb-4">Ajustes del sistema, información de entorno y acciones rápidas de administración.</p>
        <div class="text-gray-600 font-semibold text-sm flex items-center gap-1">
            Abrir
            <span class="text-lg">→</span>
        </div>
    </a>

</div>
