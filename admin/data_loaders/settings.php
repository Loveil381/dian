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
}
