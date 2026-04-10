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

<body class="bg-gray-100 min-h-screen flex flex-col pt-0 lg:pt-20">

    <!-- NAVBAR SUPERIOR -->
    <header class="bg-superarse-morado-oscuro shadow-lg w-full z-50 static lg:fixed top-0 left-0">

        <div class="max-w-7xl mx-auto px-4 py-3">

            <div class="flex justify-between items-center gap-3">
                <!-- LOGO -->
                <div class="flex items-center whitespace-nowrap shrink-0 pr-2">
                    <h1 class="text-xl font-bold text-white whitespace-nowrap">Superarse Conectados Admin</h1>
                </div>

                <!-- MENU DESKTOP -->
                <nav class="hidden lg:flex items-center space-x-3 xl:space-x-6 text-white text-sm">
                    <?php foreach ($adminMainNavItems as $item): ?>
                        <?php if (!$canAccessModule($item['module'])) continue; ?>
                        <a href="<?php echo $basePath . $item['path']; ?>" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition<?php echo !empty($item['badge']) ? ' relative' : ''; ?>">
                            <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if (!empty($item['badge']) && ($pendingResetCount ?? 0) > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                                    <?= min((int)$pendingResetCount, 99) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>

                    <?php if ($canAccessAdministrationMenu): ?>
                        <div class="relative group">
                            <button type="button" class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition flex items-center gap-1 focus:bg-superarse-morado-medio">
                                Administración
                                <span class="text-xs">▼</span>
                            </button>
                            <div class="absolute left-0 top-full pt-1 min-w-52 bg-white text-gray-800 rounded-md shadow-lg border border-gray-200 py-1 hidden group-hover:block group-focus-within:block z-50">
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
    <main class="flex-grow p-4 sm:p-6 w-full lg:w-[90%] lg:mx-[5%]">

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