<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4><?php echo _l('sunat_reports'); ?></h4>
                                </div>
                                <div class="col-md-6">
                                    <?php
                                    $selected_period = $this->input->get('period') ? $this->input->get('period') : date('Ym');
                                    echo render_period_select($selected_period);
                                    ?>
                                </div>
                            </div>
                            <hr class="hr-panel-separator" />
                        </div>
                        
                        <div class="row">
                            <?php foreach($report_types as $key => $name) { ?>
                            <div class="col-md-4">
                                <div class="panel panel-info">
                                    <div class="panel-heading">
                                        <h3 class="panel-title"><?php echo $name; ?></h3>
                                    </div>
                                    <div class="panel-body">
                                        <p class="text-muted">
                                            <?php echo _l($key.'_description'); ?>
                                        </p>
                                        <div class="btn-group btn-block">
                                            <a href="<?php echo admin_url('warehouse/sunat_reports/'.strtolower($key).'/'.$selected_period); ?>" 
                                               class="btn btn-info">
                                                <i class="fa fa-eye"></i> <?php echo _l('view'); ?>
                                            </a>
                                            <a href="<?php echo admin_url('warehouse/sunat_reports/export/'.$key.'/'.$selected_period); ?>" 
                                               class="btn btn-success">
                                                <i class="fa fa-download"></i> <?php echo _l('export'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function(){
    // Actualizar al cambiar el período
    $('select[name="period"]').on('change', function(){
        window.location.href = '<?php echo admin_url('warehouse/sunat_reports?period='); ?>' + $(this).val();
    });
});
</script>
