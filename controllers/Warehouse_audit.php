<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Warehouse_audit extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('warehouse_audit_helper');
        
        // Solo administradores y usuarios con permiso específico
        if (!is_admin() && !has_permission('warehouse_audit', '', 'view')) {
            access_denied('warehouse_audit');
        }
    }
    
    /**
     * Ver registro de auditoría
     */
    public function index() {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('warehouse_audit_log');
        }
        
        $data['title'] = _l('warehouse_audit_log');
        $this->load->view('warehouse/audit/manage', $data);
    }
    
    /**
     * Ver detalles de una entrada de auditoría
     */
    public function view($id) {
        if (!$id) {
            redirect(admin_url('warehouse/audit'));
        }
        
        $data['title'] = _l('warehouse_audit_entry');
        $data['entry'] = $this->db
            ->where('id', $id)
            ->get(db_prefix() . 'warehouse_audit_log')
            ->row_array();
            
        if (!$data['entry']) {
            show_404();
        }
        
        // Decodificar valores JSON
        if ($data['entry']['old_value']) {
            $data['entry']['old_value'] = json_decode($data['entry']['old_value'], true);
        }
        if ($data['entry']['new_value']) {
            $data['entry']['new_value'] = json_decode($data['entry']['new_value'], true);
        }
        
        $this->load->view('warehouse/audit/view', $data);
    }
    
    /**
     * Exportar registro de auditoría
     */
    public function export() {
        if (!is_admin()) {
            access_denied('warehouse_audit_export');
        }
        
        $this->load->library('pdf');
        $this->load->helper('export');
        
        $from = $this->input->get('from');
        $to = $this->input->get('to');
        $type = $this->input->get('type');
        
        $where = [];
        if ($from) {
            $where['timestamp >='] = $from;
        }
        if ($to) {
            $where['timestamp <='] = $to;
        }
        if ($type) {
            $where['action_type'] = $type;
        }
        
        $data['entries'] = $this->db
            ->where($where)
            ->order_by('timestamp', 'DESC')
            ->get(db_prefix() . 'warehouse_audit_log')
            ->result_array();
            
        $data['title'] = _l('warehouse_audit_export');
        
        try {
            $pdf = audit_log_pdf($data);
            $pdf->Output('audit_log_' . date('Y-m-d') . '.pdf', 'D');
        } catch (Exception $e) {
            $this->session->set_flashdata('warning', _l('audit_export_error'));
            redirect(admin_url('warehouse/audit'));
        }
    }
    
    /**
     * Limpiar entradas antiguas del registro
     */
    public function cleanup() {
        if (!is_admin()) {
            access_denied('warehouse_audit_cleanup');
        }
        
        $days = $this->input->post('days');
        if (!$days || !is_numeric($days) || $days < 30) {
            $this->session->set_flashdata('warning', _l('invalid_cleanup_parameters'));
            redirect(admin_url('warehouse/audit'));
        }
        
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));
        
        $this->db->where('timestamp <', $cutoff);
        $deleted = $this->db->delete(db_prefix() . 'warehouse_audit_log');
        
        if ($deleted) {
            $this->session->set_flashdata('success', _l('audit_cleanup_success'));
        } else {
            $this->session->set_flashdata('warning', _l('audit_cleanup_error'));
        }
        
        redirect(admin_url('warehouse/audit'));
    }
}
