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
                                    <?php echo _l('inventory_items'); ?>
                                    <?php if(has_permission('items','','create')){ ?>
                                        <a href="<?php echo admin_url('warehouse/item'); ?>" class="btn btn-info pull-right">
                                            <i class="fa fa-plus"></i> <?php echo _l('new_item'); ?>
                                        </a>
                                    <?php } ?>
                                </h4>
                                <hr class="hr-panel-separator" />
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="row filter-row">
                            <div class="col-md-3">
                                <?php echo render_select('category_filter', $categories, ['id', 'name'], 'category', '', ['multiple' => true, 'data-actions-box' => true]); ?>
                            </div>
                            <div class="col-md-3">
                                <?php echo render_select('warehouse_filter', $warehouses, ['id', 'name'], 'warehouse', '', ['multiple' => true, 'data-actions-box' => true]); ?>
                            </div>
                            <div class="col-md-2">
                                <?php echo render_select('status_filter', [
                                    ['id' => 'active', 'name' => _l('active')],
                                    ['id' => 'inactive', 'name' => _l('inactive')],
                                    ['id' => 'low_stock', 'name' => _l('low_stock')]
                                ], ['id', 'name'], 'status'); ?>
                            </div>
                            <div class="col-md-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="#" class="btn btn-default btn-block" onclick="clearFilters()">
                                            <i class="fa fa-remove"></i> <?php echo _l('clear_filters'); ?>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a href="#" class="btn btn-info btn-block" onclick="exportItems()">
                                            <i class="fa fa-download"></i> <?php echo _l('export'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vista en Tabla/Tarjetas -->
                        <div class="row mtop15">
                            <div class="col-md-12 text-right">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-default active" data-view="table">
                                        <i class="fa fa-list"></i>
                                    </button>
                                    <button type="button" class="btn btn-default" data-view="grid">
                                        <i class="fa fa-th"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Vista de Tabla -->
                        <div id="items_table_view" class="mtop15">
                            <div class="row">
                                <div class="col-md-12">
                                    <?php render_datatable([
                                        _l('image'),
                                        _l('code'),
                                        _l('name'),
                                        _l('category'),
                                        _l('unit'),
                                        _l('quantity'),
                                        _l('unit_price'),
                                        _l('tax'),
                                        _l('status'),
                                        _l('options')
                                    ], 'items'); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Vista de Cuadrícula -->
                        <div id="items_grid_view" class="mtop15 hide">
                            <div class="row" id="items_grid"></div>
                            <div class="row mtop15">
                                <div class="col-md-12 text-center">
                                    <a href="#" class="btn btn-info" id="load_more" onclick="loadMore()">
                                        <?php echo _l('load_more'); ?>
                                    </a>
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
    // Inicializar tabla
    var itemsTable = initDataTable('.table-items', admin_url + 'warehouse/items_table', [0], [0], {}, [1, 'asc']);
    
    // Inicializar selectpicker
    $('.selectpicker').selectpicker();
    
    // Cambiar vista tabla/cuadrícula
    $('[data-view]').on('click', function() {
        var view = $(this).data('view');
        $('[data-view]').removeClass('active');
        $(this).addClass('active');
        
        if (view === 'grid') {
            $('#items_table_view').addClass('hide');
            $('#items_grid_view').removeClass('hide');
            loadGridItems();
        } else {
            $('#items_grid_view').addClass('hide');
            $('#items_table_view').removeClass('hide');
            itemsTable.ajax.reload();
        }
    });
    
    // Aplicar filtros
    $('.filter-row select').on('change', function() {
        if ($('#items_grid_view').hasClass('hide')) {
            itemsTable.ajax.reload();
        } else {
            loadGridItems(true);
        }
    });
});

// Cargar items en vista de cuadrícula
function loadGridItems(reset = false) {
    var page = reset ? 1 : parseInt($('#items_grid').data('page') || 1);
    
    $.get(admin_url + 'warehouse/get_items_grid', {
        page: page,
        category: $('#category_filter').val(),
        warehouse: $('#warehouse_filter').val(),
        status: $('#status_filter').val()
    })
    .done(function(response) {
        response = JSON.parse(response);
        
        if (reset) {
            $('#items_grid').html('');
        }
        
        response.items.forEach(function(item) {
            $('#items_grid').append(getItemCard(item));
        });
        
        $('#items_grid').data('page', page + 1);
        $('#load_more').toggle(response.has_more);
    });
}

// Generar tarjeta de item
function getItemCard(item) {
    return `
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default item-card">
                <div class="panel-body">
                    <div class="item-image">
                        <img src="${item.image_url || admin_url + 'assets/images/placeholder.png'}" alt="${item.name}">
                    </div>
                    <h4 class="item-name">${item.name}</h4>
                    <p class="item-code">${item.code}</p>
                    <div class="item-details">
                        <span class="label ${item.status === 'active' ? 'label-success' : 'label-danger'}">${item.status}</span>
                        <span class="label label-info">${item.category}</span>
                    </div>
                    <div class="item-stock">
                        <strong>${_l('stock')}:</strong> ${item.quantity} ${item.unit}
                    </div>
                    <div class="item-price">
                        <strong>${_l('price')}:</strong> ${format_money(item.unit_price)}
                    </div>
                </div>
                <div class="panel-footer">
                    <div class="btn-group btn-group-justified">
                        <a href="${admin_url}warehouse/item/${item.id}" class="btn btn-default btn-sm">
                            <i class="fa fa-pencil"></i>
                        </a>
                        <a href="#" class="btn btn-default btn-sm" onclick="showHistory(${item.id})">
                            <i class="fa fa-history"></i>
                        </a>
                        <a href="#" class="btn btn-default btn-sm" onclick="viewMovements(${item.id})">
                            <i class="fa fa-exchange-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Limpiar filtros
function clearFilters() {
    $('.filter-row select').val('').selectpicker('refresh');
    if ($('#items_grid_view').hasClass('hide')) {
        itemsTable.ajax.reload();
    } else {
        loadGridItems(true);
    }
}

// Exportar items
function exportItems() {
    var url = admin_url + 'warehouse/export_items?' + $.param({
        category: $('#category_filter').val(),
        warehouse: $('#warehouse_filter').val(),
        status: $('#status_filter').val()
    });
    window.location = url;
}

// Ver historial
function showHistory(id) {
    $('#item_history_modal').modal('show');
    initDataTable('.table-item-history', 
        admin_url + 'warehouse/get_item_history/' + id, 
        [], [], {}, [0, 'desc']
    );
}

// Ver movimientos
function viewMovements(id) {
    $('#item_movements_modal').modal('show');
    initDataTable('.table-item-movements',
        admin_url + 'warehouse/get_item_movements/' + id,
        [], [], {}, [0, 'desc']
    );
}
</script>

<!-- Modal Historial -->
<div class="modal fade" id="item_history_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('item_history'); ?></h4>
            </div>
            <div class="modal-body">
                <table class="table table-item-history">
                    <thead>
                        <tr>
                            <th><?php echo _l('date'); ?></th>
                            <th><?php echo _l('staff'); ?></th>
                            <th><?php echo _l('field'); ?></th>
                            <th><?php echo _l('old_value'); ?></th>
                            <th><?php echo _l('new_value'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Movimientos -->
<div class="modal fade" id="item_movements_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('item_movements'); ?></h4>
            </div>
            <div class="modal-body">
                <table class="table table-item-movements">
                    <thead>
                        <tr>
                            <th><?php echo _l('date'); ?></th>
                            <th><?php echo _l('type'); ?></th>
                            <th><?php echo _l('warehouse'); ?></th>
                            <th><?php echo _l('quantity'); ?></th>
                            <th><?php echo _l('reference'); ?></th>
                            <th><?php echo _l('staff'); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
