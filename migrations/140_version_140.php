<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_140 extends CI_Migration {
    public function up() {
        $CI = &get_instance();
        
        // Añadir campos para manejo mejorado de series y lotes
        if (!$CI->db->field_exists('batch_number', db_prefix() . 'inventory_manage')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'inventory_manage`
                ADD COLUMN `batch_number` VARCHAR(100) NULL,
                ADD COLUMN `manufacturing_date` DATE NULL,
                ADD COLUMN `expiration_alert_days` INT NULL DEFAULT 30
            ');
        }

        // Añadir campos para control de calidad
        if (!$CI->db->field_exists('quality_status', db_prefix() . 'goods_receipt_detail')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'goods_receipt_detail`
                ADD COLUMN `quality_status` VARCHAR(20) NULL DEFAULT "pending",
                ADD COLUMN `inspection_note` TEXT NULL,
                ADD COLUMN `inspector_id` INT NULL
            ');
        }

        // Añadir campos para trazabilidad
        if (!$CI->db->table_exists(db_prefix() . 'inventory_movements_log')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "inventory_movements_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `item_id` INT(11) NOT NULL,
                `type` VARCHAR(20) NOT NULL,
                `quantity` DECIMAL(15,2) NOT NULL,
                `warehouse_id` INT(11) NOT NULL,
                `date` DATETIME NOT NULL,
                `user_id` INT(11) NOT NULL,
                `reference` VARCHAR(100) NULL,
                `notes` TEXT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set);
        }

        // Añadir tabla para gestión de ubicaciones dentro del almacén
        if (!$CI->db->table_exists(db_prefix() . 'warehouse_locations')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "warehouse_locations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `warehouse_id` INT(11) NOT NULL,
                `location_code` VARCHAR(50) NOT NULL,
                `description` TEXT NULL,
                `status` VARCHAR(20) DEFAULT 'active',
                `capacity` DECIMAL(15,2) NULL,
                `aisle` VARCHAR(20) NULL,
                `rack` VARCHAR(20) NULL,
                `bin` VARCHAR(20) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_location` (`warehouse_id`, `location_code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set);
        }

        // Campos para gestión de unidades alternativas
        if (!$CI->db->table_exists(db_prefix() . 'item_unit_conversions')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "item_unit_conversions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `item_id` INT(11) NOT NULL,
                `base_unit_id` INT(11) NOT NULL,
                `alt_unit_id` INT(11) NOT NULL,
                `conversion_rate` DECIMAL(15,5) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_conversion` (`item_id`, `base_unit_id`, `alt_unit_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set);
        }

        // Actualizar items para gestión mejorada de productos
        if (!$CI->db->field_exists('minimum_stock_warning', db_prefix() . 'items')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'items`
                ADD COLUMN `minimum_stock_warning` DECIMAL(15,2) NULL DEFAULT 0,
                ADD COLUMN `maximum_stock_warning` DECIMAL(15,2) NULL DEFAULT 0,
                ADD COLUMN `reorder_point` DECIMAL(15,2) NULL DEFAULT 0,
                ADD COLUMN `preferred_vendor_id` INT NULL,
                ADD COLUMN `lead_time_days` INT NULL
            ');
        }
    }

    public function down() {
        return true;
    }
}
