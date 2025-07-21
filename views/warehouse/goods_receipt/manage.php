<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('warehouse/manage_goods_receipt/save'), ['id'=>'goods-receipt-form']); ?>
                        
                        <!-- Header Section -->
                        <div class="row">
                            <div class="col-md-6">
                                <h4><?php echo _l('goods_receipt_entry'); ?></h4>
                            </div>
                            <div class="col-md-6 text-right">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                        <?php echo _l('column_settings'); ?> <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu column-settings-menu pull-right">
                                        <?php foreach($available_columns as $key => $column): ?>
                                        <li>
                                            <label>
                                                <input type="checkbox" 
                                                       name="visible_columns[]" 
                                                       value="<?php echo $key; ?>"
                                                       <?php echo ($column['visible'] ? 'checked' : ''); ?>
                                                       <?php echo ($column['required'] ? 'disabled' : ''); ?>>
                                                <?php echo $column['label']; ?>
                                            </label>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="hr-panel-separator" />
                        
                        <!-- Document Info -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="receipt_number"><?php echo _l('receipt_number'); ?></label>
                                    <input type="text" class="form-control" id="receipt_number" name="receipt_number" value="<?php echo $receipt_number; ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="accounting_date" class="required"><?php echo _l('accounting_date'); ?></label>
                                    <input type="text" class="form-control datepicker" id="accounting_date" name="accounting_date" value="<?php echo _d(date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="document_date" class="required"><?php echo _l('document_date'); ?></label>
                                    <input type="text" class="form-control datepicker" id="document_date" name="document_date" value="<?php echo _d(date('Y-m-d')); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="supplier_id" class="required"><?php echo _l('supplier'); ?></label>
                                    <select class="form-control selectpicker" id="supplier_id" name="supplier_id" data-live-search="true" required>
                                        <option value=""><?php echo _l('select_supplier'); ?></option>
                                        <?php foreach($suppliers as $supplier): ?>
                                        <option value="<?php echo $supplier['id']; ?>"><?php echo $supplier['company']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="warehouse_id" class="required"><?php echo _l('warehouse'); ?></label>
                                    <select class="form-control selectpicker" id="warehouse_id" name="warehouse_id" data-live-search="true" required>
                                        <option value=""><?php echo _l('select_warehouse'); ?></option>
                                        <?php foreach($warehouses as $warehouse): ?>
                                        <option value="<?php echo $warehouse['id']; ?>"><?php echo $warehouse['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="reference"><?php echo _l('reference_document'); ?></label>
                                    <input type="text" class="form-control" id="reference" name="reference" <?php echo ($receipt_settings['require_reference_doc'] ? 'required' : ''); ?>>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Items Table -->
                        <div class="table-responsive">
                            <table class="table items table-bordered table-hover" id="receiptItems">
                                <thead>
                                    <tr>
                                        <th class="required"><?php echo _l('item'); ?></th>
                                        <th class="required"><?php echo _l('warehouse'); ?></th>
                                        <th class="required"><?php echo _l('quantity'); ?></th>
                                        <th class="required"><?php echo _l('unit_price'); ?></th>
                                        <?php if($receipt_settings['enable_lots']): ?>
                                        <th><?php echo _l('lot_number'); ?></th>
                                        <?php endif; ?>
                                        <?php if($receipt_settings['enable_manufacturing_dates']): ?>
                                        <th><?php echo _l('manufacture_date'); ?></th>
                                        <?php endif; ?>
                                        <?php if($receipt_settings['enable_expiry_dates']): ?>
                                        <th><?php echo _l('expiry_date'); ?></th>
                                        <?php endif; ?>
                                        <th class="required"><?php echo _l('total'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="main">
                                        <td>
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <input type="text" class="form-control item-search" placeholder="<?php echo _l('search_item'); ?>">
                                                    <span class="input-group-btn">
                                                        <button class="btn btn-info" type="button" onclick="showQuickAddItem()">
                                                            <i class="fa fa-plus"></i>
                                                        </button>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-control selectpicker item-warehouse">
                                                <?php foreach($warehouses as $warehouse): ?>
                                                <option value="<?php echo $warehouse['id']; ?>"><?php echo $warehouse['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-quantity" min="1" step="any">
                                        </td>
                                        <td>
                                            <input type="number" class="form-control item-price" min="0" step="any">
                                        </td>
                                        <?php if($receipt_settings['enable_lots']): ?>
                                        <td>
                                            <input type="text" class="form-control item-lot">
                                        </td>
                                        <?php endif; ?>
                                        <?php if($receipt_settings['enable_manufacturing_dates']): ?>
                                        <td>
                                            <input type="text" class="form-control datepicker item-manufacture-date">
                                        </td>
                                        <?php endif; ?>
                                        <?php if($receipt_settings['enable_expiry_dates']): ?>
                                        <td>
                                            <input type="text" class="form-control datepicker item-expiry-date">
                                        </td>
                                        <?php endif; ?>
                                        <td>
                                            <span class="item-total">0.00</span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger" onclick="removeItem(this)">
                                                <i class="fa fa-remove"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="<?php echo count($visible_columns)-1; ?>" class="text-right">
                                            <strong><?php echo _l('total'); ?>:</strong>
                                        </td>
                                        <td>
                                            <span id="total">0.00</span>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Notes -->
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes"><?php echo _l('notes'); ?></label>
                                    <?php echo render_textarea('notes','','',['rows'=>4]); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mtop15">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-info" id="submit-btn">
                                    <?php echo _l('save'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add Item Modal -->
<div class="modal fade" id="quickAddItemModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('quick_add_item'); ?></h4>
            </div>
            <div class="modal-body">
                <form id="quick-item-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_code" class="required"><?php echo _l('item_code'); ?></label>
                                <input type="text" class="form-control" id="item_code" name="code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_name" class="required"><?php echo _l('item_name'); ?></label>
                                <input type="text" class="form-control" id="item_name" name="name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_unit" class="required"><?php echo _l('unit'); ?></label>
                                <select class="form-control selectpicker" id="item_unit" name="unit_id" required>
                                    <?php foreach($units as $unit): ?>
                                    <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_group"><?php echo _l('item_group'); ?></label>
                                <select class="form-control selectpicker" id="item_group" name="group_id">
                                    <option value=""><?php echo _l('none'); ?></option>
                                    <?php foreach($item_groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_description"><?php echo _l('description'); ?></label>
                        <textarea class="form-control" id="item_description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info" onclick="saveQuickItem()"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    // Inicializar editor HTML para notas
    init_editor('#notes', {
        toolbar: 'bold italic underline | bullist numlist | link',
        plugins: 'link lists',
        height: 100
    });
    
    // Configuración de columnas
    $('.column-settings-menu input[type="checkbox"]').change(function() {
        var column = $(this).val();
        var visible = $(this).prop('checked');
        
        if(visible) {
            $('th[data-column="' + column + '"], td[data-column="' + column + '"]').show();
        } else {
            $('th[data-column="' + column + '"], td[data-column="' + column + '"]').hide();
        }
        
        // Guardar configuración
        saveColumnSettings();
    });
    
    // Búsqueda de artículos con autocompletado
    $('.item-search').autocomplete({
        source: function(request, response) {
            $.get(admin_url + 'warehouse/manage_goods_receipt/search_items', {
                q: request.term,
                warehouse_id: $('#warehouse_id').val()
            }, function(data) {
                response(data);
            }, 'json');
        },
        minLength: 2,
        select: function(event, ui) {
            var row = $(this).closest('tr');
            addItemToRow(row, ui.item);
        }
    });
    
    // Cálculos automáticos
    $(document).on('change', '.item-quantity, .item-price', function() {
        calculateRowTotal($(this).closest('tr'));
        calculateTotal();
    });
    
    // Validación del formulario
    $('#goods-receipt-form').submit(function(e) {
        e.preventDefault();
        if(validateForm()) {
            saveReceipt();
        }
    });
});

// Funciones de manejo de items
function addItemToRow(row, item) {
    row.find('.item-search').val(item.name);
    row.find('.item-quantity').val(1);
    row.find('.item-price').val(item.last_purchase);
    row.data('item-id', item.id);
    
    calculateRowTotal(row);
    calculateTotal();
    
    // Agregar nueva fila si es necesario
    if(row.is(':last-child')) {
        addNewRow();
    }
}

function calculateRowTotal(row) {
    var quantity = parseFloat(row.find('.item-quantity').val()) || 0;
    var price = parseFloat(row.find('.item-price').val()) || 0;
    var total = quantity * price;
    
    row.find('.item-total').text(formatMoney(total));
}

function calculateTotal() {
    var total = 0;
    $('.item-total').each(function() {
        total += parseFloat($(this).text().replace(/[^0-9.-]+/g,"")) || 0;
    });
    $('#total').text(formatMoney(total));
}

// Funciones de item rápido
function showQuickAddItem() {
    $('#quickAddItemModal').modal('show');
}

function saveQuickItem() {
    var form = $('#quick-item-form');
    var data = form.serialize();
    
    $.post(admin_url + 'warehouse/manage_goods_receipt/quick_add_item', data, function(response) {
        if(response.success) {
            alert_float('success', response.message);
            $('#quickAddItemModal').modal('hide');
            
            // Agregar item a la lista
            var lastRow = $('#receiptItems tbody tr:last');
            addItemToRow(lastRow, response.item);
        } else {
            alert_float('danger', response.message);
        }
    }, 'json');
}

// Funciones de guardado
function saveColumnSettings() {
    var visible = [];
    $('.column-settings-menu input[type="checkbox"]:checked').each(function() {
        visible.push($(this).val());
    });
    
    $.post(admin_url + 'warehouse/manage_goods_receipt/save_columns_settings', {
        visible: visible
    });
}

function validateForm() {
    var valid = true;
    
    // Validar campos requeridos
    $('#goods-receipt-form [required]').each(function() {
        if(!$(this).val()) {
            valid = false;
            $(this).addClass('has-error');
        } else {
            $(this).removeClass('has-error');
        }
    });
    
    // Validar items
    if($('#receiptItems tbody tr').length < 2) {
        valid = false;
        alert_float('warning', '<?php echo _l("at_least_one_item_required"); ?>');
    }
    
    return valid;
}

function saveReceipt() {
    var items = [];
    $('#receiptItems tbody tr').each(function() {
        var row = $(this);
        var itemId = row.data('item-id');
        
        if(itemId) {
            items.push({
                item_id: itemId,
                warehouse_id: row.find('.item-warehouse').val(),
                quantity: row.find('.item-quantity').val(),
                unit_price: row.find('.item-price').val(),
                lot_number: row.find('.item-lot').val(),
                manufacture_date: row.find('.item-manufacture-date').val(),
                expiry_date: row.find('.item-expiry-date').val()
            });
        }
    });
    
    var data = $('#goods-receipt-form').serializeArray();
    data.push({name: 'items', value: JSON.stringify(items)});
    
    $.post($('#goods-receipt-form').attr('action'), data, function(response) {
        if(response.success) {
            alert_float('success', response.message);
            window.location.href = admin_url + 'warehouse/view_receipt/' + response.receipt_id;
        } else {
            alert_float('danger', response.message);
        }
    }, 'json');
}
</script>
