<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <?php echo _l('audit_entry_details'); ?>
                            <a href="<?php echo admin_url('warehouse/audit'); ?>" class="btn btn-default pull-right">
                                <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_list'); ?>
                            </a>
                        </h4>
                        <hr class="hr-panel-heading" />
                        
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped">
                                    <tr>
                                        <td width="30%"><strong><?php echo _l('id'); ?>:</strong></td>
                                        <td><?php echo $entry['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('staff'); ?>:</strong></td>
                                        <td>
                                            <?php
                                            $staff = get_staff($entry['user_id']);
                                            echo ($staff ? $staff->firstname . ' ' . $staff->lastname : 'N/A');
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('date'); ?>:</strong></td>
                                        <td><?php echo _dt($entry['timestamp']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('action_type'); ?>:</strong></td>
                                        <td><?php echo _l($entry['action_type']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('module'); ?>:</strong></td>
                                        <td><?php echo _l($entry['module']); ?></td>
                                    </tr>
                                    <?php if ($entry['record_id']) { ?>
                                    <tr>
                                        <td><strong><?php echo _l('record_id'); ?>:</strong></td>
                                        <td><?php echo $entry['record_id']; ?></td>
                                    </tr>
                                    <?php } ?>
                                    <tr>
                                        <td><strong><?php echo _l('ip_address'); ?>:</strong></td>
                                        <td><?php echo $entry['ip_address']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php echo _l('user_agent'); ?>:</strong></td>
                                        <td><?php echo $entry['user_agent']; ?></td>
                                    </tr>
                                    <?php if ($entry['details']) { ?>
                                    <tr>
                                        <td><strong><?php echo _l('details'); ?>:</strong></td>
                                        <td><?php echo $entry['details']; ?></td>
                                    </tr>
                                    <?php } ?>
                                </table>
                                
                                <?php if ($entry['old_value'] || $entry['new_value']) { ?>
                                <hr />
                                <h4><?php echo _l('changes'); ?></h4>
                                <div class="row">
                                    <?php if ($entry['old_value']) { ?>
                                    <div class="col-md-6">
                                        <h5><?php echo _l('old_value'); ?></h5>
                                        <pre><?php echo json_encode($entry['old_value'], JSON_PRETTY_PRINT); ?></pre>
                                    </div>
                                    <?php } ?>
                                    
                                    <?php if ($entry['new_value']) { ?>
                                    <div class="col-md-6">
                                        <h5><?php echo _l('new_value'); ?></h5>
                                        <pre><?php echo json_encode($entry['new_value'], JSON_PRETTY_PRINT); ?></pre>
                                    </div>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
