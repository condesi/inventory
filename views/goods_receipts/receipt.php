<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open($this->uri->uri_string(), ['id' => 'goods-receipt-form']); ?>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="no-margin">
                                    <i class="fa fa-inbox" aria-hidden="true"></i> 
                                    <?php echo $title; ?>
                                    <?php if(isset($receipt->reference)){ ?>
                                        <small> - <?php echo $receipt->reference; ?></small>
                                    <?php } ?>
                                </h4>
                                <hr />
                            </div>
                        </div>

                        <!-- Información Principal -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php 
                                echo render_select('warehouse_id', 
                                    $warehouses, 
                                    ['id', 'name'], 
                                    'warehouse',
                                    isset($receipt->warehouse_id) ? $receipt->warehouse_id : '',
                                    ['required' => true]
                                ); 
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php 
                                echo render_select('supplier_id',
                                    $suppliers,
                                    ['id', 'company'],
                                    'supplier',
                                    isset($receipt->supplier_id) ? $receipt->supplier_id : ''
                                );
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_date_input(
                                    'date_received',
                                    'date_received',
                                    isset($receipt->date_received) ? _d($receipt->date_received) : _d(date('Y-m-d')),
                                    ['required' => true]
                                );
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_input(
                                    'reference_no',
                                    'reference_no',
                                    isset($receipt->reference_no) ? $receipt->reference_no : '',
                                    'text',
                                    ['readonly' => true]
                                );
                                ?>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <!-- Buscador de Productos -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="item_lookup"><?php echo _l('search_items'); ?></label>
                                    <div class="input-group">
                                        <input type="text" id="item_lookup" class="form-control" placeholder="<?php echo _l('search_items_placeholder'); ?>">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info" type="button" onclick="searchItems()">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">
                                        <?php echo _l('search_items_help'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Items -->
                        <div class="table-responsive mtop15">
                            <table class="table items table-main-receipt-edit has-calculations no-mtop">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%"><?php echo _l('item_code'); ?></th>
                                        <th width="25%"><?php echo _l('item_name'); ?></th>
                                        <th width="10%"><?php echo _l('unit'); ?></th>
                                        <th width="10%"><?php echo _l('qty'); ?></th>
                                        <th width="10%"><?php echo _l('price'); ?></th>
                                        <th width="10%"><?php echo _l('tax'); ?></th>
                                        <th width="10%"><?php echo _l('total'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(isset($receipt->items)){ 
                                        foreach($receipt->items as $item){ ?>
                                            <?php echo $this->load->view(
                                                'goods_receipts/_item_row', 
                                                ['item' => $item], 
                                                true
                                            ); ?>
                                        <?php }
                                    } ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Botón Agregar Item -->
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-info" onclick="addItemRow();">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_item'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Totales -->
                        <div class="row mtop15">
                            <div class="col-md-8 col-md-offset-4">
                                <table class="table text-right">
                                    <tbody>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('subtotal'); ?></span></td>
                                            <td>
                                                <input type="number" name="subtotal" class="form-control text-right" value="<?php echo isset($receipt->subtotal) ? $receipt->subtotal : '0.00'; ?>" readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('tax_total'); ?></span></td>
                                            <td>
                                                <input type="number" name="tax_total" class="form-control text-right" value="<?php echo isset($receipt->tax_total) ? $receipt->tax_total : '0.00'; ?>" readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><span class="bold"><?php echo _l('total'); ?></span></td>
                                            <td>
                                                <input type="number" name="total" class="form-control text-right" value="<?php echo isset($receipt->total) ? $receipt->total : '0.00'; ?>" readonly>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notas -->
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <?php echo render_textarea('notes', 'notes', isset($receipt->notes) ? $receipt->notes : ''); ?>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="btn-bottom-toolbar text-right">
                                    <button type="button" class="btn btn-default" onclick="window.history.back();">
                                        <?php echo _l('cancel'); ?>
                                    </button>
                                    <?php if(isset($receipt->status) && $receipt->status == 'draft'){ ?>
                                        <button type="submit" class="btn btn-info save-receipt">
                                            <?php echo _l('submit'); ?>
                                        </button>
                                    <?php } ?>
                                    <?php if(isset($receipt->status) && in_array($receipt->status, ['pending', 'approved'])){ ?>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                                <?php echo _l('actions'); ?> <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right">
                                                <?php if($receipt->status == 'pending' && has_permission('warehouse', '', 'edit')){ ?>
                                                    <li>
                                                        <a href="#" onclick="approveReceipt(<?php echo $receipt->id; ?>)">
                                                            <i class="fa fa-check"></i> <?php echo _l('approve'); ?>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a href="#" onclick="rejectReceipt(<?php echo $receipt->id; ?>)">
                                                            <i class="fa fa-times"></i> <?php echo _l('reject'); ?>
                                                        </a>
                                                    </li>
                                                <?php } ?>
                                                <li>
                                                    <a href="<?php echo admin_url('warehouse/goods_receipts/pdf/'.$receipt->id); ?>" target="_blank">
                                                        <i class="fa fa-file-pdf-o"></i> <?php echo _l('view_pdf'); ?>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php } ?>
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

<!-- Template para nueva fila de item -->
<script type="text/template" id="item-row-template">
    <tr class="item-row" data-index="{index}">
        <td>{index}</td>
        <td>
            <input type="text" name="items[{index}][code]" class="form-control item-code" onblur="searchByCode(this)">
            <input type="hidden" name="items[{index}][item_id]" class="item-id">
        </td>
        <td>
            <input type="text" name="items[{index}][name]" class="form-control item-name" readonly>
        </td>
        <td>
            <?php echo render_select('items[{index}][unit_id]', $units, ['id', 'name'], '', '', ['class' => 'item-unit']); ?>
        </td>
        <td>
            <input type="number" name="items[{index}][qty]" class="form-control item-qty" value="1" min="1" onchange="calculateRow(this)">
        </td>
        <td>
            <input type="number" name="items[{index}][price]" class="form-control item-price" value="0" min="0" onchange="calculateRow(this)">
        </td>
        <td>
            <?php echo render_select('items[{index}][tax]', $taxes, ['id', 'name', 'rate'], '', '', ['class' => 'item-tax']); ?>
        </td>
        <td>
            <input type="number" name="items[{index}][total]" class="form-control item-total" readonly>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-xs" onclick="removeRow(this)">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
</script>

<script>
// Variables globales
var itemRowTemplate = document.getElementById('item-row-template').innerHTML;
var nextIndex = <?php echo isset($receipt->items) ? count($receipt->items) + 1 : 1; ?>;

// Funciones
function addItemRow() {
    var template = itemRowTemplate.replace(/{index}/g, nextIndex);
    $('.items tbody').append(template);
    nextIndex++;
    initializeSelect2();
}

function removeRow(btn) {
    $(btn).closest('tr').remove();
    calculateTotals();
}

function searchItems() {
    var query = $('#item_lookup').val();
    
    if(!query) return;
    
    $.get(admin_url + 'warehouse/search_items', {q: query}, function(response) {
        var items = JSON.parse(response);
        if(items.length > 0) {
            showItemsModal(items);
        } else {
            alert_float('warning', '<?php echo _l("no_items_found"); ?>');
        }
    });
}

function searchByCode(input) {
    var code = $(input).val();
    var row = $(input).closest('tr');
    
    if(!code) return;
    
    $.post(admin_url + 'warehouse/goods_receipts/get_item_by_code', {
        code: code,
        warehouse_id: $('[name="warehouse_id"]').val()
    }, function(response) {
        var data = JSON.parse(response);
        if(data.success) {
            populateRow(row, data.item);
        } else {
            alert_float('warning', data.message);
            $(input).val('');
        }
    });
}

function populateRow(row, item) {
    row.find('.item-id').val(item.id);
    row.find('.item-name').val(item.name);
    row.find('.item-unit').val(item.unit_id).trigger('change');
    row.find('.item-price').val(item.last_price);
    calculateRow(row.find('.item-qty'));
}

function calculateRow(input) {
    var row = $(input).closest('tr');
    var qty = parseFloat(row.find('.item-qty').val()) || 0;
    var price = parseFloat(row.find('.item-price').val()) || 0;
    var taxRate = parseFloat(row.find('.item-tax option:selected').data('rate')) || 0;
    
    var subtotal = qty * price;
    var tax = subtotal * (taxRate / 100);
    var total = subtotal + tax;
    
    row.find('.item-total').val(total.toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    var subtotal = 0;
    var taxTotal = 0;
    
    $('.item-row').each(function() {
        var qty = parseFloat($(this).find('.item-qty').val()) || 0;
        var price = parseFloat($(this).find('.item-price').val()) || 0;
        var taxRate = parseFloat($(this).find('.item-tax option:selected').data('rate')) || 0;
        
        var rowSubtotal = qty * price;
        var rowTax = rowSubtotal * (taxRate / 100);
        
        subtotal += rowSubtotal;
        taxTotal += rowTax;
    });
    
    var total = subtotal + taxTotal;
    
    $('[name="subtotal"]').val(subtotal.toFixed(2));
    $('[name="tax_total"]').val(taxTotal.toFixed(2));
    $('[name="total"]').val(total.toFixed(2));
}

// Inicialización
$(function() {
    initializeSelect2();
    
    // Validación del formulario
    $('#goods-receipt-form').validate({
        rules: {
            warehouse_id: 'required',
            date_received: 'required'
        }
    });
});

function initializeSelect2() {
    $('.item-unit, .item-tax').select2();
}
</script>
