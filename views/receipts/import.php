<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="panel_s">
    <div class="panel-body">
        <?php echo form_open_multipart(admin_url('stock_receipts/import_excel'), ['id' => 'import-form']); ?>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="warehouse_id"><?php echo _l('warehouse'); ?> <span class="text-danger">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="selectpicker" data-live-search="true" data-width="100%" required>
                        <option value=""><?php echo _l('select_warehouse'); ?></option>
                        <?php foreach($warehouses as $warehouse) { ?>
                        <option value="<?php echo $warehouse['id']; ?>"><?php echo $warehouse['warehouse_name']; ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="file_csv"><?php echo _l('select_excel_file'); ?> <span class="text-danger">*</span></label>
                    <input type="file" name="file_csv" id="file_csv" class="form-control" required accept=".xlsx">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h5 class="bold"><?php echo _l('import_types'); ?></h5>
                <hr class="hr-panel-heading" />
            </div>

            <div class="col-md-4">
                <button type="submit" name="receipt_import" class="btn btn-primary btn-block">
                    <i class="fa fa-file-excel-o"></i> <?php echo _l('import_receipts'); ?>
                </button>
                <p class="text-muted mtop10"><?php echo _l('import_receipts_desc'); ?></p>
                <a href="<?php echo base_url('uploads/file_sample/stock_receipts_sample.xlsx'); ?>" class="btn btn-info btn-block">
                    <i class="fa fa-download"></i> <?php echo _l('download_sample'); ?>
                </a>
            </div>

            <div class="col-md-4">
                <button type="submit" name="item_import" class="btn btn-success btn-block">
                    <i class="fa fa-file-excel-o"></i> <?php echo _l('import_items'); ?>
                </button>
                <p class="text-muted mtop10"><?php echo _l('import_items_desc'); ?></p>
                <a href="<?php echo base_url('uploads/file_sample/items_sample.xlsx'); ?>" class="btn btn-info btn-block">
                    <i class="fa fa-download"></i> <?php echo _l('download_sample'); ?>
                </a>
            </div>

            <div class="col-md-4">
                <button type="submit" name="opening_stock_import" class="btn btn-warning btn-block">
                    <i class="fa fa-file-excel-o"></i> <?php echo _l('import_opening_stock'); ?>
                </button>
                <p class="text-muted mtop10"><?php echo _l('import_opening_stock_desc'); ?></p>
                <a href="<?php echo base_url('uploads/file_sample/opening_stock_sample.xlsx'); ?>" class="btn btn-info btn-block">
                    <i class="fa fa-download"></i> <?php echo _l('download_sample'); ?>
                </a>
            </div>
        </div>

        <?php echo form_close(); ?>

        <div class="row mtop20">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <h4 class="bold"><?php echo _l('import_notes'); ?></h4>
                    <ul class="mtop10">
                        <li><?php echo _l('import_note_1'); ?></li>
                        <li><?php echo _l('import_note_2'); ?></li>
                        <li><?php echo _l('import_note_3'); ?></li>
                        <li><?php echo _l('import_note_4'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    appValidateForm($('#import-form'), {
        warehouse_id: 'required',
        file_csv: {
            required: true,
            extension: "xlsx"
        }
    });
});
</script>
