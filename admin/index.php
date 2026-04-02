<?php
declare(strict_types=1);

if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    header('Location: ../index.php?page=admin');
    exit;
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/pagination.php';

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
    'email' => '',
    'phone' => '',
    'level' => '普通会员',
    'status' => 'active',
    'address' => '',
    'last_login' => date('Y-m-d H:i:s'),
    'note' => '',
];

$userRows = $users;

$pdo = get_db_connection();
$prefix = get_db_prefix();
$wechatQr = '';
$alipayQr = '';
$requireAddress = '0';
$perPage = 20;
$productsPage = max(1, (int) ($_GET['products_page'] ?? 1));
$ordersPage = max(1, (int) ($_GET['orders_page'] ?? 1));
$usersPage = max(1, (int) ($_GET['users_page'] ?? 1));
$orderStatusFilter = shop_normalize_order_status(trim((string) ($_GET['order_status'] ?? '')));
$productCategoryFilter = trim((string) ($_GET['product_category'] ?? ''));
$productStatusFilter = trim((string) ($_GET['product_status'] ?? ''));

if ($productCategoryFilter !== '' && !in_array($productCategoryFilter, $categoryChoices, true)) {
    $productCategoryFilter = '';
}

if (!in_array($productStatusFilter, ['', 'on_sale', 'off_sale'], true)) {
    $productStatusFilter = '';
}

$orderStatusOptions = shop_order_status_options();
if ($orderStatusFilter !== '' && !isset($orderStatusOptions[$orderStatusFilter])) {
    $orderStatusFilter = '';
}

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

$productRows = $products;
$productPagination = shop_paginate(count($products), $perPage, $productsPage);
$productPaginationBase = $adminUrl . '&tab=products';
if ($productCategoryFilter !== '') {
    $productPaginationBase .= '&product_category=' . urlencode($productCategoryFilter);
}
if ($productStatusFilter !== '') {
    $productPaginationBase .= '&product_status=' . urlencode($productStatusFilter);
}
$productPaginationUrl = $productPaginationBase . '&products_page=';
$userPagination = shop_paginate(count($users), $perPage, $usersPage);
$userPaginationUrl = $adminUrl . '&tab=users&users_page=';
$orderPagination = shop_paginate(0, $perPage, $ordersPage);
$orderPaginationBase = $adminUrl . '&tab=orders';
if ($orderStatusFilter !== '') {
    $orderPaginationBase .= '&order_status=' . urlencode($orderStatusFilter);
}
$orderPaginationUrl = $orderPaginationBase . '&orders_page=';
$pagedOrderRows = [];

if ($pdo) {
    try {
        $productWhere = [];
        $productParams = [];
        if ($productCategoryFilter !== '') {
            $productWhere[] = 'category = ?';
            $productParams[] = $productCategoryFilter;
        }
        if ($productStatusFilter !== '') {
            $productWhere[] = 'status = ?';
            $productParams[] = $productStatusFilter;
        }
        $productWhereSql = $productWhere === [] ? '' : ' WHERE ' . implode(' AND ', $productWhere);
        $productCountStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}products`" . $productWhereSql);
        $productCountStmt->execute($productParams);
        $productTotal = (int) $productCountStmt->fetchColumn();
        $productPagination = shop_paginate($productTotal, $perPage, $productsPage);
        $productStmt = $pdo->prepare("SELECT * FROM `{$prefix}products`" . $productWhereSql . " ORDER BY id ASC LIMIT ? OFFSET ?");
        $productBindIndex = 1;
        foreach ($productParams as $productParam) {
            $productStmt->bindValue($productBindIndex, $productParam, PDO::PARAM_STR);
            $productBindIndex++;
        }
        $productStmt->bindValue($productBindIndex, (int) $productPagination['limit'], PDO::PARAM_INT);
        $productStmt->bindValue($productBindIndex + 1, (int) $productPagination['offset'], PDO::PARAM_INT);
        $productStmt->execute();
        $productRows = array_map(
            static fn (array $row): array => shop_normalize_product($row, (int) ($row['id'] ?? 0)),
            $productStmt->fetchAll()
        );

        $userTotal = (int) $pdo->query("SELECT COUNT(*) FROM `{$prefix}users`")->fetchColumn();
        $userPagination = shop_paginate($userTotal, $perPage, $usersPage);
        $userStmt = $pdo->prepare("SELECT * FROM `{$prefix}users` ORDER BY id ASC LIMIT ? OFFSET ?");
        $userStmt->bindValue(1, (int) $userPagination['limit'], PDO::PARAM_INT);
        $userStmt->bindValue(2, (int) $userPagination['offset'], PDO::PARAM_INT);
        $userStmt->execute();
        $userRows = array_map(static function (array $row): array {
            $normalized = shop_normalize_user($row, (int) ($row['id'] ?? 0));
            $normalized['created_at'] = (string) ($row['created_at'] ?? '');
            return $normalized;
        }, $userStmt->fetchAll());

        $orderWhere = [];
        $orderParams = [];
        if ($orderStatusFilter !== '') {
            $orderWhere[] = 'status = ?';
            $orderParams[] = $orderStatusFilter;
        }
        $orderWhereSql = $orderWhere === [] ? '' : ' WHERE ' . implode(' AND ', $orderWhere);
        $orderCountStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}orders`" . $orderWhereSql);
        $orderCountStmt->execute($orderParams);
        $orderTotal = (int) $orderCountStmt->fetchColumn();
        $orderPagination = shop_paginate($orderTotal, $perPage, $ordersPage);
        $orderStmt = $pdo->prepare("SELECT * FROM `{$prefix}orders`" . $orderWhereSql . " ORDER BY id DESC LIMIT ? OFFSET ?");
        $orderBindIndex = 1;
        foreach ($orderParams as $orderParam) {
            $orderStmt->bindValue($orderBindIndex, $orderParam, PDO::PARAM_STR);
            $orderBindIndex++;
        }
        $orderStmt->bindValue($orderBindIndex, (int) $orderPagination['limit'], PDO::PARAM_INT);
        $orderStmt->bindValue($orderBindIndex + 1, (int) $orderPagination['offset'], PDO::PARAM_INT);
        $orderStmt->execute();
        $pagedOrderRows = array_map('shop_normalize_order', $orderStmt->fetchAll());
        $orderRows = $pagedOrderRows;
    } catch (PDOException $exception) {
        shop_log_exception('后台分页查询失败', $exception);
        $productPagination = shop_paginate(count($products), $perPage, $productsPage);
        $productRows = array_values(array_filter($products, static function (array $product) use ($productCategoryFilter, $productStatusFilter): bool {
            if ($productCategoryFilter !== '' && (string) ($product['category'] ?? '') !== $productCategoryFilter) {
                return false;
            }
            if ($productStatusFilter !== '' && (string) ($product['status'] ?? '') !== $productStatusFilter) {
                return false;
            }

            return true;
        }));
        $productPagination = shop_paginate(count($productRows), $perPage, $productsPage);
        $productRows = array_slice($productRows, (int) $productPagination['offset'], (int) $productPagination['limit']);
        $userPagination = shop_paginate(count($users), $perPage, $usersPage);
        $userRows = array_slice($users, (int) $userPagination['offset'], (int) $userPagination['limit']);
        $fallbackOrders = array_values(array_filter(shop_get_orders(), static function (array $order) use ($orderStatusFilter): bool {
            return $orderStatusFilter === '' || (string) ($order['status'] ?? '') === $orderStatusFilter;
        }));
        $orderPagination = shop_paginate(count($fallbackOrders), $perPage, $ordersPage);
        $orderRows = array_slice($fallbackOrders, (int) $orderPagination['offset'], (int) $orderPagination['limit']);
    }
} else {
    $productRows = array_values(array_filter($products, static function (array $product) use ($productCategoryFilter, $productStatusFilter): bool {
        if ($productCategoryFilter !== '' && (string) ($product['category'] ?? '') !== $productCategoryFilter) {
            return false;
        }
        if ($productStatusFilter !== '' && (string) ($product['status'] ?? '') !== $productStatusFilter) {
            return false;
        }

        return true;
    }));
    $productPagination = shop_paginate(count($productRows), $perPage, $productsPage);
    $productRows = array_slice($productRows, (int) $productPagination['offset'], (int) $productPagination['limit']);
    $userRows = array_slice($users, (int) $userPagination['offset'], (int) $userPagination['limit']);
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
$orderRows = [];
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

    $orderRows[] = [
        'id' => (int) ($order['id'] ?? 0),
        'order_no' => (string) ($order['order_no'] ?? ''),
        'created_at' => (string) ($order['time'] ?? ''),
        'items_data' => $order['items_data'] ?? [],
        'items_summary' => $order['items_summary'] ?? '暂无商品',
        'quantity' => shop_order_items_quantity($order['items_data'] ?? []),
        'user_id' => $order['user_id'] ?? null,
        'express_company' => (string) ($order['express_company'] ?? ''),
        'total' => (float) ($order['total'] ?? 0),
        'status' => (string) ($order['status'] ?? ''),
    ];
}

if ($pdo && $pagedOrderRows !== []) {
    $orderRows = $pagedOrderRows;
} else {
    $orderRows = array_values(array_filter($orderRows, static function (array $order) use ($orderStatusFilter): bool {
        return $orderStatusFilter === '' || (string) ($order['status'] ?? '') === $orderStatusFilter;
    }));
    $orderPagination = shop_paginate(count($orderRows), $perPage, $ordersPage);
    $orderRows = array_slice($orderRows, (int) $orderPagination['offset'], (int) $orderPagination['limit']);
}

$orderStats = [
    'pending_confirm' => 0,
    'pending_ship' => 0,
    'done' => 0,
    'total' => count($orders),
];

foreach ($orders as $order) {
    $status = shop_normalize_order_status((string) ($order['status'] ?? ''));

    if ($status === 'paid') {
        $orderStats['pending_confirm']++;
    } elseif ($status === 'shipped') {
        $orderStats['pending_ship']++;
    } elseif ($status === 'completed') {
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

// 更新角色成员数。
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

// 插件数据已独立，不再在此处构造旧结构。

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

// 角色数据已在上方动态统计。

$settings = [
    ['label' => '站点名称', 'value' => '魔女小店'],
    ['label' => '首页排序规则', 'value' => 'home_sort > 0 固定，0 按销量'],
    ['label' => '商品页排序规则', 'value' => 'page_sort > 0 固定，0 按销量'],
    ['label' => '商品总数', 'value' => shop_format_sales((int) $metrics['count'])],
    ['label' => '累计销量', 'value' => shop_format_sales((int) $metrics['sales'])],
    ['label' => '数据模式', 'value' => 'JSON 文件驱动'],
];

require __DIR__ . '/views/layout.php';
