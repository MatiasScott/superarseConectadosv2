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

    if ($moduleKey === 'plan_estrategico') {
        return !empty($adminPermissionState['matrix']['plan_estrategico']['view'])
            || !empty($adminPermissionState['matrix']['pedi']['view'])
            || !empty($adminPermissionState['matrix']['poa']['view']);
    }

    return !empty($adminPermissionState['matrix'][$moduleKey]['view']);
};

$adminMainNavItems = [
    ['module' => 'dashboard', 'label' => 'Dashboard', 'path' => '/admin/dashboard'],
    ['module' => 'practicas', 'label' => 'Prácticas', 'path' => '/admin/practicas'],
    ['module' => 'vinculacion', 'label' => 'Vinculación', 'path' => '/admin/vinculacion'],
    ['module' => 'investigacion', 'label' => 'Investigación', 'path' => '/admin/investigacion'],
    ['module' => 'plan_estrategico', 'label' => 'Planificación', 'path' => '/admin/plan-estrategico'],
    ['module' => 'convenios', 'label' => 'Convenios', 'path' => '/admin/convenio'],
    ['module' => 'reportes', 'label' => 'Reportes', 'path' => '/admin/reportes'],
];

$adminManagementNavItems = [
    ['module' => 'auditoria', 'label' => 'Auditoría', 'path' => '/admin/auditoria-general'],
    ['module' => 'cuentas', 'label' => 'Cuentas', 'path' => '/admin/accounts'],
    ['module' => 'solicitudes', 'label' => 'Solicitudes', 'path' => '/admin/reset-requests', 'badge' => true],
    ['module' => 'configuracion', 'label' => 'Configuración', 'path' => '/admin/configuracion'],
];

$canAccessAdministrationMenu = false;
foreach ($adminManagementNavItems as $managementItem) {
    if ($canAccessModule($managementItem['module'])) {
        $canAccessAdministrationMenu = true;
        break;
    }
}
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

<body class="bg-gray-100 min-h-screen flex flex-col">

    <!-- NAVBAR SUPERIOR -->
    <header class="bg-superarse-morado-oscuro shadow-lg w-full z-50 sticky top-0">

        <div class="px-3 sm:px-4 py-2">

            <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                <!-- LOGO -->
                <div class="flex items-center shrink-0">
                    <h1 class="text-base sm:text-lg xl:text-xl font-bold text-white truncate max-w-[180px] sm:max-w-none">Superarse Conectados</h1>
                </div>

                <!-- MENU DESKTOP -->
                <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-white text-sm xl:text-base flex-wrap flex-1 justify-center min-w-0 px-2">
                    <?php foreach ($adminMainNavItems as $item): ?>
                        <?php if (!$canAccessModule($item['module'])) continue; ?>
                        <a href="<?php echo $basePath . $item['path']; ?>" class="hover:bg-superarse-morado-medio px-2 xl:px-3 py-1.5 rounded transition whitespace-nowrap<?php echo !empty($item['badge']) ? ' relative' : ''; ?>">
                            <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($item['badge']) && ($pendingResetCount ?? 0) > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold">
                                    <?= min((int)$pendingResetCount, 99) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($canAccessAdministrationMenu): ?>
                        <div class="relative group">
                            <button type="button" class="hover:bg-superarse-morado-medio px-2 xl:px-3 py-1.5 rounded transition flex items-center gap-1 whitespace-nowrap focus:bg-superarse-morado-medio text-sm xl:text-base">
                                Admin
                                <span class="text-xs">▼</span>
                            </button>
                            <div class="absolute left-0 top-full pt-1 min-w-44 bg-white text-gray-800 rounded-md shadow-lg border border-gray-200 py-1 hidden group-hover:block group-focus-within:block z-50">
                                <?php foreach ($adminManagementNavItems as $item): ?>
                                    <?php if (!$canAccessModule($item['module'])) continue; ?>
                                    <a href="<?php echo $basePath . $item['path']; ?>" class="px-3 py-2 hover:bg-gray-100 transition text-sm flex items-center justify-between">
                                        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if (!empty($item['badge']) && ($pendingResetCount ?? 0) > 0): ?>
                                            <span class="bg-red-500 text-white text-[10px] rounded-full min-w-5 h-5 px-1.5 inline-flex items-center justify-center font-bold">
                                                <?= min((int)$pendingResetCount, 99) ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </nav>

                <!-- USUARIO DESKTOP -->
                <div class="hidden lg:flex items-center gap-2 shrink-0">
                    <span class="text-white text-xs xl:text-sm truncate max-w-24 xl:max-w-40">
                        <?= $nombreCompleto ?>
                    </span>
                    <a href="<?php echo $basePath; ?>/admin/logout"
                        class="bg-superarse-rosa hover:bg-superarse-morado-medio text-white text-xs font-semibold py-1 px-2.5 rounded-full transition shadow-md whitespace-nowrap">
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
            <nav class="lg:hidden mt-2 grid grid-cols-2 sm:grid-cols-4 gap-1 text-white text-xs sm:text-sm">
                <?php foreach ($adminMainNavItems as $item): ?>
                    <?php if (!$canAccessModule($item['module'])) continue; ?>
                    <a href="<?php echo $basePath . $item['path']; ?>" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition<?php echo !empty($item['badge']) ? ' relative' : ''; ?>">
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                        <?php if (!empty($item['badge']) && ($pendingResetCount ?? 0) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold">
                                <?= min((int)$pendingResetCount, 99) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>

                <?php if ($canAccessAdministrationMenu): ?>
                    <div class="col-span-2 sm:col-span-4 bg-superarse-morado-medio/20 rounded p-2">
                        <p class="font-semibold text-center mb-2">Administración</p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            <?php foreach ($adminManagementNavItems as $item): ?>
                                <?php if (!$canAccessModule($item['module'])) continue; ?>
                                <a href="<?php echo $basePath . $item['path']; ?>" class="text-center bg-superarse-morado-medio/30 hover:bg-superarse-morado-medio px-2 py-2 rounded transition relative">
                                    <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if (!empty($item['badge']) && ($pendingResetCount ?? 0) > 0): ?>
                                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] rounded-full w-4 h-4 flex items-center justify-center font-bold">
                                            <?= min((int)$pendingResetCount, 99) ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </nav>

            <p class="lg:hidden text-white/85 text-xs mt-2 truncate"><?= htmlspecialchars($nombreCompleto ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>

    </header>

    <!-- CONTENIDO -->
    <main class="flex-grow p-3 sm:p-4 w-full overflow-x-auto">

        <h1 class="font-semibold text-base sm:text-lg mb-3">
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