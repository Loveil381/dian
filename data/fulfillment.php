<?php
declare(strict_types=1);

/**
 * 发货方式实体 CRUD。
 *
 * 依赖：includes/db.php, includes/logger.php
 */

require_once __DIR__ . '/../includes/db.php';

/**
 * 标准化单条发货方式数据。
 */
function shop_normalize_fulfillment_type(array $row, int $fallbackId = 0): array
{
    $status = trim((string) ($row['status'] ?? 'active'));
    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    return [
        'id'               => max(0, (int) ($row['id'] ?? $fallbackId)),
        'slug'             => trim((string) ($row['slug'] ?? '')),
        'name'             => trim((string) ($row['name'] ?? '')),
        'description'      => trim((string) ($row['description'] ?? '')),
        'icon'             => trim((string) ($row['icon'] ?? 'local_shipping')),
        'badge_color'      => trim((string) ($row['badge_color'] ?? '#6a37d4')),
        'allow_zero_stock' => (int) ($row['allow_zero_stock'] ?? 0),
        'sort'             => max(0, (int) ($row['sort'] ?? 0)),
        'status'           => $status,
    ];
}

/**
 * 获取全部发货方式（按 sort 升序、id 升序）。
 */
function shop_get_fulfillment_types(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT * FROM `{$prefix}fulfillment_types` ORDER BY sort ASC, id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(function (array $row): array {
            return shop_normalize_fulfillment_type($row, (int) $row['id']);
        }, $rows ?: []);
    } catch (PDOException $e) {
        shop_log_exception('读取发货方式失败', $e);
        return [];
    }
}

/**
 * 获取所有启用的发货方式。
 */
function shop_get_active_fulfillment_types(): array
{
    return array_values(array_filter(shop_get_fulfillment_types(), static function (array $ft): bool {
        return $ft['status'] === 'active';
    }));
}

/**
 * 按 ID 获取单条。
 */
function shop_get_fulfillment_type_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}fulfillment_types` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? shop_normalize_fulfillment_type($row, $id) : null;
    } catch (PDOException $e) {
        shop_log_exception('按 ID 读取发货方式失败', $e);
        return null;
    }
}

/**
 * 新建或更新发货方式。id=0 插入，id>0 更新。
 */
function shop_upsert_fulfillment_type(array $data): bool
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }

    $ft = shop_normalize_fulfillment_type($data, (int) ($data['id'] ?? 0));
    $prefix = get_db_prefix();

    try {
        if ($ft['id'] > 0) {
            $stmt = $pdo->prepare(
                "UPDATE `{$prefix}fulfillment_types` SET slug=?, name=?, description=?, icon=?, badge_color=?, allow_zero_stock=?, sort=?, status=? WHERE id=?"
            );
            $stmt->execute([
                $ft['slug'], $ft['name'], $ft['description'], $ft['icon'],
                $ft['badge_color'], $ft['allow_zero_stock'], $ft['sort'], $ft['status'],
                $ft['id'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO `{$prefix}fulfillment_types` (slug, name, description, icon, badge_color, allow_zero_stock, sort, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $ft['slug'], $ft['name'], $ft['description'], $ft['icon'],
                $ft['badge_color'], $ft['allow_zero_stock'], $ft['sort'], $ft['status'],
            ]);
        }
        return true;
    } catch (PDOException $e) {
        shop_log_exception('保存发货方式失败', $e);
        return false;
    }
}

/**
 * 删除发货方式。
 */
function shop_delete_fulfillment_type(int $id): bool
{
    $pdo = get_db_connection();
    if (!$pdo) {
        return false;
    }

    $prefix = get_db_prefix();
    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}fulfillment_types` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log_exception('删除发货方式失败', $e);
        return false;
    }
}

/**
 * 解析商品的 fulfillment_options JSON，返回标准化数组。
 *
 * 每个元素：['type_id' => int, 'price_adjust' => float, 'note' => string]
 */
function shop_decode_fulfillment_options(string $json): array
{
    if ($json === '') {
        return [];
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }

    $result = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }
        $result[] = [
            'type_id'      => (int) ($item['type_id'] ?? 0),
            'price_adjust' => (float) ($item['price_adjust'] ?? 0),
            'note'         => trim((string) ($item['note'] ?? '')),
        ];
    }
    return $result;
}

/**
 * 将发货方式选项编码为 JSON 存储。
 */
function shop_encode_fulfillment_options(array $options): string
{
    if ($options === []) {
        return '';
    }
    return json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
}

/**
 * 根据商品的 fulfillment_options 和全局发货方式列表，
 * 返回带完整类型信息的选项数组（供前台渲染）。
 *
 * 每个元素包含发货方式的完整字段 + price_adjust + note
 */
function shop_get_product_fulfillment_choices(array $product, ?array $allTypes = null): array
{
    $optionsJson = (string) ($product['fulfillment_options'] ?? '');
    $options = shop_decode_fulfillment_options($optionsJson);
    if ($options === []) {
        return [];
    }

    if ($allTypes === null) {
        $allTypes = shop_get_active_fulfillment_types();
    }

    // 按 type_id 索引
    $typeMap = [];
    foreach ($allTypes as $ft) {
        $typeMap[(int) $ft['id']] = $ft;
    }

    $choices = [];
    foreach ($options as $opt) {
        $typeId = $opt['type_id'];
        if (!isset($typeMap[$typeId])) {
            continue; // 类型已被删除或停用
        }
        $ft = $typeMap[$typeId];
        $choices[] = array_merge($ft, [
            'price_adjust' => $opt['price_adjust'],
            'note'         => $opt['note'] !== '' ? $opt['note'] : $ft['description'],
        ]);
    }

    return $choices;
}
