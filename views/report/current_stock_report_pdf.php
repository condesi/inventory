<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Calculate totals
$total_quantity = 0;
$total_value = 0;
?>

<div class="pdf-container">
    <div class="pdf_header">
        <div class="company_info">
            <div class="company-logo">
                <?php get_company_logo(); ?>
            </div>
            <div class="company_address">
                <?php echo format_organization_info(); ?>
            </div>
        </div>
        
        <h1 class="report_title"><?php echo _l('current_stock_report'); ?></h1>
        <p class="report_date"><?php echo _d(date('Y-m-d')); ?></p>
    </div>
    
    <table class="table items stock_table">
        <thead>
            <tr>
                <th><?php echo _l('_order'); ?></th>
                <th><?php echo _l('commodity_code'); ?></th>
                <th><?php echo _l('commodity_name'); ?></th>
                <th><?php echo _l('wh_unit_name'); ?></th>
                <th class="text-center"><?php echo _l('available_quantity'); ?></th>
                <th><?php echo _l('warehouse_name'); ?></th>
                <th class="text-right"><?php echo _l('unit_cost'); ?></th>
                <th class="text-right"><?php echo _l('total_value'); ?></th>
                <th><?php echo _l('stock_status'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($stock_data as $key => $item) { 
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
            ?>
            <tr>
                <td><?php echo $row_number; ?></td>
                <td><?php echo $item['commodity_code']; ?></td>
                <td><?php echo $item['commodity_name']; ?></td>
                <td><?php echo $item['unit_name']; ?></td>
                <td class="text-center"><?php echo $item['quantity']; ?></td>
                <td><?php echo $item['warehouse_name']; ?></td>
                <td class="text-right"><?php echo app_format_money($item['unit_cost'], ''); ?></td>
                <td class="text-right"><?php echo app_format_money($item_total_value, ''); ?></td>
                <td><?php echo $status_text; ?></td>
            </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right"><?php echo _l('total'); ?>:</th>
                <th class="text-center"><?php echo $total_quantity; ?></th>
                <th></th>
                <th></th>
                <th class="text-right"><?php echo app_format_money($total_value, ''); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>
</div>

<style>
    .pdf-container {
        font-family: Arial, sans-serif;
        color: #444;
        width: 100%;
    }
    
    .pdf_header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .company_info {
        margin-bottom: 20px;
    }
    
    .company-logo {
        width: 100%;
        text-align: center;
        margin-bottom: 10px;
    }
    
    .company-logo img {
        max-width: 150px;
    }
    
    .company_address {
        text-align: center;
        font-size: 12px;
    }
    
    .report_title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    
    .report_date {
        font-size: 13px;
        margin-bottom: 20px;
    }
    
    .stock_table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 12px;
    }
    
    .stock_table th, .stock_table td {
        border: 1px solid #ddd;
        padding: 7px 5px;
    }
    
    .stock_table th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: left;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-right {
        text-align: right;
    }
    
    tfoot tr {
        background-color: #f5f5f5;
    }
</style>