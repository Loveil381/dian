<?php
declare(strict_types=1);

/**
 * 页面管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string(), shop_admin_post_int() — admin/includes/helpers.php
 *   shop_get_page_by_id(), shop_update_page() — data/pages.php
 */

require_once __DIR__ . '/../../data/pages.php';

function handle_save_page(): array
{
    $id = shop_admin_post_int('id');
    $title = shop_admin_post_string('title');
    $content = (string) ($_POST['content'] ?? '');

    if ($title === '') {
        return ['页面标题不能为空。', 'error'];
    }

    $page = shop_get_page_by_id($id);
    if ($page === null) {
        return ['未找到要编辑的页面。', 'error'];
    }

    if (!shop_update_page($id, $title, $content)) {
        return ['页面保存失败。', 'error'];
    }

    return ['页面已更新。', 'success'];
}
