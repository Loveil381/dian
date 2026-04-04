<?php
declare(strict_types=1);

/**
 * 分类实体 CRUD 及元数据查询。
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/helpers.php';

function shop_normalize_category(array $category, int $fallbackId = 0): array
{
    $name = trim((string) ($category['name'] ?? '未分类'));
    if ($name === '') {
        $name = '未分类';
    }

    $accent = trim((string) ($category['accent'] ?? '#cbd5e1'));
    if ($accent === '') {
        $accent = '#cbd5e1';
    }

    $emoji = trim((string) ($category['emoji'] ?? '🛍️'));
    if ($emoji === '') {
        $emoji = '🛍️';
    }

    return [
        'id' => max(0, (int) ($category['id'] ?? $fallbackId)),
        'name' => $name,
        'description' => trim((string) ($category['description'] ?? '')),
        'accent' => $accent,
        'emoji' => $emoji,
        'sort' => max(0, (int) ($category['sort'] ?? ($fallbackId > 0 ? $fallbackId : 0))),
    ];
}

function shop_get_categories(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `{$prefix}categories` ORDER BY sort ASC, name ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return array_map(function($row) {
                    return shop_normalize_category($row, (int)$row['id']);
                }, $rows);
            }
        } catch (PDOException $e) {
            shop_log_exception('读取分类失败', $e);
            return [];
        }
    }
    return [];
}


function shop_get_category_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `{$prefix}categories` WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (is_array($row)) {
                return shop_normalize_category($row, (int) ($row['id'] ?? $id));
            }
        } catch (PDOException $e) {
            shop_log_exception('按 ID 读取分类失败', $e);
        }
    }

    return null;
}

function shop_upsert_category(array $category): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $cat = shop_normalize_category($category, (int) ($category['id'] ?? 0));

    try {
        $prefix = get_db_prefix();
        if ($cat['id'] > 0) {
            $stmt = $pdo->prepare("UPDATE `{$prefix}categories` SET name=?, description=?, accent=?, emoji=?, sort=? WHERE id=?");
            $stmt->execute([$cat['name'], $cat['description'], $cat['accent'], $cat['emoji'], $cat['sort'], $cat['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO `{$prefix}categories` (name, description, accent, emoji, sort) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$cat['name'], $cat['description'], $cat['accent'], $cat['emoji'], $cat['sort']]);
        }
        return true;
    } catch (PDOException $e) {
        shop_log_exception('保存分类失败', $e);
        return false;
    }
}

function shop_delete_category(int $id): bool
{
    $pdo = get_db_connection();
    if (!$pdo) return false;

    $prefix = get_db_prefix();
    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}categories` WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log_exception('删除分类失败', $e);
        return false;
    }
}

function shop_category_meta(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $meta = [];
    foreach (shop_get_categories() as $category) {
        $name = (string) ($category['name'] ?? '未分类');
        if ($name === '') continue;
        $meta[$name] = [
            'rank' => (int) ($category['sort'] ?? 999),
            'description' => (string) ($category['description'] ?? ''),
            'accent' => (string) ($category['accent'] ?? '#cbd5e1'),
            'emoji' => (string) ($category['emoji'] ?? '🛍️'),
        ];
    }
    $cache = $meta;
    return $meta;
}

function shop_get_category_info(string $category): array
{
    $meta = shop_category_meta();
    return $meta[$category] ?? [
        'rank' => 999,
        'description' => '精选商品分类。',
        'accent' => '#cbd5e1',
        'emoji' => '🛍️',
    ];
}

function shop_category_names(): array
{
    return array_keys(shop_category_meta());
}
