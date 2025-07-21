<!-- Modal de Registro Rápido -->
<div class="modal fade" id="quickItemModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('quick_add_item'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <?php echo _l('quick_add_item_help'); ?>
                        </div>
                    </div>
                </div>

                <form id="quickItemForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="code"><?php echo _l('item_code'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" required readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="name"><?php echo _l('item_name'); ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="unit_id"><?php echo _l('unit'); ?> <span class="text-danger">*</span></label>
                                <select class="selectpicker" data-width="100%" id="unit_id" name="unit_id" required>
                                    <option value=""><?php echo _l('select_unit'); ?></option>
                                    <?php foreach($units as $unit) { ?>
                                    <option value="<?php echo $unit['id']; ?>"><?php echo $unit['name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id"><?php echo _l('category'); ?></label>
                                <select class="selectpicker" data-width="100%" id="category_id" name="category_id">
                                    <option value=""><?php echo _l('select_category'); ?></option>
                                    <?php foreach($categories as $category) { ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cost_price"><?php echo _l('cost_price'); ?></label>
                                <input type="number" class="form-control" id="cost_price" name="cost_price" step="0.01" min="0">
                            </div>
                        </div>
                    </div>
                </form>

                <div id="quickItemAlert" class="alert" style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info" id="btnSaveQuickItem">
                    <?php echo _l('save_and_add'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
