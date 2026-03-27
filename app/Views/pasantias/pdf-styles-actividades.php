<?php
/**
 * pdf-styles-actividades.php
 * 
 * Genera los estilos CSS para PDFs de actividades diarias
 * Este archivo se incluye en actividades_diarias_pdf.php de forma inline
 * RAZÓN: DomPDF requiere estilos inline para renderizar PDFs correctamente
 * 
 * Uso: <?php include __DIR__ . '/../../css/pdf-styles-actividades.php'; ?>
 */

$styles = <<<'CSS'
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 25px;
    font-size: 10pt;
    color: #333;
}

.header {
    width: 100%;
    margin-bottom: 20px;
}

.header-table {
    width: 100%;
    border-collapse: collapse;
    border: 2px solid #333;
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
    color: #1E40AF;
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

.info-estudiante {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.info-estudiante p {
    margin: 5px 0;
    font-size: 10pt;
}

.info-estudiante strong {
    color: #1E40AF;
    display: inline-block;
    width: 180px;
}

.semana-container {
    margin-bottom: 30px;
    page-break-inside: avoid;
}

.semana-header {
    background-color: #1E40AF;
    color: white;
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 5px;
}

.semana-header h3 {
    margin: 0;
    font-size: 12pt;
}

.semana-header p {
    margin: 5px 0 0 0;
    font-size: 9pt;
    opacity: 0.9;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

table thead {
    background-color: #f0f0f0;
}

table th {
    padding: 8px;
    text-align: left;
    font-size: 9pt;
    border: 1px solid #ddd;
    font-weight: bold;
    color: #333;
}

table td {
    padding: 8px;
    border: 1px solid #ddd;
    font-size: 9pt;
    vertical-align: top;
}

table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}

.total-horas {
    text-align: right;
    font-weight: bold;
    background-color: #e8e8e8;
    padding: 10px;
    margin-top: 10px;
    border-radius: 3px;
}

.firmas-container {
    margin-top: 50px;
    page-break-inside: avoid;
}

.firmas-grid {
    display: table;
    width: 100%;
    margin-top: 30px;
}

.firma-box {
    display: table-cell;
    width: 45%;
    vertical-align: top;
    padding: 10px;
}

.firma-box:first-child {
    padding-right: 5%;
}

.firma-line {
    border-top: 2px solid #333;
    margin-top: 60px;
    margin-bottom: 10px;
}

.firma-label {
    font-weight: bold;
    text-align: center;
    font-size: 10pt;
    color: #1E40AF;
}

.firma-info {
    text-align: center;
    font-size: 9pt;
    color: #666;
    margin-top: 5px;
}

.resumen-total {
    background-color: #1E40AF;
    color: white;
    padding: 15px;
    margin: 30px 0;
    border-radius: 5px;
    text-align: center;
}

.resumen-total h3 {
    margin: 0 0 10px 0;
    font-size: 14pt;
}

.resumen-total p {
    margin: 5px 0;
    font-size: 11pt;
}

.fecha-generacion {
    text-align: right;
    font-size: 8pt;
    color: #999;
    margin-top: 20px;
}
CSS;

echo "<style>\n" . $styles . "\n</style>";
