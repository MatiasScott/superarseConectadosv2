<div id="asignaturas" class="tab-pane hidden">
    <h3 class="text-xl font-semibold text-superarse-morado-oscuro mb-6">Asignaturas Registradas por
        Nivel</h3>

    <?php

                    if (isset($data['infoAsignaturas']['SQL_ERROR'])): ?>
    <p class="text-red-500 font-bold">Error al cargar asignaturas:
        <?php echo htmlspecialchars($data['infoAsignaturas']['SQL_ERROR']); ?></p>
    <?php elseif (!empty($data['infoAsignaturas'])): ?>

    <?php

                        foreach ($data['infoAsignaturas'] as $nivel => $asignaturas):

                            if (!empty($asignaturas)):
                        ?>
    <div class="mb-8 p-4 bg-gray-50 rounded-xl shadow-inner border border-gray-200">
        <h4 class="text-lg font-bold text-superarse-rosa mb-4 border-b pb-2">
            Nivel <?php echo htmlspecialchars($nivel); ?>
        </h4>

        <ul class="space-y-3">
            <?php foreach ($asignaturas as $a): ?>
            <li
                class="p-3 bg-white rounded-lg shadow-sm flex justify-between items-center transition duration-200 hover:bg-gray-100">
                <div>
                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($a['nombre']); ?></span>
                    <span class="text-xs text-gray-500 ml-2">(<?php echo htmlspecialchars($a['codigo']); ?>)</span>
                </div>
                <div class="flex items-center space-x-4 text-sm">
                    <span
                        class="font-medium px-2 py-1 rounded-full 
                                        <?php echo strtolower($a['estado']) == 'activa' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo htmlspecialchars($a['estado']); ?>
                    </span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
                            endif;
                        endforeach;
                        ?>

    <?php else: ?>
    <p class="text-gray-500">No hay asignaturas registradas para tu programa.</p>
    <?php endif; ?>
</div>