<?php
$moduleTitle = $moduleTitle ?? 'Módulo';
$sections = $sections ?? [];
?>

<div class="space-y-6">
    <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Reportes De <?= htmlspecialchars((string) $moduleTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-sm text-gray-500 mt-1">Cada reporte está separado y se puede descargar en Excel o PDF.</p>
            </div>
            <a href="<?= $basePath ?>/admin/reportes" class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm transition">Volver a módulos</a>
        </div>

        <div class="mt-5 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php if (empty($sections)): ?>
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 text-sm text-gray-500">
                    No hay secciones configuradas para este módulo.
                </div>
            <?php else: ?>
                <?php foreach ($sections as $section): ?>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <h3 class="text-base font-semibold text-gray-800"><?= htmlspecialchars((string) ($section['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars((string) ($section['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>

                        <form method="GET" action="<?= $basePath ?>/admin/reportes/export/modulo" class="mt-3 flex items-center gap-2">
                            <input type="hidden" name="module" value="<?= htmlspecialchars((string) ($section['key'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <select name="format" class="border border-gray-300 rounded-lg px-3 py-2 text-sm text-gray-700">
                                <option value="excel">Excel</option>
                                <option value="pdf">PDF</option>
                            </select>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-lg bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white font-semibold text-sm transition"
                            >
                                Descargar
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
