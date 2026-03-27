document.addEventListener('DOMContentLoaded', function() {
    const rucInput = document.getElementById('entidad_ruc');
    const mensajeBusqueda = document.getElementById('mensaje_busqueda_ruc');
    const campos = [
        'entidad_nombre_empresa',
        'entidad_razon_social',
        'entidad_persona_contacto',
        'entidad_telefono_contacto',
        'entidad_email_contacto'
    ];
    
    function mostrarMensaje(mensaje, tipo = 'info') {
        if (mensajeBusqueda) {
            mensajeBusqueda.textContent = mensaje;
            mensajeBusqueda.className = `text-sm ${tipo === 'error' ? 'text-red-600' : 'text-gray-600'}`;
            mensajeBusqueda.classList.remove('hidden');
            setTimeout(() => {
                mensajeBusqueda.classList.add('hidden');
            }, 3000);
        }
    }

    function limpiarCampos() {
        campos.forEach(campo => {
            const elemento = document.getElementById(campo);
            if (elemento) elemento.value = '';
        });
    }

    if (rucInput) {
        let timeoutId;
        
        rucInput.addEventListener('input', function() {
            clearTimeout(timeoutId);
            if (this.value.length >= 13) { // RUC ecuatoriano tiene 13 dígitos
                timeoutId = setTimeout(() => buscarEntidad(this.value), 500);
            }
        });

        rucInput.addEventListener('blur', function() {
            if (this.value.trim()) {
                buscarEntidad(this.value);
            }
        });
    }

    async function buscarEntidad(ruc) {
        mostrarMensaje('Buscando información de la empresa...');
        
        try {
            const formData = new FormData();
            formData.append('ruc', ruc);

            const response = await fetch('/superarseconectadosv2/public/pasantia/buscarEntidadPorRUC', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success && data.entidad) {
                mostrarMensaje('¡Información encontrada!');
                campos.forEach(campo => {
                    const elemento = document.getElementById(campo);
                    if (elemento && data.entidad[campo.replace('entidad_', '')]) {
                        elemento.value = data.entidad[campo.replace('entidad_', '')];
                    }
                });
            } else {
                mostrarMensaje('Empresa no encontrada. Puede ingresar los datos manualmente.', 'error');
                limpiarCampos();
            }
        } catch (error) {
            console.error('Error al buscar la entidad:', error);
            mostrarMensaje('Error al buscar la información. Intente nuevamente.', 'error');
            limpiarCampos();
        }
    }
});