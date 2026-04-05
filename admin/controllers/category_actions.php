<?php
declare(strict_types=1);

/**
 * 分类管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string(), shop_admin_post_int() — admin/includes/helpers.php
 *   shop_get_category_by_id(), shop_upsert_category(), shop_delete_category() — data/products.php
 *   get_db_connection(), get_db_prefix() — includes/db.php
 */

function handle_save_category(): array
{
    $id = shop_admin_post_int('id');
    $oldCategory = $id > 0 ? shop_get_category_by_id($id) : null;
    $category = [
        'id' => $id,
        'name' => shop_admin_post_string('name'),
        'description' => shop_admin_post_string('description'),
        'accent' => shop_admin_post_string('accent', '#cbd5e1'),
        'emoji' => shop_admin_post_string('emoji', '🛍️'),
        'sort' => shop_admin_post_int('sort'),
    ];

    if ($category['name'] === '') {
        $category['name'] = '未分类';
    }

    if (!shop_upsert_category($category)) {
        return ['分类保存失败。', 'error'];
    }

    if ($oldCategory !== null) {
        $oldName = (string) ($oldCategory['name'] ?? '');

        if ($oldName !== '' && $oldName !== $category['name']) {
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}products` SET category = ? WHERE category = ?");
                    $stmt->execute([$category['name'], $oldName]);
                } catch (PDOException $e) {
                    return ['分类已保存，但同步商品分类失败: ' . $e->getMessage(), 'error'];
                }
            }
        }
    }

    shop_admin_log('save_category', 'category', $id, $id > 0 ? '更新分类' : '新建分类');
    return [$id > 0 ? '分类已更新。' : '分类已新增。', 'success'];
}

function handle_delete_category(): array
{
    $id = shop_admin_post_int('id');
    if (!shop_delete_category($id)) {
        return ['未找到要删除的分类。', 'error'];
    }
    shop_admin_log('delete_category', 'category', $id, '删除分类');
    return ['分类已删除。', 'success'];
}
