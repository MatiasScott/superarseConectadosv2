<div class="flex flex-col items-center justify-center bg-white border border-gray-200 rounded-lg shadow-sm p-8 mt-8 mb-8 text-base" style="font-size: 80%;">
    <?php $basePath = $data['basePath'] ?? ($basePath ?? '/superarseconectadosv2/public'); ?>
    <h2 class="text-xl font-bold text-center text-indigo-800 mb-2">Plan de Aprendizaje</h2>
    <p class="text-center text-gray-600 mb-4">
        Para ingresar al plan de aprendizaje, haz clic en el siguiente enlace. Serás redirigido al PDF.
    </p>

    <?php
    // Función para normalizar texto (eliminar tildes y convertir a mayúsculas)
    function normalizarTexto($texto)
    {
        $texto = strtoupper($texto);
        $tildes = ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N'];
        return strtr($texto, $tildes);
    }

    // Mapeo de programas a archivos (una sola vez, sin duplicados)
    $programaFileMap = [
        'ADMINISTRACION' => 'plan_de_aprendizaje_administracion',
        'EDUCACION BASICA' => 'plan_de_aprendizaje_educacion_basica',
        'ENFERMERIA' => 'plan_de_aprendizaje_enfermeria',
        'MARKETING' => 'plan_de_aprendizaje_marketing',
        'PRODUCCION ANIMAL' => 'plan_de_aprendizaje_produccion_animal',
        'SEGURIDAD Y PREVENCION DE RIESGOS LABORALES' => 'plan_de_aprendizaje_seguridad_prevencion',
        'TOPOGRAFIA' => 'plan_de_aprendizaje_topografia'
    ];

    // Determinar el archivo basándose en el programa del estudiante
    $programaEstudiante = $data['infoPersonal']['programa'] ?? '';
    $programaNormalizado = normalizarTexto($programaEstudiante);
    $fileName = null;

    // Buscar coincidencia exacta o parcial (normalizando ambos textos)
    foreach ($programaFileMap as $programa => $file) {
        if (stripos($programaNormalizado, $programa) !== false || stripos($programa, $programaNormalizado) !== false) {
            $fileName = $file;
            break;
        }
    }

    // Si no hay mapeo, intentar usar el campo file de infoPrograma
    if (!$fileName && isset($data['infoPrograma']['file']) && !empty($data['infoPrograma']['file'])) {
        $fileName = $data['infoPrograma']['file'];
    }

    if ($fileName) {
        // Verificar si es un formulario (todos ahora son formularios de plan de aprendizaje)
        if (strpos($fileName, 'plan_de_aprendizaje_') === 0) {
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
            <?php
        } else {
            // Es un PDF
            $filePath = __DIR__ . '/../../../public/Assets/files/' . $fileName . '.pdf';

            if (file_exists($filePath)) {
                $fileUrl = $basePath . '/Assets/files/' . $fileName . '.pdf';
            ?>
                <a href="<?= $fileUrl ?>" target="_blank"
                    class="inline-flex items-center gap-2 px-8 py-3 bg-pink-600 text-white font-semibold rounded-lg hover:bg-pink-700 transition text-base"
                    style="font-size: 80%;">
                    Ver PDF
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 13v6a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h6M15 3h6m0 0v6m0-6L10 14" />
                    </svg>
                </a>
    <?php
            } else {
                echo '<p class="text-red-600 text-center">El archivo no se encontró en la carpeta "assets/files".</p>';
                echo "<p class='text-xs text-gray-500'>Programa: " . htmlspecialchars($programaEstudiante) . "</p>";
                echo "<p class='text-xs text-gray-500'>Archivo buscado: {$fileName}.pdf</p>";
                echo "<p class='text-xs text-gray-500'>Ruta: {$filePath}</p>";
            }
        }
    } else {
        echo '<p class="text-gray-500 text-center">No se pudo determinar el archivo para el programa: <strong>' . htmlspecialchars($programaEstudiante) . '</strong></p>';
        echo '<p class="text-xs text-gray-500 text-center">Por favor, contacta al administrador.</p>';
    }
    ?>
</div>