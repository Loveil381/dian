<?php
declare(strict_types=1);

/**
 * 订单实体 CRUD 及辅助函数。
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/order_status.php';
require_once __DIR__ . '/helpers.php';

function shop_normalize_order_item(array $item): array
{
    return [
        'product_id' => max(0, (int) ($item['product_id'] ?? 0)),
        'name' => trim((string) ($item['name'] ?? '')),
        'sku_name' => trim((string) ($item['sku_name'] ?? '')),
        'price' => max(0, (float) ($item['price'] ?? 0)),
        'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
        'cover_image' => trim((string) ($item['cover_image'] ?? '')),
        'fulfillment_type' => trim((string) ($item['fulfillment_type'] ?? '')),
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
        'coupon_code' => trim((string) ($order['coupon_code'] ?? '')),
        'coupon_discount' => max(0, (float) ($order['coupon_discount'] ?? 0)),
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
