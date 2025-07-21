<div class="col-md-12">
  <?php echo form_open_multipart(admin_url('warehouse/current_stock_report_pdf'), array('id'=>'print_report')); ?>
  <div class="row">
    <div class="col-md-2">
      <div class="form-group">
        <label><?php echo _l('warehouse_name') ?></label>
        <select name="warehouse_filter[]" id="warehouse_filter" class="selectpicker" multiple="true" data-live-search="true" data-width="100%" data-none-selected-text="" data-actions-box="true">
          <?php foreach($warehouse_filter as $warehouse) { ?>
            <option value="<?php echo new_html_entity_decode($warehouse['warehouse_id']); ?>"><?php echo new_html_entity_decode($warehouse['warehouse_name']); ?></option>
          <?php } ?>
        </select>
      </div>
    </div>

    <div class="col-md-2">
      <div class="form-group">
        <label><?php echo _l('item_group') ?></label>
        <select name="group_filter[]" id="group_filter" class="selectpicker" multiple="true" data-live-search="true" data-width="100%" data-none-selected-text="" data-actions-box="true">
          <?php foreach($item_groups as $group) { ?>
            <option value="<?php echo $group['id']; ?>"><?php echo $group['name']; ?></option>
          <?php } ?>
        </select>
      </div>
    </div>

    <div class="col-md-3">
      <?php $this->load->view('warehouse/item_include/item_select', ['select_name' => 'commodity_filter[]', 'id_name' => 'commodity_filter', 'multiple' => true, 'label_name' => 'commodity']); ?>
    </div>

    <div class="col-md-2">
      <div class="form-group">
        <label><?php echo _l('stock_status') ?></label>
        <select name="status_filter" id="status_filter" class="selectpicker" data-live-search="true" data-width="100%">
          <option value=""><?php echo _l('all') ?></option>
          <option value="available"><?php echo _l('available') ?></option>
          <option value="warning"><?php echo _l('stock_warning') ?></option>
          <option value="critical"><?php echo _l('stock_critical') ?></option>
        </select>
      </div>
    </div>

    <div class="col-md-3">
      <div class="form-group mtop25">
        <div class="btn-group">
          <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-file-download"></i> <?php echo _l('export'); ?> <span class="caret"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="#" onclick="current_stock_report_pdf(); return false;"><i class="fa fa-file-pdf"></i> <?php echo _l('download_pdf'); ?></a></li>
            <li><a href="#" onclick="current_stock_report_excel(); return false;"><i class="fa fa-file-excel"></i> <?php echo _l('download_xlsx'); ?></a></li>
            <li><a href="#" onclick="current_stock_report_csv(); return false;"><i class="fa fa-file-csv"></i> <?php echo _l('export_csv'); ?></a></li>
          </ul>
        </div>
        <a href="#" id="download_report" class="btn btn-warning pull-left mr-4 button-margin-r-b hide">
          <?php echo _l('download_xlsx'); ?>
        </a>
        <a href="#" onclick="get_current_stock_report(); return false;" class="btn btn-primary">
          <?php echo _l('_filter'); ?>
        </a>
      </div>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>

<hr class="hr-panel-heading" />

<div class="col-md-12" id="report">
  <div class="panel panel-info col-md-12 panel-padding">
    <div class="panel-body" id="current_stock_report_data">
      <p><h3 class="bold text-center"><?php echo mb_strtoupper(_l('current_stock_report')); ?></h3></p>
      <div class="col-md-12">
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th><?php echo _l('_order'); ?></th>
                <th><?php echo _l('commodity_code'); ?></th>
                <th><?php echo _l('commodity_name'); ?></th>
                <th><?php echo _l('wh_unit_name'); ?></th>
                <th class="text-center"><?php echo _l('available_quantity'); ?></th>
                <th><?php echo _l('warehouse_name'); ?></th>
                <th><?php echo _l('item_location'); ?></th>
                <th class="text-right"><?php echo _l('unit_cost'); ?></th>
                <th class="text-right"><?php echo _l('total_value'); ?></th>
                <th><?php echo _l('stock_status'); ?></th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="10" class="text-center"><?php echo _l('no_data_available'); ?></td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="info">
                <th colspan="4" class="text-right"><?php echo _l('total'); ?>:</th>
                <th class="text-center" id="total_quantity"></th>
                <th colspan="3" class="text-right"></th>
                <th class="text-right" id="total_value"></th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>