<?php
require_once APPPATH . 'third_party/PHPSpreadsheet/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Crear nuevo libro
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = [
    'A1' => _l('item_code'),
    'B1' => _l('item_name'), 
    'C1' => _l('quantity'),
    'D1' => _l('unit'),
    'E1' => _l('unit_price'),
    'F1' => _l('tax'),
    'G1' => _l('date')
];

// Aplicar encabezados
foreach ($headers as $cell => $value) {
    $sheet->setCellValue($cell, $value);
}

// Datos de ejemplo
$rows = [
    [
        'COM001', // código
        'Laptop Dell XPS 13', // nombre
        '5', // cantidad
        'PCS', // unidad
        '1299.99', // precio
        '10', // impuesto
        date('Y-m-d') // fecha
    ],
    [
        'COM002',
        'Monitor Dell 27"',
        '10',
        'PCS',
        '399.99',
        '10',
        date('Y-m-d')
    ],
    [
        'COM003', 
        'Teclado Mecánico',
        '20',
        'PCS',
        '89.99',
        '10',
        date('Y-m-d')
    ]
];

// Agregar datos
$row = 2;
foreach ($rows as $data) {
    $col = 'A';
    foreach ($data as $value) {
        $sheet->setCellValue($col . $row, $value);
        $col++;
    }
    $row++;
}

// Ajustar ancho de columnas
foreach(range('A','G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Aplicar estilo a encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '1F497D']
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
    ]
];

$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Guardar archivo
$writer = new Xlsx($spreadsheet);
$writer->save(FCPATH . 'uploads/file_sample/stock_receipts_sample.xlsx');
