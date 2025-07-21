<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin"><?php echo _l('unit_conversions'); ?></h4>
        <hr class="hr-panel-heading" />
        
        <div class="row">
            <div class="col-md-12">
                <a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#conversion-modal">
                    <i class="fa fa-plus"></i> <?php echo _l('add_conversion'); ?>
                </a>
            </div>
        </div>
        
        <div class="row mtop15">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-unit-conversions">
                        <thead>
                            <tr>
                                <th><?php echo _l('items'); ?></th>
                                <th><?php echo _l('base_unit'); ?></th>
                                <th><?php echo _l('conversion_rate'); ?></th>
                                <th><?php echo _l('alternate_unit'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Conversión -->
        <div class="modal fade" id="conversion-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo _l('unit_conversion'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open(admin_url('warehouse/save_unit_conversion'), ['id'=>'unit-conversion-form']); ?>
                        <input type="hidden" name="id" id="conversion_id">
                        
                        <div class="form-group">
                            <label for="item_id"><?php echo _l('items'); ?></label>
                            <select name="item_id" id="item_id" class="form-control selectpicker" data-live-search="true">
                                <?php foreach($items as $item) { ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo $item['description']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_unit_id"><?php echo _l('base_unit'); ?></label>
                                    <select name="base_unit_id" id="base_unit_id" class="form-control selectpicker">
                                        <?php foreach($units as $unit) { ?>
                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alt_unit_id"><?php echo _l('alternate_unit'); ?></label>
                                    <select name="alt_unit_id" id="alt_unit_id" class="form-control selectpicker">
                                        <?php foreach($units as $unit) { ?>
                                            <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="conversion_rate"><?php echo _l('conversion_rate'); ?></label>
                            <div class="input-group">
                                <input type="number" name="conversion_rate" id="conversion_rate" class="form-control" min="0.00001" step="0.00001" required>
                                <span class="input-group-addon">
                                    <span id="conversion-formula"></span>
                                </span>
                            </div>
                            <small class="form-text text-muted">Ejemplo: Si 1 caja = 12 unidades, ingrese 12</small>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-conversion"><?php echo _l('save'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var conversionsTable = initDataTable('.table-unit-conversions', 
        '<?php echo admin_url('warehouse/get_unit_conversions_data'); ?>', 
        [0, 1, 2, 3], 
        [0, 1, 2, 3],
        'id', 
        [0, 'asc']
    );

    $('#save-conversion').on('click', function() {
        var form = $('#unit-conversion-form');
        var data = form.serialize();
        
        $.post(form.attr('action'), data)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('#conversion-modal').modal('hide');
                    conversionsTable.DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
    });

    // Actualizar la fórmula de conversión al cambiar unidades
    $('#base_unit_id, #alt_unit_id').on('change', updateConversionFormula);
    
    function updateConversionFormula() {
        var baseUnit = $('#base_unit_id option:selected').text();
        var altUnit = $('#alt_unit_id option:selected').text();
        $('#conversion-formula').text('1 ' + altUnit + ' = X ' + baseUnit);
    }
});

function editConversion(id) {
    $.get('<?php echo admin_url('warehouse/get_unit_conversion/'); ?>' + id, function(response) {
        var data = JSON.parse(response);
        $('#conversion_id').val(data.id);
        $('#item_id').selectpicker('val', data.item_id);
        $('#base_unit_id').selectpicker('val', data.base_unit_id);
        $('#alt_unit_id').selectpicker('val', data.alt_unit_id);
        $('#conversion_rate').val(data.conversion_rate);
        updateConversionFormula();
        $('#conversion-modal').modal('show');
    });
}

function deleteConversion(id) {
    if (confirm(app.lang.confirm_action_prompt)) {
        $.post('<?php echo admin_url('warehouse/delete_unit_conversion/'); ?>' + id)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('.table-unit-conversions').DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
    }
}
</script>
