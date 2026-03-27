/**
 * tailwind-config.js
 * Tailwind CSS configuration for Superarse Conectados V2
 * This file can be referenced instead of inline <script> tags
 */

if (typeof tailwind !== 'undefined') {
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'superarse-morado-oscuro': '#4A148C',
                    'superarse-morado-medio': '#673AB7',
                    'superarse-rosa': '#E91E63',
                }
            }
        }
    };
}
