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
                                    <?php echo _l('inventory_reports'); ?>
                                </h4>
                                <hr class="hr-panel-separator" />
                            </div>
                        </div>

                        <div class="row">
                            <!-- Panel de Movimientos -->
                            <div class="col-md-6">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo _l('inventory_movements_report'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><?php echo _l('inventory_movements_description'); ?></p>
                                        <a href="<?php echo admin_url('warehouse/inventory_reports/movements'); ?>" class="btn btn-info">
                                            <?php echo _l('view_report'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de Valorización -->
                            <div class="col-md-6">
                                <div class="panel panel-success">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo _l('inventory_valuation_report'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <p><?php echo _l('inventory_valuation_description'); ?></p>
                                        <a href="<?php echo admin_url('warehouse/inventory_reports/valuation'); ?>" class="btn btn-success">
                                            <?php echo _l('view_report'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mtop20">
                            <!-- Panel de Movimientos por Producto -->
                            <div class="col-md-6">
                                <div class="panel panel-warning">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo _l('item_movements_report'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <?php echo form_open(admin_url('warehouse/inventory_reports/item_movements'), ['method' => 'get']); ?>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="item_id"><?php echo _l('select_item'); ?></label>
                                                    <select name="item_id" id="item_id" class="selectpicker" data-live-search="true" required>
                                                        <option value=""><?php echo _l('select_item'); ?></option>
                                                        <?php foreach($items as $item) { ?>
                                                            <option value="<?php echo $item['id']; ?>"><?php echo $item['description']; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-warning">
                                                    <?php echo _l('view_report'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <?php echo form_close(); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel de Alertas de Stock -->
                            <div class="col-md-6">
                                <div class="panel panel-danger">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo _l('stock_alerts_report'); ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><?php echo _l('item'); ?></th>
                                                    <th><?php echo _l('warehouse'); ?></th>
                                                    <th><?php echo _l('current_stock'); ?></th>
                                                    <th><?php echo _l('minimum_stock'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($stock_alerts as $alert) { ?>
                                                    <tr class="danger">
                                                        <td><?php echo $alert['item_name']; ?></td>
                                                        <td><?php echo $alert['warehouse_name']; ?></td>
                                                        <td><?php echo $alert['current_stock']; ?></td>
                                                        <td><?php echo $alert['minimum_stock']; ?></td>
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
</div>

<?php init_tail(); ?>

<script>
$(function() {
    // Inicializar selectpicker
    $('.selectpicker').selectpicker();
    
    // Actualizar alertas automáticamente
    function updateStockAlerts() {
        $.get(admin_url + 'warehouse/inventory_reports/get_stock_alerts', function(response) {
            $('#stock_alerts_table tbody').html(response);
        });
    }
    
    // Actualizar cada 5 minutos
    setInterval(updateStockAlerts, 300000);
});
</script>
