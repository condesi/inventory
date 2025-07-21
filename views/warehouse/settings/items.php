<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <?php echo form_open('warehouse/settings/save_items', ['id' => 'items-settings-form']); ?>
                
                <h4 class="no-margin"><?php echo _l('items_settings'); ?></h4>
                <hr class="hr-panel-separator" />

                <!-- Configuración General de Items -->
                <div class="row">
                    <div class="col-md-6">
                        <h4><?php echo _l('general_settings'); ?></h4>
                        
                        <?php echo render_yes_no_option('enable_items_approval', _l('enable_items_approval')); ?>
                        <?php echo render_yes_no_option('track_variants', _l('track_variants')); ?>
                        <?php echo render_yes_no_option('track_serial_numbers', _l('track_serial_numbers')); ?>
                        <?php echo render_yes_no_option('track_batches', _l('track_batches')); ?>
                        <?php echo render_yes_no_option('require_image', _l('require_image')); ?>
                        <?php echo render_yes_no_option('show_out_of_stock', _l('show_out_of_stock')); ?>
                        <?php echo render_yes_no_option('allow_negative_stock', _l('allow_negative_stock')); ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h4><?php echo _l('codes_settings'); ?></h4>
                        
                        <?php echo render_input('item_code_prefix', 'item_code_prefix', get_option('item_code_prefix')); ?>
                        <?php echo render_yes_no_option('auto_generate_code', _l('auto_generate_code')); ?>
                        
                        <div class="form-group">
                            <label><?php echo _l('code_format'); ?></label>
                            <?php
                            $formats = [
                                'PREFIX-{CATEGORY}-{NUMBER}' => _l('format_prefix_category_number'),
                                'PREFIX-{NUMBER}' => _l('format_prefix_number'),
                                '{CATEGORY}-{NUMBER}' => _l('format_category_number'),
                                '{NUMBER}' => _l('format_number_only')
                            ];
                            echo render_select('code_format', $formats, ['id', 'name'], '', get_option('item_code_format'));
                            ?>
                        </div>
                        
                        <?php echo render_input('code_number_length', 'code_number_length', get_option('item_code_length'), 'number'); ?>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Configuración de Campos -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('fields_settings'); ?></h4>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><?php echo _l('required_fields'); ?></h5>
                        <?php
                        $required_fields = [
                            'code' => _l('code'),
                            'name' => _l('name'),
                            'category' => _l('category'),
                            'unit' => _l('unit'),
                            'purchase_price' => _l('purchase_price'),
                            'selling_price' => _l('selling_price'),
                            'tax' => _l('tax'),
                            'description' => _l('description'),
                            'minimum_stock' => _l('minimum_stock')
                        ];
                        $selected_required = json_decode(get_option('item_required_fields'));
                        foreach($required_fields as $field => $label) {
                            echo '<div class="checkbox checkbox-primary">';
                            echo '<input type="checkbox" name="required_fields[]" value="'.$field.'" '.
                                (in_array($field, $selected_required) ? 'checked' : '').'>';
                            echo '<label>'.$label.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><?php echo _l('visible_fields'); ?></h5>
                        <?php
                        $visible_fields = [
                            'image' => _l('image'),
                            'barcode' => _l('barcode'),
                            'manufacturer' => _l('manufacturer'),
                            'model' => _l('model'),
                            'brand' => _l('brand'),
                            'dimensions' => _l('dimensions'),
                            'weight' => _l('weight'),
                            'warranty' => _l('warranty'),
                            'notes' => _l('notes')
                        ];
                        $selected_visible = json_decode(get_option('item_visible_fields'));
                        foreach($visible_fields as $field => $label) {
                            echo '<div class="checkbox checkbox-primary">';
                            echo '<input type="checkbox" name="visible_fields[]" value="'.$field.'" '.
                                (in_array($field, $selected_visible) ? 'checked' : '').'>';
                            echo '<label>'.$label.'</label>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Configuración de Variantes -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('variants_settings'); ?></h4>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="form-group">
                            <label><?php echo _l('variant_attributes'); ?></label>
                            <div class="input-group">
                                <input type="text" name="new_attribute" class="form-control" placeholder="<?php echo _l('new_attribute'); ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-info" type="button" onclick="addAttribute()">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('attribute'); ?></th>
                                        <th><?php echo _l('values'); ?></th>
                                        <th width="10%"><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="attributes_table">
                                    <?php
                                    $attributes = json_decode(get_option('item_attributes'));
                                    if($attributes) {
                                        foreach($attributes as $attr) {
                                            echo '<tr>';
                                            echo '<td>'.$attr->name.'</td>';
                                            echo '<td>'.implode(', ', $attr->values).'</td>';
                                            echo '<td><button type="button" class="btn btn-danger btn-xs" onclick="removeAttribute(this)"><i class="fa fa-remove"></i></button></td>';
                                            echo '</tr>';
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <hr class="hr-panel-separator" />

                <!-- Configuración de Precios -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><?php echo _l('pricing_settings'); ?></h4>
                    </div>
                    
                    <div class="col-md-6">
                        <?php echo render_yes_no_option('enable_wholesale_price', _l('enable_wholesale_price')); ?>
                        <?php echo render_yes_no_option('enable_discount_levels', _l('enable_discount_levels')); ?>
                        <?php echo render_yes_no_option('enable_price_groups', _l('enable_price_groups')); ?>
                        <?php echo render_yes_no_option('show_purchase_price', _l('show_purchase_price')); ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php echo render_input('default_profit_margin', 'default_profit_margin', get_option('default_profit_margin'), 'number'); ?>
                        <?php echo render_input('wholesale_quantity', 'wholesale_quantity', get_option('wholesale_quantity'), 'number'); ?>
                        <?php echo render_input('maximum_discount', 'maximum_discount', get_option('maximum_discount'), 'number'); ?>
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
    appValidateForm($('#items-settings-form'), {
        code_number_length: 'required',
        default_profit_margin: 'required',
        wholesale_quantity: 'required',
        maximum_discount: 'required'
    });
    
    // Mostrar/ocultar campos relacionados
    $('input[name="track_variants"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.variants-settings').show();
        } else {
            $('.variants-settings').hide();
        }
    }).trigger('change');
});

// Agregar atributo
function addAttribute() {
    var attribute = $('input[name="new_attribute"]').val();
    if (!attribute) return;
    
    var html = `
        <tr>
            <td>${attribute}</td>
            <td>
                <input type="text" name="attributes[${attribute}]" class="form-control" 
                       placeholder="<?php echo _l('comma_separated_values'); ?>">
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-xs" onclick="removeAttribute(this)">
                    <i class="fa fa-remove"></i>
                </button>
            </td>
        </tr>
    `;
    
    $('#attributes_table').append(html);
    $('input[name="new_attribute"]').val('');
}

// Eliminar atributo
function removeAttribute(btn) {
    $(btn).closest('tr').remove();
}

// Actualizar ejemplo de código
function updateCodeExample() {
    var prefix = $('input[name="item_code_prefix"]').val();
    var format = $('select[name="code_format"]').val();
    var length = $('input[name="code_number_length"]').val();
    
    var example = format
        .replace('{PREFIX}', prefix)
        .replace('{CATEGORY}', 'CAT')
        .replace('{NUMBER}', '0'.repeat(length));
        
    $('.code-example').text(example);
}
</script>
