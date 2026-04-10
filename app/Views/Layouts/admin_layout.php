<!DOCTYPE html>
<html lang="es">

<?php
$moduleCss = $moduleCss ?? [];
$moduleJs = $moduleJs ?? [];
$moduleHeadScripts = $moduleHeadScripts ?? [];
$moduleBodyScripts = $moduleBodyScripts ?? [];

$adminPermissionState = $_SESSION['admin_permissions'] ?? ['enabled' => false, 'matrix' => []];
$canAccessModule = function ($moduleKey) use ($adminPermissionState) {
    if (empty($adminPermissionState['enabled'])) {
        return true;
    }

    return !empty($adminPermissionState['matrix'][$moduleKey]['view']);
};
?>

<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Panel Admin' ?></title>

    <link rel="icon" type="image/png" href="<?php echo $basePath; ?>/Assets/img/logoSuperarse.png" />

    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/layout.css">
    <?php foreach ($moduleCss as $cssFile): ?>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/<?php echo ltrim($cssFile, '/'); ?>">
    <?php endforeach; ?>

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'superarse-morado-oscuro': '#4A148C',
                        'superarse-morado-medio': '#673AB7',
                        'superarse-rosa': '#E91E63',
                    }
                }
            }
        }
    </script>

    <?php foreach ($moduleHeadScripts as $scriptSrc): ?>
    <script src="<?php echo htmlspecialchars($scriptSrc, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>

</head>

<body class="bg-gray-100 min-h-screen flex flex-col pt-0 lg:pt-20">

    <!-- NAVBAR SUPERIOR -->
    <header class="bg-superarse-morado-oscuro shadow-lg w-full z-50 static lg:fixed top-0 left-0">

        <div class="max-w-7xl mx-auto px-4 py-3">

            <div class="flex justify-between items-center gap-3">
                <!-- LOGO -->
                <h1 class="text-lg sm:text-xl font-bold text-white whitespace-nowrap">
                    Superarse Admin
                </h1>

                <!-- MENU DESKTOP -->
                <nav class="hidden lg:flex items-center space-x-3 xl:space-x-6 text-white text-sm">
                    <?php if ($canAccessModule('dashboard')): ?>
                        <a href="<?php echo $basePath; ?>/admin/dashboard" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Dashboard</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('practicas')): ?>
                        <a href="<?php echo $basePath; ?>/admin/practicas" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Prácticas</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('vinculacion')): ?>
                        <a href="<?php echo $basePath; ?>/admin/vinculacion" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Vinculación</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('investigacion')): ?>
                        <a href="<?php echo $basePath; ?>/admin/investigacion" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Investigación</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('plan_estrategico')): ?>
                        <a href="<?php echo $basePath; ?>/admin/plan-estrategico" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Planificación</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('convenios')): ?>
                        <a href="<?php echo $basePath; ?>/admin/convenio" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Convenios</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('auditoria')): ?>
                        <a href="<?php echo $basePath; ?>/admin/auditoria-general" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Auditoría</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('reportes')): ?>
                        <a href="<?php echo $basePath; ?>/admin/reportes" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Reportes</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('cuentas')): ?>
                        <a href="<?php echo $basePath; ?>/admin/accounts" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">Cuentas</a>
                    <?php endif; ?>
                    <?php if ($canAccessModule('solicitudes')): ?>
                        <a href="<?php echo $basePath; ?>/admin/reset-requests" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition relative">
                            Solicitudes
                            <?php if (($pendingResetCount ?? 0) > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                    <?= min((int)$pendingResetCount, 99) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </nav>

                <!-- USUARIO DESKTOP -->
                <div class="hidden lg:flex items-center space-x-4">
                    <span class="text-white text-sm">
                        <?= $nombreCompleto ?>
                    </span>
                    <a href="<?php echo $basePath; ?>/admin/logout"
                        class="bg-superarse-rosa hover:bg-superarse-morado-medio text-white text-sm font-semibold py-1 px-3 rounded-full transition shadow-md">
                        Salir
                    </a>
                </div>

                <!-- SALIR MOBILE -->
                <a href="<?php echo $basePath; ?>/admin/logout"
                    class="lg:hidden bg-superarse-rosa hover:bg-superarse-morado-medio text-white text-xs font-semibold py-1.5 px-3 rounded-full transition shadow-md whitespace-nowrap">
                    Salir
                </a>
            </div>

            <!-- MENU MOBILE -->
            <nav class="lg:hidden mt-3 grid grid-cols-2 sm:grid-cols-4 gap-2 text-white text-xs sm:text-sm">
                <?php if ($canAccessModule('dashboard')): ?>
                    <a href="<?php echo $basePath; ?>/admin/dashboard" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Dashboard</a>
                <?php endif; ?>
                <?php if ($canAccessModule('practicas')): ?>
                    <a href="<?php echo $basePath; ?>/admin/practicas" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Prácticas</a>
                <?php endif; ?>
                <?php if ($canAccessModule('vinculacion')): ?>
                    <a href="<?php echo $basePath; ?>/admin/vinculacion" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Vinculación</a>
                <?php endif; ?>
                <?php if ($canAccessModule('investigacion')): ?>
                    <a href="<?php echo $basePath; ?>/admin/investigacion" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Investigación</a>
                <?php endif; ?>
                <?php if ($canAccessModule('plan_estrategico')): ?>
                    <a href="<?php echo $basePath; ?>/admin/plan-estrategico" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Planificación</a>
                <?php endif; ?>
                <?php if ($canAccessModule('convenios')): ?>
                    <a href="<?php echo $basePath; ?>/admin/convenio" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Convenios</a>
                <?php endif; ?>
                <?php if ($canAccessModule('auditoria')): ?>
                    <a href="<?php echo $basePath; ?>/admin/auditoria-general" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Auditoría</a>
                <?php endif; ?>
                <?php if ($canAccessModule('reportes')): ?>
                    <a href="<?php echo $basePath; ?>/admin/reportes" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Reportes</a>
                <?php endif; ?>
                <?php if ($canAccessModule('cuentas')): ?>
                    <a href="<?php echo $basePath; ?>/admin/accounts" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition">Cuentas</a>
                <?php endif; ?>
                <?php if ($canAccessModule('solicitudes')): ?>
                    <a href="<?php echo $basePath; ?>/admin/reset-requests" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition relative">
                        Solicitudes
                        <?php if (($pendingResetCount ?? 0) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold">
                                <?= min((int)$pendingResetCount, 99) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </nav>

            <p class="lg:hidden text-white/85 text-xs mt-2 truncate"><?= htmlspecialchars($nombreCompleto ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>

    </header>

    <!-- CONTENIDO -->
    <main class="flex-grow p-4 sm:p-6 max-w-7xl mx-auto w-full">

        <h1 class="font-semibold text-xl mb-4">
            <?= $title ?? '' ?>
        </h1>

        <?php require $content; ?>

    </main>

    <?php foreach ($moduleBodyScripts as $scriptSrc): ?>
    <script src="<?php echo htmlspecialchars($scriptSrc, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
    <?php foreach ($moduleJs as $jsFile): ?>
    <script src="<?php echo $basePath; ?>/Assets/js/<?php echo ltrim($jsFile, '/'); ?>"></script>
    <?php endforeach; ?>

</body>

</html>