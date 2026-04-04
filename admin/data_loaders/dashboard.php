<?php
declare(strict_types=1);

/**
 * Dashboard tab 数据加载。
 * 依赖父作用域：$products, $pdo
 * 设置变量：$metrics, $homePreview, $pagePreview, $storageState, $fileState
 */

$metrics = shop_product_dashboard_metrics($products);
$homePreview = array_slice(shop_sort_products_for_home($products), 0, 6);
$pagePreview = array_slice(shop_sort_products_for_page($products), 0, 6);
$storageState = $pdo !== null ? '已连接' : '连接失败';
$fileState = '基于数据库';
