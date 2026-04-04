<?php
declare(strict_types=1);

/**
 * Payment tab 数据加载。
 * 依赖父作用域：$pdo, $prefix
 * 设置变量：$wechatQr, $alipayQr, $requireAddress
 */

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('wechat_qr', 'alipay_qr', 'require_address')");
        while ($row = $stmt->fetch()) {
            if ($row['key'] === 'wechat_qr') $wechatQr = $row['value'];
            if ($row['key'] === 'alipay_qr') $alipayQr = $row['value'];
            if ($row['key'] === 'require_address') $requireAddress = $row['value'];
        }
    } catch (PDOException $e) {}
}
