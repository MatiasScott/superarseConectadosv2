<?php
// Si no hay datos cargados, cargarlos desde la sesión y base de datos
if (!isset($estudiante) || !isset($practica)) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['identificacion'])) {
        die('Error: Debe iniciar sesión para acceder al Plan de Aprendizaje.');
    }
    
    require_once __DIR__ . '/../../Models/PasantiaModel.php';
    require_once __DIR__ . '/../../Models/UserModel.php';
    
    $pasantiaModel = new PasantiaModel();
    $userModel = new UserModel();
    
    $userId = $_SESSION['id_usuario'];
    $practica = $pasantiaModel->getActivePracticaByUserId($userId);
    
    if (!$practica) {
        die('Error: No tiene una práctica registrada. Por favor registre una práctica primero.');
    }
    
    if (!$practica['estado_fase_uno_completado']) {
        die('Error: La Fase 1 debe estar completa y aprobada para acceder al Plan de Aprendizaje.');
    }
    
    $estudiante = $userModel->getUserInfoByIdentificacion($_SESSION['identificacion']);
    
    // Obtener tutor académico
    $tutorAcademico = null;
    if (!empty($estudiante['programa'])) {
        $tutoresAcademicos = $userModel->getTutoresAcademicosByPrograma($estudiante['programa']);
        $tutorAcademico = !empty($tutoresAcademicos) ? $tutoresAcademicos[0] : null;
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

// Datos del tutor académico
$nombreTutorAcademico = $tutorAcademico['nombre_completo'] ?? '';
$correoTutorAcademico = $tutorAcademico['email'] ?? '';

// Función para normalizar texto (quitar tildes y convertir a mayúsculas)
function normalizar($texto) {
    $texto = strtoupper($texto);
    $texto = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['A', 'E', 'I', 'O', 'U', 'N'],
        $texto
    );
    return trim($texto);
}

// Normalizar programa para comparación
$programaNormalizado = normalizar($programa);

// Extraer número del nivel (puede venir como "Nivel 1", "N1", "1", etc.)
$nivelNumero = '';
if (preg_match('/\d+/', $nivel, $matches)) {
    $nivelNumero = $matches[0];
}
?>

    <div class="container">
        <!-- Mensaje de éxito (oculto por defecto) -->
        <div id="successMessage" class="success-message" style="display: none;">
            ✓ Plan de Aprendizaje enviado exitosamente. Los datos se han guardado.
        </div>
        
        <!-- Encabezado del documento -->
        <div class="document-header">
            <div class="header-logo">
                <img src="<?php echo $basePath; ?>/Assets/img/LOGO SUPERARSE PNG-02.png" alt="Superarse Tecnológico" style="width: 140px; height: auto;">
            </div>
            <div class="header-title">
                <h1>Gestión de Prácticas Pre Profesionales laborales2</h1>
                <h2>Plan de Aprendizaje Práctico</h2>
            </div>
            <div class="header-info">
                <div><strong>VERSIÓN:</strong> 002</div>
                <div><strong>CÓDIGO:</strong> ISTS-GIDIVS-05-004</div>
                <div><strong>FECHA:</strong> 22/11/2025</div>
            </div>
        </div>

        <form action="<?php echo $basePath; ?>/estudiante/generar-plan-aprendizaje-pdf" method="POST" id="planForm">
            <!-- Sección 1: Datos del estudiante -->
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
                        <td>Cédula</td>
                        <td><input type="text" name="cedula" value="<?php echo htmlspecialchars($cedula); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Correo electrónico</td>
                        <td><input type="email" name="correo" value="<?php echo htmlspecialchars($correo); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Teléfono</td>
                        <td><input type="text" name="telefono" value="<?php echo htmlspecialchars($telefono); ?>" required></td>
                    </tr>
                </table>
            </div>

            <!-- Sección 2: Datos de la empresa -->
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
                        <td>Actividad económica principal</td>
                        <td><input type="text" name="actividad_economica" required></td>
                    </tr>
                    <tr>
                        <td>Ubicación</td>
                        <td><input type="text" name="ubicacion" value="<?php echo htmlspecialchars($direccion); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Área/departamento donde realizará la práctica</td>
                        <td><input type="text" name="area_departamento" value="<?php echo htmlspecialchars($departamento); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Nombre del tutor empresarial</td>
                        <td><input type="text" name="nombre_tutor_empresarial" value="<?php echo htmlspecialchars($nombreTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Teléfono</td>
                        <td><input type="text" name="telefono_tutor_empresarial" value="<?php echo htmlspecialchars($telefonoTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Correo electrónico tutor</td>
                        <td><input type="email" name="correo_tutor_empresarial" value="<?php echo htmlspecialchars($correoTutorEmpresarial); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Descripción general de la empresa</td>
                        <td><textarea name="descripcion_empresa" required></textarea></td>
                    </tr>
                </table>
            </div>

            <!-- Sección 3: Datos del periodo de prácticas -->
            <div class="form-section">
                <h3>3. Datos del periodo de prácticas</h3>
                <table>
                    <tr>
                        <td>Periodo Académico</td>
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
                                        <strong>Número de total de horas:</strong> <span style="display: inline-block; margin-left: 5px; font-weight: 600;">192</span>
                                        <input type="hidden" name="total_horas" value="192">
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
                                <option value="En línea">En línea</option>
                                <option value="Híbrida">Híbrida</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Nombre del tutor académico</td>
                        <td><input type="text" name="nombre_tutor_academico" value="<?php echo htmlspecialchars($nombreTutorAcademico); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                    <tr>
                        <td>Correo tutor académico institucional</td>
                        <td><input type="email" name="correo_tutor_academico" value="<?php echo htmlspecialchars($correoTutorAcademico); ?>" readonly style="background-color: #f5f5f5; cursor: not-allowed;"></td>
                    </tr>
                </table>
            </div>

            <!-- Sección 4: Objetivo de las prácticas preprofesionales -->
            <div class="form-section">
                <h3>4. Objetivo de las prácticas preprofesionales</h3>
                <div class="objective-text">
                    Aplicar los conocimientos y procedimientos de la carrera de Administración en un entorno laboral real, mediante la ejecución de actividades administrativas, contables, tributarias, logísticas y de apoyo a la gestión del talento humano, fortaleciendo competencias técnicas, digitales, éticas y de mejora continua, en coherencia con el perfil de egreso.
                </div>
            </div>

            <!-- Sección 5: Resultados de Aprendizaje -->
            <div class="form-section">
                <h3>5. Resultados de Aprendizaje</h3>
                <p style="margin-bottom: 10px; font-size: 12px;">Al finalizar las prácticas preprofesionales, el estudiante será capaz de:</p>
                
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
                            <td><strong>RA1.</strong> Colaborar en la organización de actividades administrativas para un mejor flujo eficiente de información en la empresa.</td>
                            <td>A1, A2, A8</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra2" value="1"></td>
                            <td><strong>RA2.</strong> Aplicar herramientas tecnológicas en los procesos administrativos para aumentar la productividad.</td>
                            <td>A7, A1</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra3" value="1"></td>
                            <td><strong>RA3.</strong> Colaborar en la elaboración de informes financieros y administrativos bajo los protocolos internos y normativa vigente.</td>
                            <td>A3, A8</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra4" value="1"></td>
                            <td><strong>RA4.</strong> Fomentar el respeto a los derechos laborales y el bienestar de los empleados en el entorno organizacional.</td>
                            <td>A6, A9, A10</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra5" value="1"></td>
                            <td><strong>RA5.</strong> Aplicar los procesos contables asegurando la precisión en el registro de transacciones y la generación de informes financieros.</td>
                            <td>A3, A7</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra6" value="1"></td>
                            <td><strong>RA6.</strong> Apoyar en el desarrollo de estrategias de gestión, orientando flujo continuo de materiales y reducción de costos operativos.</td>
                            <td>A5, A7</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra7" value="1"></td>
                            <td><strong>RA7.</strong> Participar en la gestión de nómina y del talento humano: fomentando ambiente positivo y bienestar.</td>
                            <td>A6, A2</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra8" value="1"></td>
                            <td><strong>RA8.</strong> Apoyar en el cumplimiento de las obligaciones tributarias, asegurando el registro y pago adecuado de impuestos.</td>
                            <td>A4, A7</td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" name="ra9" value="1"></td>
                            <td><strong>RA9.</strong> Colaborar en la planificación y ejecución de proyectos administrativos.</td>
                            <td>A2, A8</td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="note-box">
                    <strong>Nota.</strong> Marque con X solo 5 resultados aplicables a este periodo: <strong>4 base (RA1-RA4)</strong> y <strong>1 de énfasis</strong> según especialidad asignada. NO marque todos, la bitácora y la evaluación se alinearán a los <strong>RA</strong> seleccionados.
                </div>
            </div>

            <!-- Sección 6: Actividades prácticas esenciales -->
            <div class="form-section">
                <h3>6. Actividades prácticas esenciales</h3>
                <p style="margin-bottom: 10px; font-size: 12px;">Las actividades se ejecutarán según el área asignada, garantizando coherencia con los RA Marcados:</p>
                <ul class="activities-list">
                    <li><strong>A1.</strong> Gestión documental y flujo de información: organización, archivo físico/digital, control de correspondencia, actualización de bases de datos, elaboración de formatos internos.</li>
                    <li><strong>A2.</strong> Soporte administrativo y coordinación: agenda, coordinación de reuniones, seguimiento de requerimientos internos, elaboración de minutas, control de procesos administrativos.</li>
                    <li><strong>A3.</strong> Apoyo contable/financiero (si aplica): registro de transacciones, conciliaciones simples, verificación de soportes, elaboración de reportes básicos bajo lineamientos internos.</li>
                    <li><strong>A4.</strong> Apoyo tributario (si aplica): organización de comprobantes, verificación documental, apoyo en preparación de información para declaraciones bajo supervisión.</li>
                    <li><strong>A5.</strong> Inventarios y logística (si aplica): control de entradas/salidas, kardex, apoyo en compras/abastecimiento, verificación de stock, reportes de movimiento.</li>
                    <li><strong>A6.</strong> Talento humano y nómina (si aplica): apoyo en actualización de expedientes, asistencia en control de asistencia, documentos de nómina, comunicación interna y bienestar.</li>
                    <li><strong>A7.</strong> Herramientas tecnológicas: uso de ofimática y/o sistemas internos (ERP, facturación, inventarios, RR.HH.) con registros verificables.</li>
                    <li><strong>A8.</strong> Comunicación e informes: elaboración de reportes administrativos conforme protocolos; comunicación oportuna de novedades al tutor empresarial.</li>
                    <li><strong>A9.</strong> Ética, confidencialidad y buenas prácticas: manejo responsable de información; cumplimiento de normas internas; comportamiento profesional.</li>
                    <li><strong>A10.</strong> Seguridad y salud en el trabajo: aplicación de medidas básicas de prevención (ergonomía, orden del puesto, protocolos internos), conforme la entidad.</li>
                </ul>
            </div>

            <!-- Sección 7: Nota de Flexibilidad -->
            <div class="form-section">
                <h3>7. Nota de flexibilidad</h3>
                <div class="note-box">
                    Las actividades descritas podrán adaptarse según la naturaleza y servicios de la entidad formadora, manteniendo coherencia con los resultados de aprendizaje definidos en este PAPR y con validación del tutor académico.
                </div>
            </div>

            <!-- Sección 8: Seguimiento -->
            <div class="form-section">
                <h3>8. Seguimiento</h3>
                <ul class="activities-list">
                    <li>Registro semanal en bitácora individual del estudiante.</li>
                    <li>Validación del tutor empresarial.</li>
                    <li>Revisión y acompañamiento del tutor académico.</li>
                </ul>
            </div>

            <!-- Sección 9: Evidencias -->
            <div class="form-section">
                <h3>9. Evidencias</h3>
                <ul class="activities-list">
                    <li>Bitácora de prácticas preprofesionales.</li>
                    <li>Planificación(es) y/o recursos didácticos elaborados/adaptados.</li>
                    <li>Instrumentos de evaluación aplicados y registros de resultados (según corresponda).</li>
                    <li>Informe final con descripción de actividades y propuesta breve de mejora.</li>
                </ul>
            </div>

            <!-- Sección 10: Evaluación -->
            <div class="form-section">
                <h3>10. Evaluación</h3>
                <div style="padding: 10px; font-size: 12px; line-height: 1.6; text-align: justify;">
                    La evaluación del desempeño será integral. El Tutor Empresarial valorará cualitativamente el cumplimiento de las actividades y el comportamiento profesional mediante una rúbrica institucional. Con base en dicha rúbrica y en las evidencias presentadas, el Tutor Académico consolidará la valoración y asignará la calificación final en el sistema institucional, conforme a la normativa de evaluación estudiantil vigente en el Instituto.
                </div>
            </div>

            <!-- Sección 11: Firmas -->
            <div class="form-section">
                <h3>11. Firmas</h3>
                <table class="signatures-table">
                    <thead>
                        <tr>
                            <th>Tutor empresarial</th>
                            <th>Tutor Académico</th>
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
                                    <span style="font-size: 11px;">Tutor Académico</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Botones de Envío y Descarga -->
            <div class="submit-section">
                <button type="submit" class="btn-submit" id="btnEnviar">Enviar Plan de Aprendizaje</button>
                <button type="button" class="btn-pdf" id="btnPDF" onclick="descargarPDF()" style="display: none;">Descargar PDF</button>
            </div>
        </form>

        <!-- Footer -->
        <div class="footer">
            <p>Dirección: Av. General Rumiñahui e Isla Pinta 1111, a media cuadra del San Luis Shopping</p>
            <p>Teléfono: (02) 393-0980</p>
            <p>www.superarse.edu.ec</p>
            <p style="margin-top: 10px;">Página 1 de 3</p>
        </div>
    </div>

    <script>
        // Variables para guardar datos del formulario
        let formDataSaved = null;
        
        // Validación del formulario
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
            
            // Mostrar mensaje de éxito
            document.getElementById('successMessage').style.display = 'block';
            
            // Ocultar botón de enviar y mostrar botón de PDF
            document.getElementById('btnEnviar').style.display = 'none';
            document.getElementById('btnPDF').style.display = 'inline-block';
            
            // Scroll al inicio para ver el mensaje
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            return false;
        });
        
        // Función para deshabilitar todos los campos del formulario
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
        
        // Función para descargar PDF
        function descargarPDF() {
            const data = sessionStorage.getItem('planAprendizaje');
            if (!data) {
                alert('No hay datos guardados para generar el PDF.');
                return;
            }
            
            let actionURL;
            if (window.location.hostname === 'superarse.ec') {
                actionURL = window.location.origin + '/estudiante/generar-plan-aprendizaje-pdf';
            } else {
                actionURL = '<?php echo $basePath; ?>/estudiante/generar-plan-aprendizaje-pdf';
            }
            
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
            tempForm.submit();
            document.body.removeChild(tempForm);
        }
        
        // Verificar si hay datos guardados al cargar la página
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
