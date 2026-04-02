<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/order_status.php';

function shop_log_exception(string $context, Throwable $exception): void
{
    shop_log('error', $context, ['message' => $exception->getMessage()]);
}

function shop_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function shop_slugify(string $value, string $fallbackPrefix = 'item', int $fallbackId = 0): string
{
    $slug = trim($value);
    $slug = strtolower($slug);
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    if ($slug === '') {
        $slug = $fallbackPrefix . '-' . max(1, $fallbackId);
    }

    return $slug;
}

function shop_normalize_category(array $category, int $fallbackId = 0): array
{
    $name = trim((string) ($category['name'] ?? '未分类'));
    if ($name === '') {
        $name = '未分类';
    }

    $accent = trim((string) ($category['accent'] ?? '#cbd5e1'));
    if ($accent === '') {
        $accent = '#cbd5e1';
    }

    $emoji = trim((string) ($category['emoji'] ?? '🛍️'));
    if ($emoji === '') {
        $emoji = '🛍️';
    }

    return [
        'id' => max(0, (int) ($category['id'] ?? $fallbackId)),
        'name' => $name,
        'description' => trim((string) ($category['description'] ?? '')),
        'accent' => $accent,
        'emoji' => $emoji,
        'sort' => max(0, (int) ($category['sort'] ?? ($fallbackId > 0 ? $fallbackId : 0))),
    ];
}

function shop_get_categories(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}categories` ORDER BY sort ASC, name ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_category($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取分类失败', $e);
            return [];
        }
    }
    return [];
}

function shop_save_categories(array $categories): bool
{
    return true; // 已废弃，当前通过数据库增删改查直接处理。
}

function shop_find_category(array $categories, int $id): ?array
{
    foreach ($categories as $category) {
        if ((int) ($category['id'] ?? 0) === $id) {
            return $category;
        }
    }
    return null;
}

function shop_upsert_category(array $categories, array $category): array
{
    $pdo = get_db_connection();
    if (!$pdo) return $categories;
    
    $cat = shop_normalize_category($category, (int) ($category['id'] ?? 0));
    
    try {
        $prefix = get_db_prefix();
        if ($cat['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}categories` SET name=?, description=?, accent=?, emoji=?, sort=? WHERE id=?");
            $stmt->execute([$cat['name'], $cat['description'], $cat['accent'], $cat['emoji'], $cat['sort'], $cat['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}categories` (name, description, accent, emoji, sort) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cat['name'], $cat['description'], $cat['accent'], $cat['emoji'], $cat['sort']]);
            $cat['id'] = (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        shop_log_exception('保存分类失败', $e);
        return [];
    }
    
    return shop_get_categories();
}

function shop_delete_category(array $categories, int $id): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}categories` WHERE id=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            shop_log_exception('删除分类失败', $e);
            return [];
        }
    }
    return shop_get_categories();
}

function shop_category_meta(): array
{
    $meta = [];
    foreach (shop_get_categories() as $category) {
        $name = (string) ($category['name'] ?? '未分类');
        if ($name === '') continue;
        $meta[$name] = [
            'rank' => (int) ($category['sort'] ?? 999),
            'description' => (string) ($category['description'] ?? ''),
            'accent' => (string) ($category['accent'] ?? '#cbd5e1'),
            'emoji' => (string) ($category['emoji'] ?? '🛍️'),
        ];
    }
    return $meta;
}

function shop_get_category_info(string $category): array
{
    $meta = shop_category_meta();
    return $meta[$category] ?? [
        'rank' => 999,
        'description' => '精选商品分类。',
        'accent' => '#cbd5e1',
        'emoji' => '🛍️',
    ];
}

function shop_category_names(): array
{
    return array_keys(shop_category_meta());
}


function shop_normalize_product(array $product, int $fallbackId = 0): array
{
    $publishedAt = trim((string) ($product['published_at'] ?? ''));
    if ($publishedAt === '' || strtotime($publishedAt) === false) {
        $publishedAt = date('Y-m-d H:i:s');
    }

    $status = trim((string) ($product['status'] ?? 'on_sale'));
    if (!in_array($status, ['on_sale', 'off_sale'], true)) {
        $status = 'on_sale';
    }
    
    $images = $product['images'] ?? [];
    if (is_string($images)) {
        $images = json_decode($images, true) ?: [];
    }
    if (!is_array($images)) {
        $images = [];
    }

    return [
        'id' => max(0, (int) ($product['id'] ?? $fallbackId)),
        'name' => trim((string) ($product['name'] ?? '未命名商品')),
        'category' => trim((string) ($product['category'] ?? '未分类')),
        'sales' => max(0, (int) ($product['sales'] ?? 0)),
        'published_at' => $publishedAt,
        'price' => max(0, (float) ($product['price'] ?? 0)),
        'stock' => max(0, (int) ($product['stock'] ?? 0)),
        'tag' => trim((string) ($product['tag'] ?? '')),
        'home_sort' => max(0, (int) ($product['home_sort'] ?? 0)),
        'page_sort' => max(0, (int) ($product['page_sort'] ?? 0)),
        'sku' => trim((string) ($product['sku'] ?? '')),
        'cover_image' => trim((string) ($product['cover_image'] ?? '')),
        'images' => $images,
        'description' => trim((string) ($product['description'] ?? '')),
        'status' => $status,
    ];
}

function shop_get_products(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}products` ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_product($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取商品失败', $e);
            return [];
        }
    }
    return [];
}

function shop_save_products(array $products): bool
{
    return true; // 已废弃，当前通过数据库增删改查直接处理。
}

function shop_find_product_index(array $products, int $id): ?int
{
    foreach ($products as $index => $product) {
        if ((int) ($product['id'] ?? 0) === $id) return $index;
    }
    return null;
}

function shop_find_product(array $products, int $id): ?array
{
    foreach ($products as $product) {
        if ((int) ($product['id'] ?? 0) === $id) return $product;
    }
    return null;
}

function shop_upsert_product(array $products, array $product): array
{
    $pdo = get_db_connection();
    if (!$pdo) return $products;
    
    $p = shop_normalize_product($product, (int) ($product['id'] ?? 0));
    
    try {
        $prefix = get_db_prefix();
        if ($p['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}products` SET name=?, category=?, sales=?, price=?, stock=?, tag=?, home_sort=?, page_sort=?, sku=?, cover_image=?, images=?, description=?, status=?, published_at=? WHERE id=?");
            $stmt->execute([
                $p['name'], $p['category'], $p['sales'], $p['price'], $p['stock'], $p['tag'],
                $p['home_sort'], $p['page_sort'], $p['sku'], $p['cover_image'], json_encode($p['images']),
                $p['description'], $p['status'], $p['published_at'], $p['id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}products` (name, category, sales, price, stock, tag, home_sort, page_sort, sku, cover_image, images, description, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $p['name'], $p['category'], $p['sales'], $p['price'], $p['stock'], $p['tag'],
                $p['home_sort'], $p['page_sort'], $p['sku'], $p['cover_image'], json_encode($p['images']),
                $p['description'], $p['status'], $p['published_at']
            ]);
            $p['id'] = (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        shop_log_exception('保存商品失败', $e);
        return [];
    }
    
    return shop_get_products();
}

function shop_delete_product(array $products, int $id): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}products` WHERE id=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            shop_log_exception('删除商品失败', $e);
            return [];
        }
    }
    return shop_get_products();
}


function shop_compare_products_by_field(array $a, array $b, string $sortField): int
{
    $aSort = max(0, (int) ($a[$sortField] ?? 0));
    $bSort = max(0, (int) ($b[$sortField] ?? 0));

    $aHasSort = $aSort > 0;
    $bHasSort = $bSort > 0;

    if ($aHasSort !== $bHasSort) {
        return $aHasSort ? -1 : 1;
    }

    if ($aHasSort && $bHasSort && $aSort !== $bSort) {
        return $aSort <=> $bSort;
    }

    $salesDiff = ((int) ($b['sales'] ?? 0)) <=> ((int) ($a['sales'] ?? 0));
    if ($salesDiff !== 0) return $salesDiff;

    $timeDiff = strtotime((string) ($b['published_at'] ?? '1970-01-01 00:00:00')) <=> strtotime((string) ($a['published_at'] ?? '1970-01-01 00:00:00'));
    if ($timeDiff !== 0) return $timeDiff;

    return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
}

function shop_sort_products_by_field(array $products, string $sortField): array
{
    usort($products, static function (array $a, array $b) use ($sortField): int {
        return shop_compare_products_by_field($a, $b, $sortField);
    });
    return $products;
}

function shop_sort_products_for_home(array $products): array
{
    return shop_sort_products_by_field($products, 'home_sort');
}

function shop_sort_products_for_page(array $products): array
{
    return shop_sort_products_by_field($products, 'page_sort');
}

function shop_filter_products(array $products, string $keyword): array
{
    $keyword = trim($keyword);
    if ($keyword === '') return array_values($products);

    return array_values(array_filter($products, static function (array $product) use ($keyword): bool {
        $haystack = implode(' ', array_filter([
            (string) ($product['name'] ?? ''),
            (string) ($product['category'] ?? ''),
            (string) ($product['tag'] ?? ''),
            (string) ($product['sku'] ?? ''),
        ]));
        return stripos($haystack, $keyword) !== false;
    }));
}

function shop_group_products_by_category(array $products, string $sortField = 'home_sort'): array
{
    $groups = [];

    foreach ($products as $product) {
        $category = (string) ($product['category'] ?? '未分类');
        $categoryInfo = shop_get_category_info($category);

        if (!isset($groups[$category])) {
            $groups[$category] = [
                'name' => $category,
                'rank' => (int) ($categoryInfo['rank'] ?? 999),
                'description' => (string) ($categoryInfo['description'] ?? ''),
                'accent' => (string) ($categoryInfo['accent'] ?? '#cbd5e1'),
                'emoji' => (string) ($categoryInfo['emoji'] ?? '🛍️'),
                'products' => [],
            ];
        }
        $groups[$category]['products'][] = $product;
    }

    foreach ($groups as &$group) {
        $group['products'] = shop_sort_products_by_field($group['products'], $sortField);
    }
    unset($group);

    uasort($groups, static function (array $a, array $b): int {
        $rankDiff = ((int) ($a['rank'] ?? 999)) <=> ((int) ($b['rank'] ?? 999));
        if ($rankDiff !== 0) return $rankDiff;
        return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return array_values($groups);
}

function shop_product_dashboard_metrics(array $products): array
{
    $metrics = [
        'count' => 0,
        'category_count' => 0,
        'sales' => 0,
        'home_priority_count' => 0,
        'page_priority_count' => 0,
    ];
    $categories = [];

    foreach ($products as $product) {
        $metrics['count']++;
        $metrics['sales'] += (int) ($product['sales'] ?? 0);
        if ((int) ($product['home_sort'] ?? 0) > 0) $metrics['home_priority_count']++;
        if ((int) ($product['page_sort'] ?? 0) > 0) $metrics['page_priority_count']++;
        $categories[(string) ($product['category'] ?? '未分类')] = true;
    }
    $metrics['category_count'] = count($categories);

    return $metrics;
}

function shop_sort_label(array $product, string $sortField, string $labelPrefix): string
{
    $sort = max(0, (int) ($product[$sortField] ?? 0));
    if ($sort === 0) return '销量优先';
    return $labelPrefix . ' ' . $sort;
}

function shop_format_price(float $price): string
{
    $formatted = number_format($price, 2, '.', ',');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return '￥' . $formatted;
}

function shop_format_sales(int $sales): string
{
    return number_format($sales, 0, '.', ',');
}

function shop_short_date(string $datetime): string
{
    return date('m-d', strtotime($datetime));
}

function shop_short_datetime(string $datetime): string
{
    return date('m-d H:i', strtotime($datetime));
}

function shop_to_input_datetime(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return '';
    return date('Y-m-d\TH:i', $timestamp);
}

function shop_from_input_datetime(string $value): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) return date('Y-m-d H:i:s');
    return date('Y-m-d H:i:s', $timestamp);
}

function shop_normalize_user(array $user, int $fallbackId = 0): array
{
    $status = trim((string) ($user['status'] ?? 'active'));
    if (!in_array($status, ['active', 'follow_up', 'sleeping'], true)) {
        $status = 'active';
    }

    return [
        'id' => max(0, (int) ($user['id'] ?? $fallbackId)),
        'username' => trim((string) ($user['username'] ?? '')),
        'name' => trim((string) ($user['name'] ?? '未命名用户')),
        'email' => trim((string) ($user['email'] ?? '')),
        'phone' => trim((string) ($user['phone'] ?? '')),
        'level' => trim((string) ($user['level'] ?? '普通会员')),
        'status' => $status,
        'address' => trim((string) ($user['address'] ?? '')),
        'last_login' => trim((string) ($user['last_login'] ?? '')),
        'note' => trim((string) ($user['note'] ?? '')),
        'reset_token' => trim((string) ($user['reset_token'] ?? '')),
        'reset_expires' => trim((string) ($user['reset_expires'] ?? '')),
    ];
}

function shop_get_users(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}users` ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_user($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取用户失败', $e);
            return [];
        }
    }
    return [];
}

function shop_save_users(array $users): bool
{
    return true; // 已废弃，当前通过数据库增删改查直接处理。
}

function shop_find_user(array $users, int $id): ?array
{
    foreach ($users as $user) {
        if ((int) ($user['id'] ?? 0) === $id) return $user;
    }
    return null;
}

function shop_upsert_user(array $users, array $user): array
{
    $pdo = get_db_connection();
    if (!$pdo) return $users;
    
    $u = shop_normalize_user($user, (int) ($user['id'] ?? 0));
    
    try {
        $prefix = get_db_prefix();
        if ($u['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, email=?, phone=?, level=?, status=?, address=?, last_login=?, note=?, reset_token=?, reset_expires=? WHERE id=?");
            $stmt->execute([
                $u['username'],
                $u['name'],
                $u['email'] === '' ? null : $u['email'],
                $u['phone'],
                $u['level'],
                $u['status'],
                $u['address'],
                $u['last_login'] === '' ? null : $u['last_login'],
                $u['note'],
                $u['reset_token'] === '' ? null : $u['reset_token'],
                $u['reset_expires'] === '' ? null : $u['reset_expires'],
                $u['id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, phone, level, status, address, last_login, note, reset_token, reset_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $u['username'],
                $u['name'],
                $u['email'] === '' ? null : $u['email'],
                $u['phone'],
                $u['level'],
                $u['status'],
                $u['address'],
                $u['last_login'] === '' ? null : $u['last_login'],
                $u['note'],
                $u['reset_token'] === '' ? null : $u['reset_token'],
                $u['reset_expires'] === '' ? null : $u['reset_expires']
            ]);
            $u['id'] = (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        shop_log_exception('保存用户失败', $e);
        return [];
    }
    
    return shop_get_users();
}

function shop_delete_user(array $users, int $id): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}users` WHERE id=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            shop_log_exception('删除用户失败', $e);
            return [];
        }
    }
    return shop_get_users();
}

function shop_normalize_plugin(array $plugin, int $fallbackId = 0): array
{
    $type = trim((string) ($plugin['type'] ?? 'none'));
    if (!in_array($type, ['none', 'pay'], true)) {
        $type = 'none';
    }

    $config = $plugin['config'] ?? [];
    if (is_string($config)) {
        $config = json_decode($config, true) ?: [];
    }

    return [
        'id' => max(0, (int) ($plugin['id'] ?? $fallbackId)),
        'name' => trim((string) ($plugin['name'] ?? '未命名插件')),
        'type' => $type,
        'description' => trim((string) ($plugin['description'] ?? '')),
        'version' => trim((string) ($plugin['version'] ?? '1.0.0')),
        'enabled' => (int) ($plugin['enabled'] ?? 1) > 0 ? 1 : 0,
        'config' => $config,
    ];
}

function shop_get_plugins(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}plugins` ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_plugin($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取插件失败', $e);
            return [];
        }
    }
    return [];
}

function shop_save_plugins(array $plugins): bool
{
    return true; // 已废弃，当前通过数据库增删改查直接处理。
}

function shop_find_plugin(array $plugins, int $id): ?array
{
    foreach ($plugins as $plugin) {
        if ((int) ($plugin['id'] ?? 0) === $id) return $plugin;
    }
    return null;
}

function shop_upsert_plugin(array $plugins, array $plugin): array
{
    $pdo = get_db_connection();
    if (!$pdo) return $plugins;
    
    $p = shop_normalize_plugin($plugin, (int) ($plugin['id'] ?? 0));
    
    try {
        $prefix = get_db_prefix();
        if ($p['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}plugins` SET name=?, type=?, description=?, version=?, enabled=?, config=? WHERE id=?");
            $stmt->execute([
                $p['name'], $p['type'], $p['description'], $p['version'], $p['enabled'], json_encode($p['config']), $p['id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}plugins` (name, type, description, version, enabled, config) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $p['name'], $p['type'], $p['description'], $p['version'], $p['enabled'], json_encode($p['config'])
            ]);
            $p['id'] = (int)$pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        shop_log_exception('保存插件失败', $e);
        return [];
    }
    
    return shop_get_plugins();
}

function shop_delete_plugin(array $plugins, int $id): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}plugins` WHERE id=?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            shop_log_exception('删除插件失败', $e);
            return [];
        }
    }
    return shop_get_plugins();
}

function shop_reset_products(): bool { return true; }
function shop_reset_categories(): bool { return true; }
function shop_reset_users(): bool { return true; }
function shop_reset_plugins(): bool { return true; }
function shop_storage_file(): string { return __DIR__ . '/../storage/dummy.json'; }

function shop_normalize_order_item(array $item): array
{
    return [
        'product_id' => max(0, (int) ($item['product_id'] ?? 0)),
        'name' => trim((string) ($item['name'] ?? '')),
        'sku_name' => trim((string) ($item['sku_name'] ?? '')),
        'price' => max(0, (float) ($item['price'] ?? 0)),
        'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
    ];
}

function shop_decode_order_items(mixed $items): array
{
    if (is_array($items)) {
        return array_values(array_map('shop_normalize_order_item', $items));
    }

    if (!is_string($items) || trim($items) === '') {
        return [];
    }

    $decoded = json_decode($items, true);
    if (is_array($decoded)) {
        return array_values(array_map('shop_normalize_order_item', $decoded));
    }

    return [[
        'product_id' => 0,
        'name' => trim($items),
        'sku_name' => '',
        'price' => 0,
        'quantity' => 1,
    ]];
}

function shop_encode_order_items(array $items): string
{
    $normalized_items = array_values(array_map('shop_normalize_order_item', $items));
    return json_encode($normalized_items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
}

function shop_order_items_summary(array $items): string
{
    if ($items === []) {
        return '暂无商品';
    }

    $segments = [];
    foreach ($items as $item) {
        $name = trim((string) ($item['name'] ?? '商品'));
        $sku_name = trim((string) ($item['sku_name'] ?? ''));
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $segments[] = $name . ($sku_name !== '' ? '（' . $sku_name . '）' : '') . ' ×' . $quantity;
    }

    return implode('，', $segments);
}

function shop_order_items_quantity(array $items): int
{
    $quantity = 0;
    foreach ($items as $item) {
        $quantity += max(1, (int) ($item['quantity'] ?? 1));
    }
    return $quantity;
}

function shop_normalize_order(array $order): array
{
    $items_data = shop_decode_order_items($order['items'] ?? []);
    $status = shop_normalize_order_status((string) ($order['status'] ?? ''));

    return [
        'id' => max(0, (int) ($order['id'] ?? 0)),
        'order_no' => trim((string) ($order['order_no'] ?? '')),
        'user_id' => isset($order['user_id']) && $order['user_id'] !== null ? (int) $order['user_id'] : null,
        'customer' => trim((string) ($order['customer'] ?? '')),
        'phone' => trim((string) ($order['phone'] ?? '')),
        'address' => trim((string) ($order['address'] ?? '')),
        'status' => $status,
        'pay_method' => trim((string) ($order['pay_method'] ?? '')),
        'express_company' => trim((string) ($order['express_company'] ?? '')),
        'tracking_numbers' => trim((string) ($order['tracking_numbers'] ?? '')),
        'items' => (string) ($order['items'] ?? ''),
        'items_data' => $items_data,
        'items_summary' => shop_order_items_summary($items_data),
        'total' => max(0, (float) ($order['total'] ?? 0)),
        'remark' => trim((string) ($order['remark'] ?? '')),
        'time' => trim((string) ($order['time'] ?? '')),
        'created_at' => trim((string) ($order['created_at'] ?? ($order['time'] ?? ''))),
        'updated_at' => trim((string) ($order['updated_at'] ?? '')),
    ];
}

function shop_find_order_by_no(array $orders, string $order_no): ?array
{
    foreach ($orders as $order) {
        if ((string) ($order['order_no'] ?? '') === $order_no) {
            return $order;
        }
    }

    return null;
}

function shop_user_can_view_order(array $order, ?int $user_id, array $my_order_nos): bool
{
    if ($user_id !== null && isset($order['user_id']) && $order['user_id'] !== null && (int) $order['user_id'] === $user_id) {
        return true;
    }

    return in_array((string) ($order['order_no'] ?? ''), $my_order_nos, true);
}

// 订单函数
function shop_get_orders(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}orders` ORDER BY id DESC");
            $rows = $stmt->fetchAll();
            return array_map('shop_normalize_order', $rows);
        } catch (PDOException $e) {
            shop_log_exception('读取订单失败', $e);
            return [];
        }
    }
    return [];
}
