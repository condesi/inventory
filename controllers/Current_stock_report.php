<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Current_stock_report extends AdminController {
    public function __construct() {
        parent::__construct();
        $this->load->model('warehouse_model');
    }

    /**
     * Display current stock report page
     */
    public function index() {
        if (!has_permission('warehouse', '', 'view') && !has_permission('warehouse', '', 'view_own') && !is_admin()) {
            access_denied('warehouse');
        }

        $data['title'] = _l('current_stock_report');
        $data['warehouse_filter'] = $this->warehouse_model->get_warehouse();
        $data['item_groups'] = $this->warehouse_model->get_groups();
        
        // Load the necessary assets
        $this->app_scripts->add('current_stock_report-js', module_dir_url('warehouse', 'assets/js/current_stock_report.js'));
        
        $this->load->view('report/current_stock_report', $data);
    }

    /**
     * Get current stock data for the report
     * @return JSON
     */
    public function get_data() {
        if (!has_permission('warehouse', '', 'view') && !has_permission('warehouse', '', 'view_own') && !is_admin()) {
            ajax_access_denied();
        }
        
        if ($this->input->post()) {
            $warehouse_filter = $this->input->post('warehouse_filter');
            $commodity_filter = $this->input->post('commodity_filter');
            $group_filter = $this->input->post('group_filter');
            $status_filter = $this->input->post('status_filter');
            
            // Get current stock data from model
            $stock_data = $this->warehouse_model->get_current_stock_data($warehouse_filter, $commodity_filter, $group_filter, $status_filter);
            
            // Generate HTML for report
            $html = '';
            $has_data = false;
            $total_quantity = 0;
            $total_value = 0;
            
            $html .= '<p><h3 class="bold text-center">' . mb_strtoupper(_l('current_stock_report')) . '</h3></p>';
            $html .= '<div class="col-md-12"><div class="table-responsive">';
            $html .= '<table class="table table-bordered table-striped">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th>' . _l('_order') . '</th>';
            $html .= '<th>' . _l('commodity_code') . '</th>';
            $html .= '<th>' . _l('commodity_name') . '</th>';
            $html .= '<th>' . _l('wh_unit_name') . '</th>';
            $html .= '<th class="text-center">' . _l('available_quantity') . '</th>';
            $html .= '<th>' . _l('warehouse_name') . '</th>';
            $html .= '<th>' . _l('item_location') . '</th>';
            $html .= '<th class="text-right">' . _l('unit_cost') . '</th>';
            $html .= '<th class="text-right">' . _l('total_value') . '</th>';
            $html .= '<th>' . _l('stock_status') . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody>';
            
            if (!empty($stock_data)) {
                $has_data = true;
                foreach ($stock_data as $key => $item) {
                    $row_number = $key + 1;
                    $status_class = '';
                    $status_text = _l('available');
                    
                    // Determine status based on stock levels
                    if ($item['quantity'] <= $item['minimum_stock'] && $item['quantity'] > 0) {
                        $status_class = 'stock-status-warning';
                        $status_text = _l('stock_warning');
                    } else if ($item['quantity'] <= 0) {
                        $status_class = 'stock-status-critical';
                        $status_text = _l('stock_critical');
                    } else {
                        $status_class = 'stock-status-available';
                    }
                    
                    $total_quantity += $item['quantity'];
                    $item_total_value = $item['quantity'] * $item['unit_cost'];
                    $total_value += $item_total_value;
                    
                    $html .= '<tr>';
                    $html .= '<td>' . $row_number . '</td>';
                    $html .= '<td>' . $item['commodity_code'] . '</td>';
                    $html .= '<td>' . $item['commodity_name'] . '</td>';
                    $html .= '<td>' . $item['unit_name'] . '</td>';
                    $html .= '<td class="text-center">' . $item['quantity'] . '</td>';
                    $html .= '<td>' . $item['warehouse_name'] . '</td>';
                    $html .= '<td>' . ($item['location'] ? $item['location'] : '-') . '</td>';
                    $html .= '<td class="text-right">' . app_format_money($item['unit_cost'], '') . '</td>';
                    $html .= '<td class="text-right">' . app_format_money($item_total_value, '') . '</td>';
                    $html .= '<td class="' . $status_class . '">' . $status_text . '</td>';
                    $html .= '</tr>';
                }
            } else {
                $html .= '<tr><td colspan="10" class="text-center">' . _l('no_data_available') . '</td></tr>';
            }
            
            $html .= '</tbody>';
            $html .= '<tfoot>';
            $html .= '<tr class="info">';
            $html .= '<th colspan="4" class="text-right">' . _l('total') . ':</th>';
            $html .= '<th class="text-center">' . $total_quantity . '</th>';
            $html .= '<th colspan="3" class="text-right"></th>';
            $html .= '<th class="text-right">' . app_format_money($total_value, '') . '</th>';
            $html .= '<th></th>';
            $html .= '</tr>';
            $html .= '</tfoot>';
            $html .= '</table>';
            $html .= '</div></div>';
            
            echo json_encode([
                'html' => $html,
                'has_data' => $has_data,
                'total_quantity' => $total_quantity,
                'total_value' => app_format_money($total_value, '')
            ]);
            die();
        }
    }
    
    /**
     * Export current stock report to Excel
     * @return JSON
     */
    public function export_excel() {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        
        if (!class_exists('XLSXWriter_fin')) {
            require_once(module_dir_path(WAREHOUSE_MODULE_NAME) . '/assets/plugins/XLSXReader/XLSXReader.php');
        }
        require_once(module_dir_path(WAREHOUSE_MODULE_NAME) . '/assets/plugins/XLSXWriter/xlsxwriter.class.php');
        
        $warehouse_filter = $this->input->post('warehouse_filter');
        $commodity_filter = $this->input->post('commodity_filter');
        $group_filter = $this->input->post('group_filter');
        $status_filter = $this->input->post('status_filter');
        
        // Get data
        $stock_data = $this->warehouse_model->get_current_stock_data($warehouse_filter, $commodity_filter, $group_filter, $status_filter);
        
        // Create Excel file
        $writer = new XLSXWriter();
        
        $header = [
            _l('_order') => 'integer',
            _l('commodity_code') => 'string',
            _l('commodity_name') => 'string',
            _l('wh_unit_name') => 'string',
            _l('available_quantity') => 'integer',
            _l('warehouse_name') => 'string',
            _l('item_location') => 'string',
            _l('unit_cost') => 'price',
            _l('total_value') => 'price',
            _l('stock_status') => 'string'
        ];
        
        // Add header
        $writer->writeSheetHeader('Current Stock Report', $header);
        
        // Add data rows
        $total_quantity = 0;
        $total_value = 0;
        
        foreach ($stock_data as $key => $item) {
            $row_number = $key + 1;
            $status_text = _l('available');
            
            // Determine status based on stock levels
            if ($item['quantity'] <= $item['minimum_stock'] && $item['quantity'] > 0) {
                $status_text = _l('stock_warning');
            } else if ($item['quantity'] <= 0) {
                $status_text = _l('stock_critical');
            }
            
            $total_quantity += $item['quantity'];
            $item_total_value = $item['quantity'] * $item['unit_cost'];
            $total_value += $item_total_value;
            
            $writer->writeSheetRow('Current Stock Report', [
                $row_number,
                $item['commodity_code'],
                $item['commodity_name'],
                $item['unit_name'],
                $item['quantity'],
                $item['warehouse_name'],
                $item['location'] ? $item['location'] : '-',
                $item['unit_cost'],
                $item_total_value,
                $status_text
            ]);
        }
        
        // Add totals row
        $writer->writeSheetRow('Current Stock Report', [
            '',
            '',
            '',
            _l('total'),
            $total_quantity,
            '',
            '',
            '',
            $total_value,
            ''
        ]);
        
        // Generate file
        $filename = 'current_stock_report_' . date('Ymd_His') . '.xlsx';
        $writer->writeToFile(WAREHOUSE_REPORT . $filename);
        
        echo json_encode([
            'success' => true,
            'message' => _l('file_export_success'),
            'filename' => 'modules/warehouse/uploads/report/' . $filename
        ]);
        die();
    }
    
    /**
     * Export current stock report to PDF
     */
    public function export_pdf() {
        if (!has_permission('warehouse', '', 'view') && !has_permission('warehouse', '', 'view_own') && !is_admin()) {
            access_denied('warehouse');
        }
        
        $warehouse_filter = json_decode($this->input->get('warehouse_filter'));
        $commodity_filter = json_decode($this->input->get('commodity_filter'));
        $group_filter = json_decode($this->input->get('group_filter'));
        $status_filter = $this->input->get('status_filter');
        
        // Get data
        $stock_data = $this->warehouse_model->get_current_stock_data($warehouse_filter, $commodity_filter, $group_filter, $status_filter);
        
        // Prepare PDF data
        $data['stock_data'] = $stock_data;
        $data['title'] = _l('current_stock_report');
        
        // Generate PDF
        try {
            $pdf = $this->warehouse_model->current_stock_report_pdf($data);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_error('Image failed to upload. Check that your PHP installation has the GD extension enabled.');
            }
            die;
        }
        
        $type = 'D';
        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }
        
        if ($this->input->get('print')) {
            $type = 'I';
        }
        
        $pdf->Output('current_stock_report_' . date('Ymd_His') . '.pdf', $type);
    }
    
    /**
     * Export current stock report to CSV
     */
    public function export_csv() {
        if (!has_permission('warehouse', '', 'view') && !has_permission('warehouse', '', 'view_own') && !is_admin()) {
            access_denied('warehouse');
        }
        
        $warehouse_filter = json_decode($this->input->get('warehouse_filter'));
        $commodity_filter = json_decode($this->input->get('commodity_filter'));
        $group_filter = json_decode($this->input->get('group_filter'));
        $status_filter = $this->input->get('status_filter');
        
        // Get data
        $stock_data = $this->warehouse_model->get_current_stock_data($warehouse_filter, $commodity_filter, $group_filter, $status_filter);
        
        // Prepare CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="current_stock_report_' . date('Ymd_His') . '.csv"');
        
        // Create CSV file
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        // Add headers
        fputcsv($output, [
            _l('_order'),
            _l('commodity_code'),
            _l('commodity_name'),
            _l('wh_unit_name'),
            _l('available_quantity'),
            _l('warehouse_name'),
            _l('item_location'),
            _l('unit_cost'),
            _l('total_value'),
            _l('stock_status')
        ]);
        
        // Add data rows
        $total_quantity = 0;
        $total_value = 0;
        
        foreach ($stock_data as $key => $item) {
            $row_number = $key + 1;
            $status_text = _l('available');
            
            // Determine status based on stock levels
            if ($item['quantity'] <= $item['minimum_stock'] && $item['quantity'] > 0) {
                $status_text = _l('stock_warning');
            } else if ($item['quantity'] <= 0) {
                $status_text = _l('stock_critical');
            }
            
            $total_quantity += $item['quantity'];
            $item_total_value = $item['quantity'] * $item['unit_cost'];
            $total_value += $item_total_value;
            
            fputcsv($output, [
                $row_number,
                $item['commodity_code'],
                $item['commodity_name'],
                $item['unit_name'],
                $item['quantity'],
                $item['warehouse_name'],
                $item['location'] ? $item['location'] : '-',
                app_format_money($item['unit_cost'], ''),
                app_format_money($item_total_value, ''),
                $status_text
            ]);
        }
        
        // Add totals row
        fputcsv($output, [
            '',
            '',
            '',
            _l('total'),
            $total_quantity,
            '',
            '',
            '',
            app_format_money($total_value, ''),
            ''
        ]);
        
        fclose($output);
        die();
    }
}