<?php declare(strict_types=1);

/**
 * 返回订单状态配置。
 *
 * @return array<string, array<string, string>>
 */
function shop_order_status_options(): array
{
    return [
        'pending' => [
            'label' => '待支付',
            'badge_background' => '#fef3c7',
            'badge_color' => '#92400e',
            'admin_class' => 'warning',
        ],
        'paid' => [
            'label' => '已支付',
            'badge_background' => '#dbeafe',
            'badge_color' => '#1d4ed8',
            'admin_class' => 'info',
        ],
        'shipped' => [
            'label' => '已发货',
            'badge_background' => '#dcfce7',
            'badge_color' => '#166534',
            'admin_class' => 'success',
        ],
        'completed' => [
            'label' => '已完成',
            'badge_background' => '#e0f2fe',
            'badge_color' => '#0369a1',
            'admin_class' => 'success',
        ],
        'cancelled' => [
            'label' => '已取消',
            'badge_background' => '#fee2e2',
            'badge_color' => '#b91c1c',
            'admin_class' => 'danger',
        ],
    ];
}

/**
 * 兼容历史中文状态值，统一转为英文状态键。
 */
function shop_normalize_order_status(string $status): string
{
    $status = trim($status);
    if ($status === '') {
        return '';
    }

    $alias_map = [
        '待支付' => 'pending',
        '已支付' => 'paid',
        '已支付，待发货' => 'paid',
        '已支付 待确认 未发货' => 'paid',
        '已支付 已确认 待发货' => 'paid',
        '已发货' => 'shipped',
        '已支付 已确认 已发货' => 'shipped',
        '已完成' => 'completed',
        '已取消' => 'cancelled',
    ];

    if (isset($alias_map[$status])) {
        return $alias_map[$status];
    }

    return $status;
}

/**
 * 返回状态展示信息，便于前后台统一渲染。
 *
 * @return array<string, string>
 */
function shop_order_status_meta(string $status): array
{
    $normalized_status = shop_normalize_order_status($status);
    $options = shop_order_status_options();

    if (isset($options[$normalized_status])) {
        return ['value' => $normalized_status] + $options[$normalized_status];
    }

    return [
        'value' => $normalized_status !== '' ? $normalized_status : 'unknown',
        'label' => $status !== '' ? $status : '未知状态',
        'badge_background' => '#e2e8f0',
        'badge_color' => '#475569',
        'admin_class' => 'muted',
    ];
}

function shop_can_transition(string $from, string $to): bool
{
    $from = shop_normalize_order_status($from);
    $to = shop_normalize_order_status($to);
    $options = shop_order_status_options();

    if (!isset($options[$from]) || !isset($options[$to])) {
        return false;
    }

    // 保留原状态视为无变更，允许直接提交。
    if ($from === $to) {
        return true;
    }

    if ($to === 'cancelled') {
        return true;
    }

    $matrix = [
        'pending' => ['paid'],
        'paid' => ['shipped'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    return in_array($to, $matrix[$from] ?? [], true);
}
