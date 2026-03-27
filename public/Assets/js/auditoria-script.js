/**
 * auditoria-script.js
 * Functionality script for audit phase 2 view
 * Extracted from auditoria_fase_dos.php
 */

// Configuración
const auditoriaContainer = document.querySelector('[data-basepath]');
const basePath = document.body.getAttribute('data-basepath')
    || (auditoriaContainer ? auditoriaContainer.getAttribute('data-basepath') : null)
    || '/superarseconectadosv2';

// Smooth scroll behavior and initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('Auditoría Fase Dos cargada correctamente');
    console.log('basePath final:', basePath);
    
    initializeAuditoriaView();
});

/**
 * Initialize audit view functionality
 */
function initializeAuditoriaView() {
    // Clear search functionality
    document.querySelectorAll('a.clear-search').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;
            window.location.href = url;
        });
    });
}
