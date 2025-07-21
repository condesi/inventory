<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sunat_reports extends Admin_controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse/warehouse_model');
        $this->load->helper('warehouse_peru_helper');
    }

    /**
     * Vista principal de reportes SUNAT
     */
    public function index() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $data['title'] = _l('sunat_reports');
        $data['report_types'] = [
            'LE' => 'Libro Electrónico de Inventario',
            'RK' => 'Registro de Kardex',
            'RCI' => 'Registro de Costos de Inventario'
        ];
        
        $this->load->view('warehouse/peru/sunat_reports_index', $data);
    }

    /**
     * Libro de Inventarios y Balances
     */
    public function inventory_book($period = null) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if (!$period) {
            $period = date('Ym');
        }

        $data['title'] = _l('inventory_book');
        $data['inventory'] = $this->warehouse_model->get_inventory_for_period($period);
        $data['period'] = $period;
        
        // Calcular totales
        $totals = [
            'total_quantity' => 0,
            'total_value' => 0
        ];
        
        foreach ($data['inventory'] as $item) {
            $totals['total_quantity'] += $item->quantity;
            $totals['total_value'] += $item->total_value;
        }
        
        $data['totals'] = $totals;
        
        $this->load->view('warehouse/peru/inventory_book', $data);
    }

    /**
     * Registro de Inventario Permanente Valorizado
     */
    public function permanent_inventory($period = null) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if (!$period) {
            $period = date('Ym');
        }

        $data['title'] = _l('permanent_inventory');
        $data['movements'] = $this->warehouse_model->get_movements_for_period($period);
        $data['period'] = $period;
        
        $this->load->view('warehouse/peru/permanent_inventory', $data);
    }

    /**
     * Registro de Costos
     */
    public function cost_registry($period = null) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if (!$period) {
            $period = date('Ym');
        }

        $data['title'] = _l('cost_registry');
        $data['costs'] = $this->warehouse_model->get_costs_for_period($period);
        $data['period'] = $period;
        
        $this->load->view('warehouse/peru/cost_registry', $data);
    }

    /**
     * Exportación de reportes en formato SUNAT
     */
    public function export($type, $period) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        // Validar formato del periodo
        if (!preg_match('/^\d{6}$/', $period)) {
            set_alert('danger', _l('invalid_period_format'));
            redirect(admin_url('warehouse/sunat_reports'));
        }

        $data = $this->get_report_data($type, $period);
        if (!$data) {
            set_alert('danger', _l('invalid_report_type'));
            redirect(admin_url('warehouse/sunat_reports'));
        }

        $filename = $this->get_report_filename($type, $period);
        $content = $this->format_report_content($type, $data);
        
        force_download($filename, $content);
    }

    /**
     * Obtiene los datos del reporte según tipo
     */
    private function get_report_data($type, $period) {
        switch ($type) {
            case 'LE':
                return $this->warehouse_model->get_inventory_for_period($period);
            case 'RK':
                return $this->warehouse_model->get_movements_for_period($period);
            case 'RCI':
                return $this->warehouse_model->get_costs_for_period($period);
            default:
                return false;
        }
    }

    /**
     * Genera el nombre del archivo según tipo
     */
    private function get_report_filename($type, $period) {
        switch ($type) {
            case 'LE':
                return 'LE' . $this->warehouse_model->get_company_ruc() . $period . '130100001111.txt';
            case 'RK':
                return 'Kardex_' . $period . '.txt';
            case 'RCI':
                return 'Costos_' . $period . '.txt';
        }
    }

    /**
     * Formatea el contenido según tipo de reporte
     */
    private function format_report_content($type, $data) {
        $content = '';
        foreach ($data as $item) {
            $line = $this->format_report_line($type, $item);
            $content .= implode('|', $line) . "\r\n";
        }
        return $content;
    }

    /**
     * Formatea una línea según tipo de reporte
     */
    private function format_report_line($type, $item) {
        switch ($type) {
            case 'LE':
                return [
                    $period,
                    str_pad($item->code, 24, ' '),
                    str_pad($item->description, 80, ' '),
                    str_pad($item->unit, 3, ' '),
                    str_pad(number_format($item->initial_quantity, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->initial_unit_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->initial_total_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->input_quantity, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->input_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->output_quantity, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->output_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->final_quantity, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->final_unit_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    str_pad(number_format($item->final_total_value, 2, '.', ''), 12, '0', STR_PAD_LEFT),
                    '1'
                ];
            case 'RK':
                return [
                    $item->date,
                    $item->type,
                    $item->document_type,
                    $item->document_number,
                    $item->operation_type,
                    number_format($item->quantity, 2),
                    number_format($item->unit_value, 2),
                    number_format($item->total_value, 2)
                ];
            case 'RCI':
                return [
                    $item->item_code,
                    $item->description,
                    number_format($item->quantity, 2),
                    number_format($item->unit_cost, 2),
                    number_format($item->total_cost, 2),
                    $item->method
                ];
        }
    }
    }

    /**
     * Validación de estructura de libros electrónicos
     */
    public function validate_structure($file_id) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $file = $this->warehouse_model->get_sunat_file($file_id);
        
        // Validar estructura según tipo
        $result = $this->validate_file_structure($file);
        
        if ($result['valid']) {
            set_alert('success', _l('file_structure_valid'));
        } else {
            set_alert('danger', $result['errors']);
        }
        
        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Validación de archivo
     */
    private function validate_file_structure($file) {
        $content = file_get_contents($file->path);
        $lines = explode("\n", $content);
        $errors = [];
        
        foreach ($lines as $i => $line) {
            $fields = explode('|', $line);
            
            // Validar según tipo de archivo
            switch ($file->type) {
                case 'LE':
                    if (count($fields) !== 15) {
                        $errors[] = "Línea " . ($i + 1) . ": Número incorrecto de campos";
                    }
                    break;
                
                case 'RK':
                    if (count($fields) !== 8) {
                        $errors[] = "Línea " . ($i + 1) . ": Número incorrecto de campos";
                    }
                    break;
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
