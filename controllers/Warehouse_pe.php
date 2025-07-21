<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Warehouse_pe extends Admin_controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse/warehouse_model');
        $this->load->helper('warehouse_peru_helper');
    }

    /**
     * Vista principal del módulo
     */
    public function index() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $data['title'] = _l('warehouse_management');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_list();
        
        // Información específica para Perú
        $data['document_types'] = PE_DOCUMENT_TYPES;
        $data['operation_types'] = PE_OPERATION_TYPES;
        
        $this->load->view('warehouse/peru/dashboard', $data);
    }

    /**
     * Gestión de Guías de Remisión
     */
    public function guias_remision($action = '', $id = '') {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if ($action == 'create' || $action == 'edit') {
            if ($action == 'edit') {
                $data['guia'] = $this->warehouse_model->get_guia_remision($id);
                if (!$data['guia']) {
                    show_404();
                }
            }

            $data['title'] = $action == 'create' ? _l('create_guia_remision') : _l('edit_guia_remision');
            $data['establecimientos'] = $this->warehouse_model->get_establecimientos();
            $data['motivos_traslado'] = [
                '01' => 'Venta',
                '02' => 'Compra',
                '04' => 'Traslado entre establecimientos',
                '08' => 'Importación',
                '09' => 'Exportación',
                '13' => 'Otros',
                '14' => 'Venta sujeta a confirmación',
                '18' => 'Traslado emisor itinerante',
                '19' => 'Traslado a zona primaria'
            ];

            $this->load->view('warehouse/peru/guia_remision_form', $data);
        } else {
            // Listado de guías
            $data['title'] = _l('guias_remision');
            $this->load->view('warehouse/peru/guias_remision', $data);
        }
    }

    /**
     * Gestión de Kardex
     */
    public function kardex($item_id = null) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            $filters = [
                'start_date' => $this->input->post('start_date'),
                'end_date' => $this->input->post('end_date'),
                'item_id' => $this->input->post('item_id'),
                'warehouse_id' => $this->input->post('warehouse_id')
            ];

            $data['kardex'] = calculate_kardex_fifo(
                $filters['item_id'],
                $filters['start_date'],
                $filters['end_date']
            );
        }

        $data['title'] = _l('kardex_valorizado');
        $data['items'] = $this->warehouse_model->get_items();
        $data['warehouses'] = $this->warehouse_model->get_warehouse_list();
        
        if ($item_id) {
            $data['item'] = $this->warehouse_model->get_item($item_id);
            $data['movements'] = $this->warehouse_model->get_item_movements($item_id);
        }

        $this->load->view('warehouse/peru/kardex', $data);
    }

    /**
     * Gestión de Reportes SUNAT
     */
    public function sunat_reports() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            $period = $this->input->post('period');
            $type = $this->input->post('report_type');

            $data['report'] = generate_sunat_report($type, $period);
        }

        $data['title'] = _l('sunat_reports');
        $this->load->view('warehouse/peru/sunat_reports', $data);
    }

    /**
     * Validación de documentos
     */
    public function validate_document() {
        $type = $this->input->post('type');
        $number = $this->input->post('number');

        $response = ['valid' => false];

        if (validate_document_number($type, $number)) {
            // Verificar en SUNAT si el documento está activo
            $sunat_status = check_sunat_document($type, $number);
            $response = [
                'valid' => true,
                'sunat_status' => $sunat_status
            ];
        }

        echo json_encode($response);
    }

    /**
     * Generación de XML SUNAT
     */
    public function generate_xml($invoice_id) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $invoice_data = $this->warehouse_model->get_invoice($invoice_id);
        if (!$invoice_data) {
            show_404();
        }

        $xml = generate_sunat_xml($invoice_data);
        
        // Guardar XML
        $filename = 'XML_' . $invoice_data->serie_numero . '.xml';
        write_file(WAREHOUSE_MODULE_UPLOAD_FOLDER . '/sunat/' . $filename, $xml);

        // Descargar archivo
        force_download($filename, $xml);
    }

    /**
     * Control de stock según normativa peruana
     */
    public function check_stock_levels() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $items = $this->warehouse_model->get_items_with_stock_alert();
        
        foreach ($items as $item) {
            check_minimum_stock_pe($item->id);
        }

        $data['title'] = _l('stock_control');
        $data['items'] = $items;
        
        $this->load->view('warehouse/peru/stock_control', $data);
    }

    /**
     * Cálculo de costos promedio
     */
    public function calculate_average_cost($item_id) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $movements = $this->warehouse_model->get_item_movements($item_id);
        $total_quantity = 0;
        $total_value = 0;

        foreach ($movements as $movement) {
            if ($movement->type == 'in') {
                $total_quantity += $movement->quantity;
                $total_value += ($movement->quantity * $movement->unit_value);
            }
        }

        return $total_quantity > 0 ? ($total_value / $total_quantity) : 0;
    }

    /**
     * Proceso de cierre mensual
     */
    public function monthly_close($year, $month) {
        if (!has_permission('warehouse', '', 'admin')) {
            access_denied('warehouse');
        }

        // 1. Generar Libro de Inventarios
        generate_sunat_report('LE', $year . $month);

        // 2. Valorización de inventario
        $items = $this->warehouse_model->get_items();
        foreach ($items as $item) {
            $average_cost = $this->calculate_average_cost($item->id);
            $this->warehouse_model->update_item_cost($item->id, $average_cost);
        }

        // 3. Generar backups y registros
        $this->warehouse_model->register_monthly_close([
            'period' => $year . $month,
            'closed_by' => get_staff_user_id(),
            'closed_at' => date('Y-m-d H:i:s')
        ]);

        set_alert('success', _l('monthly_close_completed'));
        redirect(admin_url('warehouse/sunat_reports'));
    }
}
