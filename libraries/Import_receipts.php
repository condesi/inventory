<?php 
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clase auxiliar para importación de ingresos desde Excel
 */
class Import_receipts {
    private $ci;
    private $errors = [];
    private $successful = [];
    private $warehouse_id;

    public function __construct() {
        $this->ci =& get_instance();
        $this->ci->load->model('Stock_receipt_model');
    }

    /**
     * Importar desde archivo Excel
     */
    public function import($file, $warehouse_id) {
        $this->warehouse_id = $warehouse_id;
        
        try {
            // Cargar librería PHPSpreadsheet
            require_once(APPPATH . 'third_party/PHPSpreadsheet/vendor/autoload.php');
            
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($file);
            $worksheet = $spreadsheet->getActiveSheet();

            // Validar estructura del archivo
            $this->validate_headers($worksheet);

            // Procesar filas
            $rows = $worksheet->toArray();
            array_shift($rows); // Remover encabezados
            
            $this->process_rows($rows);

            // Generar reporte
            return $this->generate_report();

        } catch (Exception $e) {
            log_activity('Error importing stock receipts: ' . $e->getMessage());
            throw new Exception(_l('import_error') . ': ' . $e->getMessage());
        }
    }

    /**
     * Validar encabezados del archivo
     */
    private function validate_headers($worksheet) {
        $required_headers = [
            'code' => _l('item_code'),
            'name' => _l('item_name'),
            'quantity' => _l('quantity'),
            'unit' => _l('unit'),
            'price' => _l('unit_price'),
            'tax' => _l('tax'),
            'date' => _l('date')
        ];

        $headers = $worksheet->rangeToArray('A1:G1')[0];

        foreach ($required_headers as $key => $label) {
            if (!in_array($label, $headers)) {
                throw new Exception(sprintf(
                    _l('missing_import_column'),
                    $label
                ));
            }
        }
    }

    /**
     * Procesar filas del archivo
     */
    private function process_rows($rows) {
        $current_receipt = null;
        $receipt_items = [];

        foreach ($rows as $row_number => $row) {
            try {
                // Validar datos requeridos
                $this->validate_row($row);

                // Agrupar items por fecha
                $date = date('Y-m-d', strtotime($row[6]));
                
                if ($current_receipt && $current_receipt['date'] != $date) {
                    // Guardar receipt anterior
                    $this->save_receipt($current_receipt);
                    $receipt_items = [];
                }

                // Preparar item
                $item = [
                    'commodity_code' => $row[0],
                    'commodity_name' => $row[1],
                    'quantities' => $row[2],
                    'unit_id' => $this->get_unit_id($row[3]),
                    'unit_price' => $row[4],
                    'tax' => $row[5] ?? 0
                ];

                $receipt_items[] = $item;
                
                $current_receipt = [
                    'warehouse_id' => $this->warehouse_id,
                    'date_add' => $date,
                    'items' => $receipt_items
                ];

                $this->successful[] = $row_number + 2;

            } catch (Exception $e) {
                $this->errors[] = sprintf(
                    _l('import_row_error'),
                    $row_number + 2,
                    $e->getMessage()
                );
            }
        }

        // Guardar último receipt
        if ($current_receipt) {
            $this->save_receipt($current_receipt);
        }
    }

    /**
     * Validar fila
     */
    private function validate_row($row) {
        // Validar código
        if (empty($row[0])) {
            throw new Exception(_l('missing_item_code'));
        }

        // Validar nombre
        if (empty($row[1])) {
            throw new Exception(_l('missing_item_name')); 
        }

        // Validar cantidad
        if (!is_numeric($row[2]) || $row[2] <= 0) {
            throw new Exception(_l('invalid_quantity'));
        }

        // Validar unidad
        if (empty($row[3])) {
            throw new Exception(_l('missing_unit'));
        }

        // Validar precio
        if (!is_numeric($row[4]) || $row[4] < 0) {
            throw new Exception(_l('invalid_price'));
        }

        // Validar fecha
        if (!strtotime($row[6])) {
            throw new Exception(_l('invalid_date'));
        }
    }

    /**
     * Obtener ID de unidad
     */
    private function get_unit_id($unit_name) {
        $unit = $this->ci->db->where('name', $unit_name)
                            ->get(db_prefix() . 'units')
                            ->row();
                            
        if (!$unit) {
            throw new Exception(sprintf(
                _l('unit_not_found'),
                $unit_name
            ));
        }

        return $unit->id;
    }

    /**
     * Guardar ingreso
     */
    private function save_receipt($data) {
        try {
            $this->ci->Stock_receipt_model->create_receipt($data);
        } catch (Exception $e) {
            throw new Exception(_l('receipt_save_error') . ': ' . $e->getMessage());
        }
    }

    /**
     * Generar reporte de importación
     */
    private function generate_report() {
        $total = count($this->successful) + count($this->errors);
        
        $report = [
            'total' => $total,
            'successful' => count($this->successful),
            'errors' => count($this->errors),
            'error_rows' => $this->errors
        ];

        // Generar archivo de errores si hay
        if (count($this->errors) > 0) {
            $this->generate_error_file($report);
        }

        return $report;
    }

    /**
     * Generar archivo de errores
     */
    private function generate_error_file($report) {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Encabezados
        $sheet->setCellValue('A1', _l('row'));
        $sheet->setCellValue('B1', _l('error'));

        // Datos
        $row = 2;
        foreach ($this->errors as $error) {
            $sheet->setCellValue('A' . $row, $error['row']);
            $sheet->setCellValue('B' . $row, $error['message']);
            $row++;
        }

        // Guardar archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'stock_receipts_import_errors_' . date('YmdHis') . '.xlsx';
        $writer->save(FCPATH . 'uploads/stock_receipts/' . $filename);

        $report['error_file'] = $filename;
    }
}
