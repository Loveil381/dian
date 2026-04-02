<?php declare(strict_types=1);

require_once __DIR__ . '/../../includes/order_status.php';

function shop_admin_flash(string $message, string $type = 'success'): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function shop_admin_status_label(string $status): string
{
    return $status === 'off_sale' ? '已下架' : '上架中';
}

function shop_admin_status_class(string $status): string
{
    return $status === 'off_sale' ? 'danger' : 'success';
}

function shop_admin_order_status_label(string $status): string
{
    return (string) (shop_order_status_meta($status)['label'] ?? '未知状态');
}

function shop_admin_order_status_class(string $status): string
{
    return (string) (shop_order_status_meta($status)['admin_class'] ?? 'muted');
}

function shop_admin_user_status_label(string $lastLogin): string
{
    if (empty($lastLogin)) {
        return '未登录';
    }

    $loginTime = strtotime($lastLogin);
    if ($loginTime === false) {
        return '未登录';
    }

    // 最近 30 分钟内登录视为在线。
    if (time() - $loginTime < 1800) {
        return '在线';
    }

    return '离线';
}

function shop_admin_user_status_class(string $lastLogin): string
{
    $label = shop_admin_user_status_label($lastLogin);
    return match ($label) {
        '在线' => 'success',
        '离线', '未登录' => 'muted',
        default => 'muted',
    };
}

function shop_admin_plugin_type_label(string $type): string
{
    return match ($type) {
        'pay' => '支付',
        default => '无',
    };
}

function shop_admin_plugin_type_class(string $type): string
{
    return match ($type) {
        'pay' => 'info',
        default => 'muted',
    };
}

function shop_admin_post_string(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function shop_admin_post_int(string $key, int $default = 0): int
{
    return max(0, (int) ($_POST[$key] ?? $default));
}

function shop_admin_post_float(string $key, float $default = 0): float
{
    return max(0, (float) ($_POST[$key] ?? $default));
}

function shop_admin_post_checked(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}
