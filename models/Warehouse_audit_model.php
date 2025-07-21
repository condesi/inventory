<?php defined('BASEPATH') or exit('No direct script access allowed');

class Warehouse_audit_model extends App_Model {
    private $table = 'warehouse_audit_log';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Registra una acción en el log de auditoría
     */
    public function log_action($data) {
        $data['user_id'] = get_staff_user_id();
        $data['timestamp'] = date('Y-m-d H:i:s');
        $data['ip_address'] = $this->input->ip_address();
        $data['user_agent'] = $this->input->user_agent();
        
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Obtiene el historial de auditoría con filtros
     */
    public function get_audit_logs($filters = []) {
        $this->db->select('warehouse_audit_log.*, staff.firstname, staff.lastname');
        $this->db->from($this->table);
        $this->db->join('staff', 'staff.staffid = warehouse_audit_log.user_id');
        
        if (isset($filters['start_date'])) {
            $this->db->where('timestamp >=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $this->db->where('timestamp <=', $filters['end_date']);
        }
        
        if (isset($filters['action_type'])) {
            $this->db->where('action_type', $filters['action_type']);
        }
        
        if (isset($filters['user_id'])) {
            $this->db->where('user_id', $filters['user_id']);
        }
        
        $this->db->order_by('timestamp', 'desc');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Obtiene el detalle de una entrada del log
     */
    public function get_log_detail($id) {
        $this->db->select('warehouse_audit_log.*, staff.firstname, staff.lastname');
        $this->db->from($this->table);
        $this->db->join('staff', 'staff.staffid = warehouse_audit_log.user_id');
        $this->db->where('warehouse_audit_log.id', $id);
        
        return $this->db->get()->row();
    }
    
    /**
     * Exporta el log de auditoría a CSV
     */
    public function export_logs($filters = []) {
        $logs = $this->get_audit_logs($filters);
        
        $csv = [];
        $headers = ['Fecha', 'Usuario', 'Acción', 'Módulo', 'Detalles', 'IP'];
        
        foreach ($logs as $log) {
            $csv[] = [
                $log['timestamp'],
                $log['firstname'] . ' ' . $log['lastname'],
                $log['action_type'],
                $log['module'],
                $log['details'],
                $log['ip_address']
            ];
        }
        
        return ['headers' => $headers, 'data' => $csv];
    }
}
