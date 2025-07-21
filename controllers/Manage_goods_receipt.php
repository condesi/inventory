<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manage_goods_receipt extends Admin_controller {
    use WarehousePermissionsTrait;

    private $receipt_settings;
    
    public function __construct() {
        parent::__construct();
        $this->load->model(['Goods_receipt_model', 'Quick_item_model', 'Warehouse_model']);
        $this->load->helper(['warehouse', 'warehouse_peru']);
        
        // Cargar configuración de columnas y preferencias
        $this->receipt_settings = $this->load_receipt_settings();
    }

    /**
     * Vista principal de recepción de mercancías
     */
    public function index() {
        $this->check_permission('view');
        
        $data = $this->load_common_data();
        $data['title'] = _l('goods_receipt_management');
        $data['receipt_settings'] = $this->receipt_settings;
        
        // Configuración de columnas
        $data['available_columns'] = [
            'article' => [
                'label' => _l('article'),
                'visible' => true,
                'required' => true
            ],
            'warehouse' => [
                'label' => _l('warehouse'),
                'visible' => true,
                'required' => true
            ],
            'quantity' => [
                'label' => _l('quantity'),
                'visible' => true,
                'required' => true
            ],
            'unit_price' => [
                'label' => _l('unit_price'),
                'visible' => true,
                'required' => true
            ],
            'tax' => [
                'label' => _l('tax'),
                'visible' => true,
                'required' => false
            ],
            'lot_number' => [
                'label' => _l('lot_number'),
                'visible' => true,
                'required' => false
            ],
            'manufacture_date' => [
                'label' => _l('manufacture_date'),
                'visible' => true,
                'required' => false
            ],
            'expiry_date' => [
                'label' => _l('expiry_date'),
                'visible' => true,
                'required' => false
            ],
            'total' => [
                'label' => _l('total'),
                'visible' => true,
                'required' => true
            ]
        ];
        
        // Editor HTML para notas
        $this->load->library('editor');
        $data['editor_settings'] = [
            'textarea_name' => 'notes',
            'media_buttons' => false,
            'tinymce' => [
                'toolbar' => 'bold italic underline | bullist numlist | link',
                'plugins' => 'link lists'
            ]
        ];
        
        $this->load->view('warehouse/goods_receipt/manage', $data);
    }

    /**
     * Guardar recepción de mercancías
     */
    public function save() {
        $this->check_permission('create');
        
        if ($this->input->post()) {
            try {
                $data = $this->input->post();
                
                // Validar datos
                $this->validate_receipt_data($data);
                
                // Procesar items
                $items = json_decode($data['items'], true);
                unset($data['items']);
                
                // Formatear fechas
                $data['accounting_date'] = to_sql_date($data['accounting_date']);
                $data['document_date'] = to_sql_date($data['document_date']);
                $data['expiry_date'] = to_sql_date($data['expiry_date']);
                
                // Procesar notas HTML
                $data['notes'] = $this->security->xss_clean($data['notes']);
                
                // Iniciar transacción
                $this->db->trans_start();
                
                // Guardar cabecera
                $receipt_id = $this->Goods_receipt_model->add($data);
                
                // Guardar items
                foreach ($items as $item) {
                    $item['receipt_id'] = $receipt_id;
                    $this->Goods_receipt_model->add_item($item);
                    
                    // Actualizar stock
                    $this->Warehouse_model->update_stock($item['warehouse_id'], $item['item_id'], $item['quantity']);
                }
                
                // Finalizar transacción
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception(_l('error_saving_receipt'));
                }
                
                // Registrar actividad
                log_activity('Nueva recepción de mercancías creada [ID: '.$receipt_id.']');
                
                echo json_encode([
                    'success' => true,
                    'message' => _l('goods_receipt_saved'),
                    'receipt_id' => $receipt_id
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Crear producto rápido desde recepción
     */
    public function quick_add_item() {
        $this->check_permission('create');
        
        if ($this->input->post()) {
            try {
                $data = $this->input->post();
                
                // Validar datos mínimos
                $required_fields = ['code', 'name', 'unit_id'];
                foreach ($required_fields as $field) {
                    if (empty($data[$field])) {
                        throw new Exception(_l('field_required_'.$field));
                    }
                }
                
                // Procesar datos
                $item_data = [
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'unit_id' => $data['unit_id'],
                    'description' => $data['description'] ?? '',
                    'group_id' => $data['group_id'] ?? null,
                    'subgroup_id' => $data['subgroup_id'] ?? null,
                    'tax_rate' => $data['tax_rate'] ?? 0,
                    'purchase_price' => $data['purchase_price'] ?? 0
                ];
                
                // Crear producto
                $item_id = $this->Quick_item_model->create_quick_item($item_data);
                
                // Obtener datos completos
                $item = $this->Quick_item_model->get_item($item_id);
                
                echo json_encode([
                    'success' => true,
                    'message' => _l('item_created_successfully'),
                    'item' => $item
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Guardar configuración de columnas
     */
    public function save_columns_settings() {
        if ($this->input->post()) {
            $settings = $this->input->post();
            $this->Goods_receipt_model->update_user_settings(
                get_staff_user_id(),
                ['visible_columns' => json_encode($settings)]
            );
            
            echo json_encode(['success' => true]);
        }
    }

    /**
     * Buscar productos
     */
    public function search_items() {
        if ($this->input->get()) {
            $search = $this->input->get('q');
            $warehouse_id = $this->input->get('warehouse_id');
            
            $items = $this->Warehouse_model->search_items($search, $warehouse_id);
            
            // Agregar información adicional
            foreach ($items as &$item) {
                $item->stock = $this->Warehouse_model->get_item_stock($item->id, $warehouse_id);
                $item->last_purchase = $this->Warehouse_model->get_last_purchase_price($item->id);
            }
            
            echo json_encode($items);
        }
    }

    /**
     * Cargar datos comunes
     */
    private function load_common_data() {
        return [
            'warehouses' => $this->Warehouse_model->get_warehouses(),
            'suppliers' => $this->Warehouse_model->get_suppliers(),
            'units' => $this->Warehouse_model->get_units(),
            'item_groups' => $this->Warehouse_model->get_groups(),
            'taxes' => get_taxes(),
            'payment_methods' => get_payment_methods(),
            'currencies' => get_currencies(),
            'staff' => get_staff()
        ];
    }

    /**
     * Cargar configuración de recepción
     */
    private function load_receipt_settings() {
        $default_settings = [
            'visible_columns' => array_keys($this->get_default_columns()),
            'enable_lots' => true,
            'enable_expiry_dates' => true,
            'enable_manufacturing_dates' => true,
            'require_reference_doc' => true
        ];
        
        $user_settings = $this->Goods_receipt_model->get_user_settings(get_staff_user_id());
        
        return array_merge($default_settings, $user_settings);
    }

    /**
     * Obtener columnas predeterminadas
     */
    private function get_default_columns() {
        return [
            'article' => true,
            'warehouse' => true,
            'quantity' => true,
            'unit_price' => true,
            'tax' => true,
            'lot_number' => true,
            'manufacture_date' => true,
            'expiry_date' => true,
            'total' => true
        ];
    }

    /**
     * Validar datos de recepción
     */
    private function validate_receipt_data($data) {
        $required_fields = [
            'supplier_id' => _l('supplier_required'),
            'warehouse_id' => _l('warehouse_required'),
            'accounting_date' => _l('accounting_date_required'),
            'document_date' => _l('document_date_required'),
            'items' => _l('items_required')
        ];
        
        foreach ($required_fields as $field => $error) {
            if (empty($data[$field])) {
                throw new Exception($error);
            }
        }
        
        // Validar items
        $items = json_decode($data['items'], true);
        if (empty($items)) {
            throw new Exception(_l('items_required'));
        }
        
        foreach ($items as $item) {
            if (empty($item['item_id']) || empty($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception(_l('invalid_item_data'));
            }
        }
    }
}
