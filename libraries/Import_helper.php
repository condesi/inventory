<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Import_helper {
    private $ci;
    private $required_columns;
    private $optional_columns;
    private $error_log;
    private $success_count;
    private $error_count;
    
    public function __construct() {
        $this->ci =& get_instance();
        $this->error_log = [];
        $this->success_count = 0;
        $this->error_count = 0;
        
        // Definir columnas requeridas y opcionales
        $this->required_columns = [
            'codigo' => 'Código del producto',
            'nombre' => 'Nombre del producto',
            'cantidad' => 'Cantidad'
        ];
        
        $this->optional_columns = [
            'descripcion' => 'Descripción',
            'precio_unitario' => 'Precio unitario',
            'ubicacion' => 'Ubicación en almacén'
        ];
    }
    
    /**
     * Valida y procesa archivo de importación
     * @param string $file_path Ruta al archivo
     * @param string $type Tipo de importación (inventory, products, suppliers)
     * @return array Resultado del proceso
     */
    public function process_import_file($file_path, $type = 'inventory') {
        if (!file_exists($file_path)) {
            throw new Exception("Archivo no encontrado");
        }

        // Determinar tipo de archivo y leer
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $data = $this->read_file($file_path, $extension);

        // Validar estructura
        if (!$this->validate_structure($data)) {
            return [
                'status' => false,
                'message' => 'Estructura de archivo inválida',
                'errors' => $this->error_log
            ];
        }

        // Procesar registros
        $results = $this->process_records($data, $type);

        return [
            'status' => ($this->error_count === 0),
            'success_count' => $this->success_count,
            'error_count' => $this->error_count,
            'errors' => $this->error_log,
            'results' => $results
        ];
    }

    /**
     * Lee archivo según su extensión
     */
    private function read_file($file_path, $extension) {
        switch(strtolower($extension)) {
            case 'xlsx':
                return $this->read_excel($file_path);
            case 'csv':
                return $this->read_csv($file_path);
            default:
                throw new Exception("Formato de archivo no soportado");
        }
    }

    /**
     * Lee archivo Excel
     */
    private function read_excel($file_path) {
        $this->ci->load->library('excel');
        // TODO: Implementar lectura de Excel
        return [];
    }

    /**
     * Lee archivo CSV
     */
    private function read_csv($file_path) {
        $data = [];
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }

    /**
     * Valida estructura del archivo
     */
    private function validate_structure($data) {
        if (empty($data)) {
            $this->error_log[] = "Archivo vacío";
            return false;
        }

        // Verificar columnas requeridas
        $first_row = reset($data);
        foreach ($this->required_columns as $column => $label) {
            if (!isset($first_row[$column])) {
                $this->error_log[] = "Falta la columna requerida: {$label}";
                return false;
            }
        }

        return true;
    }

    /**
     * Procesa los registros
     */
    private function process_records($data, $type) {
        $results = [];
        foreach ($data as $index => $row) {
            try {
                $this->validate_row($row);
                $processed = $this->save_record($row, $type);
                $results[] = $processed;
                $this->success_count++;
            } catch (Exception $e) {
                $this->error_log[] = "Error en línea " . ($index + 2) . ": " . $e->getMessage();
                $this->error_count++;
            }
        }
        return $results;
    }

    /**
     * Valida una fila de datos
     */
    private function validate_row($row) {
        // Validar campos requeridos no vacíos
        foreach ($this->required_columns as $column => $label) {
            if (empty($row[$column])) {
                throw new Exception("{$label} no puede estar vacío");
            }
        }

        // Validar formato de números
        if (isset($row['cantidad']) && !is_numeric($row['cantidad'])) {
            throw new Exception("Cantidad debe ser un número");
        }
        
        if (isset($row['precio_unitario']) && !is_numeric($row['precio_unitario'])) {
            throw new Exception("Precio unitario debe ser un número");
        }
    }

    /**
     * Guarda un registro según su tipo
     */
    private function save_record($row, $type) {
        switch($type) {
            case 'inventory':
                return $this->save_inventory_record($row);
            case 'products':
                return $this->save_product_record($row);
            case 'suppliers':
                return $this->save_supplier_record($row);
            default:
                throw new Exception("Tipo de importación no válido");
        }
    }

    /**
     * Guarda registro de inventario
     */
    private function save_inventory_record($row) {
        // TODO: Implementar lógica de guardado de inventario
        return $row;
    }

    /**
     * Guarda registro de producto
     */
    private function save_product_record($row) {
        // TODO: Implementar lógica de guardado de producto
        return $row;
    }

    /**
     * Guarda registro de proveedor
     */
    private function save_supplier_record($row) {
        // TODO: Implementar lógica de guardado de proveedor
        return $row;
    }
}
