<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quality_reports extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse_model');
        $this->load->helper(['warehouse_helper', 'export_helper']);
    }
    
    /**
     * Exportar reporte de control de calidad
     */
    public function export_quality_report() {
        $format = $this->input->get('format');
        $status = $this->input->get('status');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        // Obtener datos
        $data = $this->warehouse_model->get_quality_control_data([
            'status' => $status,
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        // Preparar datos para exportación
        $export_data = [];
        $headers = [
            _l('date'),
            _l('batch_number'),
            _l('commodity_name'),
            _l('quantity'),
            _l('quality_status'),
            _l('inspector'),
            _l('inspection_note')
        ];
        
        foreach ($data as $row) {
            $export_data[] = [
                _dt($row['date']),
                $row['batch_number'],
                $row['commodity_name'],
                $row['quantity'],
                _l('quality_' . $row['quality_status']),
                $row['inspector'],
                $row['inspection_note']
            ];
        }
        
        // Estadísticas
        $stats = $this->warehouse_model->get_quality_stats([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        $filename = 'quality_control_report_' . date('Y-m-d');
        
        switch ($format) {
            case 'excel':
                $this->export_excel($headers, $export_data, $stats, $filename);
                break;
                
            case 'pdf':
                $this->export_pdf($headers, $export_data, $stats, $filename);
                break;
                
            case 'csv':
                $this->export_csv($headers, $export_data, $filename);
                break;
                
            default:
                show_404();
        }
    }
    
    /**
     * Exportar a Excel
     */
    private function export_excel($headers, $data, $stats, $filename) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Título
        $sheet->setCellValue('A1', _l('quality_control_report'));
        $sheet->mergeCells('A1:G1');
        
        // Período
        $sheet->setCellValue('A2', _l('period') . ': ' . $this->input->get('start_date') . ' - ' . $this->input->get('end_date'));
        $sheet->mergeCells('A2:G2');
        
        // Encabezados
        $col = 'A';
        $row = 4;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        
        // Datos
        $row = 5;
        foreach ($data as $rowData) {
            $col = 'A';
            foreach ($rowData as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Estadísticas
        $row += 2;
        $sheet->setCellValue('A' . $row, _l('quality_statistics'));
        $sheet->mergeCells('A'.$row.':G'.$row);
        
        $row++;
        $sheet->setCellValue('A' . $row, _l('total_inspections') . ': ' . $stats['total']);
        $sheet->setCellValue('D' . $row, _l('approval_rate') . ': ' . $stats['approval_rate'] . '%');
        
        $row++;
        $sheet->setCellValue('A' . $row, _l('quality_pending') . ': ' . $stats['pending']);
        $sheet->setCellValue('D' . $row, _l('quality_approved') . ': ' . $stats['approved']);
        $sheet->setCellValue('F' . $row, _l('quality_rejected') . ': ' . $stats['rejected']);
        
        // Estilo
        $styleArray = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]]
        ];
        
        $sheet->getStyle('A4:G4')->applyFromArray($styleArray);
        
        // Auto-ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Exportar
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }
    
    /**
     * Exportar a PDF
     */
    private function export_pdf($headers, $data, $stats, $filename) {
        $this->load->library('pdf');
        
        $pdf = new Pdf('L', 'mm', 'A4', true, 'UTF-8', false);
        
        $pdf->SetTitle(_l('quality_control_report'));
        $pdf->SetHeaderMargin(20);
        $pdf->SetTopMargin(20);
        $pdf->setFooterMargin(20);
        $pdf->SetAutoPageBreak(true);
        $pdf->SetAuthor(get_option('company_name'));
        $pdf->SetDisplayMode('real', 'default');
        
        $pdf->AddPage();
        
        // Título
        $pdf->SetFont('dejavusans', 'B', 20);
        $pdf->Cell(0, 10, _l('quality_control_report'), 0, 1, 'C');
        
        // Período
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 10, _l('period') . ': ' . $this->input->get('start_date') . ' - ' . $this->input->get('end_date'), 0, 1, 'C');
        
        $pdf->Ln(10);
        
        // Tabla de datos
        $pdf->SetFont('dejavusans', 'B', 10);
        
        $widths = [25, 30, 50, 20, 30, 40, 60];
        $pdf->SetFillColor(223, 223, 223);
        
        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        
        $pdf->Ln();
        
        $pdf->SetFont('dejavusans', '', 9);
        foreach ($data as $row) {
            foreach ($row as $i => $cell) {
                $pdf->Cell($widths[$i], 6, $cell, 1, 0, 'L');
            }
            $pdf->Ln();
        }
        
        $pdf->Ln(10);
        
        // Estadísticas
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, _l('quality_statistics'), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(95, 7, _l('total_inspections') . ': ' . $stats['total'], 0, 0);
        $pdf->Cell(95, 7, _l('approval_rate') . ': ' . $stats['approval_rate'] . '%', 0, 1);
        
        $pdf->Cell(95, 7, _l('quality_pending') . ': ' . $stats['pending'], 0, 0);
        $pdf->Cell(95, 7, _l('quality_approved') . ': ' . $stats['approved'], 0, 0);
        $pdf->Cell(95, 7, _l('quality_rejected') . ': ' . $stats['rejected'], 0, 1);
        
        $pdf->Output($filename . '.pdf', 'D');
    }
    
    /**
     * Exportar a CSV
     */
    private function export_csv($headers, $data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, $headers);
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    /**
     * Obtener estadísticas
     */
    public function get_quality_stats() {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        
        $stats = $this->warehouse_model->get_quality_stats([
            'start_date' => $start_date,
            'end_date' => $end_date
        ]);
        
        echo json_encode(['success' => true, 'stats' => $stats]);
    }
}
