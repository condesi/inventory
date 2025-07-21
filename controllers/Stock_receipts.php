<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stock_receipts extends AdminController {
    public function __construct() {
        parent::__construct();
        $this->load->model('Stock_receipt_model');
        
        // Verificar permisos
        if (!has_permission('wh_stock_import', '', 'view')) {
            access_denied('stock_receipts');
        }
    }

    /**
     * Lista de ingresos de stock
     */
    public function index() {
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('warehouse', 'receipts/table'));
        }

        $data['title'] = _l('stock_receipts');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_name();
        $this->load->view('receipts/manage', $data);
    }

    /**
     * Crear o editar ingreso
     */
    public function receipt($id = '') {
        if ($this->input->post()) {
            if ($id == '') {
                if (!has_permission('wh_stock_import', '', 'create')) {
                    access_denied('stock_receipts');
                }
                $id = $this->Stock_receipt_model->create_receipt($this->input->post());
                if ($id) {
                    set_alert('success', _l('created_successfully', _l('stock_receipt')));
                    redirect(admin_url('warehouse/stock_receipts/receipt/' . $id));
                }
            } else {
                if (!has_permission('wh_stock_import', '', 'edit')) {
                    access_denied('stock_receipts');
                }
                // TODO: Implementar actualización
            }
        }

        if ($id == '') {
            $title = _l('create_new_stock_receipt');
        } else {
            $data['receipt'] = $this->Stock_receipt_model->get_receipt($id);
            $title = _l('edit_stock_receipt');
        }

        $data['title'] = $title;
        $data['warehouses'] = $this->warehouse_model->get_warehouse_name();
        $data['items'] = $this->warehouse_model->get_commodity_name();
        $data['units'] = $this->warehouse_model->get_units();
        $data['taxes'] = get_taxes();

        $this->load->view('receipts/receipt', $data);
    }

    /**
     * Obtener información del producto
     */
    public function get_item_info() {
        if ($this->input->is_ajax_request()) {
            $item_id = $this->input->post('item_id');
            $warehouse_id = $this->input->post('warehouse_id');

            $item = $this->warehouse_model->get_commodity($item_id);
            if ($item) {
                $item->current_stock = $this->warehouse_model->get_commodity_warehouse_quantity($item_id, $warehouse_id);
            }

            echo json_encode($item);
        }
    }

    /**
     * Aprobar ingreso
     */
    public function approve($id) {
        if (!has_permission('wh_stock_import', '', 'edit')) {
            access_denied('stock_receipts');
        }

        try {
            $success = $this->Stock_receipt_model->approve_receipt($id);
            if ($success) {
                set_alert('success', _l('stock_receipt_approved'));
            }
        } catch (Exception $e) {
            set_alert('danger', $e->getMessage());
        }

        redirect(admin_url('warehouse/stock_receipts/receipt/' . $id));
    }

    /**
     * Previsualizar PDF
     */
    public function pdf($id) {
        if (!$id) {
            redirect(admin_url('warehouse/stock_receipts'));
        }

        $receipt = $this->Stock_receipt_model->get_receipt($id);
        try {
            $pdf = stock_receipt_pdf($receipt);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_error('Image failed to load: ' . $message);
            }
        }
    }

    /**
     * Vista de importación
     */
    public function import() {
        $data['title'] = _l('import_stock');
        $data['warehouses'] = $this->warehouse_model->get_warehouse_name();
        $this->load->view('receipts/import', $data);
    }

    /**
     * Import items from excel
     */
    public function import_excel() {
        if (!has_permission('warehouse', '', 'create')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            try {
                $warehouse_id = $this->input->post('warehouse_id');

                if (!isset($_FILES['file_csv']['name']) || empty($_FILES['file_csv']['name'])) {
                    throw new Exception(_l('no_file_selected'));
                }

                // Validar tipo de archivo
                $file_ext = strtolower(pathinfo($_FILES['file_csv']['name'], PATHINFO_EXTENSION));
                if ($file_ext != 'xlsx') {
                    throw new Exception(_l('invalid_file_type'));
                }

                if ($this->input->post('item_import')) {
                    // Importar items
                    do_action('before_import_items');
                    
                    $this->load->library('import_items');
                    $total_row_success = $this->import_items->import_items($warehouse_id, 1);
                    
                    do_action('after_import_items');
                    
                    $this->handle_import_result(0, $total_row_success);

                } elseif ($this->input->post('opening_stock_import')) {
                    // Importar stock inicial
                    do_action('before_import_opening_stock');
                    
                    $this->load->library('import_items');
                    $result = $this->import_items->import_opening_stock($warehouse_id);
                    
                    do_action('after_import_opening_stock');
                    
                    $this->handle_import_result($result['false_rows'], $result['total_rows']);

                } elseif ($this->input->post('receipt_import')) {
                    // Importar ingresos
                    do_action('before_import_receipts');
                    
                    $this->load->library('import_receipts');
                    $importer = new Import_receipts();
                    
                    $result = $importer->import($_FILES['file_csv']['tmp_name'], $warehouse_id);
                    
                    do_action('after_import_receipts');

                    // Mostrar resultado
                    $message = sprintf(
                        _l('import_result'),
                        $result['total'],
                        $result['successful'],
                        $result['errors']
                    );

                    if ($result['errors'] > 0) {
                        $message .= ' ' . sprintf(
                            _l('import_error_details'),
                            admin_url('warehouse/download_error_file/' . $result['error_file'])
                        );
                        set_alert('warning', $message);
                    } else {
                        set_alert('success', $message);
                    }
                }

            } catch (Exception $e) {
                set_alert('danger', $e->getMessage());
            }

            redirect(admin_url('warehouse/stock_receipts/import'));
        }
    }

    /**
     * Manejar resultado de importación
     */
    private function handle_import_result($errors, $success) {
        if ($errors > 0) {
            set_alert('warning', sprintf(_l('import_error_count'), $errors));
        }

        if ($success > 0) {
            set_alert('success', sprintf(_l('import_success_count'), $success));
        } else {
            set_alert('warning', _l('import_no_data'));
        }
    }

    /**
     * Descargar archivo de errores
     */
    public function download_error_file($filename) {
        $path = FCPATH . 'uploads/stock_receipts/' . $filename;
        
        if (!file_exists($path)) {
            set_alert('danger', _l('error_file_not_found'));
            redirect(admin_url('warehouse/stock_receipts/import'));
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        readfile($path);
        unlink($path); // Eliminar archivo después de descarga
    }
