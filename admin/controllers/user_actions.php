<?php
declare(strict_types=1);

/**
 * 用户管理 action handlers。
 *
 * 依赖外部变量（由 actions.php dispatch 时提供）：
 *   $users, $adminUrl
 * 依赖函数：
 *   shop_admin_post_string/int(), shop_admin_flash() — admin/includes/helpers.php
 *   shop_delete_user() — data/products.php
 *   get_db_connection(), get_db_prefix() — includes/db.php
 */

function handle_save_user(string $adminUrl): array
{
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

    // 检查用户名是否重复。
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

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, email=?, phone=?, level=?, address=?, note=? WHERE id=?");
            $stmt->execute([$user['username'], $user['name'], $user['email'] === '' ? null : $user['email'], $user['phone'], $user['level'], $user['address'], $user['note'], $id]);
            return ['用户已更新。', 'success'];
        }

        $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, phone, level, address, last_login, note) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$user['username'], $user['name'], $user['email'] === '' ? null : $user['email'], $user['phone'], $user['level'], $user['address'], $user['note']]);
        return ['用户已新增。', 'success'];
    } catch (PDOException $e) {
        return ['用户保存失败: ' . $e->getMessage(), 'error'];
    }
}

function handle_delete_user(): array
{
    $id = shop_admin_post_int('id');
    if (!shop_delete_user($id)) {
        return ['未找到要删除的用户。', 'error'];
    }
    return ['用户已删除。', 'success'];
}

function handle_toggle_user_status(): array
{
    $id = shop_admin_post_int('id');
    $status = shop_admin_post_string('status');
    if (!in_array($status, ['active', 'banned'], true)) {
        return ['不允许的用户状态。', 'error'];
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        return ['用户状态已更新。', 'success'];
    } catch (PDOException $e) {
        return ['用户状态更新失败: ' . $e->getMessage(), 'error'];
    }
}
