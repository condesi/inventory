<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Registra una entrada en el log de auditoría
 *
 * @param string $action_type Tipo de acción (create, update, delete, view, etc)
 * @param string $module Módulo afectado
 * @param int $record_id ID del registro afectado (opcional)
 * @param string $details Detalles adicionales
 * @param mixed $old_value Valor anterior (para actualizaciones)
 * @param mixed $new_value Nuevo valor (para actualizaciones)
 * @return bool
 */
function log_warehouse_activity($action_type, $module, $record_id = null, $details = '', $old_value = null, $new_value = null) {
    $CI = &get_instance();
    
    if (!is_logged_in()) {
        return false;
    }

    $data = [
        'user_id' => get_staff_user_id(),
        'timestamp' => date('Y-m-d H:i:s'),
        'action_type' => $action_type,
        'module' => $module,
        'record_id' => $record_id,
        'details' => $details,
        'ip_address' => $CI->input->ip_address(),
        'user_agent' => substr($CI->input->user_agent(), 0, 255),
        'old_value' => ($old_value !== null) ? json_encode($old_value) : null,
        'new_value' => ($new_value !== null) ? json_encode($new_value) : null
    ];

    return $CI->db->insert(db_prefix() . 'warehouse_audit_log', $data);
}

/**
 * Guarda una firma digital
 *
 * @param string $document_type Tipo de documento
 * @param int $document_id ID del documento
 * @param string $signature Firma en base64
 * @param string $coordinates Coordenadas de la firma en el documento (opcional)
 * @return bool|int
 */
function save_warehouse_signature($document_type, $document_id, $signature, $coordinates = null) {
    $CI = &get_instance();
    
    if (!is_logged_in()) {
        return false;
    }

    $data = [
        'user_id' => get_staff_user_id(),
        'document_type' => $document_type,
        'document_id' => $document_id,
        'signature' => $signature,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $CI->input->ip_address(),
        'coordinates' => $coordinates,
        'device_info' => json_encode([
            'user_agent' => $CI->input->user_agent(),
            'platform' => $CI->agent->platform(),
            'browser' => $CI->agent->browser() . ' ' . $CI->agent->version()
        ])
    ];

    $CI->db->insert(db_prefix() . 'warehouse_digital_signatures', $data);
    return $CI->db->insert_id();
}

/**
 * Guarda un valor en caché
 *
 * @param string $key Clave única
 * @param mixed $value Valor a guardar
 * @param int $expiration Tiempo de expiración en segundos
 * @return bool
 */
function warehouse_cache_set($key, $value, $expiration = 3600) {
    $CI = &get_instance();
    
    $data = [
        'key' => $key,
        'value' => serialize($value),
        'expiration' => time() + $expiration
    ];

    // Si la clave existe, actualizarla
    $CI->db->where('key', $key);
    $exists = $CI->db->get(db_prefix() . 'warehouse_cache')->num_rows();
    
    if ($exists) {
        return $CI->db->update(db_prefix() . 'warehouse_cache', $data, ['key' => $key]);
    }
    
    return $CI->db->insert(db_prefix() . 'warehouse_cache', $data);
}

/**
 * Obtiene un valor de caché
 *
 * @param string $key Clave a buscar
 * @return mixed|null Valor almacenado o null si no existe o expiró
 */
function warehouse_cache_get($key) {
    $CI = &get_instance();
    
    $CI->db->where('key', $key);
    $CI->db->where('expiration >', time());
    $query = $CI->db->get(db_prefix() . 'warehouse_cache');
    
    if ($query->num_rows() == 0) {
        return null;
    }
    
    $row = $query->row();
    return unserialize($row->value);
}

/**
 * Limpia entradas expiradas de caché
 */
function warehouse_cache_cleanup() {
    $CI = &get_instance();
    $CI->db->where('expiration <=', time());
    $CI->db->delete(db_prefix() . 'warehouse_cache');
}
