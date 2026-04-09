<!DOCTYPE html>
<html lang="es">

<?php
$basePath = $data['basePath'] ?? '/superarseconectadosv2/public';
$moduleCss = $data['moduleCss'] ?? [];
$moduleJs = $data['moduleJs'] ?? [];

$buildAssetVersion = function ($relativePath) {
    $fullPath = __DIR__ . '/../../../public/' . ltrim($relativePath, '/');
    return file_exists($fullPath) ? (string) filemtime($fullPath) : '1';
};
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido(a) <?php echo htmlspecialchars($data['nombreCompleto'] ?? 'Usuario'); ?> - Superarse Conectados v2
    </title>
    <link rel="icon" type="image/png" href="<?php echo $basePath; ?>/Assets/img/logoSuperarse.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="<?php echo $basePath; ?>/Assets/js/tailwind-config.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/variables.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/layout.css">
    <?php foreach ($moduleCss as $cssFile): ?>
    <link rel="stylesheet" href="<?php echo $basePath; ?>/Assets/css/<?php echo ltrim($cssFile, '/'); ?>">
    <?php endforeach; ?>
</head>

<body
    data-basepath="<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>"
    class="bg-gradient-to-r from-superarse-morado-oscuro via-superarse-morado-medio to-superarse-rosa min-h-screen flex flex-col pt-20">

    <header class="bg-superarse-morado-oscuro shadow-lg fixed top-0 left-0 w-full z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold text-white">Superarse Conectados</h1>
            <div class="flex items-center space-x-4">
                <span class="text-white text-sm hidden sm:block">
                    Bienvenido(a), <?php echo htmlspecialchars($data['nombreCompleto'] ?? 'N/D'); ?>
                </span>
                <a href="<?php echo $basePath; ?>/login/logout"
                    class="bg-superarse-rosa hover:bg-superarse-morado-medio text-white text-sm font-semibold py-1 px-3 rounded-full transition duration-300 shadow-md">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow flex justify-center pt-4 w-full">
        <div class="w-full flex justify-center">
            <?php include $vista_contenido;?>
        </div>
    </main>

    <footer class="bg-transparent text-white w-full py-3">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm m-0">&copy; 2025 Instituto Superarse. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script>
    const DATOS_ESTUDIANTE = <?php echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>;
    </script>
    <script src="<?php echo $basePath; ?>/Assets/js/datos.js?v=<?php echo $buildAssetVersion('Assets/js/datos.js'); ?>"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo $basePath; ?>/Assets/js/transferencia.js?v=<?php echo $buildAssetVersion('Assets/js/transferencia.js'); ?>"></script>
    <script src="<?php echo $basePath; ?>/Assets/js/payphone.js?v=<?php echo $buildAssetVersion('Assets/js/payphone.js'); ?>"></script>
    <?php foreach ($moduleJs as $jsFile): ?>
    <script src="<?php echo $basePath; ?>/Assets/js/<?php echo ltrim($jsFile, '/'); ?>?v=<?php echo $buildAssetVersion('Assets/js/' . ltrim($jsFile, '/')); ?>"></script>
    <?php endforeach; ?>
</body>

</html>