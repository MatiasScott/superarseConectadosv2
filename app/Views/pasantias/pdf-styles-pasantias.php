<?php
/**
 * pdf-styles-pasantias.php
 * 
 * Genera los estilos CSS para PDFs de pasantías (Fase 1)
 * Este archivo se incluye en pasantias_pdf_fase1.php de forma inline
 * RAZÓN: DomPDF requiere estilos inline para renderizar PDFs correctamente
 * 
 * Uso: <?php include __DIR__ . '/../../css/pdf-styles-pasantias.php'; ?>
 */

$styles = <<<'CSS'
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 25px;
    font-size: 10pt;
    color: #333;
}

/* Estilos del Encabezado */
.header {
    width: 100%;
    margin-bottom: 20px;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid #333;
    margin-bottom: 25px;
}

.header-table td {
    padding: 10px;
    vertical-align: middle;
    border: 1px solid #333;
}

.logo-cell {
    width: 30%;
    text-align: center;
    background-color: #f5f5f5;
}

.logo-text {
    font-size: 18pt;
    font-weight: bold;
    color: #5B21B6;
    margin: 0;
    padding-bottom: 2px;
}

.logo-subtext {
    font-size: 9pt;
    color: #666;
    margin: 0;
    border-bottom: 3px solid #3b82f6;
    display: inline-block;
    padding-bottom: 2px;
}

.title-cell {
    width: 50%;
    text-align: center;
    background-color: white;
    color: #333;
}

.title-text {
    font-size: 9pt;
    font-weight: normal;
    margin: 0;
    line-height: 1.4;
}

.info-cell {
    width: 20%;
    text-align: left;
    font-size: 7pt;
    background-color: white;
}

.info-cell p {
    margin: 2px 0;
    line-height: 1.3;
}

/* Estilos de Contenido General */
.section {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 4px;
    background-color: #f9f9f9;
}

.section-title {
    color: #5B21B6;
    font-size: 13pt;
    margin-top: 0;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
    margin-bottom: 10px;
    font-weight: bold;
}

.data-row {
    margin-bottom: 5px;
    display: block;
    overflow: auto;
    line-height: 1.4;
}

.data-label {
    font-weight: bold;
    width: 160px;
    display: inline-block;
    float: left;
    color: #444;
}

.data-value {
    display: block;
    overflow: hidden;
}

/* Estilos de Firmas */
.signature-area {
    margin-top: 60px;
    text-align: center;
}

.signature-table {
    width: 90%;
    margin: 0 auto;
    border: none;
}

.signature-table td {
    width: 33%;
    text-align: center;
    border-top: 1px solid #000;
    padding-top: 15px;
    font-size: 9pt;
}

.clear {
    clear: both;
}
CSS;

echo "<style>\n" . $styles . "\n</style>";
