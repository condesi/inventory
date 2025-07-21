<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Goods_delivery extends Admin_controller {
    use WarehousePermissionsTrait;

    private $settings;

    public function __construct() {
        parent::__construct();
        $this->load->model([
            'Goods_delivery_model',
            'Warehouse_model',
            'Taxes_model',
            'Currencies_model'
        ]);
        
        // Cargar configuraciones
        $this->settings = $this->load_delivery_settings();
    }

    /**
     * Lista de entregas
     */
    public function index() {
        $this->check_permission('view');
        
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('goods_delivery');
            return;
        }

        $data = [];
        $data['title'] = _l('goods_delivery');
        $data['delivery_statuses'] = $this->Goods_delivery_model->get_statuses();
        $data['warehouses'] = $this->Warehouse_model->get_warehouses();
        $data['settings'] = $this->settings;
        
        // Estadísticas
        $data['stats'] = [
            'total' => $this->Goods_delivery_model->count_all(),
            'pending' => $this->Goods_delivery_model->count_by_status('pending'),
            'approved' => $this->Goods_delivery_model->count_by_status('approved'),
            'completed' => $this->Goods_delivery_model->count_by_status('completed'),
            'cancelled' => $this->Goods_delivery_model->count_by_status('cancelled')
        ];
        
        $this->load->view('warehouse/delivery/manage', $data);
    }

    /**
     * Crear o editar entrega
     */
    public function delivery($id = '') {
        $this->check_permission('create');
        
        if ($this->input->post()) {
            if ($id == '') {
                $delivery_id = $this->Goods_delivery_model->add($this->input->post());
                if ($delivery_id) {
                    set_alert('success', _l('added_successfully'));
                    redirect(admin_url('warehouse/goods_delivery/delivery/' . $delivery_id));
                }
            } else {
                $success = $this->Goods_delivery_model->update($this->input->post(), $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully'));
                }
                redirect(admin_url('warehouse/goods_delivery/delivery/' . $id));
            }
            return;
        }

        if ($id == '') {
            $title = _l('add_goods_delivery');
            $data = $this->load_delivery_defaults();
        } else {
            $delivery = $this->Goods_delivery_model->get($id);
            if (!$delivery) {
                show_404();
            }
            
            $data = $this->load_delivery_data($delivery);
            $title = _l('edit_goods_delivery');
        }

        $data['title'] = $title;
        $data['bodyclass'] = 'delivery';
        
        $this->load->view('warehouse/delivery/delivery', $data);
    }

    /**
     * Ver entrega
     */
    public function view($id) {
        $this->check_permission('view');
        
        $delivery = $this->Goods_delivery_model->get($id);
        if (!$delivery) {
            show_404();
        }
        
        // Obtener historial
        $data = $this->load_delivery_data($delivery);
        $data['title'] = format_delivery_number($delivery->id);
        $data['history'] = $this->Goods_delivery_model->get_history($id);
        
        $this->load->view('warehouse/delivery/view', $data);
    }

    /**
     * Actualizar estado
     */
    public function update_status($id) {
        $this->check_permission('edit');
        
        if ($this->input->post()) {
            $success = $this->Goods_delivery_model->update_status(
                $id,
                $this->input->post('status'),
                $this->input->post('note')
            );
            
            if ($success) {
                set_alert('success', _l('status_updated_successfully'));
            }
        }
        
        redirect(admin_url('warehouse/goods_delivery/view/' . $id));
    }

    /**
     * Aprobar entrega
     */
    public function approve($id) {
        $this->check_permission('approve');
        
        $success = $this->Goods_delivery_model->approve($id);
        if ($success) {
            set_alert('success', _l('delivery_approved'));
        }
        
        redirect(admin_url('warehouse/goods_delivery/view/' . $id));
    }

    /**
     * Rechazar entrega
     */
    public function reject($id) {
        $this->check_permission('approve');
        
        if ($this->input->post()) {
            $success = $this->Goods_delivery_model->reject(
                $id,
                $this->input->post('reason')
            );
            
            if ($success) {
                set_alert('success', _l('delivery_rejected'));
            }
        }
        
        redirect(admin_url('warehouse/goods_delivery/view/' . $id));
    }

    /**
     * Imprimir entrega
     */
    public function pdf($id) {
        $this->check_permission('view');
        
        $delivery = $this->Goods_delivery_model->get($id);
        if (!$delivery) {
            show_404();
        }
        
        $pdf = delivery_pdf($delivery);
        $pdf->Output(format_delivery_number($delivery->id) . '.pdf', 'D');
    }

    /**
     * Enviar por email
     */
    public function send_to_email($id) {
        $this->check_permission('view');
        
        $success = $this->Goods_delivery_model->send_to_email($id);
        if ($success) {
            set_alert('success', _l('delivery_sent_to_email_successfully'));
        }
        
        redirect(admin_url('warehouse/goods_delivery/view/' . $id));
    }

    /**
     * Obtener items para select2
     */
    public function get_items() {
        if ($this->input->is_ajax_request()) {
            $search = $this->input->get('q');
            $warehouse_id = $this->input->get('warehouse_id');
            
            $items = $this->Warehouse_model->search_items_with_stock($search, $warehouse_id);
            
            $result = [];
            foreach ($items as $item) {
                $result[] = [
                    'id' => $item->id,
                    'text' => $item->code . ' - ' . $item->name,
                    'unit_name' => $item->unit_name,
                    'stock' => $item->stock,
                    'price' => $item->unit_price,
                    'tax_rate' => $item->taxrate
                ];
            }
            
            echo json_encode($result);
        }
    }

    /* Métodos privados */

    private function load_delivery_settings() {
        return [
            'require_approval' => get_option('enable_goods_delivery_approval'),
            'allow_negative_stock' => get_option('allow_negative_stock'),
            'track_serial_numbers' => get_option('enable_serial_number'),
            'enable_lot_number' => get_option('enable_lot_number'),
            'enable_expiry_date' => get_option('enable_expiry_date'),
            'notification_email' => get_option('delivery_notification_email')
        ];
    }

    private function load_delivery_defaults() {
        $last_number = $this->Goods_delivery_model->get_last_number();
        
        return [
            'settings' => $this->settings,
            'warehouses' => $this->Warehouse_model->get_warehouses(),
            'taxes' => $this->Taxes_model->get(),
            'currencies' => $this->Currencies_model->get(),
            'staff' => $this->staff_model->get(),
            'number' => $last_number + 1,
            'prefix' => get_option('goods_delivery_prefix'),
            'date' => _d(date('Y-m-d')),
            'items' => [],
            'delivery' => null
        ];
    }

    private function load_delivery_data($delivery) {
        $data = $this->load_delivery_defaults();
        $data['delivery'] = $delivery;
        $data['items'] = $this->Goods_delivery_model->get_items($delivery->id);
        
        // Cargar documentos relacionados
        if ($delivery->sale_id) {
            $this->load->model('Sales_model');
            $data['sale'] = $this->Sales_model->get($delivery->sale_id);
        }
        
        return $data;
    }
}
