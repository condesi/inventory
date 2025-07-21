<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Current_stock_model extends App_Model {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get current stock data for enhanced stock report
     * @param  array $warehouse_filter
     * @param  array $commodity_filter
     * @param  array $group_filter
     * @param  string $status_filter
     * @return array
     */
    public function get_current_stock_data($warehouse_filter = [], $commodity_filter = [], $group_filter = [], $status_filter = '') {
        // Base query to get stock data
        $this->db->select('it.id, it.commodity_code, it.description as commodity_name, it.unit_id, unit.unit_name, 
                          wh.warehouse_name, it.rate as unit_cost, it.purchase_price, it.minimum_inventory as minimum_stock, 
                          it.group_id, gi.commodity_group_code, gi.name as group_name, 
                          COALESCE(im.inventory_number, 0) as quantity,
                          whm.location');
        
        $this->db->from(db_prefix() . 'items as it');
        $this->db->join(db_prefix() . 'ware_unit_type as unit', 'it.unit_id = unit.unit_type_id', 'left');
        $this->db->join(db_prefix() . 'items_groups as gi', 'it.group_id = gi.id', 'left');
        $this->db->join('(' . $this->get_inventory_query($warehouse_filter) . ') as im', 'it.id = im.commodity_id', 'left');
        $this->db->join(db_prefix() . 'warehouse as wh', 'im.warehouse_id = wh.warehouse_id', 'left');
        $this->db->join(db_prefix() . 'warehouse_manage as whm', 'im.warehouse_id = whm.warehouse_id AND it.id = whm.commodity_id', 'left');
        
        // Apply filters
        if (is_array($commodity_filter) && count($commodity_filter) > 0) {
            $this->db->where_in('it.id', $commodity_filter);
        }
        
        if (is_array($group_filter) && count($group_filter) > 0) {
            $this->db->where_in('it.group_id', $group_filter);
        }
        
        // Apply stock status filter
        if ($status_filter == 'warning') {
            $this->db->where('im.inventory_number <= it.minimum_inventory');
            $this->db->where('im.inventory_number > 0');
        } else if ($status_filter == 'critical') {
            $this->db->where('im.inventory_number <= 0');
        } else if ($status_filter == 'available') {
            $this->db->where('im.inventory_number > it.minimum_inventory');
        }
        
        // Only include inventory items
        $this->db->where('it.inventory_item', 1);
        
        // Group by item and warehouse if we're showing multiple warehouses
        $this->db->group_by('it.id, im.warehouse_id');
        
        // Order by item code
        $this->db->order_by('it.commodity_code', 'asc');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Generate subquery for inventory data
     * @param  array $warehouse_filter
     * @return string
     */
    private function get_inventory_query($warehouse_filter = []) {
        $query = "SELECT commodity_id, warehouse_id, SUM(inventory_number) as inventory_number FROM " . db_prefix() . "inventory_manage ";
        
        if (is_array($warehouse_filter) && count($warehouse_filter) > 0) {
            $query .= "WHERE warehouse_id IN (" . implode(',', array_map(function($id) {
                return $this->db->escape($id);
            }, $warehouse_filter)) . ") ";
        }
        
        $query .= "GROUP BY commodity_id, warehouse_id";
        
        return $query;
    }
    
    /**
     * Generate PDF for current stock report
     * @param  array $data
     * @return object
     */
    public function current_stock_report_pdf($data) {
        return app_pdf('current_stock_report', module_dir_path(WAREHOUSE_MODULE_NAME, 'views/report/current_stock_report_pdf.php'), $data);
    }
}