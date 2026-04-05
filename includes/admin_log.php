<?php
declare(strict_types=1);

/**
 * 管理员操作日志记录。
 *
 * 在 action handler 中调用 shop_admin_log() 记录写操作，
 * 日志存入 {prefix}admin_logs 表，失败时静默（不中断主流程）。
 */

/**
 * 写入一条管理员操作日志。
 *
 * @param string $action     操作标识，如 save_product、delete_order
 * @param string $targetType 目标类型，如 product、order、user、coupon
 * @param int    $targetId   目标 ID
 * @param string $detail     详情描述（中文）
 */
function shop_admin_log(string $action, string $targetType = '', int $targetId = 0, string $detail = ''): void
{
    try {
        $pdo = get_db_connection();
        if (!$pdo instanceof PDO) {
            return;
        }
        $prefix = get_db_prefix();
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $adminName = (string) ($_SESSION['admin_username'] ?? '');
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        $stmt = $pdo->prepare(
            "INSERT INTO `{$prefix}admin_logs` (`admin_id`, `admin_name`, `action`, `target_type`, `target_id`, `detail`, `ip`)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$adminId, $adminName, $action, $targetType, $targetId, $detail, $ip]);
    } catch (\Throwable $e) {
        // 日志记录失败不影响主业务，静默降级到文件日志
        shop_log('warning', '操作日志写入失败', ['message' => $e->getMessage(), 'action' => $action]);
    }
}
