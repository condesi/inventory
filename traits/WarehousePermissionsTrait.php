<?php
defined('BASEPATH') or exit('No direct script access allowed');

trait WarehousePermissionsTrait {
    protected $required_permission = 'warehouse';
    protected $module_name = 'warehouse';
    
    /**
     * Verifica permisos requeridos
     */
    protected function check_permission($action = 'view') {
        if (!has_permission($this->required_permission, '', $action)) {
            access_denied($this->module_name);
        }
    }
    
    /**
     * Verifica múltiples permisos
     */
    protected function check_multiple_permissions($permissions) {
        foreach ($permissions as $permission) {
            if (!has_permission($this->required_permission, '', $permission)) {
                access_denied($this->module_name);
            }
        }
    }
}
