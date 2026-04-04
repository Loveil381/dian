<?php
declare(strict_types=1);

/**
 * 用户实体 CRUD。
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers.php';

function shop_normalize_user(array $user, int $fallbackId = 0): array
{
    $status = trim((string) ($user['status'] ?? 'active'));
    if (!in_array($status, ['active', 'follow_up', 'sleeping', 'banned'], true)) {
        $status = 'active';
    }

    return [
        'id' => max(0, (int) ($user['id'] ?? $fallbackId)),
        'username' => trim((string) ($user['username'] ?? '')),
        'name' => trim((string) ($user['name'] ?? '未命名用户')),
        'email' => trim((string) ($user['email'] ?? '')),
        'phone' => trim((string) ($user['phone'] ?? '')),
        'level' => trim((string) ($user['level'] ?? '普通会员')),
        'status' => $status,
        'address' => trim((string) ($user['address'] ?? '')),
        'last_login' => trim((string) ($user['last_login'] ?? '')),
        'note' => trim((string) ($user['note'] ?? '')),
        'reset_token' => trim((string) ($user['reset_token'] ?? '')),
        'reset_expires' => trim((string) ($user['reset_expires'] ?? '')),
    ];
}

function shop_get_users(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}users` ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_user($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取用户失败', $e);
            return [];
        }
    }
    return [];
}


function shop_find_user(array $users, int $id): ?array
{
    foreach ($users as $user) {
        if ((int) ($user['id'] ?? 0) === $id) return $user;
    }
    return null;
}

function shop_get_user_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}users` WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                return shop_normalize_user($row, (int) ($row['id'] ?? $id));
            }
        } catch (PDOException $e) {
            shop_log_exception('按 ID 读取用户失败', $e);
        }
    }

    return shop_find_user(shop_get_users(), $id);
}

function shop_upsert_user(array $user): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $u = shop_normalize_user($user, (int) ($user['id'] ?? 0));

    try {
        $prefix = get_db_prefix();
        if ($u['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET username=?, name=?, email=?, phone=?, level=?, status=?, address=?, last_login=?, note=?, reset_token=?, reset_expires=? WHERE id=?");
            $stmt->execute([
                $u['username'],
                $u['name'],
                $u['email'] === '' ? null : $u['email'],
                $u['phone'],
                $u['level'],
                $u['status'],
                $u['address'],
                $u['last_login'] === '' ? null : $u['last_login'],
                $u['note'],
                $u['reset_token'] === '' ? null : $u['reset_token'],
                $u['reset_expires'] === '' ? null : $u['reset_expires'],
                $u['id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, phone, level, status, address, last_login, note, reset_token, reset_expires) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $u['username'],
                $u['name'],
                $u['email'] === '' ? null : $u['email'],
                $u['phone'],
                $u['level'],
                $u['status'],
                $u['address'],
                $u['last_login'] === '' ? null : $u['last_login'],
                $u['note'],
                $u['reset_token'] === '' ? null : $u['reset_token'],
                $u['reset_expires'] === '' ? null : $u['reset_expires']
            ]);
        }
        return true;
    } catch (PDOException $e) {
        shop_log_exception('保存用户失败', $e);
        return false;
    }
}

function shop_delete_user(int $id): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $prefix = get_db_prefix();
    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}users` WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log_exception('删除用户失败', $e);
        return false;
    }
}
