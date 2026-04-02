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

function shop_admin_flash(string $message, string $type = 'success'): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function shop_admin_status_label(string $status): string
{
    return $status === 'off_sale' ? '下架' : '上架';
}

function shop_admin_status_class(string $status): string
{
    return $status === 'off_sale' ? 'danger' : 'success';
}

function shop_admin_order_status_label(string $status): string
{
    return match ($status) {
        '已支付 待确认 未发货' => '待确认',
        '已支付 已确认 待发货' => '待发货',
        '已支付 已确认 已发货' => '已发货',
        default => $status !== '' ? $status : '未知',
    };
}

function shop_admin_order_status_class(string $status): string
{
    return match ($status) {
        '已支付 待确认 未发货' => 'warning',
        '已支付 已确认 待发货' => 'info',
        '已支付 已确认 已发货' => 'success',
        default => 'muted',
    };
}

function shop_admin_user_status_label(string $lastLogin): string
{
    if (empty($lastLogin)) return '沉睡';
    
    $loginTime = strtotime($lastLogin);
    if ($loginTime === false) return '沉睡';
    
    // 如果最后登录时间在 30 分钟内，视为在线
    if (time() - $loginTime < 1800) {
        return '在线';
    }
    
    return '离线';
}

function shop_admin_user_status_class(string $lastLogin): string
{
    $label = shop_admin_user_status_label($lastLogin);
    return match ($label) {
        '在线' => 'success',
        '离线' => 'muted',
        '沉睡' => 'muted',
        default => 'muted',
    };
}

function shop_admin_plugin_type_label(string $type): string
{
    return match ($type) {
        'pay' => '支付',
        default => '无',
    };
}

function shop_admin_plugin_type_class(string $type): string
{
    return match ($type) {
        'pay' => 'info',
        default => 'muted',
    };
}

function shop_admin_post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function shop_admin_post_int(string $key, int $default = 0): int
{
    return max(0, (int) ($_POST[$key] ?? $default));
}

function shop_admin_post_float(string $key, float $default = 0): float
{
    return max(0, (float) ($_POST[$key] ?? $default));
}

function shop_admin_post_checked(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['admin_action'] ?? '');
    $message = '操作完成';
    $messageType = 'success';

    switch ($action) {
        case 'save_category':
            $id = shop_admin_post_int('id');
            $oldCategory = $id > 0 ? shop_find_category($categories, $id) : null;
            $category = [
                'id' => $id,
                'name' => shop_admin_post_string('name'),
                'description' => shop_admin_post_string('description'),
                'accent' => shop_admin_post_string('accent', '#cbd5e1'),
                'emoji' => shop_admin_post_string('emoji', '🛍️'),
                'sort' => shop_admin_post_int('sort'),
            ];

            if ($category['name'] === '') {
                $category['name'] = '未分类';
            }

            $categories = shop_upsert_category($categories, $category);
            $categorySaved = shop_save_categories($categories);
            $productsSaved = true;

            if ($oldCategory !== null) {
                $oldName = (string) ($oldCategory['name'] ?? '');

                if ($oldName !== '' && $oldName !== $category['name']) {
                    foreach ($products as $index => $product) {
                        if ((string) ($product['category'] ?? '') === $oldName) {
                            $products[$index]['category'] = $category['name'];
                        }
                    }

                    $productsSaved = shop_save_products($products);
                }
            }

            if ($categorySaved && $productsSaved) {
                $message = $id > 0 ? '分类已更新。' : '分类已新增。';
            } else {
                $message = !$categorySaved ? '分类保存失败。' : '分类已保存，但同步商品分类失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_category':
            $id = shop_admin_post_int('id');
            $before = count($categories);
            $categories = shop_delete_category($categories, $id);

            if ($before === count($categories)) {
                $message = '未找到要删除的分类。';
                $messageType = 'error';
                break;
            }

            if (shop_save_categories($categories)) {
                $message = '分类已删除。';
            } else {
                $message = '分类删除失败。';
                $messageType = 'error';
            }
            break;

        case 'save_product':
            $id = shop_admin_post_int('id');
            $imagesInput = shop_admin_post_string('images');
            $imagesArr = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $imagesInput))));
            
            // Handle SKU/规格
            $skuInput = $_POST['sku'] ?? '';
            $skuData = [];
            if (is_array($skuInput)) {
                foreach ($skuInput as $skuItem) {
                    if (!empty(trim($skuItem['name'] ?? ''))) {
                        $skuData[] = [
                            'name' => trim($skuItem['name']),
                            'stock' => max(0, (int)($skuItem['stock'] ?? 0)),
                            'price' => max(0, (float)($skuItem['price'] ?? 0))
                        ];
                    }
                }
            }
            $skuJson = !empty($skuData) ? json_encode($skuData, JSON_UNESCAPED_UNICODE) : '';

            $product = [
                'id' => $id,
                'name' => shop_admin_post_string('name'),
                'category' => shop_admin_post_string('category'),
                'sales' => shop_admin_post_int('sales'),
                'published_at' => shop_from_input_datetime(shop_admin_post_string('published_at')),
                'price' => shop_admin_post_float('price'),
                'stock' => shop_admin_post_int('stock'),
                'tag' => shop_admin_post_string('tag'),
                'home_sort' => shop_admin_post_int('home_sort'),
                'page_sort' => shop_admin_post_int('page_sort'),
                'sku' => $skuJson,
                'cover_image' => shop_admin_post_string('cover_image'),
                'images' => array_values($imagesArr),
                'description' => shop_admin_post_string('description'),
                'status' => shop_admin_post_string('status', 'on_sale'),
            ];

            if ($product['name'] === '') {
                $product['name'] = '未命名商品';
            }

            if ($product['category'] === '') {
                $product['category'] = $categoryOptions[0] ?? '未分类';
            }

            if (!in_array($product['status'], ['on_sale', 'off_sale'], true)) {
                $product['status'] = 'on_sale';
            }

            $products = shop_upsert_product($products, $product);

            if (shop_save_products($products)) {
                $message = $id > 0 ? '商品已更新，首页/商品页排序已保存。' : '商品已新增，首页/商品页排序已保存。';
            } else {
                $message = '商品保存失败。';
                $messageType = 'error';
            }
            break;

        case 'update_sort':
            $id = (int) ($_POST['id'] ?? 0);
            $index = shop_find_product_index($products, $id);

            if ($index === null) {
                $message = '未找到要更新的商品。';
                $messageType = 'error';
                break;
            }

            $products[$index]['home_sort'] = max(0, (int) ($_POST['home_sort'] ?? 0));
            $products[$index]['page_sort'] = max(0, (int) ($_POST['page_sort'] ?? 0));

            if (shop_save_products($products)) {
                $message = '排序已保存：0 = 按销量，非 0 = 固定排序，数字越小越靠前。';
            } else {
                $message = '排序保存失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_product':
            $id = (int) ($_POST['id'] ?? 0);
            $before = count($products);
            $products = shop_delete_product($products, $id);

            if ($before === count($products)) {
                $message = '未找到要删除的商品。';
                $messageType = 'error';
                break;
            }

            if (shop_save_products($products)) {
                $message = '商品已删除。';
            } else {
                $message = '删除失败。';
                $messageType = 'error';
            }
            break;

        case 'reset_products':
            if (shop_reset_products()) {
                $message = '示例数据已恢复。';
            } else {
                $message = '恢复示例数据失败。';
                $messageType = 'error';
            }
            break;

        case 'save_user':
            $id = shop_admin_post_int('id');
            $username = shop_admin_post_string('username');
            
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            
            if ($username === '') {
                $stmt = $pdo->query("SELECT MAX(id) as max_id FROM `{$prefix}users`");
                $row = $stmt->fetch();
                $nextId = ($row['max_id'] ?? 0) + 1;
                $username = "ID $nextId";
            }
            
            // 检查用户名是否重复
            $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                shop_admin_flash('保存失败：用户名或 ID 已被占用。', 'error');
                header('Location: ' . $adminUrl . '#admin-users');
                exit;
            }

            $user = [
                'id' => $id,
                'username' => $username,
                'name' => shop_admin_post_string('name'),
                'phone' => shop_admin_post_string('phone'),
                'level' => shop_admin_post_string('level', '普通会员'),
                'status' => 'active',
                'address' => shop_admin_post_string('address'),
                'last_login' => shop_admin_post_string('last_login'),
                'note' => shop_admin_post_string('note'),
            ];

            if ($user['name'] === '') {
                $user['name'] = '未命名用户';
            }

            if ($user['last_login'] === '') {
                $user['last_login'] = date('Y-m-d H:i:s');
            }

            // 自己实现更新，因为 shop_upsert_user 没处理 username
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, phone=?, level=?, address=?, note=? WHERE id=?");
                    $stmt->execute([$user['username'], $user['name'], $user['phone'], $user['level'], $user['address'], $user['note'], $id]);
                    $message = '用户已更新。';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, phone, level, address, last_login, note) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$user['username'], $user['name'], $user['phone'], $user['level'], $user['address'], $user['note']]);
                    $message = '用户已新增。';
                }
            } catch (PDOException $e) {
                $message = '用户保存失败: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        case 'delete_user':
            $id = shop_admin_post_int('id');
            $before = count($users);
            $users = shop_delete_user($users, $id);

            if ($before === count($users)) {
                $message = '未找到要删除的用户。';
                $messageType = 'error';
                break;
            }

            if (shop_save_users($users)) {
                $message = '用户已删除。';
            } else {
                $message = '用户删除失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_order':
            $id = shop_admin_post_int('id');
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM `{$prefix}orders` WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = '订单已删除。';
                } catch (PDOException $e) {
                    $message = '订单删除失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        case 'save_payment':
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            
            $wechatQr = shop_admin_post_string('wechat_qr');
            $alipayQr = shop_admin_post_string('alipay_qr');
            $requireAddress = shop_admin_post_checked('require_address') ? '1' : '0';
            
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("REPLACE INTO `{$prefix}settings` (`key`, `value`) VALUES ('wechat_qr', ?), ('alipay_qr', ?), ('require_address', ?)");
                    $stmt->execute([$wechatQr, $alipayQr, $requireAddress]);
                    $message = '支付配置已更新。';
                } catch (PDOException $e) {
                    $message = '支付配置保存失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
            break;
            
        case 'update_order':
            $id = shop_admin_post_int('id');
            $tracking = shop_admin_post_string('tracking_numbers');
            $expressCompany = shop_admin_post_string('express_company');
            $status = shop_admin_post_string('status');
            
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}orders` SET tracking_numbers = ?, express_company = ?, status = ? WHERE id = ?");
                    $stmt->execute([$tracking, $expressCompany, $status, $id]);
                    $message = '订单已更新。';
                } catch (PDOException $e) {
                    $message = '订单更新失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        default:
            $message = '未知操作。';
            $messageType = 'error';
            break;
    }

    shop_admin_flash($message, $messageType);
    header('Location: ' . $adminUrl);
    exit;
}

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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo shop_e($pageTitle); ?></title>
    <style>
        :root {
            --bg: #f6f7fb;
            --surface: #ffffff;
            --line: #e5e7eb;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --sidebar: #111827;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { color: inherit; }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 56px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 16px;
            background: var(--surface);
            border-bottom: 1px solid var(--line);
        }

        .menu-btn {
            width: 40px; height: 40px; border: 0; border-radius: 12px; background: #f8fafc; cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
        }

        .shell { min-height: calc(100vh - 56px); }
        .overlay {
            position: fixed;
            inset: 56px 0 0 0;
            background: rgba(15, 23, 42, .42);
            opacity: 0;
            pointer-events: none;
            transition: .25s;
            z-index: 800;
        }

        .sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            width: 280px;
            height: calc(100vh - 56px);
            background: var(--sidebar);
            color: #fff;
            transform: translateX(-100%);
            transition: .25s;
            z-index: 900;
            overflow-y: auto;
        }

        body.sidebar-open .sidebar { transform: translateX(0); }
        body.sidebar-open .overlay { opacity: 1; pointer-events: auto; }

        .sidebar-header { padding: 20px 18px 14px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .sidebar-title { font-size: 18px; font-weight: 700; }
        .sidebar-sub { margin-top: 6px; color: #9ca3af; font-size: 13px; line-height: 1.7; }
        .sidebar-nav { padding: 12px 10px 18px; }
        .nav-group { margin-top: 14px; }
        .nav-group h3 { padding: 0 10px 8px; color: #cbd5e1; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; }
        .nav-link {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 11px 12px;
            border-radius: 12px;
            color: #e5e7eb;
            text-decoration: none;
            font-size: 14px;
        }
        .nav-link:hover, .nav-link.active { background: rgba(255,255,255,.08); }
        .nav-link span { color: #9ca3af; font-size: 12px; white-space: nowrap; }

        .main { max-width: 1280px; margin: 0 auto; padding: 24px 16px 48px; }
        .section, .panel, .card { background: var(--surface); border: 1px solid var(--line); border-radius: 18px; box-shadow: 0 10px 30px rgba(15,23,42,.04); }
        .section { padding: 24px; }
        .grid { margin-top: 16px; display: grid; grid-template-columns: 1fr; gap: 16px; }
        .card { padding: 18px; }
        .card strong { display: block; font-size: 22px; line-height: 1.2; }
        .card span { display: block; margin-top: 8px; color: var(--muted); font-size: 13px; line-height: 1.7; }
        .card small { display: block; margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.5; }

        .kicker { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: rgba(37,99,235,.1); color: var(--primary); font-size: 12px; font-weight: 700; }
        .title { margin-top: 14px; font-size: 30px; line-height: 1.3; }
        .desc { margin-top: 12px; color: var(--muted); font-size: 15px; line-height: 1.8; }
        .section-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-end; }
        .section-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .section-title { font-size: 22px; line-height: 1.3; }
        .section-note { margin-top: 6px; color: var(--muted); font-size: 13px; line-height: 1.7; }
        .badge { display: inline-flex; align-items: center; justify-content: center; padding: 8px 12px; border-radius: 999px; background: #f8fafc; color: #475569; font-size: 13px; white-space: nowrap; }

        .flash { margin-bottom: 16px; padding: 14px 16px; border-radius: 14px; border: 1px solid transparent; font-size: 14px; line-height: 1.7; }
        .flash.success { background: #ecfdf5; border-color: #bbf7d0; color: #047857; }
        .flash.error { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

        .simple-list { list-style: none; display: grid; gap: 10px; margin-top: 16px; }
        .simple-list li { padding: 14px 16px; border-radius: 14px; background: #f8fafc; color: var(--text); line-height: 1.6; }
        .simple-list strong { display: block; }
        .simple-list small { display: block; margin-top: 6px; color: var(--muted); font-size: 12px; }

        .form-grid { margin-top: 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .field { display: flex; flex-direction: column; gap: 6px; }
        .field-full { grid-column: 1 / -1; }
        .label { color: var(--muted); font-size: 13px; line-height: 1.4; }
        .help { color: var(--muted); font-size: 12px; line-height: 1.6; }
        .field input, .field select, .field textarea {
            width: 100%; padding: 11px 12px; border: 1px solid var(--line); border-radius: 12px; background: #fff; color: var(--text); font: inherit; outline: none;
        }
        .field input:focus, .field select:focus, .field textarea:focus { border-color: rgba(37,99,235,.55); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .field textarea { min-height: 108px; resize: vertical; }
        .check-row {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            min-height: 44px;
        }
        .check-row input { width: auto; }

        .actions { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid transparent; text-decoration: none; cursor: pointer; font: inherit; line-height: 1.2; transition: .15s; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: #f8fafc; border-color: var(--line); color: var(--text); }
        .btn-danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
        .btn-soft { background: #eef2ff; border-color: #c7d2fe; color: #1d4ed8; }
        .btn-sm { padding: 8px 12px; border-radius: 10px; font-size: 13px; }

        .table-wrap { margin-top: 16px; overflow-x: auto; }
        .table { border-collapse: collapse; min-width: 1120px; width: 100%; }
        .table th, .table td { padding: 12px 10px; border-bottom: 1px solid var(--line); vertical-align: top; text-align: left; }
        .table th { background: #f8fafc; color: var(--muted); font-size: 13px; font-weight: 700; position: sticky; top: 0; z-index: 1; }
        .name { color: var(--text); font-size: 15px; font-weight: 700; line-height: 1.4; }
        .meta { margin-top: 6px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .status-pill { display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; border-radius: 999px; font-size: 12px; line-height: 1; white-space: nowrap; }
        .status-pill.success { background: #ecfdf5; color: #059669; }
        .status-pill.danger { background: #fef2f2; color: #dc2626; }
        .status-pill.info { background: #eff6ff; color: #2563eb; }
        .status-pill.warning { background: #fffbeb; color: #d97706; }
        .status-pill.muted { background: #f1f5f9; color: #475569; }
        .sort-form { display: grid; gap: 8px; }
        .sort-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        .sort-fields input { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 10px; background: #fff; }
        .row-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .preview-grid { margin-top: 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .preview-card { padding: 14px; border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .preview-title { font-size: 16px; line-height: 1.4; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .preview-list { list-style: none; display: grid; gap: 10px; margin-top: 12px; }
        .preview-item { padding: 10px 12px; border-radius: 12px; background: #fff; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; gap: 12px; }
        .preview-item strong { display: block; color: var(--text); font-size: 14px; line-height: 1.5; }
        .preview-item span { display: block; margin-top: 4px; color: var(--muted); font-size: 12px; line-height: 1.5; }
        .preview-meta { color: var(--primary); font-size: 12px; font-weight: 700; white-space: nowrap; }

        .status-grid { margin-top: 16px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .status-grid.two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stack { grid-template-columns: 1fr !important; }
        .category-grid { margin-top: 16px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .category-card { padding: 14px; border-radius: 14px; background: #fff; border: 1px solid #e2e8f0; }
        .category-card strong { display: block; font-size: 15px; line-height: 1.4; }
        .category-card p { margin-top: 8px; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .category-row { margin-top: 10px; display: flex; justify-content: space-between; gap: 10px; align-items: center; }
        .category-row small { color: var(--muted); font-size: 12px; line-height: 1.5; }

        .anchor { scroll-margin-top: 76px; }
        .mobile-note { display: none; margin-top: 12px; color: var(--muted); font-size: 12px; line-height: 1.6; }

        @media (max-width: 1024px) {
            .status-grid, .preview-grid, .category-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 768px) {
            .main { padding: 20px 12px 36px; }
            .title { font-size: 26px; }
            .form-grid, .sort-fields, .preview-grid, .status-grid, .category-grid { grid-template-columns: 1fr; }
            .section-head { flex-direction: column; align-items: flex-start; }
            .mobile-note { display: block; }
            .sidebar { width: min(88vw, 300px); }
        }
    </style>
</head>
<body>
<header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="打开管理菜单">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <strong>魔女小店 / 管理后台</strong>
</header>

<div class="shell">
    <div class="overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar" aria-label="管理菜单">
        <div class="sidebar-header">
            <div class="sidebar-title">管理后台</div>
            <div class="sidebar-sub">首页看板、商品、分类、库存、订单、用户、插件和配置模块都已整理好。</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">
                <h3>总览</h3>
                <a class="nav-link active" href="#admin-dashboard"><span>首页看板</span><span>Dashboard</span></a>
                <a class="nav-link" href="#admin-platform"><span>平台状态</span><span>Status</span></a>
            </div>
            <div class="nav-group">
                <h3>商品管理</h3>
                <a class="nav-link" href="#admin-editor"><span>商品编辑</span><span>Edit</span></a>
                <a class="nav-link" href="#admin-products"><span>商品列表</span><span>List</span></a>
                <a class="nav-link" href="#admin-categories"><span>分类管理</span><span>Categories</span></a>
                <a class="nav-link" href="#admin-stock"><span>库存管理</span><span>Stock</span></a>
            </div>
            <div class="nav-group">
                <h3>业务模块</h3>
                <a class="nav-link" href="#admin-orders"><span>订单管理</span><span>Orders</span></a>
                <a class="nav-link" href="#admin-users"><span>用户管理</span><span>Users</span></a>
            </div>
            <div class="nav-group">
                <h3>系统设置</h3>
                <a class="nav-link" href="#admin-roles"><span>权限角色</span><span>Roles</span></a>
                <a class="nav-link" href="#admin-payment"><span>支付管理</span><span>Payment</span></a>
                <a class="nav-link" href="#admin-settings"><span>站点配置</span><span>Settings</span></a>
            </div>
        </nav>
    </aside>

    <main class="main">
        <?php if (is_array($flash)): ?>
            <div class="flash <?php echo shop_e((string) ($flash['type'] ?? 'success')); ?>">
                <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <section class="section anchor" id="admin-dashboard">
            <div class="section-head">
                <div>
                    <span class="kicker">首页看板 / 商品管理后台</span>
                    <h1 class="title"><?php echo shop_e($editingProduct ? '编辑商品并维护两个排序字段' : '后台商品管理：首页排序与商品页排序分开维护'); ?></h1>
                    <p class="desc">首页排序和商品页排序独立设置：0 表示按销量排序；大于 0 时为固定排序，数字越接近 0 越靠前。后台商品列表可以直接维护两个排序字段，其他模块也已补齐成可继续扩展的管理页。</p>
                </div>
                <span class="badge">0 = 销量优先 / >0 = 固定排序</span>
            </div>

            <div class="status-grid" aria-label="概览卡片">
                <article class="card"><strong><?php echo shop_format_sales((int) $metrics['count']); ?></strong><span>商品总数</span></article>
                <article class="card"><strong><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong><span>首页固定</span></article>
                <article class="card"><strong><?php echo shop_format_sales((int) $metrics['page_priority_count']); ?></strong><span>商品页固定</span></article>
                <article class="card"><strong><?php echo shop_format_sales((int) $metrics['sales']); ?></strong><span>累计销量</span></article>
            </div>
        </section>

        <section class="grid">
            <div class="panel section anchor" id="admin-platform">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">平台状态</h2>
                        <p class="section-note">存储状态、排序规则和数据模式都能在这里快速查看。</p>
                    </div>
                    <span class="badge">Status</span>
                </div>

                <div class="status-grid two">
                    <article class="card"><strong><?php echo shop_e($storageState); ?></strong><span>数据库连接</span><small>MySQL 数据库连接状态</small></article>
                    <article class="card"><strong><?php echo shop_e($fileState); ?></strong><span>数据驱动</span><small>数据已由 MySQL 数据库接管</small></article>
                </div>

                <ul class="simple-list">
                    <li>首页排序：0 = 按销量排序，非 0 = 固定排序，数字越小越靠前。</li>
                    <li>商品页排序：page_sort 与首页排序互不影响。</li>
                    <li>保存商品后会写入数据库，前台页面会立即按新排序展示。</li>
                </ul>
            </div>

        </section>

        <section class="grid">
            <div class="panel section anchor" id="admin-editor">
                <div class="section-head">
                    <div>
                        <h2 class="section-title"><?php echo $editingProduct ? '编辑商品' : '新增商品'; ?></h2>
                        <p class="section-note">这里可以同时设置首页排序和商品页排序，也可以修改基础信息。</p>
                    </div>
                    <span class="badge"><?php echo $editingProduct ? '编辑模式' : '新增模式'; ?></span>
                </div>

                <form method="post">
                    <input type="hidden" name="admin_action" value="save_product">
                    <input type="hidden" name="id" value="<?php echo (int) ($selectedProduct['id'] ?? 0); ?>">

                    <div class="form-grid">
                        <label class="field field-full">
                            <span class="label">商品名称</span>
                            <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedProduct['name'] ?? '')); ?>" placeholder="请输入商品名称">
                        </label>

                        <label class="field">
                            <span class="label">商品分类</span>
                            <select name="category" required>
                                <?php foreach ($categoryChoices as $category): ?>
                                    <option value="<?php echo shop_e($category); ?>" <?php echo $selectedCategory === $category ? 'selected' : ''; ?>><?php echo shop_e($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="field">
                            <span class="label">商品状态</span>
                            <select name="status">
                                <option value="on_sale" <?php echo $statusValue === 'on_sale' ? 'selected' : ''; ?>>上架</option>
                                <option value="off_sale" <?php echo $statusValue === 'off_sale' ? 'selected' : ''; ?>>下架</option>
                            </select>
                        </label>

                        <label class="field"><span class="label">销量</span><input type="number" min="0" name="sales" value="<?php echo (int) ($selectedProduct['sales'] ?? 0); ?>"></label>
                        <label class="field"><span class="label">价格</span><input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedProduct['price'] ?? '0')); ?>"></label>
                        <label class="field"><span class="label">库存</span><input type="number" min="0" name="stock" value="<?php echo (int) ($selectedProduct['stock'] ?? 0); ?>"></label>
                        <label class="field"><span class="label">标签</span><input type="text" name="tag" value="<?php echo shop_e((string) ($selectedProduct['tag'] ?? '')); ?>" placeholder="例如：爆款 / 新品"></label>
                        <label class="field"><span class="label">首页排序</span><input type="number" min="0" name="home_sort" value="<?php echo (int) ($selectedProduct['home_sort'] ?? 0); ?>"><span class="help">0 = 按销量，数字越小越靠前</span></label>
                        <label class="field"><span class="label">商品页排序</span><input type="number" min="0" name="page_sort" value="<?php echo (int) ($selectedProduct['page_sort'] ?? 0); ?>"><span class="help">0 = 按销量，数字越小越靠前</span></label>
                        <label class="field"><span class="label">发布时间</span><input type="datetime-local" name="published_at" value="<?php echo shop_e($publishedAtInput); ?>"></label>
                        
                        <div class="field field-full">
                            <span class="label">商品规格 (SKU)</span>
                            <div id="sku-container" style="display: flex; flex-direction: column; gap: 10px;">
                                <?php 
                                $skus = [];
                                if (!empty($selectedProduct['sku'])) {
                                    $skus = json_decode($selectedProduct['sku'], true) ?: [];
                                }
                                if (empty($skus)) {
                                    $skus = [['name' => '', 'stock' => 0, 'price' => 0]];
                                }
                                foreach ($skus as $index => $sku): 
                                ?>
                                <div class="sku-item" style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <input type="text" name="sku[<?php echo $index; ?>][name]" value="<?php echo shop_e($sku['name'] ?? ''); ?>" placeholder="规格名 (如: 红色)" style="flex: 2;">
                                    <input type="number" name="sku[<?php echo $index; ?>][stock]" value="<?php echo (int)($sku['stock'] ?? 0); ?>" placeholder="库存" style="flex: 1;" min="0">
                                    <input type="number" name="sku[<?php echo $index; ?>][price]" value="<?php echo (float)($sku['price'] ?? 0); ?>" placeholder="价格" style="flex: 1;" step="0.01" min="0">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" style="align-self: flex-start; margin-top: 10px;" onclick="addSkuItem()">+ 添加规格</button>
                            <script>
                                let skuIndex = <?php echo count($skus); ?>;
                                function addSkuItem() {
                                    const container = document.getElementById('sku-container');
                                    const html = `
                                        <div class="sku-item" style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                            <input type="text" name="sku[${skuIndex}][name]" placeholder="规格名" style="flex: 2;">
                                            <input type="number" name="sku[${skuIndex}][stock]" placeholder="库存" style="flex: 1;" min="0" value="0">
                                            <input type="number" name="sku[${skuIndex}][price]" placeholder="价格" style="flex: 1;" step="0.01" min="0" value="0">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>
                                        </div>
                                    `;
                                    container.insertAdjacentHTML('beforeend', html);
                                    skuIndex++;
                                }
                            </script>
                        </div>
                        
                        <label class="field field-full">
                            <span class="label">商品多图 (一行一张图片链接，也可直接上传)</span>
                            <textarea id="imagesTextarea" name="images" placeholder="https://example.com/img1.jpg&#10;https://example.com/img2.jpg"><?php echo shop_e(implode("\n", $selectedProduct['images'] ?? [])); ?></textarea>
                            <input type="file" id="imageUpload" multiple accept="image/*" style="display: none;" onchange="handleImageUpload(event)">
                            <div style="display: flex; gap: 10px; margin-top: 5px;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('imageUpload').click()">上传图片</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="syncGallery()">刷新图库预览</button>
                            </div>
                            
                            <div id="galleryPreview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                <!-- 动态插入图片预览 -->
                            </div>
                        </label>
                        
                        <label class="field field-full">
                            <span class="label">指定封面图片 (在上方图库中点击图片即可设为封面，或手动输入)</span>
                            <input type="text" id="coverImageInput" name="cover_image" value="<?php echo shop_e((string) ($selectedProduct['cover_image'] ?? '')); ?>" placeholder="图片地址或留空">
                        </label>
                        
                        <script>
                        function handleImageUpload(event) {
                            const files = event.target.files;
                            if (!files.length) return;
                            
                            const textarea = document.getElementById('imagesTextarea');
                            
                            Array.from(files).forEach(file => {
                                const formData = new FormData();
                                formData.append('file', file);
                                
                                fetch('upload.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.url) {
                                        const currentText = textarea.value.trim();
                                        textarea.value = currentText ? currentText + '\n' + data.url : data.url;
                                        syncGallery();
                                    } else if (data.error) {
                                        alert(data.error);
                                    }
                                })
                                .catch(err => alert('上传出错'));
                            });
                        }
                        
                        function syncGallery() {
                            const textarea = document.getElementById('imagesTextarea');
                            const coverInput = document.getElementById('coverImageInput');
                            const gallery = document.getElementById('galleryPreview');
                            const currentCover = coverInput.value.trim();
                            
                            gallery.innerHTML = '';
                            const lines = textarea.value.split('\n').map(l => l.trim()).filter(l => l);
                            
                            lines.forEach(url => {
                                const isCover = url === currentCover;
                                const imgBox = document.createElement('div');
                                imgBox.style.cssText = `width: 80px; height: 80px; position: relative; border-radius: 6px; overflow: hidden; cursor: pointer; border: 3px solid ${isCover ? '#2563eb' : 'transparent'}`;
                                imgBox.onclick = () => {
                                    coverInput.value = url;
                                    syncGallery();
                                };
                                
                                const img = document.createElement('img');
                                img.src = url;
                                img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                                
                                const badge = document.createElement('div');
                                badge.style.cssText = `position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(37,99,235,0.9); color: white; font-size: 10px; text-align: center; display: ${isCover ? 'block' : 'none'}`;
                                badge.innerText = '封面';
                                
                                imgBox.appendChild(img);
                                imgBox.appendChild(badge);
                                gallery.appendChild(imgBox);
                            });
                        }
                        
                        // 初始化预览
                        setTimeout(syncGallery, 500);
                        </script>

                        <label class="field field-full"><span class="label">商品描述</span><textarea name="description" placeholder="请输入商品描述"><?php echo shop_e((string) ($selectedProduct['description'] ?? '')); ?></textarea></label>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><?php echo $editingProduct ? '保存修改' : '新增商品'; ?></button>
                        <?php if ($editingProduct): ?>
                            <a class="btn btn-secondary" href="<?php echo shop_e($adminUrl); ?>">取消编辑</a>
                        <?php endif; ?>
                        <span class="help">保存后会同步写入数据库，首页和商品页会立即按新排序展示。</span>
                    </div>
                </form>
            </div>

            <div class="panel section">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">排序预览与恢复</h2>
                        <p class="section-note">下面可直观看到首页排序和商品页排序的生效顺序。</p>
                    </div>
                    <span class="badge">实时预览</span>
                </div>

                <div class="preview-grid">
                    <div class="preview-card">
                        <div class="preview-title">首页排序预览</div>
                        <ol class="preview-list">
                            <?php if (empty($homePreview)): ?>
                                <li class="preview-item"><div><strong>暂无商品</strong><span>请先新增商品。</span></div></li>
                            <?php else: ?>
                                <?php foreach ($homePreview as $product): ?>
                                    <li class="preview-item">
                                        <div>
                                            <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                            <span><?php echo shop_e(shop_sort_label($product, 'home_sort', '首页排')); ?> · 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                        </div>
                                        <div class="preview-meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </div>

                    <div class="preview-card">
                        <div class="preview-title">商品页排序预览</div>
                        <ol class="preview-list">
                            <?php if (empty($pagePreview)): ?>
                                <li class="preview-item"><div><strong>暂无商品</strong><span>请先新增商品。</span></div></li>
                            <?php else: ?>
                                <?php foreach ($pagePreview as $product): ?>
                                    <li class="preview-item">
                                        <div>
                                            <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                            <span><?php echo shop_e(shop_sort_label($product, 'page_sort', '商品排')); ?> · 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                        </div>
                                        <div class="preview-meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </div>
                </div>

                <form method="post" class="actions" onsubmit="return confirm('确定要恢复示例数据吗？已保存的商品会被示例数据覆盖。');">
                    <input type="hidden" name="admin_action" value="reset_products">
                    <button class="btn btn-danger" type="submit">恢复示例数据</button>
                    <span class="help">如果你想重新开始测试排序，可以一键恢复默认示例商品。</span>
                </form>
            </div>
        </section>

        <section class="section panel anchor" id="admin-products" style="margin-top: 16px;">
            <div class="section-head">
                <div>
                    <h2 class="section-title">商品列表</h2>
                    <p class="section-note">每个商品都可以单独维护首页排序和商品页排序，保存后立即生效。</p>
                </div>
                <span class="badge"><?php echo count($products); ?> 件商品</span>
            </div>

            <p class="mobile-note">移动端下表格可横向滚动查看，排序输入框在每一行里直接修改即可。</p>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 22%;">商品</th>
                            <th style="width: 12%;">分类 / 状态</th>
                            <th style="width: 10%;">销量 / 库存</th>
                            <th style="width: 24%;">首页 / 商品页排序</th>
                            <th style="width: 20%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无商品，请先在上方新增商品。</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <?php $statusClass = shop_admin_status_class((string) ($product['status'] ?? 'on_sale')); ?>
                                <tr>
                                    <td>
                                        <div class="name"><a href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>" target="_blank" style="color: var(--primary); text-decoration: none;">#<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?></a></div>
                                        <div class="meta">SKU：<?php echo shop_e(trim((string) ($product['sku'] ?? '')) !== '' ? (string) ($product['sku'] ?? '') : '未设置'); ?></div>
                                        <div class="meta">上新：<?php echo shop_short_datetime((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></div>
                                    </td>
                                    <td>
                                        <div class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></div>
                                        <span class="status-pill <?php echo shop_e($statusClass); ?>"><?php echo shop_e(shop_admin_status_label((string) ($product['status'] ?? 'on_sale'))); ?></span>
                                        <div class="meta"><?php echo shop_e((string) ($product['tag'] ?? '')); ?></div>
                                    </td>
                                    <td>
                                        <div class="meta">销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>
                                        <div class="meta">库存 <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?></div>
                                        <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                    </td>
                                    <td>
                                        <form class="sort-form" method="post">
                                            <input type="hidden" name="admin_action" value="update_sort">
                                            <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                            <div class="sort-fields">
                                                <label class="field"><span class="label">首页排序</span><input type="number" min="0" name="home_sort" value="<?php echo (int) ($product['home_sort'] ?? 0); ?>"></label>
                                                <label class="field"><span class="label">商品页排序</span><input type="number" min="0" name="page_sort" value="<?php echo (int) ($product['page_sort'] ?? 0); ?>"></label>
                                            </div>
                                            <div class="help">0 = 销量优先，非 0 时数字越小越靠前。</div>
                                            <button class="btn btn-soft btn-sm" type="submit">保存排序</button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '&edit=' . (int) ($product['id'] ?? 0)); ?>">编辑</a>
                                            <form method="post" onsubmit="return confirm('确定删除这件商品吗？');">
                                                <input type="hidden" name="admin_action" value="delete_product">
                                                <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                                <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-categories">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">分类管理</h2>
                        <p class="section-note">支持添加、编辑和删除分类，分类会同步影响商品归类展示。</p>
                    </div>
                    <div class="section-actions">
                        <span class="badge"><?php echo count($categoryManagementRows); ?> 个分类</span>
                        <?php if ($editingCategory): ?>
                            <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '#admin-categories'); ?>">添加分类</a>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="admin_action" value="save_category">
                    <input type="hidden" name="id" value="<?php echo (int) ($selectedCategoryForm['id'] ?? 0); ?>">

                    <div class="form-grid">
                        <label class="field field-full">
                            <span class="label">分类名称</span>
                            <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedCategoryForm['name'] ?? '')); ?>" placeholder="请输入分类名称">
                        </label>

                        <label class="field">
                            <span class="label">分类图标</span>
                            <input type="text" name="emoji" maxlength="4" value="<?php echo shop_e((string) ($selectedCategoryForm['emoji'] ?? '🛍️')); ?>" placeholder="例如：🍰">
                        </label>

                        <label class="field">
                            <span class="label">主题颜色</span>
                            <input type="text" name="accent" value="<?php echo shop_e((string) ($selectedCategoryForm['accent'] ?? '#cbd5e1')); ?>" placeholder="#f59e0b">
                        </label>

                        <label class="field">
                            <span class="label">排序</span>
                            <input type="number" min="0" name="sort" value="<?php echo (int) ($selectedCategoryForm['sort'] ?? 0); ?>">
                        </label>

                        <label class="field field-full">
                            <span class="label">分类说明</span>
                            <textarea name="description" placeholder="请输入分类说明"><?php echo shop_e((string) ($selectedCategoryForm['description'] ?? '')); ?></textarea>
                        </label>
                    </div>

                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><?php echo $editingCategory ? '保存分类' : '添加分类'; ?></button>
                        <?php if ($editingCategory): ?>
                            <a class="btn btn-secondary" href="<?php echo shop_e($adminUrl . '#admin-categories'); ?>">取消编辑</a>
                        <?php endif; ?>
                        <span class="help">分类保存后会立即影响商品页与首页的分类展示。</span>
                    </div>
                </form>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 24%;">分类</th>
                                <th style="width: 34%;">说明</th>
                                <th style="width: 10%;">排序</th>
                                <th style="width: 12%;">商品数</th>
                                <th style="width: 20%;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categoryManagementRows)): ?>
                                <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无分类。</td></tr>
                            <?php else: ?>
                                <?php foreach ($categoryManagementRows as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="name" style="display:flex; align-items:center; gap:8px;">
                                                <span style="display:inline-flex; width:12px; height:12px; border-radius:50%; background:<?php echo shop_e((string) ($category['accent'] ?? '#cbd5e1')); ?>;"></span>
                                                <?php echo shop_e((string) ($category['emoji'] ?? '🛍️') . ' ' . (string) ($category['name'] ?? '')); ?>
                                            </div>
                                            <div class="meta">Top：<?php echo shop_e((string) ($category['top_name'] ?? '暂无商品')); ?> · 销量 <?php echo shop_format_sales((int) ($category['top_sales'] ?? 0)); ?></div>
                                        </td>
                                        <td class="meta"><?php echo shop_e((string) ($category['description'] ?? '')); ?></td>
                                        <td><div class="name"><?php echo (int) ($category['sort'] ?? 0); ?></div></td>
                                        <td><div class="name"><?php echo shop_format_sales((int) ($category['count'] ?? 0)); ?></div></td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '&edit_category=' . (int) ($category['id'] ?? 0) . '#admin-categories'); ?>">编辑</a>
                                                <form method="post" onsubmit="return confirm('确定删除这个分类吗？');">
                                                    <input type="hidden" name="admin_action" value="delete_category">
                                                    <input type="hidden" name="id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
                                                    <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel section anchor" id="admin-stock">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">库存管理</h2>
                        <p class="section-note">库存低于 50 的商品会自动汇总，便于及时补货。</p>
                    </div>
                    <span class="badge"><?php echo count($lowStockProducts); ?> 个预警</span>
                </div>

                <?php if (empty($lowStockProducts)): ?>
                    <ul class="simple-list"><li>暂无低库存商品。</li></ul>
                <?php else: ?>
                    <ul class="simple-list">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <li>
                                <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                <small>库存 <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件 · 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?> · 排序 首页 <?php echo shop_e(shop_sort_label($product, 'home_sort', '首页排')); ?> / 商品页 <?php echo shop_e(shop_sort_label($product, 'page_sort', '商品排')); ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-inventory">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">库存管理</h2>
                        <p class="section-note">支持添加、编辑和删除库存。库存低于 50 的商品会自动汇总预警。</p>
                    </div>
                    <div class="section-actions">
                        <span class="badge"><?php echo count($lowStockProducts); ?> 个预警</span>
                        <?php if ($editingInventory): ?>
                            <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '#admin-inventory'); ?>">添加库存</a>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="admin_action" value="save_product">
                    <input type="hidden" name="id" value="<?php echo (int) ($selectedInventoryForm['id'] ?? 0); ?>">
                    
                    <div class="form-grid">
                        <label class="field field-full">
                            <span class="label">商品名称</span>
                            <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedInventoryForm['name'] ?? '')); ?>" placeholder="请输入商品名称">
                        </label>
                        
                        <label class="field">
                            <span class="label">商品分类</span>
                            <select name="category" required>
                                <?php foreach ($categoryChoices as $category): ?>
                                    <option value="<?php echo shop_e($category); ?>" <?php echo (string)($selectedInventoryForm['category'] ?? '') === $category ? 'selected' : ''; ?>><?php echo shop_e($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        
                        <label class="field">
                            <span class="label">库存数量</span>
                            <input type="number" min="0" name="stock" value="<?php echo (int) ($selectedInventoryForm['stock'] ?? 0); ?>">
                        </label>
                        
                        <label class="field">
                            <span class="label">价格</span>
                            <input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedInventoryForm['price'] ?? '0')); ?>">
                        </label>
                        
                        <label class="field">
                            <span class="label">状态</span>
                            <select name="status">
                                <option value="on_sale" <?php echo (string)($selectedInventoryForm['status'] ?? 'on_sale') === 'on_sale' ? 'selected' : ''; ?>>上架</option>
                                <option value="off_sale" <?php echo (string)($selectedInventoryForm['status'] ?? '') === 'off_sale' ? 'selected' : ''; ?>>下架</option>
                            </select>
                        </label>
                        
                        <input type="hidden" name="sales" value="<?php echo (int) ($selectedInventoryForm['sales'] ?? 0); ?>">
                        <input type="hidden" name="published_at" value="<?php echo shop_e($selectedInventoryPublishedAtInput); ?>">
                        <input type="hidden" name="tag" value="<?php echo shop_e((string) ($selectedInventoryForm['tag'] ?? '')); ?>">
                        <input type="hidden" name="home_sort" value="<?php echo (int) ($selectedInventoryForm['home_sort'] ?? 0); ?>">
                        <input type="hidden" name="page_sort" value="<?php echo (int) ($selectedInventoryForm['page_sort'] ?? 0); ?>">
                        <input type="hidden" name="sku" value="<?php echo shop_e((string) ($selectedInventoryForm['sku'] ?? '')); ?>">
                        <input type="hidden" name="cover_image" value="<?php echo shop_e((string) ($selectedInventoryForm['cover_image'] ?? '')); ?>">
                        <input type="hidden" name="description" value="<?php echo shop_e((string) ($selectedInventoryForm['description'] ?? '')); ?>">
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><?php echo $editingInventory ? '保存库存' : '添加库存'; ?></button>
                        <?php if ($editingInventory): ?>
                            <a class="btn btn-secondary" href="<?php echo shop_e($adminUrl . '#admin-inventory'); ?>">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">商品</th>
                                <th style="width: 15%;">分类</th>
                                <th style="width: 15%;">库存</th>
                                <th style="width: 15%;">销量</th>
                                <th style="width: 25%;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventoryRows)): ?>
                                <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无库存。</td></tr>
                            <?php else: ?>
                                <?php foreach ($inventoryRows as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="name">#<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?></div>
                                            <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                        </td>
                                        <td class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></td>
                                        <td>
                                            <div class="name" style="color: <?php echo (int)($product['stock'] ?? 0) <= 50 ? '#dc2626' : 'inherit'; ?>">
                                                <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?>
                                            </div>
                                        </td>
                                        <td><div class="name"><?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div></td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '&edit_inventory=' . (int) ($product['id'] ?? 0) . '#admin-inventory'); ?>">编辑</a>
                                                <form method="post" onsubmit="return confirm('确定删除这个库存商品吗？');">
                                                    <input type="hidden" name="admin_action" value="delete_product">
                                                    <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                                    <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

<section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-orders">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">订单管理</h2>
                        <p class="section-note">管理所有订单信息，查看发货状态、手机号和地址。</p>
                    </div>
                    <span class="badge">Orders</span>
                </div>

                <div class="status-grid two">
                    <article class="card"><strong><?php echo $orderStats['pending_confirm'] ?? 0; ?></strong><span>待确认</span></article>
                    <article class="card"><strong><?php echo $orderStats['done'] ?? 0; ?></strong><span>已完成</span></article>
                    <article class="card"><strong><?php echo count($orders); ?></strong><span>订单总数</span></article>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 25%;">订单信息</th>
                                <th style="width: 20%;">客户/手机号</th>
                                <th style="width: 35%;">收货地址</th>
                                <th style="width: 20%;">金额/状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="4" class="meta" style="padding: 20px 10px;">暂无订单。</td></tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <div class="name">#<?php echo shop_e((string) ($order['no'] ?? '')); ?></div>
                                            <div class="meta"><?php echo shop_short_datetime((string) ($order['time'] ?? date('Y-m-d H:i:s'))); ?></div>
                                        </td>
                                        <td>
                                            <div class="name"><?php echo shop_e((string) ($order['customer'] ?? '')); ?></div>
                                            <div class="meta"><?php echo shop_e((string) ($order['phone'] ?? '')); ?></div>
                                        </td>
                                        <td class="meta" style="white-space: normal; line-height: 1.5;">
                                            <div style="margin-bottom: 8px; color: var(--text); font-weight: 500;"><?php echo shop_e($order['address'] ?: '未提供地址'); ?></div>
                                            <form method="post" action="index.php?page=admin">
                                                <input type="hidden" name="admin_action" value="update_order">
                                                <input type="hidden" name="id" value="<?php echo $order['id'] ?? 0; ?>">
                                                
                                                <div style="margin-bottom: 8px; display: flex; gap: 8px;">
                                                    <select name="status" style="padding: 6px; border-radius: 4px; border: 1px solid #cbd5e1; flex: 1;">
                                                        <option value="已支付 待确认 未发货" <?php echo ($order['status']??'') === '已支付 待确认 未发货' ? 'selected' : ''; ?>>已支付 待确认 未发货</option>
                                                        <option value="已支付 已确认 待发货" <?php echo ($order['status']??'') === '已支付 已确认 待发货' ? 'selected' : ''; ?>>已支付 已确认 待发货</option>
                                                        <option value="已支付 已确认 已发货" <?php echo ($order['status']??'') === '已支付 已确认 已发货' ? 'selected' : ''; ?>>已支付 已确认 已发货</option>
                                                    </select>
                                                </div>
                                                
                                                <div style="margin-bottom: 8px;">
                                                    <input type="text" name="express_company" placeholder="快递公司 (如: 顺丰)" value="<?php echo shop_e($order['express_company'] ?? ''); ?>" style="width: 100%; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;">
                                                </div>
                                                
                                                <textarea name="tracking_numbers" placeholder="输入快递单号，一行一个" style="width: 100%; min-height: 60px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo shop_e($order['tracking_numbers'] ?? ''); ?></textarea>
                                                
                                                <button type="submit" class="btn btn-soft btn-sm" style="margin-top: 8px;">保存物流及状态</button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="name"><?php echo shop_format_price((float) ($order['total'] ?? 0)); ?></div>
                                            <div style="margin-top: 6px;">
                                                <span class="status-pill <?php echo strpos($order['status']??'', '待确认') !== false ? 'warning' : 'success'; ?>"><?php echo shop_e($order['status']??''); ?></span>
                                            </div>
                                            <form method="post" action="index.php?page=admin" onsubmit="return confirm('确定删除这个订单吗？');" style="margin-top: 10px;">
                                                <input type="hidden" name="admin_action" value="delete_order">
                                                <input type="hidden" name="id" value="<?php echo $order['id'] ?? 0; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">删除订单</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-users">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">用户管理</h2>
                        <p class="section-note">支持添加、编辑和删除用户资料。</p>
                    </div>
                    <div class="section-actions">
                        <span class="badge"><?php echo count($users); ?> 个用户</span>
                        <?php if ($editingUser): ?>
                            <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '#admin-users'); ?>">添加用户</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="status-grid two" style="margin-bottom: 24px;">
                    <article class="card"><strong><?php echo $userStats['active'] ?? 0; ?></strong><span>活跃用户</span></article>
                    <article class="card"><strong><?php echo $userStats['follow_up'] ?? 0; ?></strong><span>待回访</span></article>
                    <article class="card"><strong><?php echo $userStats['sleeping'] ?? 0; ?></strong><span>沉睡用户</span></article>
                    <article class="card"><strong><?php echo count($users); ?></strong><span>用户总数</span></article>
                </div>

                <form method="post">
                    <input type="hidden" name="admin_action" value="save_user">
                    <input type="hidden" name="id" value="<?php echo (int) ($selectedUserForm['id'] ?? 0); ?>">
                    
                    <div class="form-grid">
                        <label class="field">
                            <span class="label">用户名</span>
                            <input type="text" name="username" value="<?php echo shop_e((string) ($selectedUserForm['username'] ?? '')); ?>" placeholder="请输入用户名（留空自动生成）">
                        </label>
                        
                        <label class="field">
                            <span class="label">昵称</span>
                            <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedUserForm['name'] ?? '')); ?>" placeholder="请输入用户昵称">
                        </label>
                        
                        <label class="field">
                            <span class="label">手机号</span>
                            <input type="text" name="phone" value="<?php echo shop_e((string) ($selectedUserForm['phone'] ?? '')); ?>" placeholder="请输入手机号">
                        </label>
                        
                        <label class="field">
                            <span class="label">会员等级</span>
                            <select name="level">
                                <?php 
                                $availableLevels = ['普通会员', '高级会员', '管理员', '超级管理员'];
                                $currentLvl = (string)($selectedUserForm['level'] ?? '普通会员');
                                foreach ($availableLevels as $lvl) {
                                    $sel = $lvl === $currentLvl ? 'selected' : '';
                                    echo "<option value=\"$lvl\" $sel>$lvl</option>";
                                }
                                ?>
                            </select>
                        </label>
                        
                        <label class="field">
                            <span class="label">状态</span>
                            <select name="status">
                                <option value="active" <?php echo (string)($selectedUserForm['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>活跃</option>
                                <option value="follow_up" <?php echo (string)($selectedUserForm['status'] ?? '') === 'follow_up' ? 'selected' : ''; ?>>待回访</option>
                                <option value="sleeping" <?php echo (string)($selectedUserForm['status'] ?? '') === 'sleeping' ? 'selected' : ''; ?>>沉睡</option>
                            </select>
                        </label>
                        
                        <label class="field field-full">
                            <span class="label">联系地址</span>
                            <input type="text" name="address" value="<?php echo shop_e((string) ($selectedUserForm['address'] ?? '')); ?>" placeholder="请输入用户联系地址">
                        </label>
                        
                        <label class="field field-full">
                            <span class="label">备注说明</span>
                            <textarea name="note" placeholder="请输入用户备注"><?php echo shop_e((string) ($selectedUserForm['note'] ?? '')); ?></textarea>
                        </label>
                        
                        <input type="hidden" name="last_login" value="<?php echo shop_e((string) ($selectedUserForm['last_login'] ?? date('Y-m-d H:i'))); ?>">
                    </div>
                    
                    <div class="actions">
                        <button class="btn btn-primary" type="submit"><?php echo $editingUser ? '保存用户' : '添加用户'; ?></button>
                        <?php if ($editingUser): ?>
                            <a class="btn btn-secondary" href="<?php echo shop_e($adminUrl . '#admin-users'); ?>">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 20%;">用户</th>
                                <th style="width: 15%;">联系方式</th>
                                <th style="width: 25%;">地址/备注</th>
                                <th style="width: 20%;">状态/最后登录</th>
                                <th style="width: 20%;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无用户。</td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <div class="name"><?php echo shop_e((string) ($user['name'] ?? '')); ?></div>
                                            <div class="meta">ID: <?php echo shop_e((string) ($user['id'] ?? '')); ?> | <?php echo shop_e((string) ($user['level'] ?? '')); ?></div>
                                            <div class="meta">用户名: <?php echo shop_e((string) ($user['username'] ?? '未设置')); ?></div>
                                        </td>
                                        <td>
                                            <div class="meta"><?php echo shop_e((string) ($user['phone'] ?? '')); ?></div>
                                        </td>
                                        <td class="meta" style="white-space: normal; line-height: 1.5;">
                                            <div><?php echo shop_e((string) ($user['address'] ?? '未设置地址')); ?></div>
                                            <div style="margin-top: 4px; color: #9ca3af;"><?php echo shop_e((string) ($user['note'] ?? '无备注')); ?></div>
                                        </td>
                                        <td>
                                            <?php $userStatus = shop_admin_user_status_label((string) ($user['last_login'] ?? '')); ?>
                                            <span class="status-pill <?php echo shop_e(shop_admin_user_status_class((string) ($user['last_login'] ?? ''))); ?>"><?php echo shop_e($userStatus); ?></span>
                                            <?php if ($userStatus !== '在线'): ?>
                                                <div class="meta" style="margin-top: 6px;">上次: <?php echo shop_e((string) ($user['last_login'] ?? '未知')); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="btn btn-secondary btn-sm" href="<?php echo shop_e($adminUrl . '&edit_user=' . (int) ($user['id'] ?? 0) . '#admin-users'); ?>">编辑</a>
                                                <form method="post" onsubmit="return confirm('确定删除这个用户吗？');">
                                                    <input type="hidden" name="admin_action" value="delete_user">
                                                    <input type="hidden" name="id" value="<?php echo (int) ($user['id'] ?? 0); ?>">
                                                    <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-roles">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">权限角色</h2>
                        <p class="section-note">角色与权限先做成模块化示例，后续可直接接到数据库。</p>
                    </div>
                    <span class="badge">Roles</span>
                </div>

                <ul class="simple-list">
                    <?php foreach ($roles as $role): ?>
                        <li>
                            <strong><?php echo shop_e((string) ($role['name'] ?? '')); ?></strong>
                            <small><?php echo shop_e((string) ($role['scope'] ?? '')); ?> · <?php echo shop_e((string) ($role['desc'] ?? '')); ?> · 成员数 <?php echo shop_format_sales((int) ($role['members'] ?? 0)); ?></small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="grid" style="margin-top: 16px;">
            <div class="panel section anchor" id="admin-payment">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">支付管理</h2>
                        <p class="section-note">在此配置微信支付和支付宝支付的收款码。</p>
                    </div>
                </div>

                <form method="post">
                    <input type="hidden" name="admin_action" value="save_payment">
                    
                    <div class="form-grid">
                        <label class="field field-full check-row">
                            <input type="checkbox" name="require_address" value="1" <?php echo $requireAddress === '1' ? 'checked' : ''; ?>>
                            <span class="label" style="margin: 0; color: inherit; font-size: 14px;">强制要求买家填写收货人、手机号和收货地址</span>
                        </label>

                        <label class="field field-full">
                            <span class="label">微信支付 (可填图片URL，也可点击上传)</span>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="wechat_qr" name="wechat_qr" value="<?php echo shop_e($wechatQr); ?>" placeholder="微信收款码地址" style="flex: 1;" oninput="updateQrPreview('wechat')">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('wechat_upload').click()">上传图</button>
                                <input type="file" id="wechat_upload" accept="image/*" style="display: none;" onchange="uploadPaymentQr(event, 'wechat')">
                            </div>
                            <div id="wechat_preview" style="margin-top: 10px; width: 150px; height: 150px; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if ($wechatQr): ?>
                                    <img src="<?php echo shop_e($wechatQr); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 12px;">暂无图片</span>
                                <?php endif; ?>
                            </div>
                        </label>
                        
                        <label class="field field-full">
                            <span class="label">支付宝支付 (可填图片URL，也可点击上传)</span>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="alipay_qr" name="alipay_qr" value="<?php echo shop_e($alipayQr); ?>" placeholder="支付宝收款码地址" style="flex: 1;" oninput="updateQrPreview('alipay')">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('alipay_upload').click()">上传图</button>
                                <input type="file" id="alipay_upload" accept="image/*" style="display: none;" onchange="uploadPaymentQr(event, 'alipay')">
                            </div>
                            <div id="alipay_preview" style="margin-top: 10px; width: 150px; height: 150px; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if ($alipayQr): ?>
                                    <img src="<?php echo shop_e($alipayQr); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 12px;">暂无图片</span>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>

                    <script>
                    function updateQrPreview(type) {
                        const input = document.getElementById(type + '_qr');
                        const preview = document.getElementById(type + '_preview');
                        const url = input.value.trim();
                        
                        if (url) {
                            preview.innerHTML = `<img src="${url}" style="width: 100%; height: 100%; object-fit: contain;">`;
                        } else {
                            preview.innerHTML = `<span style="color: #94a3b8; font-size: 12px;">暂无图片</span>`;
                        }
                    }

                    function uploadPaymentQr(event, type) {
                        const file = event.target.files[0];
                        if (!file) return;
                        
                        const formData = new FormData();
                        formData.append('file', file);
                        
                        fetch('upload.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.url) {
                                document.getElementById(type + '_qr').value = data.url;
                                updateQrPreview(type);
                            } else if (data.error) {
                                alert(data.error);
                            }
                        })
                        .catch(err => alert('上传出错'));
                    }
                    </script>
                    
                    <div class="actions">
                        <button class="btn btn-primary" type="submit">保存配置</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="section panel anchor" id="admin-settings" style="margin-top: 16px;">
            <div class="section-head">
                <div>
                    <h2 class="section-title">站点配置</h2>
                    <p class="section-note">把基础配置、排序规则和数据文件状态集中展示，后续可直接接入真实配置表。</p>
                </div>
                <span class="badge">Settings</span>
            </div>

            <ul class="simple-list">
                <?php foreach ($settings as $setting): ?>
                    <li>
                        <strong><?php echo shop_e((string) ($setting['label'] ?? '')); ?></strong>
                        <small><?php echo shop_e((string) ($setting['value'] ?? '')); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="actions">
                <a class="btn btn-secondary" href="index.php?page=home">查看首页</a>
                <a class="btn btn-secondary" href="index.php?page=products">查看商品页</a>
                <a class="btn btn-secondary" href="index.php?page=orders">查看订单页</a>
                <a class="btn btn-secondary" href="index.php?page=profile">查看个人中心</a>
            </div>
        </section>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('menuBtn');
        const overlay = document.getElementById('overlay');

        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
        };

        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-open');
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }

        document.querySelectorAll('.nav-link').forEach((link) => {
            link.addEventListener('click', closeSidebar);
        });

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });
    });
</script>
</body>
</html>