<?php
declare(strict_types=1);

/**
 * 发货方式管理 tab 数据加载器。
 */

require_once __DIR__ . '/../../data/fulfillment.php';

$fulfillmentTypes = shop_get_fulfillment_types();

$editFulfillmentId = (int) ($_GET['edit_fulfillment'] ?? 0);
$editingFulfillment = $editFulfillmentId > 0 ? shop_get_fulfillment_type_by_id($editFulfillmentId) : null;

$selectedFulfillment = $editingFulfillment ?? [
    'id' => 0,
    'slug' => '',
    'name' => '',
    'description' => '',
    'icon' => 'local_shipping',
    'badge_color' => '#6a37d4',
    'allow_zero_stock' => 0,
    'sort' => 0,
    'status' => 'active',
];
