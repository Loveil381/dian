<?php
declare(strict_types=1);

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    header('Location: ../index.php?page=admin');
    exit;
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/csrf.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php?page=admin_login');
    exit;
}

require_once __DIR__ . '/includes/helpers.php';

$pageTitle = '魔女小店 - 管理后台';
$adminUrl = 'index.php?page=admin';
$currentTab = $_GET['tab'] ?? 'dashboard';

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

// ── 编辑表单：按 ID 定向查询，仅在对应 tab 时执行 ──
$editingProduct = null;
$editingCategory = null;
$editingInventory = null;
$editingUser = null;

if ($currentTab === 'products') {
    $editId = (int) ($_GET['edit'] ?? 0);
    $editingProduct = $editId > 0 ? shop_get_product_by_id($editId) : null;
} elseif ($currentTab === 'categories') {
    $categoryEditId = (int) ($_GET['edit_category'] ?? 0);
    $editingCategory = $categoryEditId > 0 ? shop_get_category_by_id($categoryEditId) : null;
} elseif ($currentTab === 'inventory') {
    $inventoryEditId = (int) ($_GET['edit_inventory'] ?? 0);
    $editingInventory = $inventoryEditId > 0 ? shop_get_product_by_id($inventoryEditId) : null;
} elseif ($currentTab === 'users') {
    $userEditId = (int) ($_GET['edit_user'] ?? 0);
    $editingUser = $userEditId > 0 ? shop_get_user_by_id($userEditId) : null;
}

require_once __DIR__ . '/controllers/actions.php';

// POST 请求在 actions.php 中 redirect + exit，以下代码只在 GET 时执行。

$pdo = get_db_connection();
$prefix = get_db_prefix();
$categoryOptions = shop_category_names();
$perPage = 20;

// ── 仅在需要的 tab 加载全量商品（dashboard / products / categories / inventory）──
$needsProducts = in_array($currentTab, ['dashboard', 'products', 'categories', 'inventory'], true);
$products = [];
if ($needsProducts) {
    $products = shop_get_products();
    usort($products, static fn (array $a, array $b): int => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));
}

// ── 各 tab 默认变量（视图可能引用）──
$metrics = ['count' => 0, 'category_count' => 0, 'sales' => 0, 'home_priority_count' => 0, 'page_priority_count' => 0];
$homePreview = [];
$pagePreview = [];
$storageState = '';
$fileState = '';

$selectedProduct = [
    'id' => 0, 'name' => '', 'category' => $categoryOptions[0] ?? '未分类',
    'sales' => 0, 'published_at' => date('Y-m-d H:i:s'), 'price' => 0, 'stock' => 0,
    'tag' => '', 'home_sort' => 0, 'page_sort' => 0, 'sku' => '',
    'cover_image' => '', 'description' => '', 'status' => 'on_sale',
];
$publishedAtInput = date('Y-m-d\TH:i');
$selectedCategory = $categoryOptions[0] ?? '未分类';
$categoryChoices = $categoryOptions;
$productRows = [];
$productPagination = shop_paginate(0, $perPage, 1);
$productPaginationUrl = '';
$productCategoryFilter = '';
$productStatusFilter = '';

$selectedCategoryForm = ['id' => 0, 'name' => '', 'description' => '', 'accent' => '#cbd5e1', 'emoji' => '🛍️', 'sort' => 0];
$categoryManagementRows = [];

$inventoryRows = [];
$inventoryStats = ['total' => 0, 'low' => 0, 'zero' => 0, 'stock_total' => 0];
$lowStockProducts = [];
$selectedInventoryForm = [
    'id' => 0, 'name' => '', 'category' => $categoryOptions[0] ?? '未分类',
    'sales' => 0, 'published_at' => date('Y-m-d H:i:s'), 'price' => 0, 'stock' => 0,
    'tag' => '', 'home_sort' => 0, 'page_sort' => 0, 'sku' => '',
    'cover_image' => '', 'description' => '', 'status' => 'on_sale',
];
$selectedInventoryPublishedAtInput = date('Y-m-d\TH:i');

$orderRows = [];
$orderPagination = shop_paginate(0, $perPage, 1);
$orderPaginationUrl = '';
$orderStatusFilter = '';

$userRows = [];
$userPagination = shop_paginate(0, $perPage, 1);
$userPaginationUrl = '';
$selectedUserForm = [
    'id' => 0, 'username' => '', 'name' => '', 'email' => '', 'phone' => '',
    'level' => '普通会员', 'status' => 'active', 'address' => '',
    'last_login' => date('Y-m-d H:i:s'), 'note' => '',
];

$wechatQr = '';
$alipayQr = '';
$requireAddress = '0';

// ── 按当前 tab 加载对应数据 ──
$loaderMap = [
    'dashboard'  => __DIR__ . '/data_loaders/dashboard.php',
    'products'   => __DIR__ . '/data_loaders/products.php',
    'categories' => __DIR__ . '/data_loaders/categories.php',
    'inventory'  => __DIR__ . '/data_loaders/inventory.php',
    'orders'     => __DIR__ . '/data_loaders/orders.php',
    'users'      => __DIR__ . '/data_loaders/users.php',
    'payment'    => __DIR__ . '/data_loaders/payment.php',
];
if (isset($loaderMap[$currentTab])) {
    require $loaderMap[$currentTab];
}

require __DIR__ . '/views/layout.php';
