<div class="flex flex-col items-center justify-center bg-white border border-gray-200 rounded-lg shadow-sm p-8 mt-8 mb-8 text-base" style="font-size: 80%;">
    <?php
    $detectedBasePath = rtrim(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    $detectedBasePath = ($detectedBasePath === '' || $detectedBasePath === '.') ? '' : $detectedBasePath;
    $basePath = $data['basePath'] ?? ($basePath ?? $detectedBasePath);
    ?>
    <h2 class="text-xl font-bold text-center text-indigo-800 mb-2">Plan de Aprendizaje</h2>
    <p class="text-center text-gray-600 mb-4">
        Para ingresar al plan de aprendizaje, haz clic en el siguiente enlace.
    </p>

    <?php
    $formUrl = $basePath . '/estudiante/plan-aprendizaje';
    ?>

    <a href="<?= $formUrl ?>" target="_blank"
        class="inline-flex items-center gap-2 px-8 py-3 bg-pink-600 text-white font-semibold rounded-lg hover:bg-pink-700 transition text-base"
        style="font-size: 80%;">
        Completar Plan de Aprendizaje
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </a>
</div>