<div id="informacion" class="tab-pane hidden">
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4">Datos Personales</h3>

    <div class="bg-white p-4 rounded-lg shadow-xl border border-gray-200">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4">

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Código
                    Estudiantil</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['codigo_matricula'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Nombres
                    Completos</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['nombreCompleto']); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Tipo de
                    Identificación</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['tipo_identificacion'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Número de
                    Identificación</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['numero_identificacion'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Correo
                    Institucional</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['usuario'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Estado / Cond.
                    Matrícula</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['estado'] ?? 'N/D') . ' / ' . htmlspecialchars($data['infoPersonal']['cond_matricula'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Teléfono</span>
                <span class="text-base text-gray-900 font-semibold">
                    0<?php echo htmlspecialchars($data['infoPersonal']['celular'] ?? 'N/D'); ?>
                </span>
            </div>
        </div>
    </div>
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-4 mt-6">Datos Académicos</h3>
    <div class="bg-white p-4 rounded-lg shadow-xl border border-gray-200">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4">

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Periodo
                    Académico</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['periodo'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0">
                <span class="text-sm font-medium text-left text-superarse-rosa">Carrera</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php echo htmlspecialchars($data['infoPersonal']['programa'] ?? 'N/D'); ?>
                </span>
            </div>

            <div class="flex flex-col border-b md:border-b-0 pb-2 md:pb-0 md:col-span-2">
                <span class="text-sm font-medium text-left text-superarse-rosa">Nivel</span>
                <span class="text-base text-gray-900 font-semibold">
                    <?php
                    $nivelActual = htmlspecialchars($data['infoPersonal']['nivel'] ?? 'N/D');
                    echo $nivelActual;
                    if ($nivelActual !== 'N/D' && !in_array(strtoupper(trim($nivelActual)), ['NIVEL 1', 'N1', 'PRIMERO', '1'])) {
                        echo ' - ¡Recuerda prematricularte para el siguiente nivel!';
                        echo '<br>';
                        echo '<a href="https://site2.q10.com/Prematricula" target="_blank" class="text-superarse-rosa hover:underline font-semibold">¡Prematrículate aquí!</a>';
                        echo ' / ';
                        echo '<a href="https://goo.su/o1y2g " target="_blank" class="text-blue-600 hover:underline">¿Cómo me prematriculo?</a>';
                    }
                    ?>
                </span>
            </div>

        </div>
    </div>
</div>