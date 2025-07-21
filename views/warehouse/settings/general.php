<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open('warehouse/settings/save_general', ['id' => 'warehouse-settings-form']); ?>
                
                <h4 class="no-margin"><?php echo _l('warehouse_settings'); ?></h4>
                <hr class="hr-panel-separator" />

                <!-- Configuraciones Generales -->
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo _l('general_settings'); ?></h4>
                        
                        <?php echo render_yes_no_option('allow_negative_stock', _l('allow_negative_stock')); ?>
                        <?php echo render_yes_no_option('enable_lot_number', _l('enable_lot_number')); ?>
                        <?php echo render_yes_no_option('enable_expiry_date', _l('enable_expiry_date')); ?>
                        <?php echo render_yes_no_option('enable_serial_number', _l('enable_serial_number')); ?>
                        <?php echo render_yes_no_option('require_reference_doc', _l('require_reference_doc')); ?>
                        <?php echo render_yes_no_option('auto_create_purchase_request', _l('auto_create_purchase_request')); ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h4><?php echo _l('notifications_settings'); ?></h4>
                        
                        <?php echo render_yes_no_option('notify_low_stock', _l('notify_low_stock')); ?>
                        <?php echo render_yes_no_option('notify_expiring_items', _l('notify_expiring_items')); ?>
                        <?php echo render_input('low_stock_threshold', 'low_stock_threshold', get_option('low_stock_threshold'), 'number'); ?>
                        <?php echo render_input('expiry_notice_days', 'expiry_notice_days', get_option('expiry_notice_days'), 'number'); ?>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Numeración de Documentos -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('document_numbering'); ?></h4>
                    </div>
                    
                    <div class="col-md-6">
                        <?php echo render_input('goods_receipt_prefix', 'goods_receipt_prefix', get_option('goods_receipt_prefix')); ?>
                        <?php echo render_input('goods_delivery_prefix', 'goods_delivery_prefix', get_option('goods_delivery_prefix')); ?>
                        <?php echo render_input('internal_delivery_prefix', 'internal_delivery_prefix', get_option('internal_delivery_prefix')); ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php echo render_input('stock_transfer_prefix', 'stock_transfer_prefix', get_option('stock_transfer_prefix')); ?>
                        <?php echo render_input('stock_adjustment_prefix', 'stock_adjustment_prefix', get_option('stock_adjustment_prefix')); ?>
                        <?php echo render_yes_no_option('auto_increment_document', _l('auto_increment_document')); ?>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Configuración de Aprobaciones -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('approval_settings'); ?></h4>
                    </div>
                    
                    <div class="col-md-6">
                        <?php echo render_yes_no_option('enable_goods_receipt_approval', _l('enable_goods_receipt_approval')); ?>
                        <?php echo render_yes_no_option('enable_goods_delivery_approval', _l('enable_goods_delivery_approval')); ?>
                        <?php echo render_yes_no_option('enable_internal_delivery_approval', _l('enable_internal_delivery_approval')); ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php
                        $roles = $this->roles_model->get();
                        $selected_roles = json_decode(get_option('warehouse_approval_roles'));
                        echo render_select('approval_roles[]', $roles, ['roleid', 'name'], 'approval_roles', $selected_roles, ['multiple' => true]);
                        ?>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Configuración de Columnas -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('column_settings'); ?></h4>
                        
                        <div class="form-group">
                            <label><?php echo _l('visible_columns_goods_receipt'); ?></label>
                            <?php
                            $receipt_columns = [
                                'item_code' => _l('item_code'),
                                'description' => _l('description'),
                                'unit' => _l('unit'),
                                'quantity' => _l('quantity'),
                                'unit_price' => _l('unit_price'),
                                'tax' => _l('tax'),
                                'discount' => _l('discount'),
                                'lot_number' => _l('lot_number'),
                                'expiry_date' => _l('expiry_date'),
                                'serial_number' => _l('serial_number'),
                                'total' => _l('total')
                            ];
                            $selected_receipt_columns = json_decode(get_option('visible_columns_goods_receipt'));
                            foreach($receipt_columns as $key => $value) {
                                echo '<div class="checkbox checkbox-primary">';
                                echo '<input type="checkbox" name="visible_columns_goods_receipt[]" value="'.$key.'" '.
                                    (in_array($key, $selected_receipt_columns) ? 'checked' : '').'>';
                                echo '<label>'.$value.'</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary pull-right">
                            <?php echo _l('save'); ?>
                        </button>
                    </div>
                </div>
                
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    appValidateForm($('#warehouse-settings-form'), {
        low_stock_threshold: 'required',
        expiry_notice_days: 'required',
        'approval_roles[]': 'required'
    });
    
    // Mostrar/ocultar campos relacionados
    $('input[name="enable_lot_number"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('input[name="enable_expiry_date"]').closest('.form-group').show();
        } else {
            $('input[name="enable_expiry_date"]').prop('checked', false).closest('.form-group').hide();
        }
    });
    
    // Actualizar numeración de ejemplo
    $('input[name$="_prefix"]').on('keyup', function() {
        var prefix = $(this).val();
        var type = $(this).attr('name').replace('_prefix', '');
        var example = prefix + formatNumber(1);
        $('.' + type + '_example').text(example);
    });
});
</script>
