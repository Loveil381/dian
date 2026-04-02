<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    require_once __DIR__ . '/../../includes/order_status.php';
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
            
            // 处理规格数据。
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

            $email = shop_admin_post_string('email');
            if ($email !== '') {
                $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE email = ? AND id != ?");
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) {
                    shop_admin_flash('邮箱已被占用，请更换后再试。', 'error');
                    header('Location: ' . $adminUrl . '#admin-users');
                    exit;
                }
            }

            $user = [
                'id' => $id,
                'username' => $username,
                'name' => shop_admin_post_string('name'),
                'email' => $email,
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

            // 这里单独更新用户名与邮箱，避免旧辅助函数遗漏字段。
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, email=?, phone=?, level=?, address=?, note=? WHERE id=?");
                    $stmt->execute([$user['username'], $user['name'], $user['email'] === '' ? null : $user['email'], $user['phone'], $user['level'], $user['address'], $user['note'], $id]);
                    $message = '用户已更新。';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, phone, level, address, last_login, note) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
                    $stmt->execute([$user['username'], $user['name'], $user['email'] === '' ? null : $user['email'], $user['phone'], $user['level'], $user['address'], $user['note']]);
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
            $status = shop_normalize_order_status(shop_admin_post_string('status'));
            
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $status_options = shop_order_status_options();
                    if (!isset($status_options[$status])) {
                        $message = '不允许的订单状态。';
                        $messageType = 'error';
                        break;
                    }

                    $status_stmt = $pdo->prepare("SELECT status FROM `{$prefix}orders` WHERE id = ? LIMIT 1");
                    $status_stmt->execute([$id]);
                    $current_status = $status_stmt->fetchColumn();

                    if ($current_status === false) {
                        $message = '未找到对应订单。';
                        $messageType = 'error';
                        break;
                    }

                    $current_status = shop_normalize_order_status((string) $current_status);
                    if (!shop_can_transition($current_status, $status)) {
                        $from_label = (string) ($status_options[$current_status]['label'] ?? '未知状态');
                        $to_label = (string) ($status_options[$status]['label'] ?? '未知状态');
                        $message = '订单状态不能从“' . $from_label . '”变更为“' . $to_label . '”。';
                        $messageType = 'error';
                        break;
                    }

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

        case 'update_order_status':
            $id = shop_admin_post_int('id');
            $status = shop_normalize_order_status(shop_admin_post_string('status'));
            $status_options = shop_order_status_options();
            if (!isset($status_options[$status])) {
                $message = '不允许的订单状态。';
                $messageType = 'error';
                break;
            }
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $status_stmt = $pdo->prepare("SELECT status FROM `{$prefix}orders` WHERE id = ? LIMIT 1");
                    $status_stmt->execute([$id]);
                    $current_status = $status_stmt->fetchColumn();

                    if ($current_status === false) {
                        $message = '未找到对应订单。';
                        $messageType = 'error';
                        break;
                    }

                    $current_status = shop_normalize_order_status((string) $current_status);
                    if (!shop_can_transition($current_status, $status)) {
                        $from_label = (string) ($status_options[$current_status]['label'] ?? '未知状态');
                        $to_label = (string) ($status_options[$status]['label'] ?? '未知状态');
                        $message = '订单状态不能从“' . $from_label . '”变更为“' . $to_label . '”。';
                        $messageType = 'error';
                        break;
                    }

                    $stmt = $pdo->prepare("UPDATE `{$prefix}orders` SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $message = '订单状态已更新。';
                } catch (PDOException $e) {
                    $message = '订单状态更新失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        case 'toggle_user_status':
            $id = shop_admin_post_int('id');
            $status = shop_admin_post_string('status');
            if (!in_array($status, ['active', 'banned'], true)) {
                $message = '不允许的用户状态。';
                $messageType = 'error';
                break;
            }
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $message = '用户状态已更新。';
                } catch (PDOException $e) {
                    $message = '用户状态更新失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接失败';
                $messageType = 'error';
            }
            break;

        case 'change_password':
            $new_username = shop_admin_post_string('new_username');
            $new_password = shop_admin_post_string('new_password');
            if (mb_strlen($new_password, 'UTF-8') < 6) {
                $message = '密码长度不能少于6位';
                $messageType = 'error';
                break;
            }
            $admin_id = (int)($_SESSION['admin_id'] ?? 0);
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            if ($pdo && $admin_id > 0) {
                try {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    if ($new_username !== '') {
                        $stmt = $pdo->prepare("UPDATE `{$prefix}admin_users` SET username = ?, password_hash = ? WHERE id = ?");
                        $stmt->execute([$new_username, $password_hash, $admin_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE `{$prefix}admin_users` SET password_hash = ? WHERE id = ?");
                        $stmt->execute([$password_hash, $admin_id]);
                    }
                    session_destroy();
                    session_start();
                    shop_admin_flash('密码已更新，请用新密码重新登录。', 'success');
                    header('Location: login.php');
                    exit;
                } catch (PDOException $e) {
                    $message = '修改密码失败: ' . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = '数据库连接或鉴权失败';
                $messageType = 'error';
            }
            break;

        case 'save_role':
            $message = '权限管理功能开发中，敬请期待。';
            $messageType = 'info';
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
