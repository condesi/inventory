<?php
defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    'reference',
    'date_add',
    'warehouse_id',
    'supplier_id',
    'COUNT(items.id) as total_items',
    'subtotal',
    'tax_total',
    'total',
    'status'
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'stock_receipts';

$join = [
    'LEFT JOIN ' . db_prefix() . 'stock_receipt_items items ON items.receipt_id = ' . $sTable . '.id',
    'LEFT JOIN ' . db_prefix() . 'warehouses w ON w.id = ' . $sTable . '.warehouse_id',
    'LEFT JOIN ' . db_prefix() . 'inventory_suppliers s ON s.id = ' . $sTable . '.supplier_id'
];

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    [],
    [
        $sTable . '.id',
        'w.name as warehouse_name',
        's.company as supplier_name',
        'created_by',
        'approved_by'
    ],
    'GROUP BY ' . $sTable . '.id'
);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    
    // Referencia con enlace
    $reference = '<a href="' . admin_url('warehouse/stock_receipts/receipt/' . $aRow['id']) . '">' . $aRow['reference'] . '</a>';
    
    // Agregar etiqueta de estado
    if ($aRow['status'] == 'draft') {
        $reference .= '<span class="label label-default mleft5">' . _l('draft') . '</span>';
    } elseif ($aRow['status'] == 'approved') {
        $reference .= '<span class="label label-success mleft5">' . _l('approved') . '</span>';
    } elseif ($aRow['status'] == 'void') {
        $reference .= '<span class="label label-danger mleft5">' . _l('void') . '</span>';
    }
    
    $row[] = $reference;
    
    // Fecha
    $row[] = _d($aRow['date_add']);
    
    // Almacén
    $row[] = $aRow['warehouse_name'];
    
    // Proveedor
    $row[] = $aRow['supplier_name'] ? $aRow['supplier_name'] : '-';
    
    // Total items
    $row[] = $aRow['total_items'];
    
    // Subtotal
    $row[] = app_format_money($aRow['subtotal'], get_base_currency());
    
    // Total impuestos
    $row[] = app_format_money($aRow['tax_total'], get_base_currency());
    
    // Total
    $row[] = app_format_money($aRow['total'], get_base_currency());
    
    // Estado
    $status_label = '';
    if ($aRow['status'] == 'draft') {
        $status_label = '<span class="label label-default">' . _l('draft') . '</span>';
    } elseif ($aRow['status'] == 'approved') {
        $status_label = '<span class="label label-success">' . _l('approved') . '</span>';
    } elseif ($aRow['status'] == 'void') {
        $status_label = '<span class="label label-danger">' . _l('void') . '</span>';
    }
    $row[] = $status_label;
    
    // Opciones
    $options = '';
    
    // Ver
    $options .= '<a href="' . admin_url('warehouse/stock_receipts/receipt/' . $aRow['id']) . '" class="btn btn-default btn-icon" title="' . _l('view') . '"><i class="fa fa-eye"></i></a>';
    
    // Aprobar (si está en borrador y tiene permisos)
    if ($aRow['status'] == 'draft' && has_permission('warehouse', '', 'edit')) {
        $options .= ' <a href="' . admin_url('warehouse/stock_receipts/approve/' . $aRow['id']) . '" class="btn btn-success btn-icon" title="' . _l('approve') . '"><i class="fa fa-check"></i></a>';
    }
    
    // Anular (si está en borrador y tiene permisos)
    if ($aRow['status'] == 'draft' && has_permission('warehouse', '', 'delete')) {
        $options .= ' <a href="' . admin_url('warehouse/stock_receipts/void/' . $aRow['id']) . '" class="btn btn-danger btn-icon" onclick="return confirm(\'' . _l('confirm_void_receipt') . '\');" title="' . _l('void') . '"><i class="fa fa-times"></i></a>';
    }
    
    // PDF
    $options .= ' <a href="' . admin_url('warehouse/stock_receipts/pdf/' . $aRow['id']) . '" target="_blank" class="btn btn-default btn-icon" title="' . _l('view_pdf') . '"><i class="fa fa-file-pdf-o"></i></a>';
    
    $row[] = $options;
    
    $output['aaData'][] = $row;
}

// Aplicar filtros
if (isset($where)) {
    if ($this->input->post('warehouse_filter')) {
        $where .= " AND warehouse_id IN (" . implode(',', $this->input->post('warehouse_filter')) . ")";
    }
    
    if ($this->input->post('supplier_filter')) {
        $where .= " AND supplier_id IN (" . implode(',', $this->input->post('supplier_filter')) . ")";
    }
    
    if ($this->input->post('date_from')) {
        $where .= " AND date_add >= '" . to_sql_date($this->input->post('date_from')) . "'";
    }
    
    if ($this->input->post('date_to')) {
        $where .= " AND date_add <= '" . to_sql_date($this->input->post('date_to')) . "'";
    }
}
