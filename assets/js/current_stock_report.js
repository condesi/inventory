/**
 * Current Stock Report JavaScript
 * Enhanced reporting for Perfex CRM Inventory module
 * Adapted for Peruvian market
 */

// Get data for current stock report
function get_current_stock_report() {
  "use strict";
  
  var warehouse_filter = $('#warehouse_filter').val();
  var commodity_filter = $('#commodity_filter').val();
  var group_filter = $('#group_filter').val();
  var status_filter = $('#status_filter').val();

  // Show loading indicator
  $('#current_stock_report_data').html('<div class="text-center"><i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i></div>');
  
  var formData = new FormData();
  formData.append(csrfData.token_name, csrfData.hash);
  formData.append("warehouse_filter", warehouse_filter);
  formData.append("commodity_filter", commodity_filter);
  formData.append("group_filter", group_filter);
  formData.append("status_filter", status_filter);

  $.ajax({ 
    url: admin_url + 'warehouse/get_current_stock_data', 
    method: 'post', 
    data: formData, 
    contentType: false, 
    processData: false
  }).done(function(response) {
    response = JSON.parse(response);
    $('#current_stock_report_data').html(response.html);
    
    // Initialize tooltips for any new content
    $('[data-toggle="tooltip"]').tooltip();
    
    // Show download button if data is available
    if (response.has_data) {
      $('#download_report').removeClass('hide');
    } else {
      $('#download_report').addClass('hide');
    }

    // Update totals
    $('#total_quantity').html(response.total_quantity);
    $('#total_value').html(response.total_value);
  });
}

// Export current stock report to PDF
function current_stock_report_pdf() {
  "use strict";
  
  var warehouse_filter = $('#warehouse_filter').val() || [];
  var commodity_filter = $('#commodity_filter').val() || [];
  var group_filter = $('#group_filter').val() || [];
  var status_filter = $('#status_filter').val() || '';
  
  var url = admin_url + 'warehouse/current_stock_report_pdf?' + 
    'warehouse_filter=' + JSON.stringify(warehouse_filter) + 
    '&commodity_filter=' + JSON.stringify(commodity_filter) +
    '&group_filter=' + JSON.stringify(group_filter) +
    '&status_filter=' + status_filter;
  
  window.open(url, '_blank');
}

// Export current stock report to Excel
function current_stock_report_excel() {
  "use strict";
  
  var warehouse_filter = $('#warehouse_filter').val() || [];
  var commodity_filter = $('#commodity_filter').val() || [];
  var group_filter = $('#group_filter').val() || [];
  var status_filter = $('#status_filter').val() || '';
  
  var data = {};
  data.warehouse_filter = warehouse_filter;
  data.commodity_filter = commodity_filter;
  data.group_filter = group_filter;
  data.status_filter = status_filter;

  $.post(admin_url + 'warehouse/current_stock_report_excel', data)
    .done(function(response) {
      response = JSON.parse(response);
      if(response.success) {
        alert_float('success', response.message);
        $('#download_report').attr({
          target: '_blank', 
          href: site_url + response.filename
        });
      } else {
        alert_float('warning', response.message);
      }
    })
    .fail(function() {
      alert_float('danger', '<?php echo _l('something_went_wrong'); ?>');
    });
}

// Export current stock report to CSV
function current_stock_report_csv() {
  "use strict";
  
  var warehouse_filter = $('#warehouse_filter').val() || [];
  var commodity_filter = $('#commodity_filter').val() || [];
  var group_filter = $('#group_filter').val() || [];
  var status_filter = $('#status_filter').val() || '';
  
  var url = admin_url + 'warehouse/current_stock_report_csv?' + 
    'warehouse_filter=' + JSON.stringify(warehouse_filter) + 
    '&commodity_filter=' + JSON.stringify(commodity_filter) +
    '&group_filter=' + JSON.stringify(group_filter) +
    '&status_filter=' + status_filter;
  
  window.location.href = url;
}

// Initialize report page
$(function() {
  // Initialize selectpicker
  $('.selectpicker').selectpicker();
  
  // Set default filters and load initial data
  setTimeout(function() {
    get_current_stock_report();
  }, 300);

  // Add status color indicators
  $('body').append(
    '<style>' +
    '.stock-status-available { background-color: #dff0d8; color: #3c763d; }' +
    '.stock-status-warning { background-color: #fcf8e3; color: #8a6d3b; }' +
    '.stock-status-critical { background-color: #f2dede; color: #a94442; }' +
    '</style>'
  );
});