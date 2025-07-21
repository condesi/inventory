<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_142 extends CI_Migration {
    
    public function up() {
        $CI = &get_instance();
        
        // Tabla de inspecciones de calidad
        if (!$CI->db->table_exists(db_prefix() . 'quality_inspections')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'quality_inspections` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `receipt_detail_id` INT(11) NOT NULL,
                `inspector_id` INT(11) NOT NULL,
                `inspection_date` DATETIME NOT NULL,
                `quality_status` VARCHAR(20) NOT NULL DEFAULT "pending",
                `inspection_note` TEXT NULL,
                `batch_number` VARCHAR(50) NULL,
                `quantity_inspected` DECIMAL(15,2) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `receipt_detail_idx` (`receipt_detail_id`),
                INDEX `inspector_idx` (`inspector_id`),
                INDEX `status_date_idx` (`quality_status`, `inspection_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de criterios de calidad
        if (!$CI->db->table_exists(db_prefix() . 'quality_criteria')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'quality_criteria` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `type` VARCHAR(20) NOT NULL,
                `min_value` DECIMAL(15,2) NULL,
                `max_value` DECIMAL(15,2) NULL,
                `expected_value` VARCHAR(50) NULL,
                `is_required` TINYINT(1) NOT NULL DEFAULT 1,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `type_active_idx` (`type`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de resultados de criterios
        if (!$CI->db->table_exists(db_prefix() . 'quality_inspection_results')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'quality_inspection_results` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `inspection_id` INT(11) NOT NULL,
                `criteria_id` INT(11) NOT NULL,
                `result_value` VARCHAR(50) NOT NULL,
                `pass_fail` TINYINT(1) NOT NULL DEFAULT 1,
                `note` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `inspection_criteria_idx` (`inspection_id`, `criteria_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de plantillas de inspección
        if (!$CI->db->table_exists(db_prefix() . 'quality_templates')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'quality_templates` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `item_group` INT(11) NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `group_active_idx` (`item_group`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de criterios por plantilla
        if (!$CI->db->table_exists(db_prefix() . 'quality_template_criteria')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'quality_template_criteria` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `template_id` INT(11) NOT NULL,
                `criteria_id` INT(11) NOT NULL,
                `order` INT(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE INDEX `template_criteria_idx` (`template_id`, `criteria_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Agregar campos a la tabla de productos
        if (!$CI->db->field_exists('quality_template_id', db_prefix() . 'items')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'items` 
                ADD COLUMN `quality_template_id` INT(11) NULL AFTER `warehouse_id`,
                ADD INDEX `quality_template_idx` (`quality_template_id`)
            ');
        }

        // Agregar permisos
        $permissions = [
            ['name' => 'quality_control', 'short_name' => 'Quality Control'],
            ['name' => 'quality_inspection_create', 'short_name' => 'Create Inspections'],
            ['name' => 'quality_inspection_edit', 'short_name' => 'Edit Inspections'],
            ['name' => 'quality_inspection_delete', 'short_name' => 'Delete Inspections'],
            ['name' => 'quality_criteria_manage', 'short_name' => 'Manage Criteria'],
            ['name' => 'quality_templates_manage', 'short_name' => 'Manage Templates'],
            ['name' => 'quality_reports_view', 'short_name' => 'View Reports']
        ];

        foreach ($permissions as $permission) {
            if (total_rows(db_prefix() . 'permissions', ['name' => $permission['name']]) == 0) {
                $CI->db->insert(db_prefix() . 'permissions', $permission);
            }
        }

        // Insertar datos iniciales de criterios
        $default_criteria = [
            ['name' => 'Apariencia Visual', 'type' => 'boolean', 'description' => 'Inspección visual del producto'],
            ['name' => 'Peso', 'type' => 'numeric', 'description' => 'Peso del producto en kg'],
            ['name' => 'Color', 'type' => 'options', 'description' => 'Color del producto según estándar'],
            ['name' => 'Dimensiones', 'type' => 'numeric', 'description' => 'Dimensiones del producto en cm'],
            ['name' => 'Empaque', 'type' => 'boolean', 'description' => 'Estado del empaque']
        ];

        foreach ($default_criteria as $criteria) {
            $criteria['created_at'] = date('Y-m-d H:i:s');
            $CI->db->insert(db_prefix() . 'quality_criteria', $criteria);
        }
    }

    public function down() {
        $CI = &get_instance();
        
        // Eliminar tablas
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'quality_inspections`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'quality_criteria`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'quality_inspection_results`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'quality_templates`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'quality_template_criteria`');
        
        // Eliminar campo de productos
        if ($CI->db->field_exists('quality_template_id', db_prefix() . 'items')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'items` DROP COLUMN `quality_template_id`');
        }
        
        // Eliminar permisos
        $CI->db->where_in('name', [
            'quality_control',
            'quality_inspection_create',
            'quality_inspection_edit',
            'quality_inspection_delete',
            'quality_criteria_manage',
            'quality_templates_manage',
            'quality_reports_view'
        ]);
        $CI->db->delete(db_prefix() . 'permissions');
    }
}
