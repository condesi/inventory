<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_movements extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Inventory_movement_model');
        $this->load->model('Inventory_issue_model');
        $this->load->model('Stock_receipt_model');
    }

    /**
     * Vista principal de movimientos
     */
    public function index() {
        // Verificar permisos
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('inventory_movements');
        }

        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data(module_views_path('warehouse', 'movements/table'));
        }

        // Cargar datos necesarios
        $data['title'] = _l('inventory_movements');
        $data['warehouses'] = $this->warehouse_model->get_warehouses();
        $data['items'] = $this->warehouse_model->get_all_items();

        $this->load->view('movements/manage', $data);
    }

    /**
     * Vista de kardex de producto
     */
    public function kardex($item_id = null) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('inventory_movements');
        }

        if ($this->input->is_ajax_request()) {
            $filters = [
                'item_id' => $this->input->post('item_id'),
                'warehouse_id' => $this->input->post('warehouse_id'),
                'date_from' => $this->input->post('date_from'),
                'date_to' => $this->input->post('date_to')
            ];

            $movements = $this->Inventory_movement_model->get_movements($filters);
            
            // Calcular saldos
            $balance = 0;
            foreach ($movements as &$movement) {
                if (in_array($movement->type, ['receipt', 'adjustment_in', 'return_in'])) {
                    $balance += $movement->quantity;
                } else {
                    $balance -= abs($movement->quantity);
                }
                $movement->balance = $balance;
            }

            echo json_encode([
                'data' => $movements
            ]);
            die;
        }

        $data['title'] = _l('inventory_kardex');
        $data['warehouses'] = $this->warehouse_model->get_warehouses();
        $data['items'] = $this->warehouse_model->get_all_items();
        $data['selected_item'] = $item_id;

        $this->load->view('movements/kardex', $data);
    }

    /**
     * Vista de stock actual
     */
    public function stock_status() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('inventory_movements');
        }

        if ($this->input->is_ajax_request()) {
            // Construir query
            $this->db->select([
                'items.id',
                'items.code',
                'items.name',
                'items.unit_id',
                'stock.warehouse_id',
                'w.name as warehouse_name',
                'stock.quantity',
                'items.min_stock',
                'items.max_stock'
            ]);
            
            $this->db->from(db_prefix() . 'inventory_items items');
            $this->db->join(db_prefix() . 'inventory_stock stock', 'stock.item_id = items.id', 'left');
            $this->db->join(db_prefix() . 'warehouses w', 'w.id = stock.warehouse_id', 'left');

            // Aplicar filtros
            if ($this->input->post('warehouse_id')) {
                $this->db->where('stock.warehouse_id', $this->input->post('warehouse_id'));
            }

            if ($this->input->post('status')) {
                switch($this->input->post('status')) {
                    case 'out_of_stock':
                        $this->db->where('stock.quantity <= 0');
                        break;
                    case 'low_stock':
                        $this->db->where('stock.quantity <= items.min_stock');
                        $this->db->where('stock.quantity > 0');
                        break;
                    case 'over_stock':
                        $this->db->where('stock.quantity >= items.max_stock');
                        break;
                }
            }

            $stock = $this->db->get()->result();

            echo json_encode([
                'data' => $stock
            ]);
            die;
        }

        $data['title'] = _l('stock_status');
        $data['warehouses'] = $this->warehouse_model->get_warehouses();

        $this->load->view('movements/stock_status', $data);
    }

    /**
     * Exportar movimientos a Excel
     */
    public function export_movements() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('inventory_movements');
        }

        // Obtener filtros
        $filters = [
            'warehouse_id' => $this->input->get('warehouse_id'),
            'item_id' => $this->input->get('item_id'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];

        // Obtener movimientos
        $movements = $this->Inventory_movement_model->get_movements($filters);

        // Crear Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $headers = [
            _l('date'),
            _l('type'),
            _l('reference'),
            _l('item_code'),
            _l('item_name'),
            _l('warehouse'),
            _l('quantity'),
            _l('unit_cost'),
            _l('total_cost'),
            _l('created_by')
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Datos
        $row = 2;
        foreach ($movements as $movement) {
            $sheet->setCellValue('A' . $row, _dt($movement->date));
            $sheet->setCellValue('B' . $row, _l($movement->type));
            $sheet->setCellValue('C' . $row, $movement->reference);
            $sheet->setCellValue('D' . $row, $movement->item_code);
            $sheet->setCellValue('E' . $row, $movement->item_name);
            $sheet->setCellValue('F' . $row, $movement->warehouse_name);
            $sheet->setCellValue('G' . $row, $movement->quantity);
            $sheet->setCellValue('H' . $row, $movement->unit_cost);
            $sheet->setCellValue('I' . $row, $movement->total_cost);
            $sheet->setCellValue('J' . $row, $movement->created_by_name);
            $row++;
        }

        // Formato
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach(range('A','J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Descargar
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="inventory_movements.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }
}
