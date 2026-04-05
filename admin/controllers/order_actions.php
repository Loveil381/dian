<?php
declare(strict_types=1);

/**
 * 订单管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string/int() — admin/includes/helpers.php
 *   shop_normalize_order_status(), shop_order_status_options(), shop_can_transition() — includes/order_status.php
 *   get_db_connection(), get_db_prefix() — includes/db.php
 */

/**
 * 校验并执行订单状态转换（共享逻辑，供 update_order / update_order_status 复用）。
 *
 * @return array{0: string, 1: string}|null  失败时返回 [message, type]，成功返回 null
 */
function validate_order_status_transition(PDO $pdo, string $prefix, int $id, string $newStatus): ?array
{
    $status_options = shop_order_status_options();
    if (!isset($status_options[$newStatus])) {
        return ['不允许的订单状态。', 'error'];
    }

    $stmt = $pdo->prepare("SELECT status FROM `{$prefix}orders` WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $current_status = $stmt->fetchColumn();

    if ($current_status === false) {
        return ['未找到对应订单。', 'error'];
    }

    $current_status = shop_normalize_order_status((string) $current_status);
    if (!shop_can_transition($current_status, $newStatus)) {
        $from_label = (string) ($status_options[$current_status]['label'] ?? '未知状态');
        $to_label = (string) ($status_options[$newStatus]['label'] ?? '未知状态');
        return ['订单状态不能从"' . $from_label . '"变更为"' . $to_label . '"。', 'error'];
    }

    return null;
}

function handle_delete_order(): array
{
    $id = shop_admin_post_int('id');
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}orders` WHERE id = ?");
        $stmt->execute([$id]);
        shop_admin_log('delete_order', 'order', $id, '删除订单');
        return ['订单已删除。', 'success'];
    } catch (PDOException $e) {
        return ['订单删除失败: ' . $e->getMessage(), 'error'];
    }
}

function handle_update_order(): array
{
    $id = shop_admin_post_int('id');
    $tracking = shop_admin_post_string('tracking_numbers');
    $expressCompany = shop_admin_post_string('express_company');
    $status = shop_normalize_order_status(shop_admin_post_string('status'));

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $error = validate_order_status_transition($pdo, $prefix, $id, $status);
        if ($error !== null) {
            return $error;
        }

        $stmt = $pdo->prepare("UPDATE `{$prefix}orders` SET tracking_numbers = ?, express_company = ?, status = ? WHERE id = ?");
        $stmt->execute([$tracking, $expressCompany, $status, $id]);
        shop_admin_log('update_order', 'order', $id, '更新订单信息');
        return ['订单已更新。', 'success'];
    } catch (PDOException $e) {
        return ['订单更新失败: ' . $e->getMessage(), 'error'];
    }
}

function handle_update_order_status(): array
{
    $id = shop_admin_post_int('id');
    $status = shop_normalize_order_status(shop_admin_post_string('status'));

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $error = validate_order_status_transition($pdo, $prefix, $id, $status);
        if ($error !== null) {
            return $error;
        }

        $stmt = $pdo->prepare("UPDATE `{$prefix}orders` SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        shop_admin_log('update_order_status', 'order', $id, '状态变更为 ' . $status);

        // 触发通知
        try {
            require_once __DIR__ . '/../../includes/notification.php';
            $orderStmt = $pdo->prepare("SELECT * FROM `{$prefix}orders` WHERE id = ? LIMIT 1");
            $orderStmt->execute([$id]);
            $updatedOrder = $orderStmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($updatedOrder)) {
                $updatedOrder = shop_normalize_order($updatedOrder);
                shop_notify_order_event($status, $updatedOrder);
            }
        } catch (\Throwable $e) {
            shop_log('warning', '订单通知触发失败', ['order_id' => $id, 'event' => $status]);
        }

        return ['订单状态已更新。', 'success'];
    } catch (PDOException $e) {
        return ['订单状态更新失败: ' . $e->getMessage(), 'error'];
    }
}
