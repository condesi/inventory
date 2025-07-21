<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controlador para manejo de productos rápidos
 * Permite crear y gestionar productos de forma ágil durante procesos de compra/venta
 */
class Quick_items extends AdminController {
    use WarehousePermissionsTrait;
    
    private $required_permission = 'items';
    private $validation_rules = [
        'code' => [
            'required' => true,
            'min_length' => 3,
            'is_unique' => 'items.code'
        ],
        'name' => [
            'required' => true,
            'min_length' => 3
        ],
        'unit_id' => [
            'required' => true,
            'numeric' => true
        ],
        'rate' => [
            'required' => true,
            'numeric' => true,
            'greater_than' => 0
        ]
    ];
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Quick_item_model');
        $this->load->model('warehouse/Warehouse_model');
        
        // Cargar helpers necesarios
        $this->load->helper(['warehouse', 'warehouse_peru']);
    }
    
    /**
     * Verificar si existe código
     * @return json
     */
    public function check_code() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $code = $this->input->post('code');
        $item = $this->Quick_item_model->get_item_by_code($code);
        
        if ($item) {
            // Obtener información adicional
            $item->current_stock = $this->Warehouse_model->get_total_item_stock($item->id);
            $item->last_purchase = $this->Quick_item_model->get_last_purchase_info($item->id);
            $item->tax_rates = $this->Quick_item_model->get_item_tax_rates($item->id);
        }
        
        echo json_encode([
            'exists' => ($item !== null),
            'item' => $item
        ]);
    }
    
    /**
     * Crear producto rápido
     * @return json
     */
    public function create() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        try {
            $this->check_permission('create');
            
            $data = $this->input->post();
            
            // Validar datos
            $this->validate_item_data($data);
            
            // Procesar datos antes de guardar
            $data = $this->process_item_data($data);
            
            // Crear producto
            $item_id = $this->Quick_item_model->create_quick_item($data);
            
            // Procesar datos adicionales
            $this->process_additional_data($item_id, $data);
            
            // Obtener datos completos
            $item = $this->get_complete_item_data($item_id);
            
            // Registrar actividad
            log_activity('Nuevo producto rápido creado [ID: ' . $item_id . ']');
            
            $response = [
                'success' => true,
                'message' => _l('item_created_successfully'),
                'item' => $item
            ];

        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        echo json_encode($response);
    }
    
    /**
     * Actualizar producto rápido
     * @param int $id ID del producto
     * @return json
     */
    public function update($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        try {
            $this->check_permission('edit');
            
            $data = $this->input->post();
            
            // Validar datos
            $this->validate_item_data($data, $id);
            
            // Procesar datos antes de guardar
            $data = $this->process_item_data($data);
            
            // Actualizar producto
            $this->Quick_item_model->update_quick_item($id, $data);
            
            // Procesar datos adicionales
            $this->process_additional_data($id, $data);
            
            // Obtener datos actualizados
            $item = $this->get_complete_item_data($id);
            
            // Registrar actividad
            log_activity('Producto rápido actualizado [ID: ' . $id . ']');
            
            $response = [
                'success' => true,
                'message' => _l('item_updated_successfully'),
                'item' => $item
            ];

        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        echo json_encode($response);
    }
    
    /**
     * Eliminar producto
     * @param int $id ID del producto
     * @return json
     */
    public function delete($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        try {
            $this->check_permission('delete');
            
            // Verificar si se puede eliminar
            if (!$this->Quick_item_model->can_delete_item($id)) {
                throw new Exception(_l('item_cannot_be_deleted'));
            }
            
            // Eliminar producto
            $this->Quick_item_model->delete_quick_item($id);
            
            // Registrar actividad
            log_activity('Producto rápido eliminado [ID: ' . $id . ']');
            
            $response = [
                'success' => true,
                'message' => _l('item_deleted_successfully')
            ];

        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        echo json_encode($response);
    }
    
    /**
     * Validar datos del producto
     */
    private function validate_item_data($data, $id = null) {
        foreach ($this->validation_rules as $field => $rules) {
            if (!isset($data[$field]) && $rules['required']) {
                throw new Exception(sprintf(_l('field_is_required'), _l($field)));
            }
            
            if (isset($data[$field])) {
                foreach ($rules as $rule => $param) {
                    if (!$this->validate_field($data[$field], $rule, $param, $id)) {
                        throw new Exception(sprintf(_l('field_validation_error'), _l($field)));
                    }
                }
            }
        }
    }
    
    /**
     * Validar campo específico
     */
    private function validate_field($value, $rule, $param, $id = null) {
        switch ($rule) {
            case 'required':
                return !empty($value);
            
            case 'min_length':
                return strlen($value) >= $param;
            
            case 'numeric':
                return is_numeric($value);
            
            case 'greater_than':
                return floatval($value) > $param;
            
            case 'is_unique':
                if ($id) {
                    return $this->Quick_item_model->is_code_unique($value, $id);
                }
                return $this->Quick_item_model->is_code_unique($value);
            
            default:
                return true;
        }
    }
    
    /**
     * Procesar datos antes de guardar
     */
    private function process_item_data($data) {
        // Limpiar y formatear datos
        $data['code'] = trim($data['code']);
        $data['name'] = trim($data['name']);
        $data['description'] = isset($data['description']) ? trim($data['description']) : '';
        $data['rate'] = format_number($data['rate']);
        
        // Agregar datos automáticos
        $data['date_created'] = date('Y-m-d H:i:s');
        $data['created_by'] = get_staff_user_id();
        
        return $data;
    }
    
    /**
     * Procesar datos adicionales
     */
    private function process_additional_data($item_id, $data) {
        // Procesar impuestos
        if (isset($data['tax_rates']) && is_array($data['tax_rates'])) {
            $this->Quick_item_model->sync_item_taxes($item_id, $data['tax_rates']);
        }
        
        // Procesar categorías
        if (isset($data['categories']) && is_array($data['categories'])) {
            $this->Quick_item_model->sync_item_categories($item_id, $data['categories']);
        }
        
        // Procesar atributos personalizados
        if (isset($data['custom_fields'])) {
            handle_custom_fields_post($item_id, $data['custom_fields'], 'items');
        }
    }
    
    /**
     * Obtener datos completos del producto
     */
    private function get_complete_item_data($item_id) {
        $item = $this->Quick_item_model->get_item_by_id($item_id);
        
        if ($item) {
            $item->current_stock = $this->Warehouse_model->get_total_item_stock($item_id);
            $item->last_purchase = $this->Quick_item_model->get_last_purchase_info($item_id);
            $item->tax_rates = $this->Quick_item_model->get_item_tax_rates($item_id);
            $item->categories = $this->Quick_item_model->get_item_categories($item_id);
            $item->custom_fields = get_custom_fields_values('items', $item_id);
        }
        
        return $item;
    }
}
