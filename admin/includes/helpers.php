<?php declare(strict_types=1);

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
    return match ($status) {
        'pending' => '待支付',
        'paid', '已支付，待发货' => '待发货',
        'shipped', '已发货' => '已发货',
        'completed', '已完成' => '已完成',
        'cancelled', '已取消' => '已取消',
        default => $status !== '' ? $status : '未知',
    };
}

function shop_admin_order_status_class(string $status): string
{
    return match ($status) {
        'pending' => 'warning',
        'paid', '已支付，待发货' => 'info',
        'shipped', '已发货' => 'success',
        'completed', '已完成' => 'success',
        'cancelled', '已取消' => 'danger',
        default => 'muted',
    };
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
