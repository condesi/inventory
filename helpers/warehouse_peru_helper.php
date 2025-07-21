<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Funciones específicas para Perú
 */

/**
 * Genera número de serie para guía de remisión
 */
function generate_guia_remision_number($establecimiento) {
    $CI = &get_instance();
    $prefix = str_pad($establecimiento, 3, '0', STR_PAD_LEFT);
    
    $CI->db->select_max('numero_correlativo');
    $CI->db->where('establecimiento', $establecimiento);
    $result = $CI->db->get('tbl_guias_remision')->row();
    
    $correlativo = ($result->numero_correlativo ?? 0) + 1;
    $correlativo = str_pad($correlativo, 8, '0', STR_PAD_LEFT);
    
    return $prefix . '-' . $correlativo;
}

/**
 * Valida documento de identidad peruano
 */
function validate_document_number($type, $number) {
    switch($type) {
        case 'DNI':
            return strlen($number) === 8 && is_numeric($number);
        case 'RUC':
            if(strlen($number) !== 11 || !is_numeric($number)) {
                return false;
            }
            // Validación específica RUC peruano
            $sum = 0;
            $hashString = "5432765432";
            for($i = 0; $i < 10; $i++) {
                $sum += intval($number[$i]) * intval($hashString[$i]);
            }
            $diff = 11 - ($sum % 11);
            if($diff === 10) { $diff = 0; }
            if($diff === 11) { $diff = 1; }
            return $number[10] == $diff;
        default:
            return false;
    }
}

/**
 * Genera XML para facturación electrónica SUNAT
 */
function generate_sunat_xml($invoice_data) {
    $xml = new DOMDocument('1.0', 'UTF-8');
    
    // Estructura básica según UBL 2.1
    $root = $xml->createElement('Invoice');
    $root->setAttribute('xmlns', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
    $root->setAttribute('xmlns:cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
    $root->setAttribute('xmlns:cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
    
    // Agregar datos específicos de SUNAT
    $root->appendChild($xml->createElement('cbc:UBLVersionID', '2.1'));
    $root->appendChild($xml->createElement('cbc:CustomizationID', '2.0'));
    
    // ... más elementos según especificación SUNAT
    
    return $xml->saveXML();
}

/**
 * Calcula kardex valorizado según método PEPS (FIFO)
 */
function calculate_kardex_fifo($item_id, $start_date, $end_date) {
    $CI = &get_instance();
    
    // Obtener saldo inicial
    $initial_balance = $CI->warehouse_model->get_item_balance_at_date($item_id, $start_date);
    
    // Obtener movimientos en el periodo
    $movements = $CI->warehouse_model->get_item_movements($item_id, $start_date, $end_date);
    
    $kardex = [];
    $current_balance = $initial_balance;
    
    foreach($movements as $movement) {
        $entry = [
            'fecha' => $movement->date,
            'documento' => $movement->document_number,
            'tipo_operacion' => $movement->operation_type,
            'ingreso_cantidad' => $movement->type == 'in' ? $movement->quantity : 0,
            'ingreso_valor_unitario' => $movement->type == 'in' ? $movement->unit_value : 0,
            'salida_cantidad' => $movement->type == 'out' ? $movement->quantity : 0,
            'salida_valor_unitario' => $movement->type == 'out' ? $movement->unit_value : 0
        ];
        
        // Calcular saldos
        if($movement->type == 'in') {
            $current_balance += $movement->quantity;
        } else {
            $current_balance -= $movement->quantity;
        }
        
        $entry['saldo_cantidad'] = $current_balance;
        $entry['saldo_valor'] = $current_balance * $movement->unit_value;
        
        $kardex[] = $entry;
    }
    
    return $kardex;
}

/**
 * Genera reporte para SUNAT
 */
function generate_sunat_report($type, $period) {
    $CI = &get_instance();
    
    switch($type) {
        case 'LE':
            // Libro Electrónico de Inventario
            return generate_inventory_electronic_book($period);
            
        case 'RK':
            // Registro de Kardex
            return generate_kardex_report($period);
            
        case 'RCI':
            // Registro de Costos de Inventario
            return generate_inventory_cost_report($period);
    }
}

/**
 * Valida stock mínimo y genera alertas según normativa peruana
 */
function check_minimum_stock_pe($item_id) {
    $CI = &get_instance();
    
    $item = $CI->warehouse_model->get_item($item_id);
    $current_stock = $CI->warehouse_model->get_current_stock($item_id);
    
    if($current_stock <= $item->minimum_stock) {
        // Registrar en libro de ocurrencias
        log_activity('Stock mínimo alcanzado: ' . $item->description . ' - Stock actual: ' . $current_stock);
        
        // Notificar según normativa
        $CI->warehouse_model->notify_minimum_stock([
            'item' => $item,
            'current_stock' => $current_stock,
            'minimum_stock' => $item->minimum_stock,
            'suggested_order' => calculate_suggested_order($item)
        ]);
    }
}

/**
 * Calcula impuestos peruanos
 */
function calculate_pe_taxes($amount, $tax_type = 'IGV') {
    switch($tax_type) {
        case 'IGV':
            return $amount * 0.18; // 18% IGV
        case 'ISC':
            // Impuesto Selectivo al Consumo - varía según el tipo de producto
            return calculate_isc($amount);
        case 'IVAP':
            // Impuesto a la Venta de Arroz Pilado
            return $amount * 0.04; // 4% IVAP
    }
}
