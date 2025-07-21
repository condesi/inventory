<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>
                                        <?php echo _l('inventory_book'); ?> - 
                                        <?php echo format_period($period); ?>
                                    </h4>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="<?php echo admin_url('warehouse/sunat_reports/export/LE/'.$period); ?>" 
                                       class="btn btn-success">
                                        <i class="fa fa-download"></i> <?php echo _l('export_to_sunat'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('warehouse/sunat_reports'); ?>" 
                                       class="btn btn-default">
                                        <i class="fa fa-arrow-left"></i> <?php echo _l('back'); ?>
                                    </a>
                                </div>
                            </div>
                            <hr class="hr-panel-separator" />
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('code'); ?></th>
                                                <th><?php echo _l('description'); ?></th>
                                                <th><?php echo _l('unit'); ?></th>
                                                <th><?php echo _l('initial_quantity'); ?></th>
                                                <th><?php echo _l('initial_unit_value'); ?></th>
                                                <th><?php echo _l('initial_total_value'); ?></th>
                                                <th><?php echo _l('input_quantity'); ?></th>
                                                <th><?php echo _l('input_value'); ?></th>
                                                <th><?php echo _l('output_quantity'); ?></th>
                                                <th><?php echo _l('output_value'); ?></th>
                                                <th><?php echo _l('final_quantity'); ?></th>
                                                <th><?php echo _l('final_unit_value'); ?></th>
                                                <th><?php echo _l('final_total_value'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($inventory as $item) { ?>
                                            <tr>
                                                <td><?php echo $item->code; ?></td>
                                                <td><?php echo $item->description; ?></td>
                                                <td><?php echo $item->unit; ?></td>
                                                <td class="text-right"><?php echo number_format($item->initial_quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($item->initial_unit_value); ?></td>
                                                <td class="text-right"><?php echo format_money($item->initial_total_value); ?></td>
                                                <td class="text-right"><?php echo number_format($item->input_quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($item->input_value); ?></td>
                                                <td class="text-right"><?php echo number_format($item->output_quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($item->output_value); ?></td>
                                                <td class="text-right"><?php echo number_format($item->final_quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($item->final_unit_value); ?></td>
                                                <td class="text-right"><?php echo format_money($item->final_total_value); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="text-bold">
                                                <td colspan="3"><?php echo _l('totals'); ?></td>
                                                <td class="text-right"><?php echo number_format($totals['total_quantity'], 2); ?></td>
                                                <td></td>
                                                <td class="text-right"><?php echo format_money($totals['total_value']); ?></td>
                                                <td colspan="7"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
