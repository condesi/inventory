<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Goods_receipts extends AdminController {
    
    private $required_permission = 'warehouse';
    private $common_data = [];
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Goods_receipt_model');
        $this->load->model('Warehouse_model');
        
        // Cargar datos comunes
        $this->load_common_data();
    }
    
    /**
     * Verifica permisos requeridos
     */
    private function check_permission($action = 'view') {
        if (!has_permission($this->required_permission, '', $action)) {
            access_denied('goods_receipts');
        }
    }
    
    /**
     * Carga datos comunes usados en múltiples vistas
     */
    private function load_common_data() {
        $this->common_data = [
            'warehouses' => $this->Warehouse_model->get_warehouses(),
            'suppliers' => $this->Warehouse_model->get_suppliers(),
            'items_list' => $this->Warehouse_model->get_items(),
            'units' => $this->Warehouse_model->get_units(),
            'taxes' => $this->taxes_model->get()
        ];
    }
    
    /**
     * Lista de recibos
     */
    public function index() {
        $this->check_permission();
        
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('warehouse', 'goods_receipts/table'));
        }
        
        // Cargar datos específicos de la vista
        $data = array_merge($this->common_data, [
            'title' => _l('goods_receipts'),
        $data['suppliers'] = $this->Warehouse_model->get_suppliers();
        $data['statuses'] = [
            ['id' => 'draft', 'name' => _l('draft')],
            ['id' => 'pending', 'name' => _l('pending_approval')],
            ['id' => 'approved', 'name' => _l('approved')],
            ['id' => 'rejected', 'name' => _l('rejected')]
        ];
        
        $this->load->view('goods_receipts/manage', $data);
    }
    
    /**
     * Crear o editar recibo
     */
    public function receipt($id = '') {
        if (!has_permission('warehouse', '', 'create')) {
            access_denied('goods_receipts');
        }
        
        if ($this->input->post()) {
            try {
                $data = $this->input->post();
                
                // Validar datos
                $this->validate_receipt_data($data);
                
                if ($id == '') {
                    $id = $this->Goods_receipt_model->create_receipt($data);
                    if ($id) {
                        // Registrar actividad
                        log_activity('Nuevo recibo de mercancía creado [ID: ' . $id . ']');
                        
                        set_alert('success', _l('created_successfully'));
                        redirect(admin_url('warehouse/goods_receipts/receipt/' . $id));
                    }
                } else {
                    // Verificar permisos de edición
                    if (!has_permission('warehouse', '', 'edit')) {
                        access_denied('goods_receipts');
                    }
                    
                    $success = $this->Goods_receipt_model->update_receipt($id, $data);
                    if ($success) {
                        log_activity('Recibo de mercancía actualizado [ID: ' . $id . ']');
                        set_alert('success', _l('updated_successfully'));
                    }
                    redirect(admin_url('warehouse/goods_receipts/receipt/' . $id));
                }
            } catch (Exception $e) {
                set_alert('danger', $e->getMessage());
            }
        }
        
        // Cargar datos
        if ($id == '') {
            $title = _l('new_goods_receipt');
            $data['receipt'] = new stdClass();
            $data['receipt']->items = [];
            $data['receipt']->status = 'draft';
        } else {
            $data['receipt'] = $this->Goods_receipt_model->get_receipt($id);
            if (!$data['receipt']) {
                show_404();
            }
            $data['items'] = $this->Goods_receipt_model->get_receipt_items($id);
            $title = _l('edit_goods_receipt');
        }
        
        // Cargar datos adicionales
        $data['title'] = $title;
        $data['warehouses'] = $this->Warehouse_model->get_warehouses();
        $data['items_list'] = $this->Warehouse_model->get_items();
        $data['units'] = $this->Warehouse_model->get_units();
        $data['suppliers'] = $this->Warehouse_model->get_suppliers();
        $data['taxes'] = $this->taxes_model->get();
        
        // Cargar aprobadores
        $data['approvers'] = $this->Goods_receipt_model->get_receipt_approvers($id);
        
        // JavaScript
        $this->app_scripts->add('signature-pad-js', 'assets/plugins/signature-pad/signature_pad.min.js');
        $this->app_scripts->add('goods-receipt-js', 'modules/warehouse/assets/js/goods_receipts.js');
        
        $this->load->view('goods_receipts/receipt', $data);
    }
    
    /**
     * Validar datos del recibo
     */
    private function validate_receipt_data($data) {
        if (empty($data['warehouse_id'])) {
            throw new Exception(_l('warehouse_required'));
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception(_l('items_required'));
        }
        
        foreach ($data['items'] as $item) {
            if (empty($item['item_id']) || empty($item['quantity']) || $item['quantity'] <= 0) {
                throw new Exception(_l('invalid_item_data'));
            }
        }
    }
    
    /**
     * Obtener item por código
     */
    public function get_item_by_code() {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $code = $this->input->post('code');
        $warehouse_id = $this->input->post('warehouse_id');
        
        $item = $this->Warehouse_model->get_item_by_code($code);
        
        if ($item) {
            // Obtener stock actual
            $stock = $this->Warehouse_model->get_item_stock($item->id, $warehouse_id);
            $item->current_stock = $stock ? $stock->quantity : 0;
            
            // Obtener último precio
            $last_price = $this->Warehouse_model->get_item_last_price($item->id);
            $item->last_price = $last_price ?: 0;
            
            echo json_encode([
                'success' => true,
                'item' => $item
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('item_not_found')
            ]);
        }
    }
    
    /**
     * Aprobar recibo
     */
    public function approve($id) {
        if (!has_permission('warehouse', '', 'edit')) {
            access_denied('goods_receipts');
        }
        
        try {
            $success = $this->Goods_receipt_model->approve_receipt($id);
            
            if ($success) {
                // Notificar a los involucrados
                $receipt = $this->Goods_receipt_model->get_receipt($id);
                $this->notifications_model->add_notification([
                    'description' => sprintf(_l('goods_receipt_approved_notification'), $receipt->reference),
                    'touserid' => $receipt->created_by,
                    'link' => admin_url('warehouse/goods_receipts/receipt/' . $id)
                ]);
                
                set_alert('success', _l('goods_receipt_approved'));
            }
        } catch (Exception $e) {
            set_alert('danger', $e->getMessage());
        }
        
        redirect(admin_url('warehouse/goods_receipts/receipt/' . $id));
    }
    
    /**
     * Rechazar recibo
     */
    public function reject($id) {
        if (!has_permission('warehouse', '', 'edit')) {
            access_denied('goods_receipts');
        }
        
        $reason = $this->input->post('reason');
        
        try {
            $success = $this->Goods_receipt_model->reject_receipt($id, $reason);
            
            if ($success) {
                // Notificar al creador
                $receipt = $this->Goods_receipt_model->get_receipt($id);
                $this->notifications_model->add_notification([
                    'description' => sprintf(_l('goods_receipt_rejected_notification'), $receipt->reference),
                    'touserid' => $receipt->created_by,
                    'link' => admin_url('warehouse/goods_receipts/receipt/' . $id)
                ]);
                
                set_alert('success', _l('goods_receipt_rejected'));
            }
        } catch (Exception $e) {
            set_alert('danger', $e->getMessage());
        }
        
        redirect(admin_url('warehouse/goods_receipts/receipt/' . $id));
    }
    
    /**
     * Firmar recibo
     */
    public function sign($id) {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        if (!has_permission('warehouse', '', 'edit')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied')
            ]);
            die;
        }
        
        $signature = $this->input->post('signature');
        
        try {
            $success = $this->Goods_receipt_model->sign_receipt($id, $signature);
            
            echo json_encode([
                'success' => true,
                'message' => _l('goods_receipt_signed')
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generar PDF
     */
    public function pdf($id) {
        if (!$id) {
            redirect(admin_url('warehouse/goods_receipts'));
        }
        
        $receipt = $this->Goods_receipt_model->get_receipt($id);
        
        try {
            goods_receipt_pdf($receipt);
        } catch (Exception $e) {
            set_alert('danger', $e->getMessage());
            redirect(admin_url('warehouse/goods_receipts/receipt/' . $id));
        }
    }
}
