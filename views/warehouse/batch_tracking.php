<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin"><?php echo _l('batch_tracking'); ?></h4>
        <hr class="hr-panel-heading" />
        
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-batch-tracking">
                        <thead>
                            <tr>
                                <th><?php echo _l('batch_number'); ?></th>
                                <th><?php echo _l('commodity_name'); ?></th>
                                <th><?php echo _l('warehouse'); ?></th>
                                <th><?php echo _l('warehouse_location'); ?></th>
                                <th><?php echo _l('quantity'); ?></th>
                                <th><?php echo _l('manufacturing_date'); ?></th>
                                <th><?php echo _l('expiry_date'); ?></th>
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

        <!-- Modal de Detalles del Lote -->
        <div class="modal fade" id="batch-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo _l('batch_tracking'); ?> - <span id="batch-number-display"></span></h4>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#tab_info" aria-controls="tab_info" role="tab" data-toggle="tab">
                                    <?php echo _l('infor_detail'); ?>
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab_movements" aria-controls="tab_movements" role="tab" data-toggle="tab">
                                    <?php echo _l('stock_movement_history'); ?>
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#tab_quality" aria-controls="tab_quality" role="tab" data-toggle="tab">
                                    <?php echo _l('quality_control'); ?>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div role="tabpanel" class="tab-pane active" id="tab_info">
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="table table-striped">
                                            <tr>
                                                <td><strong><?php echo _l('commodity_name'); ?>:</strong></td>
                                                <td id="item-name"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('batch_number'); ?>:</strong></td>
                                                <td id="batch-number"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('manufacturing_date'); ?>:</strong></td>
                                                <td id="manufacturing-date"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('expiry_date'); ?>:</strong></td>
                                                <td id="expiry-date"></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-striped">
                                            <tr>
                                                <td><strong><?php echo _l('quantity'); ?>:</strong></td>
                                                <td id="quantity"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('warehouse'); ?>:</strong></td>
                                                <td id="warehouse"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('warehouse_location'); ?>:</strong></td>
                                                <td id="location"></td>
                                            </tr>
                                            <tr>
                                                <td><strong><?php echo _l('status'); ?>:</strong></td>
                                                <td id="batch-status"></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div role="tabpanel" class="tab-pane" id="tab_movements">
                                <div class="table-responsive">
                                    <table class="table table-batch-movements">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('date'); ?></th>
                                                <th><?php echo _l('movement_type'); ?></th>
                                                <th><?php echo _l('quantity'); ?></th>
                                                <th><?php echo _l('movement_reference'); ?></th>
                                                <th><?php echo _l('movement_notes'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se llenará dinámicamente con JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div role="tabpanel" class="tab-pane" id="tab_quality">
                                <div class="table-responsive">
                                    <table class="table table-batch-quality">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('date'); ?></th>
                                                <th><?php echo _l('quality_status'); ?></th>
                                                <th><?php echo _l('inspector'); ?></th>
                                                <th><?php echo _l('inspection_note'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Se llenará dinámicamente con JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var batchTable = initDataTable('.table-batch-tracking', 
        '<?php echo admin_url('warehouse/get_batch_tracking_data'); ?>', 
        [0, 1, 2, 3, 4, 5, 6, 7], 
        [0, 1, 2, 3, 4, 5, 6, 7],
        'batch_number', 
        [5, 'desc']
    );

    $('#batch-modal').on('show.bs.modal', function() {
        initBatchMovementsTable();
        initBatchQualityTable();
    });
});

function viewBatchDetails(batchNumber) {
    $.get('<?php echo admin_url('warehouse/get_batch_details/'); ?>' + batchNumber, function(response) {
        var data = JSON.parse(response);
        
        // Actualizar la información del lote
        $('#batch-number-display').text(data.batch_number);
        $('#item-name').text(data.item_name);
        $('#batch-number').text(data.batch_number);
        $('#manufacturing-date').text(data.manufacturing_date);
        $('#expiry-date').text(data.expiry_date);
        $('#quantity').text(data.quantity);
        $('#warehouse').text(data.warehouse);
        $('#location').text(data.location);
        $('#batch-status').text(data.status);
        
        $('#batch-modal').modal('show');
    });
}

function initBatchMovementsTable() {
    var batchNumber = $('#batch-number').text();
    if ($.fn.DataTable.isDataTable('.table-batch-movements')) {
        $('.table-batch-movements').DataTable().destroy();
    }
    
    $('.table-batch-movements').DataTable({
        "ajax": {
            "url": "<?php echo admin_url('warehouse/get_batch_movements/'); ?>" + batchNumber,
            "type": "GET"
        },
        "columns": [
            {"data": "date"},
            {"data": "type"},
            {"data": "quantity"},
            {"data": "reference"},
            {"data": "notes"}
        ],
        "order": [[0, "desc"]]
    });
}

function initBatchQualityTable() {
    var batchNumber = $('#batch-number').text();
    if ($.fn.DataTable.isDataTable('.table-batch-quality')) {
        $('.table-batch-quality').DataTable().destroy();
    }
    
    $('.table-batch-quality').DataTable({
        "ajax": {
            "url": "<?php echo admin_url('warehouse/get_batch_quality/'); ?>" + batchNumber,
            "type": "GET"
        },
        "columns": [
            {"data": "date"},
            {"data": "status"},
            {"data": "inspector"},
            {"data": "notes"}
        ],
        "order": [[0, "desc"]]
    });
}
</script>
