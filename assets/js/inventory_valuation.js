/**
 * Funciones mejoradas para el Reporte de Valorización de Inventario
 * Adaptado para el contexto peruano
 */

// Función para obtener los datos del reporte de valorización
function get_data_inventory_valuation_report() {
    "use strict";
    
    var warehouse_filter = $('select[name="warehouse_filter[]"]').val();
    var from_date = $('input[name="from_date"]').val();
    var to_date = $('input[name="to_date"]').val();
    var valuation_method = $('select[name="valuation_method"]').val();
    
    if(warehouse_filter == '' || from_date == '' || to_date == ''){
        alert('Almacén, fecha de inicio y fecha de fin son obligatorios');
        return false;
    }
    
    var data = {};
    data.warehouse_filter = warehouse_filter;
    data.from_date = from_date;
    data.to_date = to_date;
    data.valuation_method = valuation_method;

    $.post(admin_url + 'warehouse/get_data_inventory_valuation_report', data).done(function(response) {
        response = JSON.parse(response);
        $('#stock_s_report').html(response.html || '');
        if(response.html) {
            $('#dowload_items').removeClass('hide');
        }
    });
}

// Función para exportar a Excel el reporte de valorización
function inventory_valuation_report_export_excel(el) {
    "use strict";
    
    var warehouse_filter = $('select[name="warehouse_filter[]"]').val();
    var from_date = $('input[name="from_date"]').val();
    var to_date = $('input[name="to_date"]').val();
    var valuation_method = $('select[name="valuation_method"]').val();
    
    if(warehouse_filter == '' || from_date == '' || to_date == ''){
        alert('Almacén, fecha de inicio y fecha de fin son obligatorios');
        return false;
    }

    var url = admin_url + 'warehouse/inventory_valuation_report_export_excel?warehouse_filter=' + warehouse_filter + '&from_date=' + from_date + '&to_date=' + to_date + '&valuation_method=' + valuation_method;
    window.location.href = url;
}

// Función para exportar a PDF el reporte de valorización
function inventory_valuation_report_pdf(el) {
    "use strict";
    
    var warehouse_filter = $('select[name="warehouse_filter[]"]').val();
    var from_date = $('input[name="from_date"]').val();
    var to_date = $('input[name="to_date"]').val();
    var valuation_method = $('select[name="valuation_method"]').val();
    
    if(warehouse_filter == '' || from_date == '' || to_date == ''){
        alert('Almacén, fecha de inicio y fecha de fin son obligatorios');
        return false;
    }

    var url = admin_url + 'warehouse/inventory_valuation_report_pdf?warehouse_filter=' + warehouse_filter + '&from_date=' + from_date + '&to_date=' + to_date + '&valuation_method=' + valuation_method;
    window.open(url, '_blank');
}

// Función para exportar formato SUNAT
function inventory_valuation_report_sunat(el) {
    "use strict";
    
    var warehouse_filter = $('select[name="warehouse_filter[]"]').val();
    var from_date = $('input[name="from_date"]').val();
    var to_date = $('input[name="to_date"]').val();
    var valuation_method = $('select[name="valuation_method"]').val();
    
    if(warehouse_filter == '' || from_date == '' || to_date == ''){
        alert('Almacén, fecha de inicio y fecha de fin son obligatorios');
        return false;
    }

    var url = admin_url + 'warehouse/inventory_valuation_report_sunat?warehouse_filter=' + warehouse_filter + '&from_date=' + from_date + '&to_date=' + to_date + '&valuation_method=' + valuation_method;
    window.location.href = url;
}

// Inicializar tooltips para mejorar la usabilidad
function initInventoryValuationTooltips() {
    $('[data-toggle="tooltip"]').tooltip({
        container: 'body',
        placement: 'top'
    });
}

// Mostrar una ayuda rápida sobre los métodos de valoración
function showValuationMethodHelp() {
    var content = '<div class="valuation-method-help">' +
                 '<h4>Métodos de Valoración de Inventario</h4>' +
                 '<ul>' +
                 '<li><strong>Costo Promedio:</strong> Promedia el costo de todas las unidades en inventario.</li>' +
                 '<li><strong>PEPS (Primero en Entrar, Primero en Salir):</strong> Las primeras unidades que entran al inventario son las primeras en venderse.</li>' +
                 '<li><strong>UEPS (Último en Entrar, Primero en Salir):</strong> Las últimas unidades que entran al inventario son las primeras en venderse.</li>' +
                 '</ul>' +
                 '</div>';
    
    $('#valuation_method_help_modal .modal-body').html(content);
    $('#valuation_method_help_modal').modal('show');
}

// Document ready
$(function() {
    // Agregar botón de ayuda para los métodos de valoración
    if ($('#valuation_method').length > 0) {
        $('#valuation_method').after('<button type="button" class="btn btn-link" onclick="showValuationMethodHelp()" data-toggle="tooltip" title="Ver descripción de los métodos de valoración"><i class="fa fa-question-circle"></i></button>');
        
        // Agregar modal para la ayuda
        if ($('#valuation_method_help_modal').length == 0) {
            $('body').append('<div id="valuation_method_help_modal" class="modal fade" tabindex="-1" role="dialog">' +
                           '<div class="modal-dialog" role="document">' +
                           '<div class="modal-content">' +
                           '<div class="modal-header">' +
                           '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                           '<h4 class="modal-title">Métodos de Valoración</h4>' +
                           '</div>' +
                           '<div class="modal-body"></div>' +
                           '<div class="modal-footer">' +
                           '<button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>' +
                           '</div>' +
                           '</div>' +
                           '</div>' +
                           '</div>');
        }
        
        // Inicializar tooltips
        initInventoryValuationTooltips();
    }
});