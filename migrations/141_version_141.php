<?php defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_141 extends CI_Migration {
    public function up() {
        $CI = &get_instance();
        
        // Tabla de auditoría
        if (!$CI->db->table_exists(db_prefix() . 'warehouse_audit_log')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'warehouse_audit_log` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `timestamp` DATETIME NOT NULL,
                `action_type` VARCHAR(50) NOT NULL,
                `module` VARCHAR(50) NOT NULL,
                `record_id` INT(11) NULL,
                `details` TEXT NULL,
                `ip_address` VARCHAR(45) NULL,
                `user_agent` TEXT NULL,
                `old_value` TEXT NULL,
                `new_value` TEXT NULL,
                PRIMARY KEY (`id`),
                INDEX `user_id_idx` (`user_id`),
                INDEX `action_type_idx` (`action_type`),
                INDEX `timestamp_idx` (`timestamp`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de firmas digitales
        if (!$CI->db->table_exists(db_prefix() . 'warehouse_digital_signatures')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'warehouse_digital_signatures` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `document_type` VARCHAR(50) NOT NULL,
                `document_id` INT(11) NOT NULL,
                `signature` TEXT NOT NULL,
                `timestamp` DATETIME NOT NULL,
                `ip_address` VARCHAR(45) NULL,
                `coordinates` VARCHAR(50) NULL,
                `device_info` TEXT NULL,
                PRIMARY KEY (`id`),
                INDEX `document_idx` (`document_type`, `document_id`),
                INDEX `user_id_idx` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Tabla de caché
        if (!$CI->db->table_exists(db_prefix() . 'warehouse_cache')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'warehouse_cache` (
                `key` VARCHAR(255) NOT NULL,
                `value` LONGTEXT NOT NULL,
                `expiration` INT(11) NOT NULL,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
        }

        // Índices para optimización
        // Tabla de productos
        if (!$CI->db->field_exists('idx_code', db_prefix() . 'items')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'items` 
                ADD INDEX `idx_code` (`commodity_code`),
                ADD INDEX `idx_barcode` (`commodity_barcode`),
                ADD INDEX `idx_type_status` (`commodity_type`, `active`)
            ');
        }

        // Tabla de transacciones
        if (!$CI->db->field_exists('idx_date', db_prefix() . 'goods_transaction_detail')) {
            $CI->db->query('ALTER TABLE `' . db_prefix() . 'goods_transaction_detail` 
                ADD INDEX `idx_date` (`date_add`),
                ADD INDEX `idx_commodity` (`commodity_id`, `warehouse_id`)
            ');
        }
    }

    public function down() {
        $CI = &get_instance();
        
        // Eliminar tablas
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'warehouse_audit_log`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'warehouse_digital_signatures`');
        $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'warehouse_cache`');
        
        // Eliminar índices
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'items` 
            DROP INDEX IF EXISTS `idx_code`,
            DROP INDEX IF EXISTS `idx_barcode`,
            DROP INDEX IF EXISTS `idx_type_status`
        ');
        
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'goods_transaction_detail` 
            DROP INDEX IF EXISTS `idx_date`,
            DROP INDEX IF EXISTS `idx_commodity`
        ');
    }
}
