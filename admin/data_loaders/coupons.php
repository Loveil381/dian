<?php
declare(strict_types=1);

/**
 * Coupons tab 数据加载。
 * 依赖父作用域：$pdo, $prefix, $perPage, $adminUrl
 * 设置变量：$couponRows, $couponPagination, $couponPaginationUrl, $couponStatusFilter, $selectedCoupon
 */

require_once __DIR__ . '/../../data/coupons.php';

// 编辑模式：加载指定优惠券
$editCouponId = max(0, (int) ($_GET['edit_coupon'] ?? 0));
if ($editCouponId > 0) {
    $editingCoupon = shop_get_coupon_by_id($editCouponId);
    if ($editingCoupon !== null) {
        $selectedCoupon = $editingCoupon;
    }
}

$couponsPage = max(1, (int) ($_GET['coupons_page'] ?? 1));
$couponStatusFilter = trim((string) ($_GET['coupon_status'] ?? ''));
if (!in_array($couponStatusFilter, ['', 'active', 'disabled'], true)) {
    $couponStatusFilter = '';
}

// 构建分页 URL
$couponPaginationBase = $adminUrl . '&tab=coupons';
if ($couponStatusFilter !== '') {
    $couponPaginationBase .= '&coupon_status=' . urlencode($couponStatusFilter);
}
$couponPaginationUrl = $couponPaginationBase . '&coupons_page=';

if ($pdo) {
    try {
        // WHERE 条件
        $couponWhere = [];
        $couponParams = [];
        if ($couponStatusFilter !== '') {
            $couponWhere[] = '`status` = ?';
            $couponParams[] = $couponStatusFilter;
        }
        $couponWhereSql = $couponWhere === [] ? '' : ' WHERE ' . implode(' AND ', $couponWhere);

        // 总数
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}coupons`" . $couponWhereSql);
        $countStmt->execute($couponParams);
        $couponTotal = (int) $countStmt->fetchColumn();

        // 分页
        $couponPagination = shop_paginate($couponTotal, $perPage, $couponsPage);

        // 查询数据
        $dataStmt = $pdo->prepare(
            "SELECT * FROM `{$prefix}coupons`" . $couponWhereSql . " ORDER BY `id` DESC LIMIT ? OFFSET ?"
        );
        $bindIndex = 1;
        foreach ($couponParams as $param) {
            $dataStmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $dataStmt->bindValue($bindIndex, (int) $couponPagination['limit'], PDO::PARAM_INT);
        $dataStmt->bindValue($bindIndex + 1, (int) $couponPagination['offset'], PDO::PARAM_INT);
        $dataStmt->execute();
        $couponRows = array_map('shop_normalize_coupon', $dataStmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        shop_log_exception('优惠券查询失败', $e);
    }
}
