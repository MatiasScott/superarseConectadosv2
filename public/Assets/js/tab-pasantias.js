/**
 * tab-pasantias.js
 * Functionality for practices registration and management
 * Extracted from app/Views/dashboard/tab_pasantias.php
 */

document.addEventListener('DOMContentLoaded', function() {
    const $ = id => document.getElementById(id);
    const btnBuscarRuc = $('btn_buscar_ruc');
    const inputRuc = $('entidad_ruc');
    const entidadResultado = $('entidad_resultado');
    const entidadNombreResultado = $('entidad_nombre_resultado');
    const modalidadSelect = $('modalidad');
    const infoTutorEmpresarial = $('informacion-tutor-empresarial');
    const afiliacionIESS = document.getElementById('EntidadAfiliacionIESS');
    const inputAfiliacionIESS = document.getElementById('afiliacion_iees');
    const seccionEmpresa = $('seccion-empresa');
    const seccionTutorEmpresa = $('seccion-tutor-empresa');
    
    const inputFields = {
        entidad: {
            nombre_empresa: $('entidad_nombre_empresa'),
            razon_social: $('entidad_razon_social'),
            persona_contacto: $('entidad_persona_contacto'),
            telefono_contacto: $('entidad_telefono_contacto'),
            email_contacto: $('entidad_email_contacto'),
            direccion: $('entidad_direccion'),
            plazas_disponibles: $('plazas_disponibles')
        },
        tutor: {
            nombre_completo: $('tutor_emp_nombre_completo'),
            cedula: $('tutor_emp_cedula'),
            funcion: $('tutor_emp_funcion'),
            email: $('tutor_emp_email'),
            telefono: $('tutor_emp_telefono'),
            departamento: $('tutor_emp_departamento')
        }
    };
    const labelFields = {
        persona_contacto: $('EntidadPersonaContacto'),
        telefono_contacto: $('EntidadTelefonoContacto'),
        email_contacto: $('EntidadEmailContacto'),
        plazas_disponibles: $('EntidadPlazasDisponibles')
    }
    const tablaProyectos = $('TablaProyectos');
    const labelInfoTutor = $('labelInfoTutor');
    const rucContainer = inputRuc ? inputRuc.closest('.md\\:col-span-2') : null;
    const idPrograma = document.body.getAttribute('data-programa-id') || null;
    let timeoutBusqueda = null;

    // Notificación
    function notificar(mensaje, tipo = 'info') {
        const colores = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            info: 'bg-blue-500',
            warning: 'bg-yellow-500'
        };
        const iconos = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            info: 'fa-info-circle',
            warning: 'fa-exclamation-triangle'
        };
        const n = document.createElement('div');
        n.className = `fixed top-4 right-4 ${colores[tipo]} text-white px-6 py-4 rounded-lg shadow-2xl z-50 flex items-center gap-3 animate-slide-in-right max-w-md`;
        n.innerHTML = `<i class="fas ${iconos[tipo]} text-xl"></i><span class="text-sm font-medium">${mensaje}</span>`;
        document.body.appendChild(n);
        setTimeout(() => {
            n.classList.add('animate-fade-out');
            setTimeout(() => n.remove(), 300);
        }, 4000);
    }

    function showLoader(show) {
        let loader = inputRuc.parentElement.querySelector('.ruc-loader');
        if (show && !loader) {
            loader = document.createElement('div');
            loader.className = 'ruc-loader absolute right-20 top-1/2 transform -translate-y-1/2';
            loader.innerHTML = '<i class="fas fa-spinner fa-spin text-superarse-morado-medio"></i>';
            inputRuc.parentElement.style.position = 'relative';
            inputRuc.parentElement.appendChild(loader);
        } else if (!show && loader) {
            loader.remove();
        }
    }

    function showEntidad(nombre, type = 'found') {
        entidadNombreResultado.textContent = nombre || '';
        entidadResultado.classList.remove('hidden', 'bg-blue-50', 'border-blue-200', 'bg-red-50', 'border-red-200', 'bg-green-50', 'border-green-200');
        if (type === 'found') {
            entidadResultado.classList.add('bg-green-50', 'border-green-200');
        } else if (type === 'nf') {
            entidadResultado.classList.add('bg-red-50', 'border-red-200');
        } else {
            entidadResultado.classList.add('bg-blue-50', 'border-blue-200');
        }
    }

    function hideEntidad() {
        entidadResultado.classList.add('hidden');
        entidadNombreResultado.textContent = '';
    }

    function limpiarCampos(fieldsObj) {
        Object.values(fieldsObj).forEach(field => {
            if (field) field.value = '';
        });
    }

    function animarCampoLlenado(input) {
        if (input && input.value) {
            input.classList.add('bg-green-50', 'border-green-500');
            setTimeout(() => input.classList.remove('bg-green-50', 'border-green-500'), 1500);
        }
    }

    function buscarEntidadPorRUC(programa, ruc, esAuto = false) {
        const dataBasePath = (typeof DATOS_ESTUDIANTE !== 'undefined' && DATOS_ESTUDIANTE && DATOS_ESTUDIANTE.basePath)
            ? DATOS_ESTUDIANTE.basePath
            : null;
        const bodyBasePath = document.body.getAttribute('data-basepath');
        const rawBasePath = dataBasePath ?? bodyBasePath;
        const basePath = (rawBasePath !== null && rawBasePath !== undefined)
            ? String(rawBasePath).replace(/\/$/, '')
            : '/superarseconectadosv2/public';
        if (!esAuto) {
            btnBuscarRuc.disabled = true;
            btnBuscarRuc.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
        } else {
            showLoader(true);
        }
        fetch(basePath + '/pasantias/buscarEntidadPorRUC', {
                method: 'POST',
                body: new URLSearchParams({
                    ruc: ruc,
                    idPrograma: programa
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.entidad) {
                    const mapEntidad = inputFields.entidad;
                    const mapTutor = inputFields.tutor;
                    for (const dbField in mapEntidad) {
                        if (mapEntidad[dbField] && data.entidad[dbField]) {
                            mapEntidad[dbField].value = data.entidad[dbField];
                            animarCampoLlenado(mapEntidad[dbField]);
                        }
                    }
                    for (const dbField in mapTutor) {
                        if (mapTutor[dbField] && data.entidad[dbField]) {
                            mapTutor[dbField].value = data.entidad[dbField];
                            animarCampoLlenado(mapTutor[dbField]);
                        }
                    }

                    inputRuc.classList.add('border-green-500', 'bg-green-50');
                    setTimeout(() => inputRuc.classList.remove('bg-green-50'), 1500);
                    showEntidad(data.entidad.nombre_empresa || data.entidad.razon_social || 'Empresa');
                    notificar('Empresa encontrada y datos cargados correctamente', 'success');
                } else {
                    limpiarCampos(inputFields.entidad);
                    limpiarCampos(inputFields.tutor);
                    showEntidad('No encontrada', 'nf');
                    inputRuc.classList.add('border-yellow-500');
                    setTimeout(() => inputRuc.classList.remove('border-yellow-500'), 1500);
                    notificar(data.message || 'Empresa no encontrada. Puede ingresar los datos manualmente.', 'warning');
                    setTimeout(hideEntidad, 3000);
                }
            })
            .catch(() => {
                inputRuc.classList.add('border-red-500');
                setTimeout(() => inputRuc.classList.remove('border-red-500'), 1500);
                showEntidad('Error en la búsqueda', 'nf');
                notificar('Error al buscar la empresa. Por favor, intente nuevamente.', 'error');
                setTimeout(hideEntidad, 3000);
            })
            .finally(() => {
                if (!esAuto) {
                    btnBuscarRuc.disabled = false;
                    btnBuscarRuc.innerHTML = '<i class="fas fa-search"></i> Buscar';
                } else {
                    showLoader(false);
                }
            });
    }

    // Búsqueda por tipeo
    inputRuc && inputRuc.addEventListener('input', function() {
        clearTimeout(timeoutBusqueda);
        this.value = this.value.replace(/[^0-9]/g, '');
        const ruc = this.value.trim();
        if (ruc.length >= 10 && ruc.length <= 13) {
            showLoader(true);
            timeoutBusqueda = setTimeout(() => buscarEntidadPorRUC(idPrograma, ruc, true), 800);
        } else if (ruc.length < 10) {
            limpiarCampos(inputFields.entidad);
            limpiarCampos(inputFields.tutor);
            hideEntidad();
            showLoader(false);
        }
    });

    btnBuscarRuc && btnBuscarRuc.addEventListener('click', function(e) {
        e.preventDefault();
        if (!inputRuc.value.trim() || inputRuc.value.trim().length < 10) {
            notificar('Por favor, ingrese un RUC válido (mín. 10 dígitos)', 'warning');
            inputRuc.focus();
            return;
        }
        buscarEntidadPorRUC(idPrograma, inputRuc.value.trim(), false);
    });

    inputRuc && inputRuc.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnBuscarRuc && btnBuscarRuc.click();
        }
    });

    inputRuc && inputRuc.addEventListener('focus', function() {
        this.classList.remove('border-green-500', 'border-yellow-500', 'border-red-500');
    });

    function limpiarTodoElFormulario() {
        Object.values(inputFields.entidad).forEach(f => f && (f.value = ''));
        Object.values(inputFields.tutor).forEach(f => f && (f.value = ''));
        if (inputRuc) inputRuc.value = '';
        hideEntidad();
        Object.values(labelFields).forEach(l => l && (l.style.display = ''));
        if (tablaProyectos) tablaProyectos.style.display = 'none';
    }

    function setRequiredProyectoSeleccionado(state) {
        document.querySelectorAll("input[name='proyecto_seleccionado']").forEach(input => {
            if (state) input.setAttribute('required', 'required');
            else input.removeAttribute('required');
        });
    }

    function toggleCamposByModalidad(isInitialLoad = false) {
        if (!modalidadSelect) {
            console.error('modalidadSelect no encontrado');
            return;
        }
        
        if (!seccionEmpresa || !seccionTutorEmpresa) {
            console.error('Secciones no encontradas:', {
                seccionEmpresa: !!seccionEmpresa,
                seccionTutorEmpresa: !!seccionTutorEmpresa
            });
            return;
        }
        
        if (!isInitialLoad) {
            limpiarTodoElFormulario();
        }
        
        const modalidadValue = modalidadSelect.value;
        if (modalidadValue && modalidadValue.trim() !== "" && modalidadValue !== "-- Seleccione una opción --") {
            seccionEmpresa.classList.remove('hidden');
            seccionTutorEmpresa.classList.remove('hidden');
        } else {
            seccionEmpresa.classList.add('hidden');
            seccionTutorEmpresa.classList.add('hidden');
            return;
        }
        
        // Resetea el estado por defecto para la tabla de proyectos
        if (tablaProyectos) tablaProyectos.style.display = 'none';
        setRequiredProyectoSeleccionado(false);
        if (inputRuc) inputRuc.removeAttribute('readonly');
        if (infoTutorEmpresarial) infoTutorEmpresarial.style.display = '';
        if (labelInfoTutor) labelInfoTutor.style.display = '';
        Object.values(inputFields.entidad).forEach(f => f && f.removeAttribute('readonly'));
        Object.values(inputFields.tutor).forEach(f => {
            if (f) {
                f.removeAttribute('readonly');
                f.setAttribute('required', 'required');
            }
        });
        if (afiliacionIESS) afiliacionIESS.style.display = 'none';
        if (inputAfiliacionIESS) inputAfiliacionIESS.removeAttribute('required');
        if (labelFields.plazas_disponibles) labelFields.plazas_disponibles.style.display = '';

        // Modalidad specifics
        if (modalidadValue === '3') {
            if (inputRuc && (!isInitialLoad || (isInitialLoad && !inputRuc.value.trim()))) {
                inputRuc.value = '1702051704001';
                buscarEntidadPorRUC(idPrograma, '1702051704001', true);
            }
            if (inputRuc) inputRuc.setAttribute('readonly', 'readonly');
            Object.values(inputFields.entidad).forEach(f => f && f.setAttribute('readonly', 'readonly'));
            Object.values(inputFields.tutor).forEach(f => f && f.setAttribute('readonly', 'readonly'));
            if (btnBuscarRuc) btnBuscarRuc.style.display = 'none';
            Object.values(labelFields).forEach(l => l && (l.style.display = 'none'));
            if (tablaProyectos) tablaProyectos.style.display = 'block';
            setRequiredProyectoSeleccionado(true);
            if (afiliacionIESS) afiliacionIESS.style.display = 'none';
        } else if (modalidadValue === '4') {
            if (btnBuscarRuc) btnBuscarRuc.style.display = 'none';
            if (infoTutorEmpresarial) infoTutorEmpresarial.style.display = 'none';
            if (labelInfoTutor) labelInfoTutor.style.display = 'none';
            Object.values(inputFields.tutor).forEach(f => f && f.removeAttribute('required'));
            if (inputRuc) inputRuc.removeAttribute('required');
            if (labelFields.plazas_disponibles) labelFields.plazas_disponibles.style.display = 'none';
            if (inputFields.entidad.plazas_disponibles) inputFields.entidad.plazas_disponibles.value = '';
            if (afiliacionIESS) afiliacionIESS.style.display = '';
            if (inputAfiliacionIESS) inputAfiliacionIESS.setAttribute('required', 'required');
        } else if (modalidadValue === '2') {
            if (labelFields.plazas_disponibles) labelFields.plazas_disponibles.style.display = 'none';
            if (inputFields.entidad.plazas_disponibles) {
                inputFields.entidad.plazas_disponibles.value = '';
                inputFields.entidad.plazas_disponibles.removeAttribute('required');
                inputFields.entidad.plazas_disponibles.removeAttribute('name');
            }
            if (btnBuscarRuc) btnBuscarRuc.style.display = 'none';
            if (inputRuc) inputRuc.removeAttribute('required');
        } else if (modalidadValue === '1') {
            if (btnBuscarRuc) btnBuscarRuc.style.display = 'inline-flex';
            Object.values(inputFields.entidad).forEach(f => f && f.setAttribute('readonly', 'readonly'));
            Object.values(inputFields.tutor).forEach(f => f && f.setAttribute('readonly', 'readonly'));
            if (inputRuc) inputRuc.setAttribute('required', 'required');
        } else {
            if (btnBuscarRuc) btnBuscarRuc.style.display = 'inline-flex';
            if (rucContainer) rucContainer.style.display = 'block';
            if (inputRuc) inputRuc.setAttribute('required', 'required');
        }
        
    }

    // Ejecutar al cargar la página si hay una modalidad seleccionada
    if (modalidadSelect) {
        const valorInicial = modalidadSelect.value;
        if (valorInicial && valorInicial.trim() !== "" && valorInicial !== "-- Seleccione una opción --") {
            toggleCamposByModalidad(true);
        }
        
        // Evento de cambio
        modalidadSelect.addEventListener('change', function() {
            toggleCamposByModalidad(false);
        });
    }

    window.actualizarInfoTutor = function() {
        const selectTutor = $('tutor_academico');
        const correoInput = $('correo_tutor');
        if (selectTutor && correoInput) {
            const s = selectTutor.options[selectTutor.selectedIndex];
            correoInput.value = s.getAttribute('data-email') || 'N/D';
        }
    };
    
    // Agregar evento al formulario para habilitar todos los campos antes de enviar
    const formPasantia = document.querySelector('form[action*="saveFaseOne"]');
    if (formPasantia) {
        formPasantia.addEventListener('submit', function(e) {
            // Validar campos requeridos ANTES de habilitar
            const modalidadVal = modalidadSelect ? modalidadSelect.value : '';
            const rucVal = inputRuc ? inputRuc.value.trim() : '';
            
            if (!modalidadVal || modalidadVal === '' || modalidadVal === '-- Seleccione una opción --') {
                e.preventDefault();
                notificar('Por favor, seleccione una modalidad de práctica', 'error');
                console.error('Modalidad no seleccionada');
                return false;
            }
            
            if (!rucVal || rucVal === '') {
                e.preventDefault();
                notificar('Por favor, ingrese el RUC de la empresa', 'error');
                console.error('RUC vacío');
                if (inputRuc) inputRuc.focus();
                return false;
            }
            
            // Habilitar todos los campos disabled y readonly antes de enviar
            const camposDeshabilitados = formPasantia.querySelectorAll('input[disabled], select[disabled], textarea[disabled]');
            const camposSoloLectura = formPasantia.querySelectorAll('input[readonly], textarea[readonly]');
            
            // Remover disabled
            camposDeshabilitados.forEach(campo => {
                campo.removeAttribute('disabled');
            });
            
            // Remover readonly
            camposSoloLectura.forEach(campo => {
                campo.removeAttribute('readonly');
            });
        });
    } else {
        console.error('No se encontró el formulario de saveFaseOne');
    }
});
