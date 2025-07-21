<?php init_head(); ?>

<div id="wrapper">
        <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <?php $this->load->view('receipts/quick_item_modal'); ?>
                        <?php echo form_open($this->uri->uri_string(), array('id'=>'stock-receipt-form')); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Información del encabezado -->
                                <?php echo render_select('warehouse_id', $warehouses, array('warehouse_id', 'warehouse_name'), 'warehouse', '', array('required' => true)); ?>
                                
                                <?php echo render_date_input('date_add','date', date('Y-m-d'), array('required' => true)); ?>
                                
                                <?php echo render_select('supplier_id', 
                                    get_suppliers(), 
                                    array('id', 'company'), 
                                    'supplier'
                                ); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_textarea('description','description'); ?>
                            </div>
                        </div>

                        <hr class="hr-panel-heading" />

                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table items table-main-receipt-edit has-calculations">
                                        <thead>
                                            <tr>
                                                <th></th>
                                                <th width="20%" class="required"><?php echo _l('item'); ?></th>
                                                <th width="10%" class="required"><?php echo _l('qty'); ?></th>
                                                <th width="10%" class="required"><?php echo _l('unit'); ?></th>
                                                <th width="15%" class="required"><?php echo _l('unit_price'); ?></th>
                                                <th width="10%"><?php echo _l('tax'); ?></th>
                                                <th width="10%"><?php echo _l('tax_money'); ?></th>
                                                <th width="10%"><?php echo _l('total'); ?></th>
                                                <th align="center"><i class="fa fa-cog"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="main">
                                                <td></td>
                                                <td>
                                                    <div class="form-group">
                                                        <div class="input-group">
                                                            <input type="text" name="item_search" class="form-control item-search" placeholder="<?php echo _l('search_item'); ?>">
                                                            <span class="input-group-addon">
                                                                <a href="#" data-toggle="modal" data-target="#item_selector">
                                                                    <i class="fa fa-search"></i>
                                                                </a>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="number" name="quantity" class="form-control" min="0">
                                                </td>
                                                <td>
                                                    <select name="unit_id" class="form-control selectpicker">
                                                        <?php foreach($units as $unit) { ?>
                                                        <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="unit_price" class="form-control" min="0" step="0.01">
                                                </td>
                                                <td>
                                                    <select name="tax" class="form-control selectpicker">
                                                        <option value=""><?php echo _l('no_tax'); ?></option>
                                                        <?php foreach($taxes as $tax) { ?>
                                                        <option value="<?php echo $tax['id']; ?>" data-percent="<?php echo $tax['taxrate']; ?>">
                                                            <?php echo $tax['name'] . ' (' . $tax['taxrate'] . '%)'; ?>
                                                        </option>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="tax_money" class="form-control" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" name="total" class="form-control" readonly>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-success pull-right" onclick="add_item_to_table();">
                                                        <i class="fa fa-check"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8 col-md-offset-4">
                                <table class="table text-right">
                                    <tbody>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('subtotal'); ?></span></td>
                                            <td class="subtotal">0.00</td>
                                        </tr>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('total_tax'); ?></span></td>
                                            <td class="total_tax">0.00</td>
                                        </tr>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('total'); ?></span></td>
                                            <td class="total">0.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <hr class="hr-panel-heading" />

                        <div class="row">
                            <div class="col-md-12">
                                <div class="pull-right">
                                    <button type="submit" class="btn btn-info save-receipt">
                                        <?php echo _l('submit'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
// Funciones para cálculos y manipulación de items
function add_item_to_table() {
    // Validar datos requeridos
    if (!$('select[name="warehouse_id"]').val()) {
        alert_float('warning', '<?php echo _l("please_select_warehouse"); ?>');
        return;
    }

    var item = {
        warehouse_id: $('select[name="warehouse_id"]').val(),
        item_id: $('input[name="item_id"]').val(),
        description: $('input[name="item_search"]').val(),
        quantity: $('input[name="quantity"]').val(),
        unit_id: $('select[name="unit_id"]').val(),
        unit_name: $('select[name="unit_id"] option:selected').text(),
        unit_price: $('input[name="unit_price"]').val(),
        tax: $('select[name="tax"]').val(),
        tax_name: $('select[name="tax"] option:selected').text(),
        tax_percent: $('select[name="tax"] option:selected').data('percent') || 0,
        tax_money: $('input[name="tax_money"]').val(),
        total: $('input[name="total"]').val()
    };

    // Validar datos
    if (!item.item_id || !item.quantity || !item.unit_price) {
        alert_float('warning', '<?php echo _l("please_fill_required_fields"); ?>');
        return;
    }

    // Agregar fila a la tabla
    var row = '<tr class="item">';
    row += '<td><input type="hidden" name="items[' + item_index + '][item_id]" value="' + item.item_id + '"></td>';
    row += '<td>' + item.description + '</td>';
    row += '<td>' + item.quantity + '<input type="hidden" name="items[' + item_index + '][quantity]" value="' + item.quantity + '"></td>';
    row += '<td>' + item.unit_name + '<input type="hidden" name="items[' + item_index + '][unit_id]" value="' + item.unit_id + '"></td>';
    row += '<td>' + item.unit_price + '<input type="hidden" name="items[' + item_index + '][unit_price]" value="' + item.unit_price + '"></td>';
    row += '<td>' + item.tax_name + '<input type="hidden" name="items[' + item_index + '][tax]" value="' + item.tax + '"></td>';
    row += '<td>' + item.tax_money + '<input type="hidden" name="items[' + item_index + '][tax_money]" value="' + item.tax_money + '"></td>';
    row += '<td>' + item.total + '<input type="hidden" name="items[' + item_index + '][total]" value="' + item.total + '"></td>';
    row += '<td><button type="button" class="btn btn-danger" onclick="remove_item(this);"><i class="fa fa-trash"></i></button></td>';
    row += '</tr>';

    $('.table-main-receipt-edit tbody').append(row);
    item_index++;

    // Limpiar campos
    clear_item_fields();
    
    // Recalcular totales
    calculate_total();
}

function remove_item(btn) {
    $(btn).closest('tr').remove();
    calculate_total();
}

function clear_item_fields() {
    $('input[name="item_id"]').val('');
    $('input[name="item_search"]').val('');
    $('input[name="quantity"]').val('');
    $('input[name="unit_price"]').val('');
    $('select[name="tax"]').val('').change();
    $('input[name="tax_money"]').val('');
    $('input[name="total"]').val('');
}

function calculate_total() {
    var subtotal = 0;
    var total_tax = 0;
    var total = 0;

    $('.table-main-receipt-edit tbody tr.item').each(function() {
        var row_total = parseFloat($(this).find('input[name$="[total]"]').val()) || 0;
        var row_tax = parseFloat($(this).find('input[name$="[tax_money]"]').val()) || 0;

        subtotal += row_total - row_tax;
        total_tax += row_tax;
        total += row_total;
    });

    $('.subtotal').html(format_money(subtotal));
    $('.total_tax').html(format_money(total_tax));
    $('.total').html(format_money(total));
}

// Inicialización
var item_index = 0;

$(function() {
    // Validación del formulario
    appValidateForm($('#stock-receipt-form'), {
        warehouse_id: 'required',
        date_add: 'required'
    });

    // Búsqueda de items
    $('input[name="item_search"]').on('keyup', function() {
        var q = $(this).val();
        if (q) {
            $.get(admin_url + 'warehouse/stock_receipts/search_items', {q: q}, function(response) {
                if (response.success) {
                    var items = response.items;
                    var html = '';
                    for (var i in items) {
                        html += '<div class="item-search-result" data-id="' + items[i].id + '">';
                        html += items[i].description;
                        html += '</div>';
                    }
                    $('#item_search_results').html(html);
                }
            }, 'json');
        }
    });

    // Cálculos automáticos
    $('input[name="quantity"], input[name="unit_price"], select[name="tax"]').on('change', function() {
        var quantity = parseFloat($('input[name="quantity"]').val()) || 0;
        var unit_price = parseFloat($('input[name="unit_price"]').val()) || 0;
        var tax_percent = parseFloat($('select[name="tax"] option:selected').data('percent')) || 0;

        var subtotal = quantity * unit_price;
        var tax_money = (subtotal * tax_percent) / 100;
        var total = subtotal + tax_money;

        $('input[name="tax_money"]').val(format_money(tax_money));
        $('input[name="total"]').val(format_money(total));
    });
});
</script>
