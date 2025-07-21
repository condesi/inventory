<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Electronic_documents extends Sunat_document_controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Verifica permisos requeridos
     */
    private function check_permission($action = 'view') {
        if (!has_permission($this->required_permission, '', $action)) {
            access_denied('warehouse');
        }
    }

    /**
     * Vista principal de documentos electrónicos
     */
    public function index() {
        $this->check_permission();
        
        $data['title'] = _l('electronic_documents');
        $data['document_types'] = PE_DOCUMENT_TYPES;
        
        $this->load->view('warehouse/peru/electronic_documents', $data);
    }

    /**
     * Generación de facturas electrónicas
     */
    public function generate_invoice($invoice_id) {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $invoice = $this->warehouse_model->get_invoice($invoice_id);
        
        // Generar XML
        $xml = generate_sunat_xml($invoice);
        
        // Firmar XML
        $signed_xml = $this->sign_xml($xml);
        
        // Enviar a SUNAT
        $response = $this->send_to_sunat($signed_xml);
        
        if ($response['success']) {
            // Actualizar estado y guardar CDR
            $this->warehouse_model->update_electronic_document([
                'document_id' => $invoice_id,
                'status' => 'sent',
                'sunat_response' => json_encode($response),
                'cdr_path' => $response['cdr_path']
            ]);
            
            set_alert('success', _l('document_sent_to_sunat'));
        } else {
            set_alert('danger', $response['error']);
        }
        
        redirect(admin_url('warehouse/view_invoice/' . $invoice_id));
    }

    /**
     * Generación de notas de crédito
     */
    public function credit_note($invoice_id = null) {
        if (!has_permission('warehouse', '', 'create')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Validar datos según SUNAT
            if (!$this->validate_credit_note($data)) {
                set_alert('danger', _l('invalid_credit_note_data'));
                redirect(admin_url('warehouse/credit_note'));
            }
            
            // Crear nota de crédito
            $note_id = $this->warehouse_model->create_credit_note($data);
            
            if ($note_id) {
                // Generar XML y enviar a SUNAT
                $this->generate_invoice($note_id);
                set_alert('success', _l('credit_note_created'));
                redirect(admin_url('warehouse/view_credit_note/' . $note_id));
            }
        }

        $data['title'] = _l('create_credit_note');
        if ($invoice_id) {
            $data['invoice'] = $this->warehouse_model->get_invoice($invoice_id);
        }
        $data['credit_note_types'] = [
            '01' => 'Anulación de la operación',
            '02' => 'Anulación por error en el RUC',
            '03' => 'Corrección por error en la descripción',
            '04' => 'Descuento global',
            '05' => 'Descuento por ítem',
            '06' => 'Devolución total',
            '07' => 'Devolución por ítem'
        ];
        
        $this->load->view('warehouse/peru/credit_note_form', $data);
    }

    /**
     * Generación de guías de remisión electrónicas
     */
    public function remission_guide() {
        if (!has_permission('warehouse', '', 'create')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Generar número de guía
            $data['numero'] = generate_guia_remision_number($data['establecimiento']);
            
            // Crear guía
            $guide_id = $this->warehouse_model->create_remission_guide($data);
            
            if ($guide_id) {
                // Generar XML y enviar a SUNAT
                $this->generate_guide_xml($guide_id);
                set_alert('success', _l('guide_created'));
                redirect(admin_url('warehouse/view_guide/' . $guide_id));
            }
        }

        $data['title'] = _l('create_remission_guide');
        $data['establecimientos'] = $this->warehouse_model->get_establecimientos();
        $data['modalidades_transporte'] = [
            '01' => 'Transporte público',
            '02' => 'Transporte privado'
        ];
        
        $this->load->view('warehouse/peru/remission_guide_form', $data);
    }

    /**
     * Consulta estado de documentos en SUNAT
     */
    public function check_document_status() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        $serie = $this->input->post('serie');
        $numero = $this->input->post('numero');
        $tipo = $this->input->post('tipo');

        // Consultar estado en SUNAT
        $status = $this->check_sunat_status($tipo, $serie, $numero);

        echo json_encode($status);
    }

    /**
     * Comunicación de baja
     */
    public function void_document() {
        if (!has_permission('warehouse', '', 'delete')) {
            access_denied('warehouse');
        }

        $document_id = $this->input->post('document_id');
        $reason = $this->input->post('reason');

        // Generar comunicación de baja
        $void_data = [
            'fecha_documento' => date('Y-m-d'),
            'fecha_comunicacion' => date('Y-m-d'),
            'documento_id' => $document_id,
            'motivo' => $reason
        ];

        // Enviar a SUNAT
        $response = $this->send_void_to_sunat($void_data);

        if ($response['success']) {
            $this->warehouse_model->void_document($document_id, $response['ticket']);
            set_alert('success', _l('document_voided'));
        } else {
            set_alert('danger', $response['error']);
        }

        redirect($_SERVER['HTTP_REFERER']);
    }

    /**
     * Resumen diario de boletas
     */
    public function daily_summary() {
        if (!has_permission('warehouse', '', 'view')) {
            access_denied('warehouse');
        }

        if ($this->input->post()) {
            $date = $this->input->post('date');
            
            // Obtener boletas del día
            $invoices = $this->warehouse_model->get_daily_invoices($date);
            
            // Generar resumen
            $summary = $this->generate_summary_xml($invoices);
            
            // Enviar a SUNAT
            $response = $this->send_summary_to_sunat($summary);
            
            if ($response['success']) {
                set_alert('success', _l('summary_sent'));
            } else {
                set_alert('danger', $response['error']);
            }
        }

        $data['title'] = _l('daily_summary');
        $this->load->view('warehouse/peru/daily_summary', $data);
    }

    // Funciones privadas de ayuda

    private function process_sunat_document($xml, $tipo = null) {
        // Firma XML
        $signed_xml = $this->sign_xml($xml);
        
        // Envía a SUNAT
        $response = $this->send_to_sunat($signed_xml);
        
        if ($response['success']) {
            // Actualizar estado y CDR
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

    private function sign_xml($xml) {
        // Obtener configuración de certificado
        $cert_config = $this->warehouse_model->get_sunat_certificate();
        
        // TODO: Implementar firma real usando certificado
        return $xml;
    }

    private function send_to_sunat($xml) {
        // Obtener configuración de SUNAT
        $sunat_config = $this->warehouse_model->get_sunat_config();
        
        // TODO: Implementar envío real usando configuración
        return [
            'success' => true,
            'cdr_path' => 'path/to/cdr'
        ];
    }

    private function validate_sunat_document($data, $tipo) {
        $validation_rules = $this->get_validation_rules($tipo);
        
        foreach ($validation_rules as $field => $rules) {
            if (!isset($data[$field]) || !$this->validate_field($data[$field], $rules)) {
                return false;
            }
        }
        
        return true;
    }

    private function get_validation_rules($tipo) {
        // Reglas según tipo de documento
        $rules = [
            'credit_note' => [
                'invoice_id' => ['required', 'numeric'],
                'reason_code' => ['required', 'in:01,02,03,04,05,06,07'],
                'reason_description' => ['required', 'max:100']
            ],
            // Agregar reglas para otros tipos de documentos
        ];
        
        return isset($rules[$tipo]) ? $rules[$tipo] : [];
    }

    private function validate_field($value, $rules) {
        foreach ($rules as $rule) {
            if (!$this->apply_validation_rule($value, $rule)) {
                return false;
            }
        }
        return true;
    }

    private function apply_validation_rule($value, $rule) {
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
