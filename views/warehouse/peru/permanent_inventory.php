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
                                        <?php echo _l('permanent_inventory'); ?> - 
                                        <?php echo format_period($period); ?>
                                    </h4>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="<?php echo admin_url('warehouse/sunat_reports/export/RK/'.$period); ?>" 
                                       class="btn btn-success">
                                        <i class="fa fa-download"></i> <?php echo _l('export_kardex'); ?>
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
                                                <th><?php echo _l('date'); ?></th>
                                                <th><?php echo _l('type'); ?></th>
                                                <th><?php echo _l('document_type'); ?></th>
                                                <th><?php echo _l('document_number'); ?></th>
                                                <th><?php echo _l('operation_type'); ?></th>
                                                <th><?php echo _l('quantity'); ?></th>
                                                <th><?php echo _l('unit_value'); ?></th>
                                                <th><?php echo _l('total_value'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($movements as $movement) { ?>
                                            <tr>
                                                <td><?php echo _d($movement->date); ?></td>
                                                <td><?php echo _l($movement->type); ?></td>
                                                <td><?php echo $movement->document_type; ?></td>
                                                <td><?php echo $movement->document_number; ?></td>
                                                <td><?php echo _l($movement->operation_type); ?></td>
                                                <td class="text-right"><?php echo number_format($movement->quantity, 2); ?></td>
                                                <td class="text-right"><?php echo format_money($movement->unit_value); ?></td>
                                                <td class="text-right"><?php echo format_money($movement->total_value); ?></td>
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
