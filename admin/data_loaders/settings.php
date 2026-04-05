<?php
declare(strict_types=1);

/**
 * Settings tab 数据加载。
 * 依赖父作用域：$pdo, $prefix
 * 设置变量：咨询设置 7 个 + 通知设置 6 个
 */

if ($pdo) {
    // 咨询设置
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('consult_enabled', 'consult_title', 'consult_greeting', 'consult_wechat_qr', 'consult_wechat_id', 'consult_phone', 'consult_notice')");
        while ($row = $stmt->fetch()) {
            $k = (string) $row['key'];
            $v = (string) $row['value'];
            if ($k === 'consult_enabled') { $consultEnabled = $v; }
            elseif ($k === 'consult_title') { $consultTitle = $v; }
            elseif ($k === 'consult_greeting') { $consultGreeting = $v; }
            elseif ($k === 'consult_wechat_qr') { $consultWechatQr = $v; }
            elseif ($k === 'consult_wechat_id') { $consultWechatId = $v; }
            elseif ($k === 'consult_phone') { $consultPhone = $v; }
            elseif ($k === 'consult_notice') { $consultNotice = $v; }
        }
    } catch (PDOException $e) {}

    // 通知设置
    try {
        $notifyStmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('notify_admin_created', 'notify_admin_paid', 'notify_customer_created', 'notify_customer_shipped', 'notify_customer_completed', 'notify_admin_email')");
        while ($row = $notifyStmt->fetch()) {
            $k = (string) $row['key'];
            $v = (string) $row['value'];
            if ($k === 'notify_admin_created') { $notifyAdminCreated = $v; }
            elseif ($k === 'notify_admin_paid') { $notifyAdminPaid = $v; }
            elseif ($k === 'notify_customer_created') { $notifyCustomerCreated = $v; }
            elseif ($k === 'notify_customer_shipped') { $notifyCustomerShipped = $v; }
            elseif ($k === 'notify_customer_completed') { $notifyCustomerCompleted = $v; }
            elseif ($k === 'notify_admin_email') { $notifyAdminEmail = $v; }
        }
    } catch (PDOException $e) {}
}
