// Función para verificar el código del producto
function checkItemCode(code, callback) {
    $.ajax({
        url: admin_url + 'quick_items/check_code',
        type: 'POST',
        data: {
            code: code
        },
        success: function(response) {
            response = JSON.parse(response);
            callback(response);
        }
    });
}

// Función para mostrar/ocultar alerta de producto no encontrado
function toggleItemNotFound(itemRow, show, code) {
    var alertHtml = `
        <div class="item-not-found alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            ${app.lang.item_not_found.replace('{0}', code)}
            <br>
            <a href="#" class="btn btn-xs btn-info mtop10" onclick="showQuickItemModal('${code}'); return false;">
                <i class="fa fa-plus-circle"></i> ${app.lang.add_new_item}
            </a>
        </div>
    `;
    
    // Remover alerta existente
    itemRow.find('.item-not-found').remove();
    
    if (show) {
        itemRow.find('td:first').append(alertHtml);
    }
}

// Función para mostrar el modal de registro rápido
function showQuickItemModal(code) {
    var modal = $('#quickItemModal');
    
    // Limpiar formulario
    modal.find('form')[0].reset();
    
    // Establecer código
    modal.find('#code').val(code);
    
    // Mostrar modal
    modal.modal('show');
}

// Función para guardar producto rápido
function saveQuickItem(formData, callback) {
    $.ajax({
        url: admin_url + 'quick_items/create',
        type: 'POST',
        data: formData,
        success: function(response) {
            response = JSON.parse(response);
            
            if (response.success) {
                // Actualizar select2 de productos
                var newOption = new Option(response.item.name, response.item.id, true, true);
                itemsSelect.append(newOption).trigger('change');
                
                // Callback con el item creado
                callback(response.item);
                
                // Cerrar modal
                $('#quickItemModal').modal('hide');
                
                // Mostrar mensaje de éxito
                alert_float('success', response.message);
            } else {
                // Mostrar error en el modal
                var alert = $('#quickItemAlert');
                alert.removeClass('alert-success alert-danger')
                     .addClass('alert-danger')
                     .html(response.message)
                     .slideDown();
            }
        }
    });
}

// Event handler para código de producto en tabla dinámica
$(document).on('blur', '.item-code', function() {
    var code = $(this).val();
    var row = $(this).closest('tr');
    
    if (code) {
        checkItemCode(code, function(response) {
            if (!response.exists) {
                toggleItemNotFound(row, true, code);
            } else {
                toggleItemNotFound(row, false);
                // Actualizar campos del item encontrado
                row.find('.item-id').val(response.item.id);
                row.find('.item-name').val(response.item.name);
                row.find('.item-unit').val(response.item.unit_id).trigger('change');
                if (response.item.cost_price) {
                    row.find('.item-cost').val(response.item.cost_price);
                }
            }
        });
    }
});

// Event handler para guardar producto rápido
$(document).on('click', '#btnSaveQuickItem', function() {
    var form = $('#quickItemForm');
    
    // Validar formulario
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    var formData = form.serializeArray();
    
    saveQuickItem(formData, function(item) {
        // Encontrar la fila con el código
        var row = $('.item-code').filter(function() {
            return $(this).val() === item.code;
        }).closest('tr');
        
        // Actualizar campos
        row.find('.item-id').val(item.id);
        row.find('.item-name').val(item.name);
        row.find('.item-unit').val(item.unit_id).trigger('change');
        if (item.cost_price) {
            row.find('.item-cost').val(item.cost_price);
        }
        
        // Remover alerta
        toggleItemNotFound(row, false);
    });
});
