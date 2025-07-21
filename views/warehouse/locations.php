<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin"><?php echo _l('warehouse_locations'); ?></h4>
        <hr class="hr-panel-heading" />
        
        <div class="row">
            <div class="col-md-12">
                <a href="#" class="btn btn-info pull-left" data-toggle="modal" data-target="#location-modal">
                    <i class="fa fa-plus"></i> <?php echo _l('add_location'); ?>
                </a>
            </div>
        </div>
        
        <div class="row mtop15">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-warehouse-locations">
                        <thead>
                            <tr>
                                <th><?php echo _l('warehouse_id'); ?></th>
                                <th><?php echo _l('location_code'); ?></th>
                                <th><?php echo _l('description'); ?></th>
                                <th><?php echo _l('aisle'); ?></th>
                                <th><?php echo _l('rack'); ?></th>
                                <th><?php echo _l('bin'); ?></th>
                                <th><?php echo _l('location_capacity'); ?></th>
                                <th><?php echo _l('status'); ?></th>
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

        <!-- Modal de Ubicación -->
        <div class="modal fade" id="location-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo _l('warehouse_location'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open(admin_url('warehouse/save_location'), ['id'=>'warehouse-location-form']); ?>
                        <input type="hidden" name="id" id="location_id">
                        
                        <div class="form-group">
                            <label for="warehouse_id"><?php echo _l('warehouse'); ?></label>
                            <select name="warehouse_id" id="warehouse_id" class="form-control selectpicker" data-live-search="true">
                                <?php foreach($warehouses as $warehouse) { ?>
                                    <option value="<?php echo $warehouse['id']; ?>"><?php echo $warehouse['warehouse_name']; ?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location_code"><?php echo _l('location_code'); ?></label>
                                    <input type="text" name="location_code" id="location_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capacity"><?php echo _l('location_capacity'); ?></label>
                                    <input type="number" name="capacity" id="capacity" class="form-control" min="0" step="0.01">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="aisle"><?php echo _l('aisle'); ?></label>
                                    <input type="text" name="aisle" id="aisle" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="rack"><?php echo _l('rack'); ?></label>
                                    <input type="text" name="rack" id="rack" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bin"><?php echo _l('bin'); ?></label>
                                    <input type="text" name="bin" id="bin" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><?php echo _l('description'); ?></label>
                            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status"><?php echo _l('status'); ?></label>
                            <select name="status" id="status" class="form-control">
                                <option value="active"><?php echo _l('location_active'); ?></option>
                                <option value="inactive"><?php echo _l('location_inactive'); ?></option>
                                <option value="full"><?php echo _l('location_full'); ?></option>
                            </select>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-location"><?php echo _l('save'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var locationsTable = initDataTable('.table-warehouse-locations', 
        '<?php echo admin_url('warehouse/get_locations_data'); ?>', 
        [0, 1, 2, 3, 4, 5, 6, 7], 
        [0, 1, 2, 3, 4, 5, 6, 7],
        'id', 
        [1, 'asc']
    );

    $('#save-location').on('click', function() {
        var form = $('#warehouse-location-form');
        var data = form.serialize();
        
        $.post(form.attr('action'), data)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('#location-modal').modal('hide');
                    locationsTable.DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
    });
});

function editLocation(id) {
    $.get('<?php echo admin_url('warehouse/get_location/'); ?>' + id, function(response) {
        var data = JSON.parse(response);
        $('#location_id').val(data.id);
        $('#warehouse_id').selectpicker('val', data.warehouse_id);
        $('#location_code').val(data.location_code);
        $('#description').val(data.description);
        $('#aisle').val(data.aisle);
        $('#rack').val(data.rack);
        $('#bin').val(data.bin);
        $('#capacity').val(data.capacity);
        $('#status').val(data.status);
        $('#location-modal').modal('show');
    });
}

function deleteLocation(id) {
    if (confirm(app.lang.confirm_action_prompt)) {
        $.post('<?php echo admin_url('warehouse/delete_location/'); ?>' + id)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('.table-warehouse-locations').DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
    }
}
</script>
