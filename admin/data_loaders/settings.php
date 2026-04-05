<?php
declare(strict_types=1);

/**
 * Settings tab 数据加载。
 * 依赖父作用域：$pdo, $prefix
 * 设置变量：$consultEnabled, $consultTitle, $consultGreeting, $consultWechatQr, $consultWechatId, $consultPhone, $consultNotice
 */

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('consult_enabled', 'consult_title', 'consult_greeting', 'consult_wechat_qr', 'consult_wechat_id', 'consult_phone', 'consult_notice')");
        while ($row = $stmt->fetch()) {
            if ($row['key'] === 'consult_enabled') $consultEnabled = $row['value'];
            if ($row['key'] === 'consult_title') $consultTitle = $row['value'];
            if ($row['key'] === 'consult_greeting') $consultGreeting = $row['value'];
            if ($row['key'] === 'consult_wechat_qr') $consultWechatQr = $row['value'];
            if ($row['key'] === 'consult_wechat_id') $consultWechatId = $row['value'];
            if ($row['key'] === 'consult_phone') $consultPhone = $row['value'];
            if ($row['key'] === 'consult_notice') $consultNotice = $row['value'];
        }
    } catch (PDOException $e) {}

    // 通知设置
    try {
        $notifyStmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('notify_admin_created', 'notify_admin_paid', 'notify_customer_created', 'notify_customer_shipped', 'notify_customer_completed', 'notify_admin_email')");
        while ($row = $notifyStmt->fetch()) {
            $varName = 'notify' . str_replace('notify', '', str_replace('_', '', ucwords(str_replace('_', ' ', $row['key']))));
            // 简单映射到变量
            $$row['key'] = $row['value'];
        }
    } catch (PDOException $e) {}
}

// 通知设置默认值（使用 settings key 同名变量）
$notifyAdminCreated = ${'notify_admin_created'} ?? '0';
$notifyAdminPaid = ${'notify_admin_paid'} ?? '0';
$notifyCustomerCreated = ${'notify_customer_created'} ?? '0';
$notifyCustomerShipped = ${'notify_customer_shipped'} ?? '0';
$notifyCustomerCompleted = ${'notify_customer_completed'} ?? '0';
$notifyAdminEmail = ${'notify_admin_email'} ?? '';
