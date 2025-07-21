<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <div class="_buttons">
                    <?php if (has_permission('wh_stock_import', '', 'create')) { ?>
                    <a href="<?php echo admin_url('warehouse/stock_receipts/receipt'); ?>" class="btn btn-info pull-left display-block">
                        <?php echo _l('new_stock_receipt'); ?>
                    </a>
                    <?php } ?>
                </div>
                <div class="clearfix"></div>
                <hr class="hr-panel-heading" />
                
                <!-- Filtros -->
                <div class="row">
                    <div class="col-md-3">
                        <?php echo render_date_input('date_from','period_start'); ?>
                    </div>
                    <div class="col-md-3">
                        <?php echo render_date_input('date_to','period_end'); ?>
                    </div>
                    <div class="col-md-3">
                        <?php echo render_select('warehouse_id', $warehouses, array('warehouse_id', 'warehouse_name'), 'warehouse'); ?>
                    </div>
                    <div class="col-md-3">
                        <?php
                        $statuses = [
                            ['id' => '0', 'name' => _l('pending_approval')],
                            ['id' => '1', 'name' => _l('approved')],
                            ['id' => '2', 'name' => _l('rejected')]
                        ];
                        echo render_select('status', $statuses, array('id', 'name'), 'status');
                        ?>
                    </div>
                </div>
                
                <div class="clearfix"></div>
                <hr class="hr-panel-heading" />

                <?php render_datatable(array(
                    _l('receipt_number'),
                    _l('date'),
                    _l('warehouse'),
                    _l('supplier'),
                    _l('total'),
                    _l('created_by'),
                    _l('status'),
                    _l('options')
                ),'stock-receipts'); ?>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    initDataTable('.table-stock-receipts', window.location.href, [0], [0], undefined, [1, 'desc']);

    // Filtros
    var receiptsServerParams = {};
    
    receiptsServerParams['date_from'] = '[name="date_from"]';
    receiptsServerParams['date_to'] = '[name="date_to"]';
    receiptsServerParams['warehouse_id'] = '[name="warehouse_id"]';
    receiptsServerParams['status'] = '[name="status"]';

    $('.table-stock-receipts').DataTable().on('draw', function() {
        init_selectpicker();
        init_datepicker();
        init_form_validation();
    });

    // Eventos de filtro
    $('select[name="warehouse_id"]').on('change', function() {
        $('.table-stock-receipts').DataTable().ajax.reload();
    });

    $('select[name="status"]').on('change', function() {
        $('.table-stock-receipts').DataTable().ajax.reload();
    });

    $('input[name="date_from"],input[name="date_to"]').on('change', function() {
        $('.table-stock-receipts').DataTable().ajax.reload();
    });
});
</script>
