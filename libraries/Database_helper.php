<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Database_helper {
    private $ci;
    private $module = 'warehouse';
    
    public function __construct() {
        $this->ci =& get_instance();
    }
    
    /**
     * Verifica y crea las tablas necesarias
     */
    public function check_tables() {
        $tables = [
            'warehouse' => [
                'warehouse_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'warehouse_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'warehouse_name' => ['type' => 'TEXT', 'null' => true],
                'warehouse_address' => ['type' => 'TEXT', 'null' => true],
                'display' => ['type' => 'INT', 'constraint' => 1, 'null' => true],
                'note' => ['type' => 'TEXT', 'null' => true]
            ],
            'inventory_items' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'description' => ['type' => 'TEXT', 'null' => true],
                'unit_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'category_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'brand_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1]
            ],
            'inventory_stock' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'item_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'warehouse_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'min_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'max_quantity' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0]
            ],
            'inventory_movements' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'date' => ['type' => 'DATETIME', 'null' => false],
                'type' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
                'reference' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'item_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'warehouse_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'quantity' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => false],
                'unit_cost' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'notes' => ['type' => 'TEXT', 'null' => true],
                'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => false]
            ],
            'inventory_suppliers' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'contact_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'vat' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'phone' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'email' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'address' => ['type' => 'TEXT', 'null' => true],
                'city' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'state' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'zip' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                'country' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'active' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1]
            ],
            'inventory_supplier_items' => [
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'item_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'supplier_code' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'purchase_price' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
                'min_order_qty' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
                'delivery_time' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'notes' => ['type' => 'TEXT', 'null' => true]
            ]
        ];
        
        foreach ($tables as $table => $fields) {
            $this->create_table_if_not_exists($table, $fields);
        }
    }
    
    /**
     * Crea una tabla si no existe
     */
    private function create_table_if_not_exists($table, $fields) {
        $table_name = db_prefix() . $table;
        
        if (!$this->ci->db->table_exists($table_name)) {
            $this->ci->load->dbforge();
            
            // Configurar campos
            $this->ci->dbforge->add_field($fields);
            
            // Añadir clave primaria
            if (isset($fields['id'])) {
                $this->ci->dbforge->add_key('id', true);
            }
            
            // Crear tabla
            $this->ci->dbforge->create_table($table_name, true);
            
            // Log de actividad
            log_activity('Tabla creada: ' . $table_name);
        }
    }
    
    /**
     * Verifica y añade columnas faltantes
     */
    public function check_columns() {
        $updates = [
            'items' => [
                'long_description' => ['type' => 'TEXT', 'null' => true],
                'barcode' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'has_variations' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0]
            ],
            'goods_receipt' => [
                'supplier_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'expected_date' => ['type' => 'DATE', 'null' => true],
                'shipping_cost' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0]
            ]
        ];
        
        foreach ($updates as $table => $columns) {
            $this->add_missing_columns($table, $columns);
        }
    }
    
    /**
     * Añade columnas faltantes a una tabla
     */
    private function add_missing_columns($table, $columns) {
        $table_name = db_prefix() . $table;
        
        foreach ($columns as $column => $definition) {
            if (!$this->ci->db->field_exists($column, $table_name)) {
                $this->ci->load->dbforge();
                $this->ci->dbforge->add_column($table_name, [$column => $definition]);
                
                // Log de actividad
                log_activity("Columna {$column} añadida a {$table_name}");
            }
        }
    }
    
    /**
     * Optimiza las tablas del módulo
     */
    public function optimize_tables() {
        $tables = $this->ci->db->list_tables();
        
        foreach ($tables as $table) {
            if (strpos($table, db_prefix() . 'wh_') === 0 || 
                strpos($table, db_prefix() . 'inventory_') === 0) {
                $this->ci->db->query("OPTIMIZE TABLE {$table}");
            }
        }
    }
    
    /**
     * Verifica y crea índices necesarios
     */
    public function check_indexes() {
        $indexes = [
            'inventory_stock' => [
                'idx_item_warehouse' => ['item_id', 'warehouse_id'],
                'idx_item' => ['item_id'],
                'idx_warehouse' => ['warehouse_id']
            ],
            'inventory_movements' => [
                'idx_item' => ['item_id'],
                'idx_warehouse' => ['warehouse_id'],
                'idx_date' => ['date'],
                'idx_type' => ['type']
            ]
        ];
        
        foreach ($indexes as $table => $table_indexes) {
            $this->create_missing_indexes($table, $table_indexes);
        }
    }
    
    /**
     * Crea índices faltantes en una tabla
     */
    private function create_missing_indexes($table, $indexes) {
        $table_name = db_prefix() . $table;
        
        foreach ($indexes as $name => $columns) {
            // Verificar si el índice existe
            $exists = false;
            $query = $this->ci->db->query("SHOW INDEX FROM {$table_name} WHERE Key_name = '{$name}'");
            if ($query->num_rows() > 0) {
                $exists = true;
            }
            
            if (!$exists) {
                $columns_str = implode(',', $columns);
                $this->ci->db->query("ALTER TABLE {$table_name} ADD INDEX {$name} ({$columns_str})");
                
                // Log de actividad
                log_activity("Índice {$name} creado en {$table_name}");
            }
        }
    }
}
