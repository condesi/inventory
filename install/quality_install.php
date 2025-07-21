<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Instalar módulo de control de calidad
 * @return array
 */
function install_quality_module() {
    $CI = &get_instance();
    
    $success = true;
    $message = '';
    
    try {
        // 1. Verificar requisitos
        if (!extension_loaded('gd')) {
            throw new Exception('La extensión GD es requerida para los gráficos.');
        }
        
        // 2. Crear directorios necesarios
        $directories = [
            'uploads/quality/reports',
            'uploads/quality/templates',
            'uploads/quality/signatures'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir(FCPATH . $dir)) {
                mkdir(FCPATH . $dir, 0755, true);
            }
        }
        
        // 3. Copiar archivos de assets
        $asset_files = [
            'assets/js/quality_control.js',
            'assets/css/quality_control.css',
            'assets/img/quality_icons.png'
        ];
        
        foreach ($asset_files as $file) {
            if (!file_exists(FCPATH . $file)) {
                copy(__DIR__ . '/assets/' . basename($file), FCPATH . $file);
            }
        }
        
        // 4. Registrar hooks
        register_quality_hooks();
        
        // 5. Configuraciones iniciales
        $settings = [
            'quality_auto_notify' => 1,
            'quality_approval_required' => 1,
            'quality_inspection_reminder' => 24, // horas
            'quality_default_template' => 1
        ];
        
        foreach ($settings as $name => $value) {
            add_option($name, $value);
        }
        
        // 6. Crear roles predeterminados
        $roles = [
            [
                'name' => 'Inspector de Calidad',
                'permissions' => [
                    'quality_control',
                    'quality_inspection_create',
                    'quality_inspection_edit',
                    'quality_reports_view'
                ]
            ],
            [
                'name' => 'Supervisor de Calidad',
                'permissions' => [
                    'quality_control',
                    'quality_inspection_create',
                    'quality_inspection_edit',
                    'quality_inspection_delete',
                    'quality_criteria_manage',
                    'quality_templates_manage',
                    'quality_reports_view'
                ]
            ]
        ];
        
        foreach ($roles as $role) {
            $role_id = $CI->roles_model->add($role);
            if ($role_id) {
                foreach ($role['permissions'] as $permission) {
                    $CI->roles_model->add_permission($role_id, $permission);
                }
            }
        }
        
        $message = 'Módulo de Control de Calidad instalado correctamente';
        
    } catch (Exception $e) {
        $success = false;
        $message = 'Error al instalar el módulo: ' . $e->getMessage();
        log_activity('Error de instalación del módulo de calidad: ' . $e->getMessage());
    }
    
    return [
        'success' => $success,
        'message' => $message
    ];
}

/**
 * Registrar hooks del módulo
 */
function register_quality_hooks() {
    $hooks = [
        'after_item_added' => 'quality_after_item_added',
        'after_receipt_approved' => 'quality_after_receipt_approved',
        'before_item_delivered' => 'quality_before_item_delivered',
        'after_cron_run' => 'quality_check_pending_inspections'
    ];
    
    foreach ($hooks as $hook => $function) {
        hooks()->add_action($hook, $function);
    }
}

/**
 * Hook: Después de agregar un ítem
 */
function quality_after_item_added($item_id) {
    $CI = &get_instance();
    $CI->load->model('Quality_model');
    
    // Asignar plantilla por defecto si corresponde
    $default_template = get_option('quality_default_template');
    if ($default_template) {
        $CI->db->where('id', $item_id);
        $CI->db->update(db_prefix() . 'items', ['quality_template_id' => $default_template]);
    }
}

/**
 * Hook: Después de aprobar un recibo
 */
function quality_after_receipt_approved($receipt_id) {
    $CI = &get_instance();
    $CI->load->model('Quality_model');
    
    // Crear inspecciones pendientes
    $CI->Quality_model->create_pending_inspections($receipt_id);
    
    // Notificar si está habilitado
    if (get_option('quality_auto_notify')) {
        $CI->Quality_model->notify_pending_inspections($receipt_id);
    }
}

/**
 * Hook: Antes de entregar un ítem
 */
function quality_before_item_delivered($delivery) {
    if (get_option('quality_approval_required')) {
        // Verificar que todos los ítems tengan inspección aprobada
        $CI = &get_instance();
        $CI->load->model('Quality_model');
        
        $pending = $CI->Quality_model->get_pending_inspections_for_delivery($delivery->id);
        if ($pending) {
            set_alert('warning', _l('quality_inspection_required'));
            redirect(admin_url('warehouse/view_delivery/' . $delivery->id));
        }
    }
}

/**
 * Hook: Verificar inspecciones pendientes
 */
function quality_check_pending_inspections() {
    $CI = &get_instance();
    $CI->load->model('Quality_model');
    
    $reminder_hours = get_option('quality_inspection_reminder');
    if ($reminder_hours > 0) {
        $CI->Quality_model->send_inspection_reminders($reminder_hours);
    }
}
