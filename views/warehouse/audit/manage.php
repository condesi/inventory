<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if (is_admin()) { ?>
                            <div class="btn-group pull-right mleft4">
                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <?php echo _l('actions'); ?> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="#" onclick="exportAuditLog(); return false;">
                                            <i class="fa fa-download"></i> <?php echo _l('export'); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" onclick="showCleanupModal(); return false;">
                                            <i class="fa fa-trash"></i> <?php echo _l('cleanup_old_entries'); ?>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php } ?>
                            
                            <div class="btn-group pull-right">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fa fa-filter"></i> <?php echo _l('filter_by_action'); ?> <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" data-type=""><?php echo _l('all'); ?></a></li>
                                    <li><a href="#" data-type="create"><?php echo _l('created'); ?></a></li>
                                    <li><a href="#" data-type="update"><?php echo _l('updated'); ?></a></li>
                                    <li><a href="#" data-type="delete"><?php echo _l('deleted'); ?></a></li>
                                    <li><a href="#" data-type="view"><?php echo _l('viewed'); ?></a></li>
                                </ul>
                            </div>
                            
                            <div class="clearfix"></div>
                            <hr class="hr-panel-heading" />
                        </div>
                        
                        <div class="clearfix"></div>
                        <?php render_datatable([
                            _l('id'),
                            _l('staff'),
                            _l('date'),
                            _l('action_type'),
                            _l('module'),
                            _l('record_id'),
                            _l('ip_address'),
                            _l('details'),
                        ], 'warehouse-audit-log'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cleanup -->
<div class="modal fade" id="cleanup_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('cleanup_old_entries'); ?></h4>
            </div>
            <div class="modal-body">
                <?php echo form_open(admin_url('warehouse/audit/cleanup'), ['id' => 'cleanup_form']); ?>
                <div class="form-group">
                    <label for="days"><?php echo _l('delete_entries_older_than'); ?></label>
                    <div class="input-group">
                        <input type="number" name="days" id="days" class="form-control" min="30" value="90">
                        <span class="input-group-addon"><?php echo _l('days'); ?></span>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-danger" onclick="submitCleanup()"><?php echo _l('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
    var table = initDataTable('.table-warehouse-audit-log', window.location.href, undefined, undefined, undefined, [2, 'desc']);
    
    // Filtros por tipo de acción
    $('.dropdown-menu a[data-type]').click(function(e) {
        e.preventDefault();
        var type = $(this).data('type');
        table.column(3).search(type).draw();
    });
});

function exportAuditLog() {
    var url = admin_url + 'warehouse/audit/export';
    var params = {
        from: $('#from').val(),
        to: $('#to').val(),
        type: $('.table-warehouse-audit-log').DataTable().column(3).search()
    };
    
    window.location.href = url + '?' + $.param(params);
}

function showCleanupModal() {
    $('#cleanup_modal').modal('show');
}

function submitCleanup() {
    if (confirm(app.lang.confirm_action_prompt)) {
        $('#cleanup_form').submit();
    }
}
</script>
