<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Inventory_issue_model extends Inventory_movement_model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Crear salida de inventario
     */
    public function create_issue($data) {
        // Validar datos requeridos
        $required = ['warehouse_id', 'items', 'date_issue'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception(_l('required_field_' . $field));
            }
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new Exception(_l('items_required'));
        }

        // Iniciar transacción
        $this->db->trans_start();

        try {
            // Crear encabezado
            $issue = [
                'warehouse_id' => $data['warehouse_id'],
                'date_issue' => to_sql_date($data['date_issue']),
                'reference' => $this->generate_reference(),
                'department_id' => $data['department_id'] ?? null,
                'employee_id' => $data['employee_id'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
                'created_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert(db_prefix() . 'inventory_issues', $issue);
            $issue_id = $this->db->insert_id();

            // Procesar items
            foreach ($data['items'] as $item) {
                // Validar item
                if (empty($item['item_id']) || empty($item['quantity']) || $item['quantity'] <= 0) {
                    throw new Exception(_l('invalid_item_data'));
                }

                // Verificar stock
                if (!$this->check_stock_availability($item['item_id'], $data['warehouse_id'], $item['quantity'])) {
                    $item_info = $this->get_item($item['item_id']);
                    throw new Exception(sprintf(
                        _l('insufficient_stock_for_item'),
                        $item_info->name
                    ));
                }

                // Registrar item
                $issue_item = [
                    'issue_id' => $issue_id,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id']
                ];

                $this->db->insert(db_prefix() . 'inventory_issue_items', $issue_item);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception(_l('error_creating_issue'));
            }

            return $issue_id;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Aprobar salida
     */
    public function approve_issue($id) {
        // Obtener salida
        $issue = $this->get_issue($id);
        if (!$issue) {
            throw new Exception(_l('issue_not_found'));
        }

        if ($issue->status !== 'draft') {
            throw new Exception(_l('invalid_issue_status'));
        }

        // Iniciar transacción
        $this->db->trans_start();

        try {
            // Obtener items
            $items = $this->get_issue_items($id);

            // Procesar cada item
            foreach ($items as $item) {
                // Registrar movimiento
                $movement_data = [
                    'type' => 'issue',
                    'reference' => $issue->reference,
                    'item_id' => $item->item_id,
                    'warehouse_id' => $issue->warehouse_id,
                    'quantity' => $item->quantity,
                    'notes' => $issue->notes
                ];

                $this->register_movement($movement_data);
            }

            // Actualizar estado
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'inventory_issues', [
                'status' => 'approved',
                'approved_by' => get_staff_user_id(),
                'approved_at' => date('Y-m-d H:i:s')
            ]);

            // Notificar
            if ($issue->department_id) {
                $this->notify_department($issue);
            }

            if ($issue->employee_id) {
                $this->notify_employee($issue);
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception(_l('error_approving_issue'));
            }

            return true;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            throw $e;
        }
    }

    /**
     * Generar referencia única
     */
    private function generate_reference() {
        $prefix = 'OUT';
        $year = date('Y');
        $month = date('m');
        
        $this->db->where('YEAR(date_issue)', $year);
        $this->db->where('MONTH(date_issue)', $month);
        $count = $this->db->count_all_results(db_prefix() . 'inventory_issues') + 1;
        
        return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Notificar al departamento
     */
    private function notify_department($issue) {
        $department = $this->departments_model->get($issue->department_id);
        if (!$department) return;

        $this->notifications_model->add_notification([
            'description' => sprintf(
                _l('inventory_issue_approved_department'),
                $issue->reference
            ),
            'touserid' => $department->manager_id,
            'link' => admin_url('warehouse/inventory_issues/issue/' . $issue->id)
        ]);
    }

    /**
     * Notificar al empleado
     */
    private function notify_employee($issue) {
        $this->notifications_model->add_notification([
            'description' => sprintf(
                _l('inventory_issue_approved_employee'),
                $issue->reference
            ),
            'touserid' => $issue->employee_id,
            'link' => admin_url('warehouse/inventory_issues/issue/' . $issue->id)
        ]);
    }

    /**
     * Obtener salida
     */
    public function get_issue($id) {
        return $this->db->get_where(db_prefix() . 'inventory_issues', ['id' => $id])->row();
    }

    /**
     * Obtener items de salida
     */
    public function get_issue_items($issue_id) {
        return $this->db->where('issue_id', $issue_id)
                       ->get(db_prefix() . 'inventory_issue_items')
                       ->result();
    }
}
