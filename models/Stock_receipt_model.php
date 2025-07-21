<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Stock_receipt_model extends CI_Model {
    private $table = 'goods_receipt';
    private $table_details = 'goods_receipt_detail';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Obtiene una lista de ingresos de stock
     */
    public function get_receipts($filters = []) {
        $this->db->select('gr.*, CONCAT(staff.firstname, " ", staff.lastname) as created_by_name')
                 ->from($this->db->dbprefix . $this->table . ' gr')
                 ->join($this->db->dbprefix . 'staff staff', 'staff.staffid = gr.addedfrom', 'left');

        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $this->db->where('gr.date_add >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('gr.date_add <=', $filters['date_to']);
        }
        if (!empty($filters['warehouse_id'])) {
            $this->db->where('gr.warehouse_id', $filters['warehouse_id']);
        }
        if (isset($filters['status'])) {
            $this->db->where('gr.approval', $filters['status']);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Obtiene un ingreso específico con sus detalles
     */
    public function get_receipt($id) {
        $receipt = $this->db->get_where($this->db->dbprefix . $this->table, ['id' => $id])->row_array();
        if ($receipt) {
            $receipt['items'] = $this->get_receipt_items($id);
        }
        return $receipt;
    }

    /**
     * Obtiene los items de un ingreso
     */
    private function get_receipt_items($receipt_id) {
        return $this->db->get_where($this->db->dbprefix . $this->table_details, 
            ['goods_receipt_id' => $receipt_id])->result_array();
    }

    /**
     * Crea un nuevo ingreso de stock
     */
    public function create_receipt($data) {
        // Validar datos requeridos
        $required = [
            'warehouse_id' => 'Almacén',
            'date_add' => 'Fecha', 
            'items' => 'Items'
        ];
        foreach ($required as $field => $label) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception(_l('required_field_missing') . ': ' . _l($label));
            }
        }

        // Validar items
        foreach ($data['items'] as $item) {
            $this->validate_item($item);
        }

        // Datos del encabezado
        $header = [
            'goods_receipt_code' => $this->generate_receipt_code(),
            'warehouse_id' => $data['warehouse_id'],
            'supplier_id' => $data['supplier_id'] ?? null,
            'date_add' => $data['date_add'],
            'addedfrom' => get_staff_user_id(),
            'description' => $data['description'] ?? '',
            'approval' => 0, // Pendiente de aprobación
            'total_money' => 0,
            'datecreated' => date('Y-m-d H:i:s')
        ];

        $this->db->trans_start();

        // Insertar encabezado
        $this->db->insert($this->db->dbprefix . $this->table, $header);
        $receipt_id = $this->db->insert_id();

        // Insertar detalles
        $total = 0;
        foreach ($data['items'] as $item) {
            $detail = [
                'goods_receipt_id' => $receipt_id,
                'commodity_code' => $item['commodity_code'],
                'commodity_name' => $item['commodity_name'],
                'unit_id' => $item['unit_id'],
                'quantities' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax' => $item['tax'] ?? 0,
                'tax_money' => ($item['quantity'] * $item['unit_price'] * ($item['tax'] ?? 0)) / 100,
                'goods_money' => $item['quantity'] * $item['unit_price'],
                'date_manufacture' => $item['date_manufacture'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'note' => $item['note'] ?? ''
            ];

            $this->db->insert($this->db->dbprefix . $this->table_details, $detail);
            $total += $detail['goods_money'] + $detail['tax_money'];
        }

        // Actualizar total
        $this->db->where('id', $receipt_id)
                 ->update($this->db->dbprefix . $this->table, ['total_money' => $total]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new Exception("Error al crear el ingreso de stock");
        }

        return $receipt_id;
    }

    /**
     * Aprueba un ingreso de stock
     */
    public function approve_receipt($receipt_id) {
        // Verificar permisos
        if (!has_permission('wh_stock_import', '', 'edit')) {
            throw new Exception(_l('access_denied'));
        }

        $receipt = $this->get_receipt($receipt_id);
        if (!$receipt) {
            throw new Exception(_l('receipt_not_found'));
        }

        if ($receipt['approval'] == 1) {
            throw new Exception(_l('receipt_already_approved'));
        }

        $this->db->trans_start();

        try {
            // Validar existencias antes de aprobar
            foreach ($receipt['items'] as $item) {
                $this->validate_stock_update($item);
            }

            // Actualizar estado
            $this->db->where('id', $receipt_id)
                     ->update($this->db->dbprefix . $this->table, [
                         'approval' => 1,
                         'date_approval' => date('Y-m-d H:i:s'),
                         'approval_by' => get_staff_user_id()
                     ]);

            // Actualizar stock
            foreach ($receipt['items'] as $item) {
                $this->update_stock($item);
                
                // Registrar movimiento en kardex
                $this->register_kardex_movement($receipt, $item);
            }

            // Log de actividad
            $this->log_activity($receipt_id, 'stock_receipt_approved');

            $this->db->trans_commit();
            return true;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Valida la actualización de stock
     */
    private function validate_stock_update($item) {
        // Validar existencia del producto
        $product = $this->db->where('commodity_code', $item['commodity_code'])
                           ->get(db_prefix() . 'items')
                           ->row();
        
        if (!$product) {
            throw new Exception(_l('product_not_found') . ': ' . $item['commodity_code']); 
        }

        // Validar cantidades
        if ($item['quantities'] <= 0) {
            throw new Exception(_l('invalid_quantity') . ': ' . $item['commodity_name']);
        }

        // Validar límites de stock (si aplica)
        $max_stock = $this->get_max_stock_limit($product->id, $item['warehouse_id']);
        if ($max_stock > 0) {
            $current_stock = $this->get_current_stock($product->id, $item['warehouse_id']);
            if (($current_stock + $item['quantities']) > $max_stock) {
                throw new Exception(sprintf(
                    _l('stock_exceeds_limit'),
                    $item['commodity_name'],
                    $max_stock
                ));
            }
        }
    }

    /**
     * Registra movimiento en kardex
     */
    private function register_kardex_movement($receipt, $item) {
        $this->db->insert($this->db->dbprefix . 'inventory_movements', [
            'date' => date('Y-m-d H:i:s'),
            'type' => 'in',
            'document_type' => 'receipt',
            'document_id' => $receipt['id'],
            'document_number' => $receipt['goods_receipt_code'],
            'warehouse_id' => $receipt['warehouse_id'],
            'item_id' => $item['commodity_id'],
            'quantity' => $item['quantities'],
            'unit_price' => $item['unit_price'],
            'total' => $item['goods_money'],
            'created_by' => get_staff_user_id(),
            'description' => sprintf(
                _l('stock_receipt_movement'),
                $receipt['goods_receipt_code']
            )
        ]);
    }

    /**
     * Valida los datos de un item
     */
    private function validate_item($item) {
        // Campos requeridos
        $required = [
            'commodity_code' => 'Código',
            'commodity_name' => 'Nombre',
            'quantities' => 'Cantidad',
            'unit_id' => 'Unidad',
            'unit_price' => 'Precio unitario'
        ];

        foreach ($required as $field => $label) {
            if (!isset($item[$field]) || $item[$field] === '') {
                throw new Exception(_l('required_field_missing') . ': ' . _l($label));
            }
        }

        // Validar valores numéricos
        if (!is_numeric($item['quantities']) || $item['quantities'] <= 0) {
            throw new Exception(_l('invalid_quantity'));
        }

        if (!is_numeric($item['unit_price']) || $item['unit_price'] < 0) {
            throw new Exception(_l('invalid_price'));
        }

        // Validar existencia del producto
        $exists = $this->db->where('commodity_code', $item['commodity_code'])
                          ->get(db_prefix() . 'items')
                          ->num_rows();
        if ($exists == 0) {
            throw new Exception(_l('product_not_found') . ': ' . $item['commodity_code']);
        }
    }

    /**
     * Registra un movimiento de inventario
     */
    private function register_movement($item) {
        $this->db->insert($this->db->dbprefix . 'goods_transaction_detail', [
            'goods_receipt_id' => $item['goods_receipt_id'],
            'goods_id' => $item['commodity_id'],
            'quantity' => $item['quantities'],
            'date_add' => date('Y-m-d H:i:s'),
            'status' => 1, // 1: Ingreso
            'warehouse_id' => $item['warehouse_id'],
            'note' => $item['note'] ?? ''
        ]);
    }

    /**
     * Genera un código único para el ingreso
     */
    private function generate_receipt_code() {
        // Obtener formato configurado o usar default
        $format = get_option('stock_receipt_number_format') ?: 'ING-{YEAR}{MONTH}-{NUM}';
        $prefix = get_option('stock_receipt_number_prefix') ?: 'ING';
        
        // Reemplazar variables
        $code = str_replace([
            '{PREFIX}',
            '{YEAR}',
            '{MONTH}',
            '{DAY}'
        ], [
            $prefix,
            date('Y'),
            date('m'),
            date('d')
        ], $format);
        
        // Obtener último número del mes actual
        $pattern = str_replace(
            ['{PREFIX}', '{YEAR}', '{MONTH}', '{DAY}', '{NUM}'],
            [$prefix, date('Y'), date('m'), date('d'), '[0-9]+'],
            $format
        );
        
        $last = $this->db->select('goods_receipt_code')
                        ->where("goods_receipt_code REGEXP", $pattern)
                        ->order_by('id', 'desc')
                        ->limit(1)
                        ->get($this->db->dbprefix . $this->table)
                        ->row();

        if ($last) {
            // Extraer número de la última secuencia
            preg_match('/'.$pattern.'/', $last->goods_receipt_code, $matches);
            $last_number = isset($matches[1]) ? intval($matches[1]) : 0;
            $number = $last_number + 1;
        } else {
            $number = 1;
        }

        // Reemplazar número manteniendo el padding configurado
        $padding = get_option('stock_receipt_number_padding') ?: 4;
        return str_replace('{NUM}', str_pad($number, $padding, '0', STR_PAD_LEFT), $code);
    }

    /**
     * Verifica si un código ya existe
     */
    private function check_duplicate_code($code) {
        return $this->db->where('goods_receipt_code', $code)
                       ->get($this->db->dbprefix . $this->table)
                       ->num_rows() > 0;
    }
}
