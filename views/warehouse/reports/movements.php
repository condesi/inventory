<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin">
                                    <?php echo _l('inventory_movements_report'); ?>
                                </h4>
                                <hr class="hr-panel-separator" />
                            </div>
                        </div>

                        <?php echo form_open('', ['id' => 'movements-filter-form']); ?>
                        <div class="row">
                            <div class="col-md-3">
                                <?php echo render_date_input('start_date', 'start_date', $this->input->post('start_date')); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_date_input('end_date', 'end_date', $this->input->post('end_date')); ?>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="warehouse_id"><?php echo _l('warehouse'); ?></label>
                                    <select name="warehouse_id" id="warehouse_id" class="selectpicker" data-live-search="true">
                                        <option value=""><?php echo _l('all_warehouses'); ?></option>
                                        <?php foreach($warehouses as $warehouse) { ?>
                                            <option value="<?php echo $warehouse['id']; ?>" <?php if($this->input->post('warehouse_id') == $warehouse['id']) { echo 'selected'; } ?>>
                                                <?php echo $warehouse['name']; ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="movement_type"><?php echo _l('movement_type'); ?></label>
                                    <select name="movement_type" id="movement_type" class="selectpicker">
                                        <option value=""><?php echo _l('all_movements'); ?></option>
                                        <option value="in" <?php if($this->input->post('movement_type') == 'in') { echo 'selected'; } ?>>
                                            <?php echo _l('stock_in'); ?>
                                        </option>
                                        <option value="out" <?php if($this->input->post('movement_type') == 'out') { echo 'selected'; } ?>>
                                            <?php echo _l('stock_out'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-info"><?php echo _l('filter'); ?></button>
                                <a href="<?php echo admin_url('warehouse/inventory_reports/export/movements?' . $_SERVER['QUERY_STRING']); ?>" class="btn btn-success pull-right">
                                    <i class="fa fa-file-excel-o"></i> <?php echo _l('export_to_excel'); ?>
                                </a>
                            </div>
                        </div>
                        <?php echo form_close(); ?>

                        <div class="row mtop20">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-movements">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('date'); ?></th>
                                                <th><?php echo _l('reference'); ?></th>
                                                <th><?php echo _l('type'); ?></th>
                                                <th><?php echo _l('item'); ?></th>
                                                <th><?php echo _l('warehouse'); ?></th>
                                                <th class="text-right"><?php echo _l('quantity'); ?></th>
                                                <th class="text-right"><?php echo _l('unit_price'); ?></th>
                                                <th class="text-right"><?php echo _l('total'); ?></th>
                                                <th><?php echo _l('staff'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($movements as $movement) { ?>
                                                <tr>
                                                    <td><?php echo _dt($movement['date']); ?></td>
                                                    <td><?php echo $movement['reference']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $label_class = $movement['type'] == 'in' ? 'success' : 'danger';
                                                        echo '<span class="label label-'.$label_class.'">'._l('stock_'.$movement['type']).'</span>';
                                                        ?>
                                                    </td>
                                                    <td><?php echo $movement['item_name']; ?></td>
                                                    <td><?php echo $movement['warehouse_name']; ?></td>
                                                    <td class="text-right">
                                                        <?php 
                                                        $qty = $movement['type'] == 'in' ? $movement['quantity'] : -$movement['quantity'];
                                                        echo number_format($qty, 2);
                                                        ?>
                                                    </td>
                                                    <td class="text-right">
                                                        <?php echo app_format_money($movement['unit_price'], ''); ?>
                                                    </td>
                                                    <td class="text-right">
                                                        <?php 
                                                        $total = abs($movement['quantity'] * $movement['unit_price']);
                                                        echo app_format_money($total, '');
                                                        ?>
                                                    </td>
                                                    <td><?php echo $movement['staff_name']; ?></td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bold">
                                                <td colspan="5"><?php echo _l('total'); ?></td>
                                                <td class="text-right">
                                                    <?php echo _l('in').': '.number_format($totals['in'], 2); ?><br>
                                                    <?php echo _l('out').': '.number_format($totals['out'], 2); ?>
                                                </td>
                                                <td></td>
                                                <td class="text-right">
                                                    <?php echo app_format_money($totals['value'], ''); ?>
                                                </td>
                                                <td></td>
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

<script>
$(function() {
    // Inicializar selectpicker
    $('.selectpicker').selectpicker();
    
    // Inicializar datepicker
    init_datepicker();
    
    // Inicializar datatable
    var table = $('.table-movements').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": [8] }
        ]
    });
    
    // Actualizar tabla al filtrar
    $('#movements-filter-form').on('submit', function(e) {
        e.preventDefault();
        
        $.get(admin_url + 'warehouse/inventory_reports/movements?' + $(this).serialize(), function(response) {
            $('#movements-table').html(response);
        });
    });
});
</script>
