<?php
$modulosConPagina = [
    [
        'label' => 'Vinculación',
        'path' => '/admin/reportes/vinculacion',
        'description' => 'Proyectos y proyectos por carrera.',
    ],
    [
        'label' => 'Investigación',
        'path' => '/admin/reportes/investigacion',
        'description' => 'Proyectos, publicaciones, ponencias y proyectos por carrera.',
    ],
    [
        'label' => 'Planificación',
        'path' => '/admin/reportes/planificacion',
        'description' => 'PEDI, POA y actividades de POA.',
    ],
];

$modulosDirectos = [
    [
        'key' => 'planificacion_pedi',
        'label' => 'PEDI',
        'description' => 'Plan Estratégico de Desarrollo Institucional.',
    ],
    [
        'key' => 'planificacion_poa_actividades',
        'label' => 'POA',
        'description' => 'Actividades del Plan Operativo Anual con cronograma y presupuesto.',
    ],
    [
        'key' => 'practicas',
        'label' => 'Prácticas',
        'description' => 'Descarga general de prácticas.',
    ],
    [
        'key' => 'convenios',
        'label' => 'Convenios',
        'description' => 'Descarga general de convenios.',
    ],
];
?>

<div class="space-y-6">
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <h2 class="text-lg font-semibold text-gray-800">Centro De Reportes</h2>
        <p class="text-sm text-gray-500 mt-1">Vinculación, Investigación y Planificación están divididos en páginas con reportes separados.</p>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($modulosConPagina as $item): ?>
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-base font-semibold text-gray-800"><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <a href="<?= $basePath . $item['path'] ?>" class="mt-3 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white font-semibold text-sm transition">
                        Abrir página
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-5">
            <h3 class="text-base font-semibold text-gray-800">Descarga Directa</h3>
            <p class="text-xs text-gray-500 mt-1">Para módulos que no requieren división adicional.</p>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php foreach ($modulosDirectos as $item): ?>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-800"><?= htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string) $item['description'], ENT_QUOTES, 'UTF-8') ?></p>

                        <form method="GET" action="<?= $basePath ?>/admin/reportes/export/modulo" class="mt-3 flex flex-wrap items-center gap-2">
                            <input type="hidden" name="module" value="<?= htmlspecialchars((string) $item['key'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if ($item['key'] === 'planificacion_poa_actividades'): ?>
                            <select name="area_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                <option value="">Todas las áreas</option>
                                <?php if (!empty($areas)): foreach ($areas as $area): ?>
                                <option value="<?= (int)$area['id'] ?>"><?= htmlspecialchars((string)($area['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                            <?php endif; ?>
                            <select name="format" class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white font-semibold text-sm transition">
                                Descargar
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>