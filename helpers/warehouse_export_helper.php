<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Configura el estilo base para Excel
 */
function setup_excel_style($spreadsheet) {
    $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
    $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
    
    return [
        'header' => [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0066CC']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ],
        'title' => [
            'font' => [
                'bold' => true,
                'size' => 16
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ],
        'data' => [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
                ]
            ]
        ]
    ];
}

/**
 * Configura el estilo base para PDF
 */
function setup_pdf_style($pdf) {
    $pdf->SetCreator(get_option('company_name'));
    $pdf->SetAuthor(get_staff_full_name());
    $pdf->SetTitle(_l('quality_control_report'));
    
    // Configuración de fuentes
    $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
    $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
    
    // Configuración de página
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    
    // Encabezado
    $pdf->SetHeaderData(
        get_upload_path_by_type('company') . '/' . get_option('company_logo'),
        50,
        get_option('company_name'),
        get_option('company_address') . "\n" . get_option('company_city')
    );
    
    return $pdf;
}

/**
 * Genera una tabla HTML para PDF
 */
function generate_pdf_table($headers, $data) {
    $html = '<table border="1" cellpadding="3" style="border-collapse: collapse; width: 100%;">';
    
    // Encabezados
    $html .= '<thead><tr style="background-color: #0066CC; color: white;">';
    foreach ($headers as $header) {
        $html .= '<th style="font-weight: bold;">' . $header . '</th>';
    }
    $html .= '</tr></thead>';
    
    // Datos
    $html .= '<tbody>';
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . $cell . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    
    $html .= '</table>';
    
    return $html;
}

/**
 * Genera el pie de página para reportes
 */
function generate_report_footer() {
    return [
        'generated_by' => _l('generated_by') . ': ' . get_staff_full_name(),
        'generated_at' => _l('generated_at') . ': ' . _dt(date('Y-m-d H:i:s')),
        'page' => _l('page') . ' {PAGENO}'
    ];
}

/**
 * Genera estadísticas en formato HTML para PDF
 */
function generate_stats_html($stats) {
    $html = '<h3 style="color: #0066CC;">' . _l('quality_statistics') . '</h3>';
    $html .= '<table style="width: 100%; margin-top: 10px;">';
    $html .= '<tr>';
    $html .= '<td style="width: 33%;"><strong>' . _l('total_inspections') . ':</strong> ' . $stats['total'] . '</td>';
    $html .= '<td style="width: 33%;"><strong>' . _l('approval_rate') . ':</strong> ' . $stats['approval_rate'] . '%</td>';
    $html .= '<td style="width: 33%;"><strong>' . _l('rejection_rate') . ':</strong> ' . $stats['rejection_rate'] . '%</td>';
    $html .= '</tr>';
    $html .= '</table>';
    
    return $html;
}

/**
 * Formatea números para reportes
 */
function format_report_number($number, $decimals = 2) {
    return number_format($number, $decimals, get_option('decimal_separator'), get_option('thousand_separator'));
}

/**
 * Genera gráfico para PDF
 */
function generate_chart_image($stats) {
    // Usar la biblioteca Image Charts para generar gráficos en la nube
    $chart_url = 'https://image-charts.com/chart?' . http_build_query([
        'cht' => 'p3', // Gráfico de pastel 3D
        'chs' => '300x200', // Tamaño
        'chd' => 't:' . implode(',', [$stats['pending'], $stats['approved'], $stats['rejected']]),
        'chl' => implode('|', [
            _l('quality_pending') . ' (' . $stats['pending'] . ')',
            _l('quality_approved') . ' (' . $stats['approved'] . ')',
            _l('quality_rejected') . ' (' . $stats['rejected'] . ')'
        ]),
        'chco' => 'FFC107,28A745,DC3545' // Colores
    ]);
    
    return $chart_url;
}
