<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configurar estilos para Excel
 */
function get_excel_styles() {
    return [
        'header' => [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E78']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ],
        'data' => [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ],
        'totals' => [
            'font' => [
                'bold' => true
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6E6E6']
            ]
        ],
        'currency' => [
            'numberFormat' => [
                'formatCode' => '"$"#,##0.00_-'
            ]
        ],
        'percentage' => [
            'numberFormat' => [
                'formatCode' => '0.00%'
            ]
        ],
        'date' => [
            'numberFormat' => [
                'formatCode' => 'DD/MM/YYYY'
            ]
        ]
    ];
}

/**
 * Exportar productos a Excel
 */
function export_products_excel($products, $filename = 'products') {
    $CI = &get_instance();
    $CI->load->library('Excel');
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $styles = get_excel_styles();
    
    // Configurar página
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
    
    // Encabezado
    $headers = [
        'A1' => 'Código',
        'B1' => 'Nombre',
        'C1' => 'Categoría',
        'D1' => 'Unidad',
        'E1' => 'Stock Actual',
        'F1' => 'Stock Mínimo',
        'G1' => 'Stock Máximo',
        'H1' => 'Costo',
        'I1' => 'Precio',
        'J1' => 'Estado'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A1:J1')->applyFromArray($styles['header']);
    
    // Datos
    $row = 2;
    foreach ($products as $product) {
        $sheet->setCellValue('A' . $row, $product['code']);
        $sheet->setCellValue('B' . $row, $product['name']);
        $sheet->setCellValue('C' . $row, $product['category']);
        $sheet->setCellValue('D' . $row, $product['unit']);
        $sheet->setCellValue('E' . $row, $product['current_stock']);
        $sheet->setCellValue('F' . $row, $product['min_stock']);
        $sheet->setCellValue('G' . $row, $product['max_stock']);
        $sheet->setCellValue('H' . $row, $product['cost']);
        $sheet->setCellValue('I' . $row, $product['price']);
        $sheet->setCellValue('J' . $row, $product['status']);
        
        // Aplicar estilos a celdas específicas
        $sheet->getStyle('H' . $row)->applyFromArray($styles['currency']);
        $sheet->getStyle('I' . $row)->applyFromArray($styles['currency']);
        
        $row++;
    }
    
    // Aplicar estilos a toda la data
    $sheet->getStyle('A2:J' . ($row - 1))->applyFromArray($styles['data']);
    
    // Auto-dimensionar columnas
    foreach (range('A', 'J') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Agregar filtros
    $sheet->setAutoFilter('A1:J1');
    
    // Agregar totales
    $row++;
    $sheet->setCellValue('A' . $row, 'Totales:');
    $sheet->setCellValue('E' . $row, '=SUM(E2:E' . ($row - 1) . ')');
    $sheet->setCellValue('H' . $row, '=SUM(H2:H' . ($row - 1) . ')');
    $sheet->setCellValue('I' . $row, '=SUM(I2:I' . ($row - 1) . ')');
    $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($styles['totals']);
    
    // Configurar primera fila como cabecera para impresión
    $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);
    
    // Exportar
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
}

/**
 * Exportar movimientos de stock a Excel
 */
function export_stock_movements_excel($movements, $filename = 'stock_movements') {
    $CI = &get_instance();
    $CI->load->library('Excel');
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $styles = get_excel_styles();
    
    // Encabezado
    $headers = [
        'A1' => 'Fecha',
        'B1' => 'Tipo',
        'C1' => 'Producto',
        'D1' => 'Almacén',
        'E1' => 'Cantidad',
        'F1' => 'Usuario',
        'G1' => 'Referencia',
        'H1' => 'Notas'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A1:H1')->applyFromArray($styles['header']);
    
    // Datos
    $row = 2;
    foreach ($movements as $movement) {
        $sheet->setCellValue('A' . $row, _d($movement['date']));
        $sheet->setCellValue('B' . $row, _l($movement['type']));
        $sheet->setCellValue('C' . $row, $movement['product']);
        $sheet->setCellValue('D' . $row, $movement['warehouse']);
        $sheet->setCellValue('E' . $row, $movement['quantity']);
        $sheet->setCellValue('F' . $row, $movement['staff_name']);
        $sheet->setCellValue('G' . $row, $movement['reference']);
        $sheet->setCellValue('H' . $row, $movement['notes']);
        
        // Formatear fecha
        $sheet->getStyle('A' . $row)->applyFromArray($styles['date']);
        
        $row++;
    }
    
    // Estilos y ajustes finales
    $sheet->getStyle('A2:H' . ($row - 1))->applyFromArray($styles['data']);
    foreach (range('A', 'H') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    $sheet->setAutoFilter('A1:H1');
    
    // Exportar
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
}

/**
 * Exportar inventario valorizado a Excel
 */
function export_inventory_valuation_excel($data, $filename = 'inventory_valuation') {
    $CI = &get_instance();
    $CI->load->library('Excel');
    
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $styles = get_excel_styles();
    
    // Encabezado
    $headers = [
        'A1' => 'Código',
        'B1' => 'Producto',
        'C1' => 'Almacén',
        'D1' => 'Stock',
        'E1' => 'Costo Unit.',
        'F1' => 'Valor Total',
        'G1' => 'Último Movimiento'
    ];
    
    foreach ($headers as $cell => $value) {
        $sheet->setCellValue($cell, $value);
    }
    $sheet->getStyle('A1:G1')->applyFromArray($styles['header']);
    
    // Datos
    $row = 2;
    $total_value = 0;
    
    foreach ($data as $item) {
        $total = $item['stock'] * $item['unit_cost'];
        $total_value += $total;
        
        $sheet->setCellValue('A' . $row, $item['code']);
        $sheet->setCellValue('B' . $row, $item['name']);
        $sheet->setCellValue('C' . $row, $item['warehouse']);
        $sheet->setCellValue('D' . $row, $item['stock']);
        $sheet->setCellValue('E' . $row, $item['unit_cost']);
        $sheet->setCellValue('F' . $row, $total);
        $sheet->setCellValue('G' . $row, _d($item['last_movement']));
        
        // Aplicar formatos
        $sheet->getStyle('E' . $row)->applyFromArray($styles['currency']);
        $sheet->getStyle('F' . $row)->applyFromArray($styles['currency']);
        $sheet->getStyle('G' . $row)->applyFromArray($styles['date']);
        
        $row++;
    }
    
    // Totales
    $row++;
    $sheet->setCellValue('A' . $row, 'TOTAL VALORIZADO:');
    $sheet->setCellValue('F' . $row, $total_value);
    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($styles['totals']);
    $sheet->getStyle('F' . $row)->applyFromArray($styles['currency']);
    
    // Ajustes finales
    $sheet->getStyle('A2:G' . ($row - 2))->applyFromArray($styles['data']);
    foreach (range('A', 'G') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    $sheet->setAutoFilter('A1:G1');
    
    // Exportar
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
}
