<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Quick_item_model extends App_Model {
    private $ci;

    public function __construct() {
        parent::__construct();
        $this->ci =& get_instance();
    }

    /**
     * Crear producto rápido desde ingreso de stock
     */
    public function create_quick_item($data) {
        // Validar datos requeridos
        $required = ['code', 'name', 'unit_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception(_l('required_field_' . $field));
            }
        }

        // Validar código único
        if ($this->code_exists($data['code'])) {
            throw new Exception(_l('item_code_already_exists'));
        }

        // Preparar datos
        $item = [
            'code' => $data['code'],
            'name' => $data['name'],
            'unit_id' => $data['unit_id'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'category_id' => isset($data['category_id']) ? $data['category_id'] : null,
            'cost_price' => isset($data['cost_price']) ? $data['cost_price'] : 0,
            'active' => 1
        ];

        // Insertar en transacción
        $this->db->trans_start();

        $this->db->insert(db_prefix() . 'inventory_items', $item);
        $item_id = $this->db->insert_id();

        // Registrar actividad
        $this->log_activity($item_id, 'quick_item_created');

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new Exception(_l('error_creating_item'));
        }

        return $item_id;
    }

    /**
     * Verificar si el código existe
     */
    private function code_exists($code, $id = null) {
        $this->db->where('code', $code);
        if ($id) {
            $this->db->where('id !=', $id);
        }
        return $this->db->get(db_prefix() . 'inventory_items')->num_rows() > 0;
    }

    /**
     * Registrar actividad
     */
    private function log_activity($item_id, $action) {
        $item = $this->db->get_where(db_prefix() . 'inventory_items', ['id' => $item_id])->row();
        
        log_activity(sprintf(
            _l($action),
            $item->code,
            $item->name
        ));
    }

    /**
     * Obtener producto por código
     */
    public function get_item_by_code($code) {
        return $this->db->get_where(db_prefix() . 'inventory_items', ['code' => $code])->row();
    }
}
