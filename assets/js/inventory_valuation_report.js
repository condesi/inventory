// Función para obtener los datos del reporte de valorización de inventario
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

    $.post(admin_url + 'warehouse/get_inventory_valuation_report', data).done(function(response) {
        response = JSON.parse(response);
        $('#stock_s_report').html(response.html);
        $('#dowload_items').removeClass('hide');
    });
}

// Función para exportar a Excel el reporte de valorización de inventario
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

// Función para exportar a PDF el reporte de valorización de inventario
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

// Función para exportar para SUNAT el reporte de valorización de inventario
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