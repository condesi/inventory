<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Internal_delivery_model extends App_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Agregar entrega interna
     */
    public function add($data) {
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert('internal_deliveries', $data);
        $delivery_id = $this->db->insert_id();
        
        if ($delivery_id) {
            log_activity('Nueva entrega interna creada [ID: '.$delivery_id.']');
        }
        
        return $delivery_id;
    }
    
    /**
     * Agregar item de entrega
     */
    public function add_item($data) {
        $this->db->insert('internal_delivery_items', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Obtener entrega por ID
     */
    public function get($id) {
        $this->db->select('*');
        $this->db->from('internal_deliveries');
        $this->db->where('id', $id);
        
        return $this->db->get()->row();
    }
    
    /**
     * Obtener items de una entrega
     */
    public function get_items($delivery_id) {
        $this->db->select('tdi.*, ti.code, ti.name, ti.unit_id, tu.name as unit_name');
        $this->db->from('internal_delivery_items tdi');
        $this->db->join('items ti', 'ti.id = tdi.item_id');
        $this->db->join('units tu', 'tu.id = ti.unit_id', 'left');
        $this->db->where('tdi.delivery_id', $delivery_id);
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Obtener entregas
     */
    public function get_deliveries($filters = []) {
        $this->db->select('tid.*, ts.firstname, ts.lastname, tw.name as warehouse_name');
        $this->db->from('internal_deliveries tid');
        $this->db->join('staff ts', 'ts.staffid = tid.created_by');
        $this->db->join('warehouses tw', 'tw.id = tid.warehouse_id');
        
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('tid.warehouse_id', $filters['warehouse_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $this->db->where('tid.date >=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $this->db->where('tid.date <=', $filters['end_date']);
        }
        
        if (!empty($filters['action_type'])) {
            $this->db->where('tid.action_type', $filters['action_type']);
        }
        
        if (!empty($filters['created_by'])) {
            $this->db->where('tid.created_by', $filters['created_by']);
        }
        
        $this->db->order_by('tid.id', 'desc');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Obtener estadísticas
     */
    public function get_stats($filters = []) {
        // Total de entregas
        $total_deliveries = $this->db->where($filters)
                                   ->from('internal_deliveries')
                                   ->count_all_results();
        
        // Total de items entregados
        $total_items = $this->db->select('SUM(quantity) as total')
                               ->from('internal_delivery_items tdi')
                               ->join('internal_deliveries tid', 'tid.id = tdi.delivery_id')
                               ->where($filters)
                               ->get()
                               ->row()
                               ->total;
        
        // Entregas por tipo
        $by_type = $this->db->select('action_type, COUNT(*) as total')
                           ->from('internal_deliveries')
                           ->where($filters)
                           ->group_by('action_type')
                           ->get()
                           ->result_array();
        
        return [
            'total_deliveries' => $total_deliveries,
            'total_items' => $total_items,
            'by_type' => $by_type
        ];
    }
    
    /**
     * Actualizar entrega
     */
    public function update($id, $data) {
        $data['updated_by'] = get_staff_user_id();
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $this->db->where('id', $id);
        $this->db->update('internal_deliveries', $data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Entrega interna actualizada [ID: '.$id.']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Eliminar entrega
     */
    public function delete($id) {
        // Primero eliminamos los items
        $this->db->where('delivery_id', $id);
        $this->db->delete('internal_delivery_items');
        
        // Luego la entrega
        $this->db->where('id', $id);
        $this->db->delete('internal_deliveries');
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Entrega interna eliminada [ID: '.$id.']');
            return true;
        }
        
        return false;
    }
}
