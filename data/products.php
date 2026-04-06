<?php
declare(strict_types=1);

/**
 * 商品实体 CRUD、排序、筛选、分组、仪表盘指标。
 *
 * 本文件同时作为 barrel，require_once 其他实体文件，
 * 使现有 `require_once 'data/products.php'` 的调用方无需修改。
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/categories.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/fulfillment.php';

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
        'fulfillment_options' => trim((string) ($product['fulfillment_options'] ?? '')),
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

function shop_get_product_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}products` WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                return shop_normalize_product($row, (int) ($row['id'] ?? $id));
            }
        } catch (PDOException $e) {
            shop_log_exception('按 ID 读取商品失败', $e);
        }
    }

    return shop_find_product(shop_get_products(), $id);
}

function shop_upsert_product(array $product): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $p = shop_normalize_product($product, (int) ($product['id'] ?? 0));

    try {
        $prefix = get_db_prefix();
        $fulfillmentJson = $p['fulfillment_options'] !== '' ? $p['fulfillment_options'] : null;
        if ($p['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}products` SET name=?, category=?, sales=?, price=?, stock=?, tag=?, home_sort=?, page_sort=?, sku=?, cover_image=?, images=?, description=?, fulfillment_options=?, status=?, published_at=? WHERE id=?");
            $stmt->execute([
                $p['name'], $p['category'], $p['sales'], $p['price'], $p['stock'], $p['tag'],
                $p['home_sort'], $p['page_sort'], $p['sku'], $p['cover_image'], json_encode($p['images']),
                $p['description'], $fulfillmentJson, $p['status'], $p['published_at'], $p['id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}products` (name, category, sales, price, stock, tag, home_sort, page_sort, sku, cover_image, images, description, fulfillment_options, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $p['name'], $p['category'], $p['sales'], $p['price'], $p['stock'], $p['tag'],
                $p['home_sort'], $p['page_sort'], $p['sku'], $p['cover_image'], json_encode($p['images']),
                $p['description'], $fulfillmentJson, $p['status'], $p['published_at']
            ]);
        }
        return true;
    } catch (PDOException $e) {
        shop_log_exception('保存商品失败', $e);
        return false;
    }
}

function shop_delete_product(int $id): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $prefix = get_db_prefix();
    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}products` WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log_exception('删除商品失败', $e);
        return false;
    }
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
