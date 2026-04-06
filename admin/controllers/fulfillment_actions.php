<?php
declare(strict_types=1);

/**
 * 发货方式管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string/int/float() — admin/includes/helpers.php
 *   shop_upsert_fulfillment_type(), shop_delete_fulfillment_type() — data/fulfillment.php
 */

function handle_save_fulfillment_type(): array
{
    $id = shop_admin_post_int('id');
    $slug = shop_admin_post_string('slug');
    $name = shop_admin_post_string('name');

    if ($name === '') {
        return ['请填写发货方式名称。', 'error'];
    }
    if ($slug === '') {
        return ['请填写标识符（slug）。', 'error'];
    }

    // slug 仅允许字母、数字、下划线
    if (!preg_match('/^[a-z0-9_]+$/', $slug)) {
        return ['标识符只允许小写字母、数字和下划线。', 'error'];
    }

    $data = [
        'id'               => $id,
        'slug'             => $slug,
        'name'             => $name,
        'description'      => shop_admin_post_string('description'),
        'icon'             => shop_admin_post_string('icon', 'local_shipping'),
        'badge_color'      => shop_admin_post_string('badge_color', '#6a37d4'),
        'allow_zero_stock' => (int) ($_POST['allow_zero_stock'] ?? 0),
        'sort'             => shop_admin_post_int('sort'),
        'status'           => shop_admin_post_string('status', 'active'),
    ];

    if (!shop_upsert_fulfillment_type($data)) {
        return ['保存失败，请检查标识符是否重复。', 'error'];
    }

    shop_admin_log('save_fulfillment_type', 'fulfillment_type', $id, $id > 0 ? '更新发货方式' : '新建发货方式');
    return [$id > 0 ? '发货方式已更新。' : '发货方式已新增。', 'success'];
}

function handle_delete_fulfillment_type(): array
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!shop_delete_fulfillment_type($id)) {
        return ['未找到要删除的发货方式。', 'error'];
    }
    shop_admin_log('delete_fulfillment_type', 'fulfillment_type', $id, '删除发货方式');
    return ['发货方式已删除。', 'success'];
}
