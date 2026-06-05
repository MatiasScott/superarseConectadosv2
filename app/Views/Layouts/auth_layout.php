<!DOCTYPE html>
<html lang="es">

<?php
$basePath = $basePath ?? '/superarseconectadosv2/public';
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?= $basePath ?>/Assets/js/tailwind-config.js"></script>

    <?php foreach ($moduleHeadStyles as $styleHref): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($styleHref) ?>">
    <?php endforeach; ?>

    <?php foreach ($moduleHeadScripts as $scriptSrc): ?>
        <script src="<?= htmlspecialchars($scriptSrc) ?>"></script>
    <?php endforeach; ?>

    <?php foreach ($moduleHeadRaw as $rawHeadTag): ?>
        <?= $rawHeadTag ?>
    <?php endforeach; ?>

    <link rel="stylesheet" href="<?= $basePath ?>/Assets/css/variables.css">
    <?php foreach ($moduleCss as $cssFile): ?>
        <link rel="stylesheet" href="<?= $basePath ?>/Assets/css/<?= ltrim($cssFile, '/') ?>">
    <?php endforeach; ?>
</head>

<body class="<?= htmlspecialchars($bodyClass) ?>">
    <header class="bg-transparent text-white w-full py-4 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <img src="<?= $basePath ?>/Assets/img/LOGO SUPERARSE PNG-02.png"
                onerror="this.onerror=null; this.src='<?= $basePath ?>/Assets/img/LOGO SUPERARSE PNG-02.png';"
                alt="Logo de Superarse" class="logo h-20 w-auto mx-auto mb-4">

            <p class="text-xl font-light m-0 font-semibold"><?= htmlspecialchars($headerTitle) ?></p>
            <?php if (!empty($headerSubtitle)): ?>
                <p class="text-sm font-light m-0"><?= htmlspecialchars($headerSubtitle) ?></p>
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