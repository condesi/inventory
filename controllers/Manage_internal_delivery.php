<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Manage_internal_delivery extends Admin_controller {
    use WarehousePermissionsTrait;

    private $settings;

    public function __construct() {
        parent::__construct();
        $this->load->model(['Internal_delivery_model', 'Warehouse_model']);
        $this->load->helper(['warehouse', 'warehouse_peru']);
        
        // Cargar configuraciones
        $this->settings = $this->load_settings();
    }

    /**
     * Vista principal
     */
    public function index() {
        $this->check_permission('view');
        
        $data = $this->load_common_data();
        $data['title'] = _l('internal_delivery');
        $data['settings'] = $this->settings;
        
        // Generar número de entrega automático
        $data['delivery_number'] = 'INTERNAL_DELIVERY' . date('YmdHis');
        
        // Configuración de la vista
        $data['columns'] = [
            'article' => [
                'label' => _l('article'),
                'required' => true
            ],
            'available_qty' => [
                'label' => _l('available_quantity'),
                'required' => false
            ],
            'action_name' => [
                'label' => _l('action_name'),
                'required' => true
            ],
            'quantity' => [
                'label' => _l('quantity'),
                'required' => true
            ],
            'price' => [
                'label' => _l('unit_price'),
                'required' => false,
                'visible' => false
            ]
        ];

        $this->load->view('warehouse/internal_delivery/manage', $data);
    }

    /**
     * Guardar entrega interna
     */
    public function save() {
        $this->check_permission('create');
        
        if ($this->input->post()) {
            try {
                $data = $this->input->post();
                
                // Validar datos
                $this->validate_delivery_data($data);
                
                // Procesar items
                $items = json_decode($data['items'], true);
                unset($data['items']);
                
                // Formatear fechas
                $data['date'] = to_sql_date($data['date']);
                
                // Iniciar transacción
                $this->db->trans_start();
                
                // Guardar cabecera
                $delivery_id = $this->Internal_delivery_model->add($data);
                
                // Procesar items
                foreach ($items as $item) {
                    $item['delivery_id'] = $delivery_id;
                    $this->Internal_delivery_model->add_item($item);
                    
                    // Actualizar stock
                    $this->Warehouse_model->reduce_stock(
                        $item['warehouse_id'], 
                        $item['item_id'], 
                        $item['quantity'],
                        $data['action_type']
                    );
                }
                
                $this->db->trans_complete();
                
                if ($this->db->trans_status() === FALSE) {
                    throw new Exception(_l('error_saving_delivery'));
                }
                
                // Registrar actividad
                log_activity('Nueva entrega interna creada [ID: '.$delivery_id.']');
                
                echo json_encode([
                    'success' => true,
                    'message' => _l('delivery_saved'),
                    'delivery_id' => $delivery_id
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
     * Obtener stock disponible
     */
    public function get_available_stock() {
        if ($this->input->get()) {
            $item_id = $this->input->get('item_id');
            $warehouse_id = $this->input->get('warehouse_id');
            
            $stock = $this->Warehouse_model->get_item_stock($item_id, $warehouse_id);
            
            echo json_encode([
                'success' => true,
                'stock' => $stock ? $stock->quantity : 0
            ]);
        }
    }

    /**
     * Buscar productos
     */
    public function search_items() {
        if ($this->input->get()) {
            $search = $this->input->get('q');
            $warehouse_id = $this->input->get('warehouse_id');
            
            $items = $this->Warehouse_model->search_items_with_stock($search, $warehouse_id);
            
            echo json_encode($items);
        }
    }

    /**
     * Cargar datos comunes
     */
    private function load_common_data() {
        return [
            'warehouses' => $this->Warehouse_model->get_warehouses(),
            'staff' => get_staff(),
            'departments' => $this->Warehouse_model->get_departments(),
            'action_types' => [
                'consumption' => _l('consumption'),
                'transfer' => _l('transfer'),
                'damage' => _l('damage'),
                'return' => _l('return')
            ]
        ];
    }

    /**
     * Cargar configuraciones
     */
    private function load_settings() {
        return [
            'require_approval' => false,
            'allow_negative_stock' => false,
            'track_serial_numbers' => false,
            'require_notes' => true
        ];
    }

    /**
     * Validar datos de entrega
     */
    private function validate_delivery_data($data) {
        // Validar campos requeridos
        $required_fields = [
            'warehouse_id' => _l('warehouse_required'),
            'date' => _l('date_required'),
            'action_type' => _l('action_type_required'),
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
            // Validar datos básicos
            if (empty($item['item_id']) || empty($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception(_l('invalid_item_data'));
            }
            
            // Validar stock disponible si está configurado
            if (!$this->settings['allow_negative_stock']) {
                $available = $this->Warehouse_model->get_item_stock($item['item_id'], $item['warehouse_id']);
                if ($available < $item['quantity']) {
                    throw new Exception(sprintf(_l('insufficient_stock'), $item['name']));
                }
            }
        }
    }
}
