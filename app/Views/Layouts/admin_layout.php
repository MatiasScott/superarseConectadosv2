<!DOCTYPE html>
<html lang="es">

<?php
$moduleCss = $moduleCss ?? [];
$moduleJs = $moduleJs ?? [];
$moduleHeadScripts = $moduleHeadScripts ?? [];
$moduleBodyScripts = $moduleBodyScripts ?? [];
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

<body class="bg-gray-100 min-h-screen flex flex-col pt-20">

    <!-- NAVBAR SUPERIOR -->
    <header class="bg-superarse-morado-oscuro shadow-lg fixed top-0 left-0 w-full z-50">

        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">

            <!-- LOGO -->
            <h1 class="text-xl font-bold text-white">
                Superarse Admin
            </h1>

            <!-- MENU -->
            <nav class="flex items-center space-x-6 text-white text-sm">

                <a href="<?php echo $basePath; ?>/admin/dashboard"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Dashboard
                </a>

                <a href="<?php echo $basePath; ?>/admin/practicas"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Prácticas
                </a>

                <a href="<?php echo $basePath; ?>/admin/vinculacion"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Vinculación
                </a>

                <a href="<?php echo $basePath; ?>/admin/investigacion"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Investigación
                </a>

                <a href="<?php echo $basePath; ?>/admin/plan-estrategico"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Planificación
                </a>

                <a href="<?php echo $basePath; ?>/admin/convenio"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Convenios
                </a>

                <a href="<?php echo $basePath; ?>/admin/accounts"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition">
                    Cuentas
                </a>

                <a href="<?php echo $basePath; ?>/admin/reset-requests"
                    class="hover:bg-superarse-morado-medio px-3 py-1 rounded transition relative">
                    Solicitudes
                    <?php if (($pendingResetCount ?? 0) > 0): ?>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold">
                            <?= min((int)$pendingResetCount, 99) ?>
                        </span>
                    <?php endif; ?>
                </a>

            </nav>

            <!-- USUARIO -->
            <div class="flex items-center space-x-4">

                <span class="text-white text-sm hidden md:block">
                    <?= $nombreCompleto ?>
                </span>

                <a href="<?php echo $basePath; ?>/admin/logout"
                    class="bg-superarse-rosa hover:bg-superarse-morado-medio text-white text-sm font-semibold py-1 px-3 rounded-full transition shadow-md">
                    Salir
                </a>

            </div>

        </div>

    </header>

    <!-- CONTENIDO -->
    <main class="flex-grow p-6 max-w-7xl mx-auto w-full">

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