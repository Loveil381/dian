<?php
declare(strict_types=1);

/**
 * 优惠券管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string/int/float() — admin/includes/helpers.php
 *   shop_upsert_coupon(), shop_delete_coupon(), shop_get_coupon_by_code() — data/coupons.php
 *   shop_admin_log() — includes/admin_log.php
 *   shop_from_input_datetime() — data/helpers.php
 */

require_once __DIR__ . '/../../data/coupons.php';

function handle_save_coupon(): array
{
    $id = shop_admin_post_int('coupon_id');
    $code = strtoupper(trim(shop_admin_post_string('code')));

    if ($code === '') {
        return ['券码不能为空。', 'error'];
    }

    // 检查券码唯一性（排除自身）
    $existing = shop_get_coupon_by_code($code);
    if ($existing !== null && $existing['id'] !== $id) {
        return ['券码「' . $code . '」已被使用。', 'error'];
    }

    $coupon = [
        'id'               => $id,
        'code'             => $code,
        'type'             => shop_admin_post_string('type', 'fixed'),
        'value'            => shop_admin_post_float('value'),
        'min_order_amount' => shop_admin_post_float('min_order_amount'),
        'usage_limit'      => shop_admin_post_int('usage_limit'),
        'starts_at'        => shop_from_input_datetime(shop_admin_post_string('starts_at')),
        'expires_at'       => shop_from_input_datetime(shop_admin_post_string('expires_at')),
        'status'           => 'active',
    ];

    if (!shop_upsert_coupon($coupon)) {
        return ['优惠券保存失败。', 'error'];
    }

    shop_admin_log('save_coupon', 'coupon', $id, $id > 0 ? '更新优惠券 ' . $code : '创建优惠券 ' . $code);
    return [$id > 0 ? '优惠券已更新。' : '优惠券已创建。', 'success'];
}

function handle_delete_coupon(): array
{
    $id = shop_admin_post_int('coupon_id');
    if (!shop_delete_coupon($id)) {
        return ['未找到要删除的优惠券。', 'error'];
    }

    shop_admin_log('delete_coupon', 'coupon', $id, '删除优惠券');
    return ['优惠券已删除。', 'success'];
}

function handle_toggle_coupon(): array
{
    $id = shop_admin_post_int('coupon_id');
    $coupon = shop_get_coupon_by_id($id);
    if ($coupon === null) {
        return ['未找到优惠券。', 'error'];
    }

    $newStatus = $coupon['status'] === 'active' ? 'disabled' : 'active';
    $coupon['status'] = $newStatus;

    if (!shop_upsert_coupon($coupon)) {
        return ['状态切换失败。', 'error'];
    }

    shop_admin_log('toggle_coupon', 'coupon', $id, '状态切换为 ' . $newStatus);
    return ['优惠券已' . ($newStatus === 'active' ? '启用' : '禁用') . '。', 'success'];
}
