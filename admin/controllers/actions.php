<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    csrf_verify();
    $reqTab = $_POST['tab'] ?? '';
    $action = (string) ($_POST['admin_action'] ?? '');
    $message = '操作完成';
    $messageType = 'success';

    switch ($action) {
        case 'save_category':
            $id = shop_admin_post_int('id');
            $oldCategory = $id > 0 ? shop_find_category($categories, $id) : null;
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

            $categories = shop_upsert_category($categories, $category);
            $categorySaved = shop_save_categories($categories);
            $productsSaved = true;

            if ($oldCategory !== null) {
                $oldName = (string) ($oldCategory['name'] ?? '');

                if ($oldName !== '' && $oldName !== $category['name']) {
                    foreach ($products as $index => $product) {
                        if ((string) ($product['category'] ?? '') === $oldName) {
                            $products[$index]['category'] = $category['name'];
                        }
                    }

                    $productsSaved = shop_save_products($products);
                }
            }

            if ($categorySaved && $productsSaved) {
                $message = $id > 0 ? '分类已更新。' : '分类已新增。';
            } else {
                $message = !$categorySaved ? '分类保存失败。' : '分类已保存，但同步商品分类失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_category':
            $id = shop_admin_post_int('id');
            $before = count($categories);
            $categories = shop_delete_category($categories, $id);

            if ($before === count($categories)) {
                $message = '未找到要删除的分类。';
                $messageType = 'error';
                break;
            }

            if (shop_save_categories($categories)) {
                $message = '分类已删除。';
            } else {
                $message = '分类删除失败。';
                $messageType = 'error';
            }
            break;

        case 'save_product':
            $id = shop_admin_post_int('id');
            $imagesInput = shop_admin_post_string('images');
            $imagesArr = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $imagesInput))));
            
            // Handle SKU/规格
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

            $products = shop_upsert_product($products, $product);

            if (shop_save_products($products)) {
                $message = $id > 0 ? '商品已更新，首页/商品页排序已保存。' : '商品已新增，首页/商品页排序已保存。';
            } else {
                $message = '商品保存失败。';
                $messageType = 'error';
            }
            break;

        case 'update_sort':
            $id = (int) ($_POST['id'] ?? 0);
            $index = shop_find_product_index($products, $id);

            if ($index === null) {
                $message = '未找到要更新的商品。';
                $messageType = 'error';
                break;
            }

            $products[$index]['home_sort'] = max(0, (int) ($_POST['home_sort'] ?? 0));
            $products[$index]['page_sort'] = max(0, (int) ($_POST['page_sort'] ?? 0));

            if (shop_save_products($products)) {
                $message = '排序已保存：0 = 按销量，非 0 = 固定排序，数字越小越靠前。';
            } else {
                $message = '排序保存失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_product':
            $id = (int) ($_POST['id'] ?? 0);
            $before = count($products);
            $products = shop_delete_product($products, $id);

            if ($before === count($products)) {
                $message = '未找到要删除的商品。';
                $messageType = 'error';
                break;
            }

            if (shop_save_products($products)) {
                $message = '商品已删除。';
            } else {
                $message = '删除失败。';
                $messageType = 'error';
            }
            break;

        case 'reset_products':
            if (shop_reset_products()) {
                $message = '示例数据已恢复。';
            } else {
                $message = '恢复示例数据失败。';
                $messageType = 'error';
            }
            break;

        case 'save_user':
            $id = shop_admin_post_int('id');
            $username = shop_admin_post_string('username');
            
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            
            if ($username === '') {
                $stmt = $pdo->query("SELECT MAX(id) as max_id FROM `{$prefix}users`");
                $row = $stmt->fetch();
                $nextId = ($row['max_id'] ?? 0) + 1;
                $username = "ID $nextId";
            }
            
            // 检查用户名是否重复
            $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetch()) {
                shop_admin_flash('保存失败：用户名或 ID 已被占用。', 'error');
                header('Location: ' . $adminUrl . '#admin-users');
                exit;
            }

            $user = [
                'id' => $id,
                'username' => $username,
                'name' => shop_admin_post_string('name'),
                'phone' => shop_admin_post_string('phone'),
                'level' => shop_admin_post_string('level', '普通会员'),
                'status' => 'active',
                'address' => shop_admin_post_string('address'),
                'last_login' => shop_admin_post_string('last_login'),
                'note' => shop_admin_post_string('note'),
            ];

            if ($user['name'] === '') {
                $user['name'] = '未命名用户';
            }

            if ($user['last_login'] === '') {
                $user['last_login'] = date('Y-m-d H:i:s');
            }

            // 自己实现更新，因为 shop_upsert_user 没处理 username
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, phone=?, level=?, address=?, note=? WHERE id=?");
                    $stmt->execute([$user['username'], $user['name'], $user['phone'], $user['level'], $user['address'], $user['note'], $id]);
                    $message = '用户已更新。';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, phone, level, address, last_login, note) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$user['username'], $user['name'], $user['phone'], $user['level'], $user['address'], $user['note']]);
                    $message = '用户已新增。';
                }
            } catch (PDOException $e) {
                $message = '用户保存失败: ' . $e->getMessage();
                $messageType = 'error';
            }
            break;

        case 'delete_user':
            $id = shop_admin_post_int('id');
            $before = count($users);
            $users = shop_delete_user($users, $id);

            if ($before === count($users)) {
                $message = '未找到要删除的用户。';
                $messageType = 'error';
                break;
            }

            if (shop_save_users($users)) {
                $message = '用户已删除。';
            } else {
                $message = '用户删除失败。';
                $messageType = 'error';
            }
            break;

        case 'delete_order':
            $id = shop_admin_post_int('id');
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM `{$prefix}orders` WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = '订单已删除。';
                } catch (PDOException $e) {
                    $message = '订单删除失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        case 'save_payment':
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            
            $wechatQr = shop_admin_post_string('wechat_qr');
            $alipayQr = shop_admin_post_string('alipay_qr');
            $requireAddress = shop_admin_post_checked('require_address') ? '1' : '0';
            
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("REPLACE INTO `{$prefix}settings` (`key`, `value`) VALUES ('wechat_qr', ?), ('alipay_qr', ?), ('require_address', ?)");
                    $stmt->execute([$wechatQr, $alipayQr, $requireAddress]);
                    $message = '支付配置已更新。';
                } catch (PDOException $e) {
                    $message = '支付配置保存失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
            break;
            
        case 'update_order':
            $id = shop_admin_post_int('id');
            $tracking = shop_admin_post_string('tracking_numbers');
            $expressCompany = shop_admin_post_string('express_company');
            $status = shop_admin_post_string('status');
            
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}orders` SET tracking_numbers = ?, express_company = ?, status = ? WHERE id = ?");
                    $stmt->execute([$tracking, $expressCompany, $status, $id]);
                    $message = '订单已更新。';
                } catch (PDOException $e) {
                    $message = '订单更新失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        default:
            $message = '未知操作。';
            $messageType = 'error';
            break;
    }

    shop_admin_flash($message, $messageType);
    header('Location: ' . $adminUrl . ($reqTab !== '' ? '&tab=' . urlencode($reqTab) : ''));
    exit;
}
