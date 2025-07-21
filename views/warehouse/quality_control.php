<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin">
            <?php echo _l('quality_control'); ?>
            <div class="btn-group pull-right">
                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-download"></i> <?php echo _l('export'); ?> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#" onclick="exportQualityReport('excel')">
                            <i class="fa fa-file-excel-o"></i> <?php echo _l('export_excel'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="exportQualityReport('pdf')">
                            <i class="fa fa-file-pdf-o"></i> <?php echo _l('export_pdf'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="exportQualityReport('csv')">
                            <i class="fa fa-file-text-o"></i> <?php echo _l('export_csv'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Filtros -->
            <div class="btn-group pull-right mright5">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-filter"></i> <?php echo _l('filters'); ?> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a href="#" data-status="all"><?php echo _l('all'); ?></a>
                    </li>
                    <li>
                        <a href="#" data-status="pending"><?php echo _l('quality_pending'); ?></a>
                    </li>
                    <li>
                        <a href="#" data-status="approved"><?php echo _l('quality_approved'); ?></a>
                    </li>
                    <li>
                        <a href="#" data-status="rejected"><?php echo _l('quality_rejected'); ?></a>
                    </li>
                </ul>
            </div>
            
            <!-- Rango de fechas -->
            <div class="pull-right mright5">
                <div class="btn-group">
                    <button type="button" class="btn btn-default" id="daterange-btn">
                        <i class="fa fa-calendar"></i> <span><?php echo _l('date_range'); ?></span>
                        <i class="fa fa-caret-down"></i>
                    </button>
                </div>
            </div>
        </h4>
        <hr class="hr-panel-heading" />
        
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-quality-control">
                        <thead>
                            <tr>
                                <th><?php echo _l('date'); ?></th>
                                <th><?php echo _l('batch_number'); ?></th>
                                <th><?php echo _l('commodity_name'); ?></th>
                                <th><?php echo _l('quantity'); ?></th>
                                <th><?php echo _l('quality_status'); ?></th>
                                <th><?php echo _l('inspector'); ?></th>
                                <th><?php echo _l('inspection_note'); ?></th>
                                <th><?php echo _l('options'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Se llenará dinámicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Inspección -->
        <div class="modal fade" id="quality-inspection-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo _l('quality_inspection'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <?php echo form_open(admin_url('warehouse/save_quality_inspection'), ['id'=>'quality-inspection-form']); ?>
                        <input type="hidden" name="receipt_detail_id" id="receipt_detail_id">
                        
                        <div class="form-group">
                            <label for="quality_status"><?php echo _l('quality_status'); ?></label>
                            <select name="quality_status" id="quality_status" class="form-control">
                                <option value="pending"><?php echo _l('quality_pending'); ?></option>
                                <option value="approved"><?php echo _l('quality_approved'); ?></option>
                                <option value="rejected"><?php echo _l('quality_rejected'); ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="inspection_note"><?php echo _l('inspection_note'); ?></label>
                            <textarea name="inspection_note" id="inspection_note" class="form-control" rows="4"></textarea>
                        </div>

                        <?php echo form_close(); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        <button type="button" class="btn btn-primary" id="save-inspection"><?php echo _l('save'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var qualityControlTable = initDataTable('.table-quality-control', 
        '<?php echo admin_url('warehouse/get_quality_control_data'); ?>', 
        [0, 1, 2, 3, 4, 5, 6], 
        [0, 1, 2, 3, 4, 5, 6],
        'id', 
        [0, 'desc']
    );

    $('#save-inspection').on('click', function() {
        var form = $('#quality-inspection-form');
        var data = form.serialize();
        
        $.post(form.attr('action'), data)
            .done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    $('#quality-inspection-modal').modal('hide');
                    qualityControlTable.DataTable().ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
    });
});

function openInspectionModal(id) {
    $('#receipt_detail_id').val(id);
    $('#quality-inspection-modal').modal('show');
}

// Inicializar el selector de rango de fechas
var start = moment().subtract(29, 'days');
var end = moment();

function cb(start, end) {
    $('#daterange-btn span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
}

$('#daterange-btn').daterangepicker({
    startDate: start,
    endDate: end,
    ranges: {
        'Hoy': [moment(), moment()],
        'Ayer': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'Últimos 7 días': [moment().subtract(6, 'days'), moment()],
        'Últimos 30 días': [moment().subtract(29, 'days'), moment()],
        'Este mes': [moment().startOf('month'), moment().endOf('month')],
        'Mes pasado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    },
    locale: {
        format: 'DD/MM/YYYY',
        applyLabel: 'Aplicar',
        cancelLabel: 'Cancelar',
        customRangeLabel: 'Rango personalizado'
    }
}, cb);

cb(start, end);

// Manejo de filtros
$('.dropdown-menu a[data-status]').on('click', function(e) {
    e.preventDefault();
    var status = $(this).data('status');
    qualityControlTable.DataTable().column(4).search(status === 'all' ? '' : status).draw();
});

// Exportar reportes
function exportQualityReport(format) {
    var params = {
        format: format,
        status: qualityControlTable.DataTable().column(4).search(),
        start_date: $('#daterange-btn').data('daterangepicker').startDate.format('YYYY-MM-DD'),
        end_date: $('#daterange-btn').data('daterangepicker').endDate.format('YYYY-MM-DD')
    };
    
    var url = admin_url + 'warehouse/export_quality_report?' + $.param(params);
    window.location.href = url;
}

// Función para generar gráficos estadísticos
function loadQualityStats() {
    var params = {
        start_date: $('#daterange-btn').data('daterangepicker').startDate.format('YYYY-MM-DD'),
        end_date: $('#daterange-btn').data('daterangepicker').endDate.format('YYYY-MM-DD')
    };
    
    $.get(admin_url + 'warehouse/get_quality_stats', params)
        .done(function(response) {
            response = JSON.parse(response);
            
            // Gráfico de estatus
            var statusCtx = document.getElementById('quality-status-chart').getContext('2d');
            new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: ['Pendiente', 'Aprobado', 'Rechazado'],
                    datasets: [{
                        data: [
                            response.stats.pending,
                            response.stats.approved,
                            response.stats.rejected
                        ],
                        backgroundColor: ['#ffc107', '#28a745', '#dc3545']
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        position: 'bottom'
                    }
                }
            });
            
            // Actualizar contadores
            $('#total-inspections').text(response.stats.total);
            $('#approval-rate').text(response.stats.approval_rate + '%');
        });
}

// Cargar estadísticas cuando cambie el rango de fechas
$('#daterange-btn').on('apply.daterangepicker', function(ev, picker) {
    qualityControlTable.DataTable().ajax.reload();
    loadQualityStats();
});

// Cargar estadísticas iniciales
loadQualityStats();
</script>

<!-- Agregar gráficos estadísticos -->
<div class="row mtop20">
    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('quality_statistics'); ?></h4>
                <hr class="hr-panel-heading" />
                <div class="row">
                    <div class="col-md-6 text-center">
                        <h5><?php echo _l('total_inspections'); ?></h5>
                        <h3 id="total-inspections">0</h3>
                    </div>
                    <div class="col-md-6 text-center">
                        <h5><?php echo _l('approval_rate'); ?></h5>
                        <h3 id="approval-rate">0%</h3>
                    </div>
                </div>
                <div class="mtop20">
                    <canvas id="quality-status-chart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
