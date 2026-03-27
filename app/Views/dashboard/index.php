<div class="bg-white p-6 md:p-10 rounded-xl shadow-2xl w-4/5">

    <div class="flex flex-wrap justify-center border-b border-gray-200 mb-6 -mx-2">
        <button
            class="tab-button p-3 text-sm whitespace-nowrap font-medium text-gray-600 hover:text-superarse-morado-oscuro border-b-2 border-transparent hover:border-superarse-rosa transition duration-150"
            data-tab="informacion">
            <i class="fas fa-info-circle mr-2"></i> Información
        </button>
        <button
            class="tab-button p-3 text-sm whitespace-nowrap font-medium text-gray-600 hover:text-superarse-morado-oscuro border-b-2 border-transparent hover:border-superarse-rosa transition duration-150"
            data-tab="asignaturas">
            <i class="fas fa-book-open mr-2"></i> Asignaturas
        </button>
        <button
            class="tab-button p-3 text-sm whitespace-nowrap font-medium text-gray-600 hover:text-superarse-morado-oscuro border-b-2 border-transparent hover:border-superarse-rosa transition duration-150"
            data-tab="pasantias">
            <i class="fas fa-briefcase mr-2"></i> Practicas
        </button>
        <button
            class="tab-button p-3 text-sm whitespace-nowrap font-medium text-gray-600 hover:text-superarse-morado-oscuro border-b-2 border-transparent hover:border-superarse-rosa transition duration-150"
            data-tab="credenciales">
            <i class="fas fa-lock mr-2"></i> Credenciales
        </button>
        <button
            class="tab-button p-3 text-sm whitespace-nowrap font-medium text-gray-600 hover:text-superarse-morado-oscuro border-b-2 border-transparent hover:border-superarse-rosa transition duration-150"
            data-tab="pagos">
            <i class="fas fa-dollar-sign mr-2"></i> Pagos
        </button>
    </div>

    <div id="tab-content">
        <?php include __DIR__ . '/tab_informacion.php'; ?>
        <?php include __DIR__ . '/tab_asignaturas.php'; ?>
        <?php include __DIR__ . '/tab_pasantias.php'; ?>
        <?php include __DIR__ . '/tab_credenciales.php'; ?>
        <?php include __DIR__ . '/tab_pagos.php'; ?>
    </div>
</div>