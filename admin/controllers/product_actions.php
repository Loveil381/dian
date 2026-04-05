<?php
declare(strict_types=1);

/**
 * 商品管理 action handlers。
 *
 * 依赖函数：
 *   shop_admin_post_string/int/float() — admin/includes/helpers.php
 *   shop_upsert_product(), shop_delete_product(), shop_from_input_datetime(), shop_category_names() — data/products.php
 *   get_db_connection(), get_db_prefix() — includes/db.php
 */

function handle_save_product(): array
{
    $categoryOptions = shop_category_names();
    $id = shop_admin_post_int('id');
    $imagesInput = shop_admin_post_string('images');
    $imagesArr = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $imagesInput))));

    $skuInput = $_POST['sku'] ?? '';
    $skuData = [];
    if (is_array($skuInput)) {
        foreach ($skuInput as $skuItem) {
            if (!empty(trim($skuItem['name'] ?? ''))) {
                $skuData[] = [
                    'name' => trim($skuItem['name']),
                    'stock' => max(0, (int)($skuItem['stock'] ?? 0)),
                    'price' => max(0, (float)($skuItem['price'] ?? 0))
                ];
            }
        }
    }
    $skuJson = !empty($skuData) ? json_encode($skuData, JSON_UNESCAPED_UNICODE) : '';

    $product = [
        'id' => $id,
        'name' => shop_admin_post_string('name'),
        'category' => shop_admin_post_string('category'),
        'sales' => shop_admin_post_int('sales'),
        'published_at' => shop_from_input_datetime(shop_admin_post_string('published_at')),
        'price' => shop_admin_post_float('price'),
        'stock' => shop_admin_post_int('stock'),
        'tag' => shop_admin_post_string('tag'),
        'home_sort' => shop_admin_post_int('home_sort'),
        'page_sort' => shop_admin_post_int('page_sort'),
        'sku' => $skuJson,
        'cover_image' => shop_admin_post_string('cover_image'),
        'images' => array_values($imagesArr),
        'description' => shop_admin_post_string('description'),
        'status' => shop_admin_post_string('status', 'on_sale'),
    ];

    if ($product['name'] === '') {
        $product['name'] = '未命名商品';
    }

    if ($product['category'] === '') {
        $product['category'] = $categoryOptions[0] ?? '未分类';
    }

    if (!in_array($product['status'], ['on_sale', 'off_sale'], true)) {
        $product['status'] = 'on_sale';
    }

    if (!shop_upsert_product($product)) {
        return ['商品保存失败。', 'error'];
    }
    shop_admin_log('save_product', 'product', $id, $id > 0 ? '更新商品' : '新建商品');
    return [$id > 0 ? '商品已更新，首页/商品页排序已保存。' : '商品已新增，首页/商品页排序已保存。', 'success'];
}

function handle_update_sort(): array
{
    $id = (int) ($_POST['id'] ?? 0);
    $homeSort = max(0, (int) ($_POST['home_sort'] ?? 0));
    $pageSort = max(0, (int) ($_POST['page_sort'] ?? 0));

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $stmt = $pdo->prepare("UPDATE `{$prefix}products` SET home_sort = ?, page_sort = ? WHERE id = ?");
        $stmt->execute([$homeSort, $pageSort, $id]);
        if ($stmt->rowCount() > 0) {
            shop_admin_log('save_product', 'product', $id, '更新排序');
            return ['排序已保存：0 = 按销量，非 0 = 固定排序，数字越小越靠前。', 'success'];
        }
        return ['未找到要更新的商品。', 'error'];
    } catch (PDOException $e) {
        return ['排序保存失败: ' . $e->getMessage(), 'error'];
    }
}

function handle_delete_product(): array
{
    $id = (int) ($_POST['id'] ?? 0);
    if (!shop_delete_product($id)) {
        return ['未找到要删除的商品。', 'error'];
    }
    shop_admin_log('delete_product', 'product', $id, '删除商品');
    return ['商品已删除。', 'success'];
}

function handle_batch_product_action(): array
{
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])), static fn (int $id): bool => $id > 0)));
    $batchAction = trim((string) ($_POST['batch_action'] ?? ''));

    if ($ids === []) {
        return ['请先选择要操作的商品。', 'error'];
    }

    if (!in_array($batchAction, ['on_sale', 'off_sale', 'delete'], true)) {
        return ['不支持的批量操作。', 'error'];
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if ($batchAction === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM `{$prefix}products` WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            shop_admin_log('batch_product_action', 'product', 0, '批量删除 ' . count($ids) . ' 件商品');
            return ['已批量删除选中商品。', 'success'];
        }

        $stmt = $pdo->prepare("UPDATE `{$prefix}products` SET status = ? WHERE id IN ($placeholders)");
        $stmt->execute(array_merge([$batchAction], $ids));
        shop_admin_log('batch_product_action', 'product', 0, ($batchAction === 'on_sale' ? '批量上架 ' : '批量下架 ') . count($ids) . ' 件商品');
        return [$batchAction === 'on_sale' ? '已批量上架选中商品。' : '已批量下架选中商品。', 'success'];
    } catch (PDOException $e) {
        return ['批量操作失败: ' . $e->getMessage(), 'error'];
    }
}
