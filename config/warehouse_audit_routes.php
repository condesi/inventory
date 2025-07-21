<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Warehouse Audit Routes
$route['warehouse/audit'] = 'warehouse_audit/index';
$route['warehouse/audit/(:num)'] = 'warehouse_audit/view/$1';
$route['warehouse/audit/export'] = 'warehouse_audit/export';
$route['warehouse/audit/cleanup'] = 'warehouse_audit/cleanup';
