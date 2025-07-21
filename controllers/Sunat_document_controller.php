<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Sunat_document_controller extends Admin_controller {
    use WarehousePermissionsTrait;
    
    protected $sunat_config;
    protected $certificate_config;
    
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse/warehouse_model');
        $this->load->helper('warehouse_peru_helper');
        
        $this->sunat_config = $this->warehouse_model->get_sunat_config();
        $this->certificate_config = $this->warehouse_model->get_sunat_certificate();
    }
    
    /**
     * Procesa documento para SUNAT
     */
    protected function process_sunat_document($xml, $tipo = null) {
        $signed_xml = $this->sign_xml($xml);
        $response = $this->send_to_sunat($signed_xml);
        
        if ($response['success']) {
            return [
                'success' => true,
                'cdr_path' => $response['cdr_path']
            ];
        }
        
        return [
            'success' => false,
            'error' => $response['error']
        ];
    }
    
    /**
     * Firma XML
     */
    protected function sign_xml($xml) {
        // Implementar firma usando $this->certificate_config
        return $xml;
    }
    
    /**
     * Envía a SUNAT
     */
    protected function send_to_sunat($xml) {
        // Implementar envío usando $this->sunat_config
        return [
            'success' => true,
            'cdr_path' => 'path/to/cdr'
        ];
    }
    
    /**
     * Valida documento
     */
    protected function validate_sunat_document($data, $tipo) {
        $rules = $this->get_validation_rules($tipo);
        return $this->validate_rules($data, $rules);
    }
    
    /**
     * Obtiene reglas de validación
     */
    protected function get_validation_rules($tipo) {
        return [];
    }
    
    /**
     * Valida reglas
     */
    protected function validate_rules($data, $rules) {
        foreach ($rules as $field => $field_rules) {
            if (!isset($data[$field])) {
                return false;
            }
            foreach ($field_rules as $rule) {
                if (!$this->validate_rule($data[$field], $rule)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Valida una regla
     */
    protected function validate_rule($value, $rule) {
        if ($rule == 'required') {
            return !empty($value);
        }
        if (strpos($rule, 'max:') === 0) {
            $max = substr($rule, 4);
            return strlen($value) <= $max;
        }
        if (strpos($rule, 'in:') === 0) {
            $allowed = explode(',', substr($rule, 3));
            return in_array($value, $allowed);
        }
        if ($rule == 'numeric') {
            return is_numeric($value);
        }
        return true;
    }
}
