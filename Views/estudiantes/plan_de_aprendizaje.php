<?php
// Si no hay datos cargados, cargarlos desde la sesiÃ³n y base de datos
if (!isset($estudiante) || !isset($practica)) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Verificar que el usuario estÃ© logueado
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['identificacion'])) {
        die('Error: Debe iniciar sesiÃ³n para acceder al Plan de Aprendizaje.');
    }

    require_once __DIR__ . '/../../Models/PasantiaModel.php';
    require_once __DIR__ . '/../../Models/UserModel.php';

    $pasantiaModel = new PasantiaModel();
    $userModel = new UserModel();

    $userId = $_SESSION['id_usuario'];
    $practica = $pasantiaModel->getActivePracticaByUserId($userId);

    if (!$practica) {
        die('Error: No tiene una prÃ¡ctica registrada. Por favor registre una prÃ¡ctica primero.');
    }

    if (!$practica['estado_fase_uno_completado']) {
        die('Error: La Fase 1 debe estar completa y aprobada para acceder al Plan de Aprendizaje.');
    }

    $estudiante = $userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);

    // Obtener tutor acadÃ©mico
    $tutorAcademico = null;
    if (!empty($estudiante['programa'])) {
        $tutoresAcademicos = $userModel->getTutoresAcademicosByPrograma($estudiante['programa']);
        $tutorAcademico = !empty($tutoresAcademicos) ? $tutoresAcademicos[0] : null;
    }
}

// Si no hay basePath definido, establecer por defecto
if (!isset($basePath)) {
    // Configurar basePath segÃºn el entorno
    if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'superarse.ec') !== false) {
        $basePath = '';
    } else {
        $basePath = BasePath::detect();
    }
}

// Preparar datos del estudiante
$nombreCompleto = trim(($estudiante['primer_nombre'] ?? '') . ' ' .
    ($estudiante['segundo_nombre'] ?? '') . ' ' .
    ($estudiante['primer_apellido'] ?? '') . ' ' .
    ($estudiante['segundo_apellido'] ?? ''));
$programa = $estudiante['programa'] ?? '';
$nivel = $estudiante['nivel'] ?? '';
$cedula = $estudiante['numero_identificacion'] ?? '';
$correo = $estudiante['usuario'] ?? '';
$telefono = $estudiante['telefono'] ?? '';
$periodo = $estudiante['periodo'] ?? '';

// Datos de la empresa desde la fase uno
$nombreEmpresa = $practica['nombre_empresa'] ?? '';
$ruc = $practica['ruc'] ?? '';
$direccion = $practica['direccion'] ?? '';
$departamento = $practica['departamento'] ?? '';

// Datos del tutor empresarial
$nombreTutorEmpresarial = $practica['nombre_completo'] ?? '';
$telefonoTutorEmpresarial = $practica['telefono'] ?? '';
$correoTutorEmpresarial = $practica['email'] ?? '';

// Datos del tutor acadÃ©mico
$nombreTutorAcademico = $tutorAcademico['nombre_completo'] ?? '';
$correoTutorAcademico = $tutorAcademico['email'] ?? '';

// FunciÃ³n para normalizar texto (quitar tildes y convertir a mayÃºsculas)
function normalizar($texto)
{
    $texto = strtoupper($texto);
    $texto = str_replace(
        ['Ã', 'Ã‰', 'Ã', 'Ã“', 'Ãš', 'Ã‘'],
        ['A', 'E', 'I', 'O', 'U', 'N'],
        $texto
    );
    return trim($texto);
}

// Normalizar programa para comparaciÃ³n
$programaNormalizado = normalizar($programa);

// Extraer nÃºmero del nivel (puede venir como "Nivel 1", "N1", "1", etc.)
$nivelNumero = '';
if (preg_match('/\d+/', $nivel, $matches)) {
    $nivelNumero = $matches[0];
}
?>

<div class="container">
    <!-- Mensaje de Ã©xito (oculto por defecto) -->
    <div id="successMessage" class="success-message" style="display: none;">
        âœ“ Plan de Aprendizaje enviado exitosamente. Los datos se han guardado.
    </div>

    <!-- Encabezado del documento -->
    <div class="document-header">
        <div class="header-logo">
            <img src="<?php echo $basePath; ?>/Assets/img/LOGO SUPERARSE PNG-02.png" alt="Superarse TecnolÃ³gico" style="width: 140px; height: auto;">
        </div>
        <div class="header-title">
            <h1>GestiÃ³n de PrÃ¡cticas Pre Profesionales laborales9</h1>
            <h2>Plan de Aprendizaje PrÃ¡ctico</h2>
        </div>
        <div class="header-info">
            <div><strong>VERSIÃ“N:</strong> 002</div>
            <div><strong>CÃ“DIGO:</strong> ISTS-GIDIVS-05-004</div>
            <div><strong>FECHA:</strong> 22/11/2025</div>
        </div>
    </div>

    <form action="<?php echo $basePath; ?>/estudiante/generar-plan-aprendizaje-pdf" method="POST" id="planForm">
        <!-- SecciÃ³n 1: Datos del estudiante -->
        <div class="form-section">
            <h3>1. Datos del estudiante:</h3>
            <table>
                <tr>
                    <td>Apellidos y nombres</td>
                    <td><input type="text" name="apellidos_nombres" value="<?php echo htmlspecialchars($nombreCompleto); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Carrera</td>
                    <td><input type="text" name="carrera" value="<?php echo htmlspecialchars($programa); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Nivel</td>
                    <td><input type="text" name="nivel" value="<?php echo htmlspecialchars($nivel); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>CÃ©dula</td>
                    <td><input type="text" name="cedula" value="<?php echo htmlspecialchars($cedula); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Correo electrÃ³nico</td>
                    <td><input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>TelÃ©fono</td>
                    <td><input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" required></td>
                </tr>
            </table>
        </div>

        <!-- SecciÃ³n 2: Datos de la empresa -->
        <div class="form-section">
            <h3>2. Datos de la empresa:</h3>
            <table>
                <tr>
                    <td>Nombre legal de la entidad formadora</td>
                    <td><input type="text" name="nombre_empresa" value="<?php echo htmlspecialchars($nombreEmpresa); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>RUC</td>
                    <td><input type="text" name="ruc" value="<?php echo htmlspecialchars($ruc); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Tipo de entidad</td>
                    <td>
                        <select name="tipo_entidad" required>
                            <option value="">Seleccione el tipo de entidad</option>
                            <option value="Privada">Privada</option>
                            <option value="Publica">Publica</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Actividad econÃ³mica principal</td>
                    <td><input type="text" name="actividad_economica" required></td>
                </tr>
                <tr>
                    <td>UbicaciÃ³n</td>
                    <td><input type="text" name="ubicacion" value="<?php echo htmlspecialchars($direccion); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Ãrea/departamento donde realizarÃ¡ la prÃ¡ctica</td>
                    <td><input type="text" name="area_departamento" value="<?php echo htmlspecialchars($departamento); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Nombre del tutor empresarial</td>
                    <td><input type="text" name="nombre_tutor_empresarial" value="<?php echo htmlspecialchars($nombreTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>TelÃ©fono</td>
                    <td><input type="text" name="telefono_tutor_empresarial" value="<?php echo htmlspecialchars($telefonoTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Correo electrÃ³nico tutor</td>
                    <td><input type="email" name="correo_tutor_empresarial" value="<?php echo htmlspecialchars($correoTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>DescripciÃ³n general de la empresa</td>
                    <td><textarea name="descripcion_empresa" required></textarea></td>
                </tr>
            </table>
        </div>

        <!-- SecciÃ³n 3: Datos del periodo de prÃ¡cticas -->
        <div class="form-section">
            <h3>3. Datos del periodo de prÃ¡cticas</h3>
            <table>
                <tr>
                    <td>Periodo AcadÃ©mico</td>
                    <td><input type="text" name="periodo_academico" value="<?php echo htmlspecialchars($periodo); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Fecha de inicio</td>
                    <td><input type="date" name="fecha_inicio" required></td>
                </tr>
                <tr>
                    <td>Fecha de fin</td>
                    <td><input type="date" name="fecha_fin" required></td>
                </tr>
                <tr>
                    <td>Horario</td>
                    <td>
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="border: none; padding: 0; width: 50%;">
                                    <input type="text" name="horario" required>
                                </td>
                                <td style="border: none; padding: 0 0 0 10px; width: 50%;">
                                    <strong>NÃºmero de total de horas:</strong> <span style="display: inline-block; margin-left: 5px; font-weight: 600;">240</span>
                                    <input type="hidden" name="total_horas" value="240">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>Modalidad</td>
                    <td>
                        <select name="modalidad" required>
                            <option value="">Seleccione</option>
                            <option value="Presencial">Presencial</option>
                            <option value="En lÃ­nea">En lÃ­nea</option>
                            <option value="HÃ­brida">HÃ­brida</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Nombre del tutor acadÃ©mico</td>
                    <td><input type="text" name="nombre_tutor_academico" value="<?php echo htmlspecialchars($nombreTutorAcademico); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
                <tr>
                    <td>Correo tutor acadÃ©mico institucional</td>
                    <td><input type="email" name="correo_tutor_academico" value="<?php echo htmlspecialchars($correoTutorAcademico); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                </tr>
            </table>
        </div>

        <!-- SecciÃ³n 4: Objetivo de las prÃ¡cticas preprofesionales -->
        <div class="form-section">
            <h3>4. Objetivo de las prÃ¡cticas preprofesionales</h3>
            <div class="objective-text">
                Aplicar los conocimientos de la carrera de EducaciÃ³n BÃ¡sica en un entorno educativo real, apoyando los procesos de enseÃ±anza-aprendizaje, la planificaciÃ³n y gestiÃ³n didÃ¡ctica, la evaluaciÃ³n de los aprendizajes, y la atenciÃ³n a la diversidad e inclusiÃ³n, actuando con responsabilidad social, Ã©tica y enfoque humanista, en coherencia con el perfil de egreso.
            </div>
        </div>

        <!-- SecciÃ³n 5: Resultados de Aprendizaje -->
        <div class="form-section">
            <h3>5. Resultados de Aprendizaje</h3>
            <p style="margin-bottom: 10px; font-size: 12px;">Al finalizar las prÃ¡cticas preprofesionales, el estudiante serÃ¡ capaz de:</p>

            <table class="results-table">
                <thead>
                    <tr>
                        <th>Seleccionar con X</th>
                        <th>Resultados de Aprendizaje</th>
                        <th>Actividades relacionadas</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="checkbox" name="ra1" value="1"></td>
                        <td><strong>RA1.</strong> Reconocer el currÃ­culo, planes y programas oficiales aplicados en las instituciones educativas, en funciÃ³n de los distintos niveles de educaciÃ³n general bÃ¡sica.</td>
                        <td>A1, A2</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="ra2" value="1"></td>
                        <td><strong>RA2.</strong> Estructurar los procesos de enseÃ±anza-aprendizaje, en correlaciÃ³n entre la prÃ¡ctica pedagÃ³gica y las disciplinas considerando las diversas dimensiones de la pedagogÃ­a.</td>
                        <td>A1, A2, A3</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="ra3" value="1"></td>
                        <td><strong>RA3.</strong> Determinar el nivel de aprendizaje de los estudiantes utilizando estrategias e instrumentos sistemÃ¡ticos de evaluaciÃ³n, evidenciando comprensiÃ³n y logros.</td>
                        <td>A4, A6</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="ra4" value="1"></td>
                        <td><strong>RA4.</strong> Identificar los elementos del proceso didÃ¡ctico en el aula en correlaciÃ³n con una prÃ¡ctica educativa innovadora.</td>
                        <td>A3, A2</td>
                    </tr>
                    <tr>
                        <td><input type="checkbox" name="ra5" value="1"></td>
                        <td><strong>RA5.</strong> Establecer las implicaciones socioeducativas de la diversidad cultural para enfrentar desafÃ­os actuales en la sociedad, la familia y la educaciÃ³n, desde un enfoque inclusivo.</td>
                        <td>A5, A2, A6</td>
                    </tr>
                </tbody>
            </table>

            <div class="note-box">
                <strong>Nota.</strong> Marque con X los RA aplicables a este periodo, segÃºn el Ã¡rea asignada. Marque <strong>5 o RA.</strong> Las actividades y la evaluaciÃ³n se alinean a los <strong>RA</strong> marcados.
            </div>
        </div>

        <!-- SecciÃ³n 6: Actividades prÃ¡cticas esenciales -->
        <div class="form-section">
            <h3>6. Actividades prÃ¡cticas esenciales</h3>
            <ul class="activities-list">
                <li><strong>A1.</strong> Apoyar en la planificaciÃ³n microcurricular (propÃ³sitos, secuencia de actividades, recursos y tiempos) conforme al currÃ­culo institucional.</li>
                <li><strong>A2.</strong> Colaborar en la ejecuciÃ³n de actividades de aula (apoyo a grupos, acompaÃ±amiento individual, gestiÃ³n de aula y rutinas) bajo supervisiÃ³n.</li>
                <li><strong>A3.</strong> Elaborar y/o adaptar recursos didÃ¡cticos y estrategias de aprendizaje (incluyendo propuestas lÃºdicas e innovadoras) segÃºn el contexto.</li>
                <li><strong>A4.</strong> Apoyar en la aplicaciÃ³n de instrumentos de evaluaciÃ³n (rÃºbricas, listas de cotejo, pruebas, evidencias) y en el registro de resultados.</li>
                <li><strong>A5.</strong> Participar en acciones de atenciÃ³n a la diversidad (apoyos, ajustes razonables y estrategias de inclusiÃ³n) segÃºn orientaciones de la instituciÃ³n receptora.</li>
                <li><strong>A6.</strong> Registrar evidencias y avances en bitÃ¡cora, y elaborar reportes breves de actividades y hallazgos para retroalimentaciÃ³n del tutor.</li>
            </ul>
        </div>

        <!-- SecciÃ³n 7: Nota de Flexibilidad -->
        <div class="form-section">
            <h3>7. Nota de flexibilidad</h3>
            <div class="note-box">
                Las actividades son referenciales y podrÃ¡n ajustarse segÃºn el nivel, curso, asignatura y planificaciÃ³n de la instituciÃ³n receptora, manteniendo coherencia con los resultados de aprendizaje seleccionados y con validaciÃ³n del tutor acadÃ©mico y tutor institucional.
            </div>
        </div>

        <!-- SecciÃ³n 8: Seguimiento -->
        <div class="form-section">
            <h3>8. Seguimiento</h3>
            <ul class="activities-list">
                <li>Registro semanal en bitÃ¡cora individual del estudiante.</li>
                <li>ValidaciÃ³n del tutor empresarial.</li>
                <li>RevisiÃ³n y acompaÃ±amiento del tutor acadÃ©mico.</li>
            </ul>
        </div>

        <!-- SecciÃ³n 9: Evidencias -->
        <div class="form-section">
            <h3>9. Evidencias</h3>
            <ul class="activities-list">
                <li>BitÃ¡cora de prÃ¡cticas preprofesionales.</li>
                <li>PlanificaciÃ³n(es) y/o recursos didÃ¡cticos elaborados/adaptados.</li>
                <li>Instrumentos de evaluaciÃ³n aplicados y registros de resultados (segÃºn corresponda).</li>
                <li>Informe final con descripciÃ³n de actividades y propuesta breve de mejora.</li>
            </ul>
        </div>

        <!-- SecciÃ³n 10: EvaluaciÃ³n -->
        <div class="form-section">
            <h3>10. EvaluaciÃ³n</h3>
            <div style="padding: 10px; font-size: 12px; line-height: 1.6; text-align: justify;">
                La evaluaciÃ³n del desempeÃ±o serÃ¡ integral. El Tutor Empresarial valorarÃ¡ cualitativamente el cumplimiento de las actividades y el comportamiento profesional mediante una rÃºbrica institucional. Con base en dicha rÃºbrica y en las evidencias presentadas, el Tutor AcadÃ©mico consolidarÃ¡ la valoraciÃ³n y asignarÃ¡ la calificaciÃ³n final en el sistema institucional, conforme a la normativa de evaluaciÃ³n estudiantil vigente en el Instituto.
            </div>
        </div>

        <!-- SecciÃ³n 11: Responsables -->
        <div class="form-section">
            <h3>11. Responsables</h3>
            <table class="signatures-table">
                <thead>
                    <tr>
                        <th style="width: 50%; text-align: center; padding: 10px; background-color: #f0f0f0; border: 1px solid #999; font-weight: 700;">Tutor empresarial</th>
                        <th style="width: 50%; text-align: center; padding: 10px; background-color: #f0f0f0; border: 1px solid #999; font-weight: 700;">Tutor AcadÃ©mico</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="width: 50%; text-align: center; padding: 20px; vertical-align: bottom; height: 120px;">
                            <div style="margin-top: 60px; padding-top: 10px; border-top: 1px solid #999;">
                                <strong><?php echo htmlspecialchars($nombreTutorEmpresarial); ?></strong><br>
                                <span style="font-size: 11px;">Tutor Empresarial</span>
                            </div>
                        </td>
                        <td style="width: 50%; text-align: center; padding: 20px; vertical-align: bottom; height: 120px;">
                            <div style="margin-top: 60px; padding-top: 10px; border-top: 1px solid #999;">
                                <strong><?php echo htmlspecialchars($nombreTutorAcademico); ?></strong><br>
                                <span style="font-size: 11px;">Tutor AcadÃ©mico</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Botones de EnvÃ­o y Descarga -->
        <div class="submit-section">
            <button type="submit" class="btn-submit" id="btnEnviar">Enviar Plan de Aprendizaje</button>
            <button type="button" class="btn-pdf" id="btnPDF" onclick="descargarPDF()" style="display: none;">Descargar PDF</button>
        </div>
    </form>

    <!-- Footer -->
    <div class="footer">
        <p>DirecciÃ³n: Av. General RumiÃ±ahui e Isla Pinta 1111, a media cuadra del San Luis Shopping</p>
        <p>TelÃ©fono: (02) 393-0980</p>
        <p>www.superarse.edu.ec</p>
        <p style="margin-top: 10px;">PÃ¡gina 1 de 3</p>
    </div>
</div>

<script>
    // Variables para guardar datos del formulario
    let formDataSaved = null;

    // ValidaciÃ³n del formulario
    document.querySelector('#planForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Recopilar todos los datos del formulario
        const formData = new FormData(this);

        // Guardar datos en sessionStorage
        const dataObject = {};
        formData.forEach((value, key) => {
            dataObject[key] = value;
        });
        sessionStorage.setItem('planAprendizaje', JSON.stringify(dataObject));
        formDataSaved = dataObject;

        // Deshabilitar todos los campos del formulario
        disableFormFields();

        // Mostrar mensaje de Ã©xito
        document.getElementById('successMessage').style.display = 'block';

        // Ocultar botÃ³n de enviar y mostrar botÃ³n de PDF
        document.getElementById('btnEnviar').style.display = 'none';
        document.getElementById('btnPDF').style.display = 'inline-block';

        // Scroll al inicio para ver el mensaje
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });

        // Disparar de inmediato la generaciÃ³n/descarga para persistir en servidor
        descargarPDF();

        return false;
    });

    // FunciÃ³n para deshabilitar todos los campos del formulario
    function disableFormFields() {
        // Deshabilitar inputs de texto, email, date, number
        const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="date"], input[type="number"]');
        inputs.forEach(input => {
            input.readOnly = true;
            input.style.backgroundColor = '#f0f0f0';
        });

        // Deshabilitar selects
        const selects = document.querySelectorAll('select');
        selects.forEach(select => {
            select.disabled = true;
            select.style.backgroundColor = '#f0f0f0';
        });

        // Deshabilitar textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.readOnly = true;
            textarea.style.backgroundColor = '#f0f0f0';
        });

        // Deshabilitar checkboxes
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.disabled = true;
        });
    }

    // FunciÃ³n para descargar PDF
    function descargarPDF() {
        const data = sessionStorage.getItem('planAprendizaje');
        if (!data) {
            alert('No hay datos guardados para generar el PDF.');
            return;
        }

        // Detectar la URL base correcta
        let actionURL;

        // Si estamos en producciÃ³n (superarse.ec)
        if (window.location.hostname === 'superarse.ec') {
            actionURL = window.location.origin + '/estudiante/generar-plan-aprendizaje-pdf';
        }
        // Si estamos en desarrollo local
        else {
            actionURL = '<?php echo $basePath; ?>/estudiante/generar-plan-aprendizaje-pdf';
        }

        console.log('Action URL para PDF:', actionURL);

        // Crear formulario temporal para enviar datos
        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = actionURL;
        tempForm.target = '_blank';

        const dataObject = JSON.parse(data);
        for (const key in dataObject) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = dataObject[key];
            tempForm.appendChild(input);
        }

        document.body.appendChild(tempForm);
        console.log('Enviando formulario a:', tempForm.action);
        tempForm.submit();
        document.body.removeChild(tempForm);
    }

    // Verificar si hay datos guardados al cargar la pÃ¡gina
    window.addEventListener('load', function() {
        const savedData = sessionStorage.getItem('planAprendizaje');
        if (savedData) {
            const dataObject = JSON.parse(savedData);

            // Llenar el formulario con los datos guardados
            for (const key in dataObject) {
                const element = document.querySelector(`[name="${key}"]`);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = dataObject[key] === '1';
                    } else {
                        element.value = dataObject[key];
                    }
                }
            }

            // Deshabilitar campos y mostrar botones apropiados
            disableFormFields();
            document.getElementById('successMessage').style.display = 'block';
            document.getElementById('btnEnviar').style.display = 'none';
            document.getElementById('btnPDF').style.display = 'inline-block';
        }
    });
</script>
