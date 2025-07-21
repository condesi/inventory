<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase base para manejo de movimientos de inventario
 */
class Inventory_movement_model extends App_Model {
    protected $ci;
    protected $movement_types = [
        'in' => ['receipt', 'adjustment_in', 'return_in'],
        'out' => ['issue', 'adjustment_out', 'return_out']
    ];

    public function __construct() {
        parent::__construct();
        $this->ci =& get_instance();
    }

    /**
     * Registrar movimiento de inventario
     */
    protected function register_movement($data) {
        // Validar datos básicos
        $required = ['type', 'item_id', 'warehouse_id', 'quantity'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception(_l('required_field_' . $field));
            }
        }

        // Validar tipo de movimiento
        $valid_types = array_merge($this->movement_types['in'], $this->movement_types['out']);
        if (!in_array($data['type'], $valid_types)) {
            throw new Exception(_l('invalid_movement_type'));
        }

        // Determinar si es entrada o salida
        $is_in = in_array($data['type'], $this->movement_types['in']);
        $quantity = $is_in ? abs($data['quantity']) : -abs($data['quantity']);

        // Verificar stock suficiente para salidas
        if (!$is_in) {
            $current_stock = $this->get_current_stock($data['item_id'], $data['warehouse_id']);
            if ($current_stock < abs($quantity)) {
                throw new Exception(_l('insufficient_stock'));
            }
        }

        // Iniciar transacción
        $this->db->trans_start();

        try {
            // Registrar movimiento
            $movement = [
                'date' => date('Y-m-d H:i:s'),
                'type' => $data['type'],
                'reference' => $data['reference'] ?? null,
                'item_id' => $data['item_id'],
                'warehouse_id' => $data['warehouse_id'],
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'] ?? 0,
                'total_cost' => ($data['unit_cost'] ?? 0) * abs($quantity),
                'notes' => $data['notes'] ?? null,
                'created_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix() . 'inventory_movements', $movement);
            $movement_id = $this->db->insert_id();

            // Actualizar stock
            $this->update_stock_quantity($data['item_id'], $data['warehouse_id'], $quantity);

            // Actualizar costo promedio si es entrada
            if ($is_in && isset($data['unit_cost'])) {
                $this->update_average_cost($data['item_id'], $data['unit_cost'], abs($quantity));
            }

            // Registrar actividad
            $item = $this->get_item($data['item_id']);
            $warehouse = $this->get_warehouse($data['warehouse_id']);
            
            log_activity(sprintf(
                _l('inventory_movement_log'),
                $data['type'],
                $item->name,
                abs($quantity),
                $warehouse->name
            ));

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception(_l('error_processing_movement'));
            }

            return $movement_id;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Obtener stock actual
     */
    protected function get_current_stock($item_id, $warehouse_id) {
        $stock = $this->db->where('item_id', $item_id)
                         ->where('warehouse_id', $warehouse_id)
                         ->get(db_prefix() . 'inventory_stock')
                         ->row();
                         
        return $stock ? $stock->quantity : 0;
    }

    /**
     * Actualizar cantidad en stock
     */
    protected function update_stock_quantity($item_id, $warehouse_id, $quantity) {
        $stock = $this->db->where('item_id', $item_id)
                         ->where('warehouse_id', $warehouse_id)
                         ->get(db_prefix() . 'inventory_stock')
                         ->row();

        if ($stock) {
            // Actualizar registro existente
            $this->db->where('id', $stock->id);
            $this->db->set('quantity', 'quantity + ' . $quantity, FALSE);
            $this->db->update(db_prefix() . 'inventory_stock');
        } else {
            // Crear nuevo registro
            $this->db->insert(db_prefix() . 'inventory_stock', [
                'item_id' => $item_id,
                'warehouse_id' => $warehouse_id,
                'quantity' => $quantity
            ]);
        }
    }

    /**
     * Actualizar costo promedio
     */
    protected function update_average_cost($item_id, $new_cost, $new_quantity) {
        $item = $this->get_item($item_id);
        
        if (!$item) return;

        $current_stock = $this->get_total_stock($item_id);
        $current_cost = $item->average_cost ?? 0;

        // Calcular nuevo costo promedio
        $total_quantity = $current_stock + $new_quantity;
        $average_cost = (($current_stock * $current_cost) + ($new_quantity * $new_cost)) / $total_quantity;

        // Actualizar item
        $this->db->where('id', $item_id);
        $this->db->update(db_prefix() . 'inventory_items', [
            'average_cost' => $average_cost,
            'last_cost' => $new_cost,
            'last_cost_date' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener stock total
     */
    protected function get_total_stock($item_id) {
        $result = $this->db->select_sum('quantity')
                          ->where('item_id', $item_id)
                          ->get(db_prefix() . 'inventory_stock')
                          ->row();
                          
        return $result ? $result->quantity : 0;
    }

    /**
     * Obtener información de item
     */
    protected function get_item($item_id) {
        return $this->db->get_where(db_prefix() . 'inventory_items', ['id' => $item_id])->row();
    }

    /**
     * Obtener información de almacén
     */
    protected function get_warehouse($warehouse_id) {
        return $this->db->get_where(db_prefix() . 'warehouses', ['id' => $warehouse_id])->row();
    }

    /**
     * Verificar si hay stock suficiente
     */
    public function check_stock_availability($item_id, $warehouse_id, $quantity) {
        $current_stock = $this->get_current_stock($item_id, $warehouse_id);
        return $current_stock >= $quantity;
    }

    /**
     * Obtener movimientos
     */
    public function get_movements($filters = []) {
        $this->db->select([
            db_prefix() . 'inventory_movements.*',
            'items.name as item_name',
            'items.code as item_code',
            'w.name as warehouse_name',
            'CONCAT(staff.firstname, " ", staff.lastname) as created_by_name'
        ]);
        
        $this->db->from(db_prefix() . 'inventory_movements');
        $this->db->join(db_prefix() . 'inventory_items items', 'items.id = ' . db_prefix() . 'inventory_movements.item_id');
        $this->db->join(db_prefix() . 'warehouses w', 'w.id = ' . db_prefix() . 'inventory_movements.warehouse_id');
        $this->db->join(db_prefix() . 'staff staff', 'staff.staffid = ' . db_prefix() . 'inventory_movements.created_by');

        // Aplicar filtros
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['item_id'])) {
            $this->db->where('item_id', $filters['item_id']);
        }

        if (!empty($filters['type'])) {
            $this->db->where('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $this->db->where('date >=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->db->where('date <=', $filters['date_to']);
        }

        $this->db->order_by('date', 'DESC');

        return $this->db->get()->result();
    }
}
