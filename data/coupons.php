<?php
declare(strict_types=1);

/**
 * 优惠券数据访问层。
 *
 * 提供优惠券 CRUD、验证、应用、折扣计算等功能。
 * 依赖：includes/db.php, includes/logger.php
 */

/**
 * 标准化优惠券行数据。
 */
function shop_normalize_coupon(array $row): array
{
    return [
        'id'               => (int) ($row['id'] ?? 0),
        'code'             => strtoupper(trim((string) ($row['code'] ?? ''))),
        'type'             => in_array($row['type'] ?? '', ['fixed', 'percent'], true) ? (string) $row['type'] : 'fixed',
        'value'            => max(0, (float) ($row['value'] ?? 0)),
        'min_order_amount' => max(0, (float) ($row['min_order_amount'] ?? 0)),
        'usage_limit'      => max(0, (int) ($row['usage_limit'] ?? 0)),
        'used_count'       => max(0, (int) ($row['used_count'] ?? 0)),
        'starts_at'        => (string) ($row['starts_at'] ?? ''),
        'expires_at'       => (string) ($row['expires_at'] ?? ''),
        'status'           => in_array($row['status'] ?? '', ['active', 'disabled'], true) ? (string) $row['status'] : 'active',
        'created_at'       => (string) ($row['created_at'] ?? ''),
        'updated_at'       => (string) ($row['updated_at'] ?? ''),
    ];
}

/**
 * 根据 ID 获取优惠券。
 */
function shop_get_coupon_by_id(int $id): ?array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo || $id <= 0) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}coupons` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? shop_normalize_coupon($row) : null;
    } catch (PDOException $e) {
        shop_log('error', '查询优惠券失败', ['id' => $id, 'message' => $e->getMessage()]);
        return null;
    }
}

/**
 * 根据券码获取优惠券（大小写不敏感）。
 */
function shop_get_coupon_by_code(string $code): ?array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    $code = strtoupper(trim($code));
    if (!$pdo || $code === '') {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}coupons` WHERE `code` = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? shop_normalize_coupon($row) : null;
    } catch (PDOException $e) {
        shop_log('error', '查询优惠券失败', ['code' => $code, 'message' => $e->getMessage()]);
        return null;
    }
}

/**
 * 创建或更新优惠券（id=0 插入，>0 更新）。
 */
function shop_upsert_coupon(array $data): bool
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return false;
    }

    $coupon = shop_normalize_coupon($data);
    $id = $coupon['id'];

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE `{$prefix}coupons` SET `code`=?, `type`=?, `value`=?, `min_order_amount`=?,
                 `usage_limit`=?, `starts_at`=?, `expires_at`=?, `status`=? WHERE `id`=?"
            );
            $stmt->execute([
                $coupon['code'], $coupon['type'], $coupon['value'], $coupon['min_order_amount'],
                $coupon['usage_limit'],
                $coupon['starts_at'] !== '' ? $coupon['starts_at'] : null,
                $coupon['expires_at'] !== '' ? $coupon['expires_at'] : null,
                $coupon['status'], $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO `{$prefix}coupons` (`code`, `type`, `value`, `min_order_amount`, `usage_limit`, `starts_at`, `expires_at`, `status`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $coupon['code'], $coupon['type'], $coupon['value'], $coupon['min_order_amount'],
                $coupon['usage_limit'],
                $coupon['starts_at'] !== '' ? $coupon['starts_at'] : null,
                $coupon['expires_at'] !== '' ? $coupon['expires_at'] : null,
                $coupon['status'],
            ]);
        }
        return true;
    } catch (PDOException $e) {
        shop_log('error', '保存优惠券失败', ['id' => $id, 'message' => $e->getMessage()]);
        return false;
    }
}

/**
 * 删除优惠券。
 */
function shop_delete_coupon(int $id): bool
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo || $id <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM `{$prefix}coupons` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log('error', '删除优惠券失败', ['id' => $id, 'message' => $e->getMessage()]);
        return false;
    }
}

/**
 * 验证优惠券是否可用。
 *
 * @return array{valid: bool, message: string, coupon: ?array}
 */
function shop_validate_coupon(string $code, float $orderTotal): array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['valid' => false, 'message' => '请输入优惠券码。', 'coupon' => null];
    }

    $coupon = shop_get_coupon_by_code($code);
    if ($coupon === null) {
        return ['valid' => false, 'message' => '优惠券不存在。', 'coupon' => null];
    }

    if ($coupon['status'] !== 'active') {
        return ['valid' => false, 'message' => '该优惠券已被禁用。', 'coupon' => $coupon];
    }

    // 检查有效期
    $now = date('Y-m-d H:i:s');
    if ($coupon['starts_at'] !== '' && $now < $coupon['starts_at']) {
        return ['valid' => false, 'message' => '优惠券尚未开始生效。', 'coupon' => $coupon];
    }
    if ($coupon['expires_at'] !== '' && $now > $coupon['expires_at']) {
        return ['valid' => false, 'message' => '优惠券已过期。', 'coupon' => $coupon];
    }

    // 检查使用次数
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => '优惠券已达使用上限。', 'coupon' => $coupon];
    }

    // 检查最低消费
    if ($coupon['min_order_amount'] > 0 && $orderTotal < $coupon['min_order_amount']) {
        return [
            'valid' => false,
            'message' => '订单金额未达到最低消费 ' . shop_format_price($coupon['min_order_amount']) . '。',
            'coupon' => $coupon,
        ];
    }

    return ['valid' => true, 'message' => '优惠券可用。', 'coupon' => $coupon];
}

/**
 * 原子递增优惠券使用次数（防并发超限）。
 *
 * @return bool 成功返回 true，失败或超限返回 false
 */
function shop_apply_coupon(string $code): bool
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    $code = strtoupper(trim($code));
    if (!$pdo || $code === '') {
        return false;
    }

    try {
        // 原子操作：仅当 used_count 未达 usage_limit 时递增（usage_limit=0 表示不限）
        $stmt = $pdo->prepare(
            "UPDATE `{$prefix}coupons` SET `used_count` = `used_count` + 1
             WHERE `code` = ? AND `status` = 'active'
             AND (`usage_limit` = 0 OR `used_count` < `usage_limit`)"
        );
        $stmt->execute([$code]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        shop_log('error', '应用优惠券失败', ['code' => $code, 'message' => $e->getMessage()]);
        return false;
    }
}

/**
 * 计算优惠券折扣金额。
 */
function shop_calculate_discount(array $coupon, float $orderTotal): float
{
    $value = (float) ($coupon['value'] ?? 0);
    $type = (string) ($coupon['type'] ?? 'fixed');

    if ($type === 'percent') {
        // 百分比折扣：value 是折扣百分比（如 15 表示打 85 折，优惠 15%）
        return round($orderTotal * $value / 100, 2);
    }

    // 固定金额：不超过订单总额
    return min($value, $orderTotal);
}
