<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_reports extends Admin_controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('warehouse/inventory_movement_model');
    }

    /**
     * Vista principal de reportes
     */
    public function index() {
        if (!has_permission('wh_report', '', 'view')) {
            access_denied('wh_report');
        }

        $data['title'] = _l('inventory_reports');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_list();
        
        $this->load->view('warehouse/reports/index', $data);
    }

    /**
     * Reporte de movimientos de inventario
     */
    public function movements() {
        if (!has_permission('wh_report', '', 'view')) {
            access_denied('wh_report');
        }

        $filters = [
            'start_date' => $this->input->post('start_date'),
            'end_date' => $this->input->post('end_date'),
            'warehouse_id' => $this->input->post('warehouse_id'),
            'type' => $this->input->post('movement_type')
        ];

        $data['movements'] = $this->inventory_movement_model->get_movements_report($filters);
        
        // Calcular totales
        $totals = [
            'in' => 0,
            'out' => 0,
            'value' => 0
        ];
        
        foreach ($data['movements'] as $movement) {
            if ($movement['type'] == 'in') {
                $totals['in'] += $movement['quantity'];
                $totals['value'] += $movement['quantity'] * $movement['unit_price'];
            } else {
                $totals['out'] += $movement['quantity'];
            }
        }
        
        $data['totals'] = $totals;
        $data['title'] = _l('inventory_movements_report');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_list();

        if ($this->input->is_ajax_request()) {
            $this->load->view('warehouse/reports/movements_table', $data);
        } else {
            $this->load->view('warehouse/reports/movements', $data);
        }
    }

    /**
     * Reporte de valorización de inventario
     */
    public function valuation() {
        if (!has_permission('wh_report', '', 'view')) {
            access_denied('wh_report');
        }

        $warehouse_id = $this->input->get('warehouse_id');
        $data['valuation'] = $this->inventory_movement_model->get_inventory_valuation_report($warehouse_id);
        
        // Calcular totales
        $total_value = 0;
        foreach ($data['valuation'] as $item) {
            $total_value += $item['total_value'];
        }
        
        $data['total_value'] = $total_value;
        $data['title'] = _l('inventory_valuation_report');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_list();
        
        if ($this->input->is_ajax_request()) {
            $this->load->view('warehouse/reports/valuation_table', $data);
        } else {
            $this->load->view('warehouse/reports/valuation', $data);
        }
    }

    /**
     * Reporte de movimientos por producto
     */
    public function item_movements($item_id = null) {
        if (!has_permission('wh_report', '', 'view')) {
            access_denied('wh_report');
        }

        if ($item_id) {
            $data['item'] = $this->warehouse_model->get_item($item_id);
            
            $filters = [
                'item_id' => $item_id,
                'start_date' => $this->input->get('start_date'),
                'end_date' => $this->input->get('end_date')
            ];
            
            $data['movements'] = $this->inventory_movement_model->get_movements_report($filters);
            
            // Calcular saldos
            $balance = 0;
            foreach ($data['movements'] as &$movement) {
                if ($movement['type'] == 'in') {
                    $balance += $movement['quantity'];
                } else {
                    $balance -= $movement['quantity'];
                }
                $movement['balance'] = $balance;
            }
            
            $data['title'] = _l('item_movements_report') . ' - ' . $data['item']->description;
            
            $this->load->view('warehouse/reports/item_movements', $data);
        } else {
            redirect(admin_url('warehouse/inventory_reports'));
        }
    }

    /**
     * Exportar reportes a Excel
     */
    public function export($report_type) {
        if (!has_permission('wh_report', '', 'view')) {
            access_denied('wh_report');
        }

        $this->load->library('excel');
        
        switch ($report_type) {
            case 'movements':
                $this->export_movements();
                break;
                
            case 'valuation':
                $this->export_valuation();
                break;
                
            default:
                set_alert('warning', _l('invalid_report_type'));
                redirect(admin_url('warehouse/inventory_reports'));
        }
    }

    /**
     * Exportar reporte de movimientos
     */
    private function export_movements() {
        $filters = [
            'start_date' => $this->input->get('start_date'),
            'end_date' => $this->input->get('end_date'),
            'warehouse_id' => $this->input->get('warehouse_id'),
            'type' => $this->input->get('movement_type')
        ];

        $movements = $this->inventory_movement_model->get_movements_report($filters);
        
        $this->excel->setActiveSheetIndex(0);
        $this->excel->getActiveSheet()->setTitle(_l('inventory_movements_report'));
        
        // Encabezados
        $this->excel->getActiveSheet()->setCellValue('A1', _l('date'));
        $this->excel->getActiveSheet()->setCellValue('B1', _l('type'));
        $this->excel->getActiveSheet()->setCellValue('C1', _l('item'));
        $this->excel->getActiveSheet()->setCellValue('D1', _l('warehouse'));
        $this->excel->getActiveSheet()->setCellValue('E1', _l('quantity'));
        $this->excel->getActiveSheet()->setCellValue('F1', _l('unit_price'));
        $this->excel->getActiveSheet()->setCellValue('G1', _l('total'));
        
        $row = 2;
        foreach ($movements as $movement) {
            $this->excel->getActiveSheet()->setCellValue('A'.$row, _dt($movement['date']));
            $this->excel->getActiveSheet()->setCellValue('B'.$row, _l($movement['type']));
            $this->excel->getActiveSheet()->setCellValue('C'.$row, $movement['item_name']);
            $this->excel->getActiveSheet()->setCellValue('D'.$row, $movement['warehouse_name']);
            $this->excel->getActiveSheet()->setCellValue('E'.$row, $movement['quantity']);
            $this->excel->getActiveSheet()->setCellValue('F'.$row, app_format_money($movement['unit_price'],''));
            $this->excel->getActiveSheet()->setCellValue('G'.$row, app_format_money($movement['quantity'] * $movement['unit_price'],''));
            $row++;
        }

        $filename = 'inventory_movements_'.date('Y-m-d').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007');
        $writer->save('php://output');
    }
}
