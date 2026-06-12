<!DOCTYPE html>
<html lang="es">

<?php
require_once __DIR__ . '/../../Helpers/BasePath.php';
$basePath = $basePath ?? BasePath::detect();
$title = $title ?? 'Superarse Conectados';
$moduleCss = $moduleCss ?? [];
$moduleJs = $moduleJs ?? [];
$moduleHeadStyles = $moduleHeadStyles ?? [];
$moduleHeadScripts = $moduleHeadScripts ?? [];
$moduleHeadRaw = $moduleHeadRaw ?? [];
$moduleBodyScripts = $moduleBodyScripts ?? [];
$moduleBodyRaw = $moduleBodyRaw ?? [];
$bodyClass = $bodyClass ?? 'bg-gradient-to-r from-superarse-morado-oscuro via-superarse-morado-medio to-superarse-rosa min-h-screen flex flex-col';
$headerTitle = $headerTitle ?? 'Superarse Conectados';
$headerSubtitle = $headerSubtitle ?? '';
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="icon" type="image/png" href="<?= $basePath ?>/Assets/img/logoSuperarse.png" />

    <link rel="stylesheet" href="<?= $basePath ?>/Assets/css/variables.css">
    <?php foreach ($moduleCss as $cssFile): ?>
        <link rel="stylesheet" href="<?= $basePath ?>/Assets/css/<?= ltrim($cssFile, '/') ?>">
    <?php endforeach; ?>

    <?php foreach ($moduleHeadStyles as $styleHref): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($styleHref) ?>">
    <?php endforeach; ?>

    <?php foreach ($moduleHeadScripts as $scriptSrc): ?>
        <script src="<?= htmlspecialchars($scriptSrc) ?>"></script>
    <?php endforeach; ?>

    <?php foreach ($moduleHeadRaw as $rawHeadTag): ?>
        <?= $rawHeadTag ?>
    <?php endforeach; ?>
</head>

<body style="background: linear-gradient(to right, #4A148C, #673AB7, #E91E63) !important; min-height: 100vh; display: flex; flex-direction: column; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0;">
    <header class="bg-transparent text-white w-full py-4 shadow-lg" style="background: transparent; color: white; width: 100%; padding: 1rem 0; box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);">
        <div class="max-w-7xl mx-auto px-4 text-center" style="max-width: 80rem; margin: 0 auto; padding: 0 1rem; text-align: center;">
            <img src="<?= $basePath ?>/Assets/img/logoSuperarse.png"
                alt="Logo de Superarse" class="logo h-20 w-auto mx-auto mb-4" style="height: 5rem; width: auto; margin: 0 auto 1rem;">

            <p class="text-xl font-light m-0 font-semibold" style="font-size: 1.25rem; font-weight: 600; margin: 0;"><?= htmlspecialchars($headerTitle) ?></p>
            <?php if (!empty($headerSubtitle)): ?>
                <p class="text-sm font-light m-0" style="font-size: 0.875rem; font-weight: 300; margin: 0;"><?= htmlspecialchars($headerSubtitle) ?></p>
            <?php endif; ?>
        </div>
    </header>

    <?php require $content; ?>

    <footer class="bg-transparent text-white w-full py-3">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm m-0">&copy; 2025 Instituto Superarse. Todos los derechos reservados.</p>
        </div>
    </footer>

    <?php foreach ($moduleBodyScripts as $scriptSrc): ?>
        <script src="<?= htmlspecialchars($scriptSrc) ?>"></script>
    <?php endforeach; ?>

    <?php foreach ($moduleBodyRaw as $rawBodyTag): ?>
        <?= $rawBodyTag ?>
    <?php endforeach; ?>

    <?php foreach ($moduleJs as $jsFile): ?>
        <script src="<?= $basePath ?>/Assets/js/<?= ltrim($jsFile, '/') ?>"></script>
    <?php endforeach; ?>
</body>

</html>
