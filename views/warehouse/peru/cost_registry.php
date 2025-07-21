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
                                        <?php echo _l('cost_registry'); ?> - 
                                        <?php echo format_period($period); ?>
                                    </h4>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="<?php echo admin_url('warehouse/sunat_reports/export/RCI/'.$period); ?>" 
                                       class="btn btn-success">
                                        <i class="fa fa-download"></i> <?php echo _l('export_costs'); ?>
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
                                                <th><?php echo _l('item_code'); ?></th>
                                                <th><?php echo _l('description'); ?></th>
                                                <th><?php echo _l('quantity'); ?></th>
                                                <th><?php echo _l('unit_cost'); ?></th>
                                                <th><?php echo _l('total_cost'); ?></th>
                                                <th><?php echo _l('valuation_method'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($costs as $cost) { ?>
                                            <tr>
                                                <td><?php echo $cost->item_code; ?></td>
                                                <td><?php echo $cost->description; ?></td>
                                                <td class="text-right"><?php echo number_format($cost->quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($cost->unit_cost); ?></td>
                                                <td class="text-right"><?php echo format_money($cost->total_cost); ?></td>
                                                <td><?php echo _l($cost->method); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
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
