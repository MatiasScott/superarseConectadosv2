<?php

include(__DIR__ . '/../../vendor/autoload.php');
require_once __DIR__ . '/../Models/PasantiaModel.php';

use PhpOffice\Phpspreadsheet\Spreadsheet;
use PhpOffice\Phpspreadsheet\Writer\Xlsx;

$id_practica = '23';

$pasantiaModel = new PasantiaModel();
$datos = $pasantiaModel->obtenerDatosPracticaEstudiante($id_practica);

if (!$datos) {
    die("Error: No se encontraron datos para la práctica con ID: " . htmlspecialchars($id_practica));
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Datos Práctica');

$sheet->setCellValue('A1', 'Reporte de Datos de la Práctica');
$sheet->mergeCells('A1:B1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

$fila = 3;
foreach ($datos as $columna => $valor) {
    $nombreLegible = ucwords(str_replace('_', ' ', $columna));
    $sheet->setCellValue('A' . $fila, $nombreLegible);
    $sheet->getStyle('A' . $fila)->getFont()->setBold(true);
    $sheet->setCellValue('B' . $fila, $valor);
    $fila++;
}

$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);

$nombreArchivo = "reporte_dinamico_practica_" . $id_practica . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;