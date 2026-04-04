<?php
declare(strict_types=1);

/**
 * Orders tab 数据加载。
 * 依赖父作用域：$pdo, $prefix, $perPage, $adminUrl
 * 设置变量：$orderRows, $orderPagination, $orderPaginationUrl, $orderStatusFilter
 */

$ordersPage = max(1, (int) ($_GET['orders_page'] ?? 1));
$orderStatusFilter = shop_normalize_order_status(trim((string) ($_GET['order_status'] ?? '')));
$orderStatusOptions = shop_order_status_options();
if ($orderStatusFilter !== '' && !isset($orderStatusOptions[$orderStatusFilter])) {
    $orderStatusFilter = '';
}

$orderPaginationBase = $adminUrl . '&tab=orders';
if ($orderStatusFilter !== '') {
    $orderPaginationBase .= '&order_status=' . urlencode($orderStatusFilter);
}
$orderPaginationUrl = $orderPaginationBase . '&orders_page=';

if ($pdo) {
    try {
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
        $bindIndex = 1;
        foreach ($orderParams as $param) {
            $orderStmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $orderStmt->bindValue($bindIndex, (int) $orderPagination['limit'], PDO::PARAM_INT);
        $orderStmt->bindValue($bindIndex + 1, (int) $orderPagination['offset'], PDO::PARAM_INT);
        $orderStmt->execute();
        $orderRows = array_map('shop_normalize_order', $orderStmt->fetchAll());
    } catch (PDOException $e) {
        shop_log_exception('订单分页查询失败', $e);
        $allOrders = array_values(array_filter(shop_get_orders(), static function (array $o) use ($orderStatusFilter): bool {
            return $orderStatusFilter === '' || (string) ($o['status'] ?? '') === $orderStatusFilter;
        }));
        $orderPagination = shop_paginate(count($allOrders), $perPage, $ordersPage);
        $orderRows = array_slice($allOrders, (int) $orderPagination['offset'], (int) $orderPagination['limit']);
    }
} else {
    $allOrders = array_values(array_filter(shop_get_orders(), static function (array $o) use ($orderStatusFilter): bool {
        return $orderStatusFilter === '' || (string) ($o['status'] ?? '') === $orderStatusFilter;
    }));
    $orderPagination = shop_paginate(count($allOrders), $perPage, $ordersPage);
    $orderRows = array_slice($allOrders, (int) $orderPagination['offset'], (int) $orderPagination['limit']);
}
