<div class="max-w-3xl mx-auto mt-6">
    <section class="bg-white border border-amber-200 rounded-xl shadow-sm p-8 text-center">
        <p class="text-sm font-semibold text-amber-600 uppercase tracking-wider">Estado del módulo</p>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">
            <?= htmlspecialchars((string) ($moduleTitle ?? 'Módulo'), ENT_QUOTES, 'UTF-8') ?> en mantenimiento
        </h1>
        <p class="text-gray-600 mt-4">
            <?= htmlspecialchars((string) ($maintenanceMessage ?? 'Estamos realizando mejoras en este módulo.'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <a
            href="<?= $basePath ?>/admin/dashboard"
            class="inline-flex mt-6 items-center justify-center px-5 py-2.5 rounded-lg bg-superarse-morado-medio hover:bg-superarse-morado-oscuro text-white font-semibold text-sm transition"
        >
            Volver al dashboard
        </a>
    </section>
</div>
