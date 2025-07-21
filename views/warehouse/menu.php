<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<li class="<?php echo $this->uri->segment(2) == 'warehouse' ? 'active' : ''; ?>">
    <a href="#" class="open-customizer">
        <i class="fa fa-warehouse menu-icon"></i>
        <span class="menu-text"><?php echo _l('inventory_management'); ?></span>
        <span class="arrow"></span>
    </a>
    <ul class="nav nav-second-level collapse">
        <!-- Dashboard -->
        <li class="<?php echo $this->uri->segment(3) == 'dashboard' ? 'active' : ''; ?>">
            <a href="<?php echo admin_url('warehouse/dashboard'); ?>">
                <i class="fa fa-chart-line menu-icon"></i>
                <?php echo _l('dashboard'); ?>
            </a>
        </li>

        <!-- Entradas -->
        <li class="sub-menu">
            <a href="javascript:void(0);" class="<?php echo in_array($this->uri->segment(3), ['goods_receipts', 'purchase_returns']) ? 'active' : ''; ?>">
                <i class="fa fa-arrow-circle-down"></i>
                <?php echo _l('inbound'); ?>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="<?php echo $this->uri->segment(3) == 'goods_receipts' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/goods_receipts'); ?>">
                        <?php echo _l('goods_receipts'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'purchase_returns' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/purchase_returns'); ?>">
                        <?php echo _l('purchase_returns'); ?>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Salidas -->
        <li class="sub-menu">
            <a href="javascript:void(0);" class="<?php echo in_array($this->uri->segment(3), ['goods_delivery', 'sales_returns', 'internal_delivery']) ? 'active' : ''; ?>">
                <i class="fa fa-arrow-circle-up"></i>
                <?php echo _l('outbound'); ?>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="<?php echo $this->uri->segment(3) == 'goods_delivery' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/goods_delivery'); ?>">
                        <?php echo _l('goods_delivery'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'sales_returns' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/sales_returns'); ?>">
                        <?php echo _l('sales_returns'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'internal_delivery' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/internal_delivery'); ?>">
                        <?php echo _l('internal_delivery'); ?>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Stock -->
        <li class="sub-menu">
            <a href="javascript:void(0);" class="<?php echo in_array($this->uri->segment(3), ['stock', 'stock_count', 'stock_adjustments', 'stock_transfers']) ? 'active' : ''; ?>">
                <i class="fa fa-boxes"></i>
                <?php echo _l('stock'); ?>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li class="<?php echo $this->uri->segment(3) == 'stock' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/stock'); ?>">
                        <?php echo _l('stock_summary'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'stock_count' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/stock_count'); ?>">
                        <?php echo _l('stock_count'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'stock_adjustments' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/stock_adjustments'); ?>">
                        <?php echo _l('stock_adjustments'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'stock_transfers' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/stock_transfers'); ?>">
                        <?php echo _l('stock_transfers'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'batch_tracking' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/batch_tracking'); ?>">
                        <?php echo _l('batch_tracking'); ?>
                    </a>
                </li>
                <li class="<?php echo $this->uri->segment(3) == 'quality_control' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('warehouse/quality_control'); ?>">
                        <?php echo _l('quality_control'); ?>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Reportes -->
        <li class="sub-menu">
            <a href="javascript:void(0);" class="<?php echo $this->uri->segment(3) == 'reports' ? 'active' : ''; ?>">
                <i class="fa fa-chart-bar"></i>
                <?php echo _l('reports'); ?>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li>
                    <a href="<?php echo admin_url('warehouse/reports/inventory_valuation'); ?>">
                        <?php echo _l('inventory_valuation'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/reports/stock_movements'); ?>">
                        <?php echo _l('stock_movements'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/reports/low_stock'); ?>">
                        <?php echo _l('low_stock'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/reports/expiring_items'); ?>">
                        <?php echo _l('expiring_items'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/reports/profit_margin'); ?>">
                        <?php echo _l('profit_margin'); ?>
                    </a>
                </li>
            </ul>
        </li>

        <!-- Configuración -->
        <?php if(has_permission('warehouse', '', 'admin')){ ?>
        <li class="sub-menu">
            <a href="javascript:void(0);" class="<?php echo $this->uri->segment(3) == 'settings' ? 'active' : ''; ?>">
                <i class="fa fa-cog"></i>
                <?php echo _l('settings'); ?>
                <span class="arrow"></span>
            </a>
            <ul class="sub-menu">
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=general'); ?>">
                        <?php echo _l('general_settings'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=items'); ?>">
                        <?php echo _l('items_settings'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=categories'); ?>">
                        <?php echo _l('categories'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=warehouses'); ?>">
                        <?php echo _l('warehouses'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=units'); ?>">
                        <?php echo _l('units'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=taxes'); ?>">
                        <?php echo _l('taxes'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=numbering'); ?>">
                        <?php echo _l('numbering'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=approvals'); ?>">
                        <?php echo _l('approvals'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=locations'); ?>">
                        <?php echo _l('warehouse_locations'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=unit_conversions'); ?>">
                        <?php echo _l('unit_conversions'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=quality_control'); ?>">
                        <?php echo _l('quality_control'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=batch_tracking'); ?>">
                        <?php echo _l('batch_tracking'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=notifications'); ?>">
                        <?php echo _l('notifications'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=locations'); ?>">
                        <?php echo _l('warehouse_locations'); ?>
                    </a>
                </li>
                <li>
                    <a href="<?php echo admin_url('warehouse/settings?group=unit_conversions'); ?>">
                        <?php echo _l('unit_conversions'); ?>
                    </a>
                </li>
            </ul>
        </li>
        <?php } ?>
    </ul>
</li>

<script>
$(function() {
    // Mantener submenús abiertos cuando están activos
    $('.sub-menu').has('li.active').addClass('active').parent('li').addClass('active');
    
    // Contador de notificaciones
    var notification_markers = $('.notification-marker');
    if(notification_markers.length > 0) {
        $.get(admin_url + 'warehouse/get_notifications_count', function(response) {
            response = JSON.parse(response);
            notification_markers.html(response.count);
            if(response.count > 0) {
                notification_markers.removeClass('hide');
            }
        });
    }
});
</script>
