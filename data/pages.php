<?php
declare(strict_types=1);

/**
 * 页面实体 CRUD。
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers.php';

function shop_get_page_by_slug(string $slug): ?array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}pages` WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    } catch (PDOException $e) {
        shop_log_exception('按 slug 读取页面失败', $e);
        return null;
    }
}

function shop_get_page_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo instanceof PDO) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}pages` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    } catch (PDOException $e) {
        shop_log_exception('按 ID 读取页面失败', $e);
        return null;
    }
}

function shop_get_all_pages(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT id, slug, title, updated_at FROM `{$prefix}pages` ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        shop_log_exception('读取页面列表失败', $e);
        return [];
    }
}

function shop_update_page(int $id, string $title, string $content): bool
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }

    $prefix = get_db_prefix();
    try {
        $stmt = $pdo->prepare("UPDATE `{$prefix}pages` SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $id]);
        return true;
    } catch (PDOException $e) {
        shop_log_exception('更新页面失败', $e);
        return false;
    }
}

function shop_get_page_slugs(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT slug, title FROM `{$prefix}pages` ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        shop_log_exception('读取页面链接失败', $e);
        return [];
    }
}
