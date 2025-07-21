<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- Encabezado -->
<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-neutral-700">
            <?php echo _l('internal_delivery'); ?>
        </h4>
    </div>
</div>

<!-- Formulario principal -->
<div class="panel_s">
    <div class="panel-body">
        <?php echo form_open('', ['id' => 'internal-delivery-form']); ?>
        
        <!-- Información básica -->
        <div class="row">
            <div class="col-md-3">
                <?php echo render_input('delivery_number', 'delivery_number', $delivery_number, 'text', ['readonly' => true]); ?>
            </div>
            <div class="col-md-3">
                <?php echo render_date_input('date', 'date', _d(date('Y-m-d'))); ?>
            </div>
            <div class="col-md-3">
                <?php echo render_select('warehouse_id', $warehouses, ['id', 'name'], 'warehouse', '', ['required' => true]); ?>
            </div>
            <div class="col-md-3">
                <?php echo render_select('action_type', $action_types, [], 'action_type', '', ['required' => true]); ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <?php echo render_select('department_id', $departments, ['id', 'name'], 'department'); ?>
            </div>
            <div class="col-md-6">
                <?php echo render_select('responsible_id', $staff, ['staffid', ['firstname', 'lastname']], 'responsible'); ?>
            </div>
        </div>

        <!-- Tabla de items -->
        <div class="table-responsive mtop15">
            <table class="table items table-main-delivery-edit has-calculations">
                <thead>
                    <tr>
                        <th><?php echo _l('item'); ?></th>
                        <th><?php echo _l('available_quantity'); ?></th>
                        <th><?php echo _l('action_name'); ?></th>
                        <th width="10%"><?php echo _l('quantity'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="main">
                        <td>
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="item_search" class="form-control item-search" placeholder="<?php echo _l('search_item'); ?>">
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="available-quantity">0</span>
                        </td>
                        <td>
                            <input type="text" name="action_name" class="form-control" placeholder="<?php echo _l('action_name_placeholder'); ?>">
                        </td>
                        <td>
                            <input type="number" name="quantity" class="form-control" min="1">
                        </td>
                        <td>
                            <button type="button" class="btn btn-success pull-right" onclick="addItem(); return false;">
                                <i class="fa fa-check"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Items agregados -->
        <div class="row mtop15">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table items table-hover table-delivery-items">
                        <thead>
                            <tr>
                                <th width="30%"><?php echo _l('item'); ?></th>
                                <th width="15%"><?php echo _l('action_name'); ?></th>
                                <th width="15%"><?php echo _l('quantity'); ?></th>
                                <th width="30%"><?php echo _l('description'); ?></th>
                                <th width="10%"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Notas -->
        <div class="row mtop15">
            <div class="col-md-12">
                <?php echo render_textarea('notes', 'notes'); ?>
            </div>
        </div>

        <!-- Botones -->
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

<!-- Scripts -->
<script>
$(function() {
    // Inicializar select2 para búsqueda de items
    initItemSearch();
    
    // Manejar envío del formulario
    $('#internal-delivery-form').on('submit', function(e) {
        e.preventDefault();
        saveDelivery();
    });
    
    // Actualizar stock disponible al cambiar almacén
    $('select[name="warehouse_id"]').on('change', function() {
        updateAvailableStock();
    });
});

// Inicializar búsqueda de items
function initItemSearch() {
    $('.item-search').select2({
        ajax: {
            url: site_url + 'warehouse/manage_internal_delivery/search_items',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term,
                    warehouse_id: $('select[name="warehouse_id"]').val()
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.code + ' - ' + item.name,
                            item: item
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: "<?php echo _l('search_item'); ?>"
    }).on('select2:select', function(e) {
        var item = e.params.data.item;
        updateAvailableStock(item.id);
    });
}

// Agregar item a la tabla
function addItem() {
    var item = $('.item-search').select2('data')[0];
    if (!item) return;
    
    var quantity = $('input[name="quantity"]').val();
    var action_name = $('input[name="action_name"]').val();
    
    if (!quantity || quantity <= 0 || !action_name) {
        alert("<?php echo _l('fill_required_fields'); ?>");
        return;
    }
    
    var html = '<tr class="item">' +
        '<td>' + item.text + '<input type="hidden" name="items[item_id][]" value="' + item.item.id + '"></td>' +
        '<td>' + action_name + '<input type="hidden" name="items[action_name][]" value="' + action_name + '"></td>' +
        '<td>' + quantity + '<input type="hidden" name="items[quantity][]" value="' + quantity + '"></td>' +
        '<td><input type="text" name="items[description][]" class="form-control"></td>' +
        '<td><button type="button" class="btn btn-danger" onclick="removeItem(this)"><i class="fa fa-remove"></i></button></td>' +
        '</tr>';
    
    $('.table-delivery-items tbody').append(html);
    
    // Limpiar campos
    $('.item-search').val(null).trigger('change');
    $('input[name="quantity"]').val('');
    $('input[name="action_name"]').val('');
    $('.available-quantity').text('0');
}

// Eliminar item de la tabla
function removeItem(btn) {
    $(btn).closest('tr').remove();
}

// Actualizar stock disponible
function updateAvailableStock(item_id) {
    var warehouse_id = $('select[name="warehouse_id"]').val();
    if (!warehouse_id || !item_id) {
        $('.available-quantity').text('0');
        return;
    }
    
    $.get(site_url + 'warehouse/manage_internal_delivery/get_available_stock', {
        item_id: item_id,
        warehouse_id: warehouse_id
    }).done(function(response) {
        var data = JSON.parse(response);
        $('.available-quantity').text(data.stock);
    });
}

// Guardar entrega
function saveDelivery() {
    var form = $('#internal-delivery-form');
    var data = new FormData(form[0]);
    
    // Convertir items a JSON
    var items = [];
    $('.table-delivery-items tbody tr.item').each(function() {
        items.push({
            item_id: $(this).find('input[name="items[item_id][]"]').val(),
            action_name: $(this).find('input[name="items[action_name][]"]').val(),
            quantity: $(this).find('input[name="items[quantity][]"]').val(),
            description: $(this).find('input[name="items[description][]"]').val()
        });
    });
    
    data.append('items', JSON.stringify(items));
    
    $.ajax({
        url: site_url + 'warehouse/manage_internal_delivery/save',
        type: 'POST',
        data: data,
        processData: false,
        contentType: false,
        success: function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert_float('success', data.message);
                // Redireccionar a la lista o vista de impresión
                window.location.href = site_url + 'warehouse/internal_delivery/view/' + data.delivery_id;
            } else {
                alert_float('danger', data.message);
            }
        }
    });
}
</script>
