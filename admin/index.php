<?php
declare(strict_types=1);

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    header('Location: ../index.php?page=admin');
    exit;
}

require_once __DIR__ . '/../data/products.php';

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

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$products = shop_get_products();
$categories = shop_get_categories();
$users = shop_get_users();
$plugins = shop_get_plugins();

$categoryOptions = shop_category_names();
$editId = (int) ($_GET['edit'] ?? 0);
$editingProduct = $editId > 0 ? shop_find_product($products, $editId) : null;
$categoryEditId = (int) ($_GET['edit_category'] ?? 0);
$editingCategory = $categoryEditId > 0 ? shop_find_category($categories, $categoryEditId) : null;
$inventoryEditId = (int) ($_GET['edit_inventory'] ?? 0);
$editingInventory = $inventoryEditId > 0 ? shop_find_product($products, $inventoryEditId) : null;
$userEditId = (int) ($_GET['edit_user'] ?? 0);
$editingUser = $userEditId > 0 ? shop_find_user($users, $userEditId) : null;
$pluginEditId = (int) ($_GET['edit_plugin'] ?? 0);
$editingPlugin = $pluginEditId > 0 ? shop_find_plugin($plugins, $pluginEditId) : null;

require_once __DIR__ . '/controllers/actions.php';

$products = shop_get_products();
usort($products, static fn (array $a, array $b): int => ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0)));

$metrics = shop_product_dashboard_metrics($products);
$homePreview = array_slice(shop_sort_products_for_home($products), 0, 6);
$pagePreview = array_slice(shop_sort_products_for_page($products), 0, 6);

$selectedProduct = $editingProduct ? shop_normalize_product($editingProduct, (int) ($editingProduct['id'] ?? 0)) : [
    'id' => 0,
    'name' => '',
    'category' => $categoryOptions[0] ?? '未分类',
    'sales' => 0,
    'published_at' => date('Y-m-d H:i:s'),
    'price' => 0,
    'stock' => 0,
    'tag' => '',
    'home_sort' => 0,
    'page_sort' => 0,
    'sku' => '',
    'cover_image' => '',
    'description' => '',
    'status' => 'on_sale',
];

$selectedCategory = (string) ($selectedProduct['category'] ?? '未分类');
$publishedAtInput = shop_to_input_datetime((string) ($selectedProduct['published_at'] ?? ''));
if ($publishedAtInput === '') {
    $publishedAtInput = date('Y-m-d\TH:i');
}

$categoryChoices = $categoryOptions;
foreach ($products as $product) {
    $name = (string) ($product['category'] ?? '未分类');
    if (!in_array($name, $categoryChoices, true)) {
        $categoryChoices[] = $name;
    }
}

if ($selectedCategory === '') {
    $selectedCategory = $categoryChoices[0] ?? '未分类';
}

$selectedCategoryForm = $editingCategory !== null ? shop_normalize_category($editingCategory, (int) ($editingCategory['id'] ?? 0)) : [
    'id' => 0,
    'name' => '',
    'description' => '',
    'accent' => '#cbd5e1',
    'emoji' => '🛍️',
    'sort' => count($categories) + 1,
];

$selectedInventoryForm = $editingInventory !== null ? shop_normalize_product($editingInventory, (int) ($editingInventory['id'] ?? 0)) : [
    'id' => 0,
    'name' => '',
    'category' => $categoryChoices[0] ?? '未分类',
    'sales' => 0,
    'published_at' => date('Y-m-d H:i:s'),
    'price' => 0,
    'stock' => 0,
    'tag' => '',
    'home_sort' => 0,
    'page_sort' => 0,
    'sku' => '',
    'cover_image' => '',
    'description' => '',
    'status' => 'on_sale',
];

$selectedInventoryPublishedAtInput = shop_to_input_datetime((string) ($selectedInventoryForm['published_at'] ?? ''));
if ($selectedInventoryPublishedAtInput === '') {
    $selectedInventoryPublishedAtInput = date('Y-m-d\TH:i');
}

$selectedUserForm = $editingUser !== null ? $editingUser : [
    'id' => 0,
    'username' => '',
    'name' => '',
    'phone' => '',
    'level' => '普通会员',
    'status' => 'active',
    'address' => '',
    'last_login' => date('Y-m-d H:i:s'),
    'note' => '',
];

$pdo = get_db_connection();
$prefix = get_db_prefix();
$wechatQr = '';
$alipayQr = '';
$requireAddress = '0';

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('wechat_qr', 'alipay_qr', 'require_address')");
        while ($row = $stmt->fetch()) {
            if ($row['key'] === 'wechat_qr') $wechatQr = $row['value'];
            if ($row['key'] === 'alipay_qr') $alipayQr = $row['value'];
            if ($row['key'] === 'require_address') $requireAddress = $row['value'];
        }
    } catch (PDOException $e) {}
}

$statusValue = (string) ($selectedProduct['status'] ?? 'on_sale');
$storageState = get_db_connection() !== null ? '已连接' : '连接失败';
$fileState = '基于数据库';

$categorySummary = [];
foreach ($categoryChoices as $categoryName) {
    $items = array_values(array_filter($products, static fn (array $product): bool => (string) ($product['category'] ?? '') === $categoryName));
    $top = $items !== [] ? shop_sort_products_by_field($items, 'home_sort')[0] : null;
    $info = shop_get_category_info($categoryName);

    $categorySummary[] = [
        'name' => $categoryName,
        'emoji' => (string) ($info['emoji'] ?? '🛍️'),
        'accent' => (string) ($info['accent'] ?? '#cbd5e1'),
        'description' => (string) ($info['description'] ?? ''),
        'count' => count($items),
        'top_name' => $top !== null ? (string) ($top['name'] ?? '') : '暂无商品',
        'top_sales' => $top !== null ? (int) ($top['sales'] ?? 0) : 0,
    ];
}

usort($categorySummary, static fn (array $a, array $b): int => ((int) ($a['count'] ?? 0)) === ((int) ($b['count'] ?? 0))
    ? strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''))
    : (((int) ($b['count'] ?? 0)) <=> ((int) ($a['count'] ?? 0))));

$lowStockProducts = array_values(array_filter($products, static fn (array $product): bool => (int) ($product['stock'] ?? 0) <= 50));
usort($lowStockProducts, static fn (array $a, array $b): int => ((int) ($a['stock'] ?? 0)) <=> ((int) ($b['stock'] ?? 0)) ?: (((int) ($b['sales'] ?? 0)) <=> ((int) ($a['sales'] ?? 0))));

$dbOrders = shop_get_orders();
$orders = [];
foreach ($dbOrders as $order) {
    $orders[] = [
        'id' => $order['id'],
        'no' => $order['order_no'],
        'customer' => $order['customer'] ?? '未知用户',
        'phone' => $order['phone'] ?? '',
        'address' => $order['address'] ?? '',
        'status' => $order['status'],
        'statusClass' => shop_admin_order_status_class($order['status']),
        'time' => $order['time'],
        'total' => (float)$order['total'],
        'tracking_numbers' => $order['tracking_numbers'] ?? '',
    ];
}

$orderStats = [
    'pending_confirm' => 0,
    'pending_ship' => 0,
    'done' => 0,
    'total' => count($orders),
];

foreach ($orders as $order) {
    $status = (string) ($order['status'] ?? '');

    if ($status === '已支付 待确认 未发货') {
        $orderStats['pending_confirm']++;
    } elseif ($status === '已支付 已确认 待发货') {
        $orderStats['pending_ship']++;
    } elseif ($status === '已支付 已确认 已发货') {
        $orderStats['done']++;
    }
}

$userStats = [
    'total' => count($users),
    'online' => 0,
    'offline' => 0,
];

foreach ($users as $user) {
    $statusLabel = shop_admin_user_status_label((string) ($user['last_login'] ?? ''));

    if ($statusLabel === '在线') {
        $userStats['online']++;
    } else {
        $userStats['offline']++;
    }
}

// Update roles member count
$roles = [
    ['name' => '超级管理员', 'members' => 0, 'scope' => '全站', 'desc' => '拥有最高权限'],
    ['name' => '管理员', 'members' => 0, 'scope' => '后台', 'desc' => '管理商品、订单等基础业务'],
    ['name' => '高级会员', 'members' => 0, 'scope' => '前台', 'desc' => '享受专属折扣等特权'],
    ['name' => '普通会员', 'members' => 0, 'scope' => '前台', 'desc' => '默认基础会员权限'],
];

foreach ($users as $user) {
    $level = (string)($user['level'] ?? '普通会员');
    foreach ($roles as &$role) {
        if ($role['name'] === $level) {
            $role['members']++;
            break;
        }
    }
}

$inventoryRows = $products;
usort($inventoryRows, static fn (array $a, array $b): int => ((int) ($a['stock'] ?? 0)) <=> ((int) ($b['stock'] ?? 0)) ?: (((int) ($b['sales'] ?? 0)) <=> ((int) ($a['sales'] ?? 0))));

$inventoryStats = [
    'total' => count($inventoryRows),
    'low' => count($lowStockProducts),
    'zero' => 0,
    'stock_total' => 0,
];

foreach ($inventoryRows as $product) {
    $stock = (int) ($product['stock'] ?? 0);
    $inventoryStats['stock_total'] += $stock;

    if ($stock === 0) {
        $inventoryStats['zero']++;
    }
}

$categoryUsageMap = [];
foreach ($categorySummary as $summary) {
    $categoryUsageMap[(string) ($summary['name'] ?? '')] = $summary;
}

// Plugin code removed

$categoryManagementRows = [];
foreach ($categories as $category) {
    $categoryName = (string) ($category['name'] ?? '未分类');
    $items = array_values(array_filter($products, static fn (array $product): bool => (string) ($product['category'] ?? '') === $categoryName));
    $top = $items !== [] ? shop_sort_products_by_field($items, 'home_sort')[0] : null;

    $categoryManagementRows[] = [
        'id' => (int) ($category['id'] ?? 0),
        'name' => $categoryName,
        'description' => (string) ($category['description'] ?? ''),
        'accent' => (string) ($category['accent'] ?? '#cbd5e1'),
        'emoji' => (string) ($category['emoji'] ?? '🛍️'),
        'sort' => (int) ($category['sort'] ?? 0),
        'count' => count($items),
        'top_name' => $top !== null ? (string) ($top['name'] ?? '') : '暂无商品',
        'top_sales' => $top !== null ? (int) ($top['sales'] ?? 0) : 0,
    ];
}

usort($categoryManagementRows, static fn (array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));

// Roles now calculated dynamically above

$settings = [
    ['label' => '站点名称', 'value' => '魔女小店'],
    ['label' => '首页排序规则', 'value' => 'home_sort > 0 固定，0 按销量'],
    ['label' => '商品页排序规则', 'value' => 'page_sort > 0 固定，0 按销量'],
    ['label' => '商品总数', 'value' => shop_format_sales((int) $metrics['count'])],
    ['label' => '累计销量', 'value' => shop_format_sales((int) $metrics['sales'])],
    ['label' => '数据模式', 'value' => 'JSON 文件驱动'],
];

$currentTab = $_GET['tab'] ?? 'dashboard';
require __DIR__ . '/views/layout.php';
