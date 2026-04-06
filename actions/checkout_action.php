<?php
declare(strict_types=1);

/**
 * 结算页 POST 处理：快速购买 + 提交订单。
 *
 * 所有分支都以 header('Location: ...') + exit 结束，不返回。
 *
 * 依赖（由调用方保证已加载）：
 *   session 已启动
 *   includes/db.php, includes/csrf.php, includes/logger.php, data/products.php
 */

csrf_verify();

$checkout_action = trim((string) ($_POST['checkout_action'] ?? 'submit_order'));

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── 快速购买：把商品放进购物车并跳转到结算页 ──
if ($checkout_action === 'quick_buy') {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $sku_name = trim((string) ($_POST['sku_name'] ?? ''));
    $pay_method = trim((string) ($_POST['pay_method'] ?? ''));
    $buy_quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $fulfillment_type_id = (int) ($_POST['fulfillment_type_id'] ?? 0);
    $fulfillment_name = trim((string) ($_POST['fulfillment_name'] ?? ''));
    $fulfillment_adjust = (float) ($_POST['fulfillment_adjust'] ?? 0);

    $product = shop_get_product_by_id($product_id);
    if ($product === null) {
        $_SESSION['flash_message'] = '商品不存在或已下架，请重新选择。';
        header('Location: index.php?page=products');
        exit;
    }

    $verified_price = (float) ($product['price'] ?? 0);
    $decoded_skus = json_decode((string) ($product['sku'] ?? '[]'), true);
    if (is_array($decoded_skus)) {
        foreach ($decoded_skus as $sku) {
            if ((string) ($sku['name'] ?? '') === $sku_name) {
                $verified_price = (float) ($sku['price'] ?? $verified_price);
                break;
            }
        }
    }

    if ($sku_name === '') {
        $sku_name = (string) ($product['name'] ?? '默认规格');
    }

    $cover_image = trim((string) ($product['cover_image'] ?? ''));
    if ($cover_image === '' && !empty($product['images']) && is_array($product['images'])) {
        $cover_image = (string) ($product['images'][0] ?? '');
    }

    // 验证发货方式
    require_once __DIR__ . '/../data/fulfillment.php';
    if ($fulfillment_type_id > 0) {
        $choices = shop_get_product_fulfillment_choices($product);
        $validFulfillment = false;
        foreach ($choices as $fc) {
            if ((int) $fc['id'] === $fulfillment_type_id) {
                $fulfillment_adjust = (float) $fc['price_adjust'];
                $fulfillment_name = (string) $fc['name'];
                $validFulfillment = true;
                break;
            }
        }
        if (!$validFulfillment) {
            $fulfillment_type_id = 0;
            $fulfillment_name = '';
            $fulfillment_adjust = 0;
        }
    }

    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ((int) ($item['product_id'] ?? 0) === $product_id
            && (string) ($item['sku_name'] ?? '') === $sku_name
            && (int) ($item['fulfillment_type_id'] ?? 0) === $fulfillment_type_id) {
            $item['quantity'] = $buy_quantity;
            $item['price'] = $verified_price;
            $item['sku_price'] = $verified_price;
            $item['cover_image'] = $cover_image;
            $item['name'] = (string) ($product['name'] ?? '');
            $item['fulfillment_type_id'] = $fulfillment_type_id;
            $item['fulfillment_name'] = $fulfillment_name;
            $item['fulfillment_adjust'] = $fulfillment_adjust;
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'name' => (string) ($product['name'] ?? ''),
            'price' => $verified_price,
            'sku_name' => $sku_name,
            'sku_price' => $verified_price,
            'quantity' => $buy_quantity,
            'cover_image' => $cover_image,
            'fulfillment_type_id' => $fulfillment_type_id,
            'fulfillment_name' => $fulfillment_name,
            'fulfillment_adjust' => $fulfillment_adjust,
        ];
    }

    $_SESSION['checkout_selected_pay_method'] = $pay_method;
    header('Location: index.php?page=checkout');
    exit;
}

// ── 应用优惠券 ──
if ($checkout_action === 'apply_coupon') {
    // 速率限制：防止暴力枚举券码（每分钟最多 5 次）
    if (!shop_rate_limit('apply_coupon', 5, 60)) {
        $_SESSION['flash_message'] = '操作过于频繁，请稍后再试。';
        header('Location: index.php?page=checkout');
        exit;
    }

    require_once __DIR__ . '/../data/coupons.php';
    $coupon_code = strtoupper(trim((string) ($_POST['coupon_code'] ?? '')));
    $cart = $_SESSION['cart'] ?? [];
    $cartTotal = 0.0;
    foreach ($cart as $item) {
        $cartTotal += (float) ($item['price'] ?? $item['sku_price'] ?? 0) * max(1, (int) ($item['quantity'] ?? 1));
    }

    $validation = shop_validate_coupon($coupon_code, $cartTotal);
    if (!$validation['valid']) {
        $_SESSION['flash_message'] = $validation['message'];
    } else {
        $_SESSION['applied_coupon'] = $validation['coupon'];
        $_SESSION['flash_message'] = '优惠券已应用。';
    }
    header('Location: index.php?page=checkout');
    exit;
}

// ── 移除优惠券 ──
if ($checkout_action === 'remove_coupon') {
    unset($_SESSION['applied_coupon']);
    $_SESSION['flash_message'] = '优惠券已移除。';
    header('Location: index.php?page=checkout');
    exit;
}

// ── 提交订单 ──
$customer_name = trim((string) ($_POST['customer_name'] ?? ''));
$customer_phone = trim((string) ($_POST['customer_phone'] ?? ''));
$customer_address = trim((string) ($_POST['customer_address'] ?? ''));
$pay_method = trim((string) ($_POST['pay_method'] ?? ''));
$user_id = $_SESSION['user_id'] ?? null;
$cart = $_SESSION['cart'] ?? [];

// 已登录用户：实时检查账号状态，防止封禁账号继续下单
if ($user_id !== null) {
    $pdo_check = get_db_connection();
    if ($pdo_check instanceof PDO) {
        $prefix_tmp = get_db_prefix();
        $stmt_status = $pdo_check->prepare("SELECT status FROM `{$prefix_tmp}users` WHERE id = ? LIMIT 1");
        $stmt_status->execute([(int) $user_id]);
        $row_status = $stmt_status->fetch(PDO::FETCH_ASSOC);
        if ($row_status && trim((string) ($row_status['status'] ?? '')) === 'banned') {
            session_destroy();
            header('Location: index.php?page=auth&checkout_blocked=1');
            exit;
        }
    }
}

if ($customer_name === '') {
    $customer_name = '游客';
}

if ($cart === []) {
    $_SESSION['flash'] = '购物车为空，请先选购商品。';
    $_SESSION['flash_message'] = '购物车为空，请先选购商品。';
    header('Location: index.php?page=cart');
    exit;
}

$price_changed = false;
foreach ($_SESSION['cart'] as &$item) {
    $db_product = shop_get_product_by_id((int) ($item['product_id'] ?? 0));
    if ($db_product === null) {
        unset($item);
        $_SESSION['flash_message'] = '购物车中存在已下架商品，请重新确认。';
        header('Location: index.php?page=cart');
        exit;
    }

    $verified_price = (float) ($db_product['price'] ?? 0);
    $db_skus = json_decode((string) ($db_product['sku'] ?? '[]'), true);
    if (is_array($db_skus)) {
        foreach ($db_skus as $sku) {
            if ((string) ($sku['name'] ?? '') === (string) ($item['sku_name'] ?? '')) {
                $verified_price = (float) ($sku['price'] ?? $verified_price);
                break;
            }
        }
    }

    $original_price = (float) ($item['sku_price'] ?? $item['price'] ?? 0);
    if (abs($original_price - $verified_price) > 0.00001) {
        $price_changed = true;
    }

    $item['name'] = (string) ($db_product['name'] ?? ($item['name'] ?? ''));
    $item['price'] = $verified_price;
    $item['sku_price'] = $verified_price;
}
unset($item);

$cart = $_SESSION['cart'];

if ($price_changed) {
    $_SESSION['flash_message'] = '购物车中部分商品价格已按最新数据更新，请确认后重新提交订单。';
    header('Location: index.php?page=checkout');
    exit;
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
if (!$pdo instanceof PDO) {
    $_SESSION['flash_message'] = '数据库连接失败，请稍后重试。';
    header('Location: index.php?page=cart');
    exit;
}

try {
    $pdo->beginTransaction();

    $order_no = date('YmdHis') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $total = 0;
    $order_items = [];

    // 加载发货方式数据供库存判断
    require_once __DIR__ . '/../data/fulfillment.php';

    foreach ($cart as $item) {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        $sku_price = max(0, (float) ($item['sku_price'] ?? $item['price'] ?? 0));
        $product_id = (int) ($item['product_id'] ?? 0);
        $name = trim((string) ($item['name'] ?? ''));
        $sku_name = trim((string) ($item['sku_name'] ?? ''));
        $ft_type_id = (int) ($item['fulfillment_type_id'] ?? 0);
        $ft_name = trim((string) ($item['fulfillment_name'] ?? ''));
        $ft_adjust = (float) ($item['fulfillment_adjust'] ?? 0);

        // 最终单价 = SKU 价格 + 发货方式调整
        $unit_price = max(0.01, $sku_price + $ft_adjust);
        $total += $unit_price * $quantity;
        $item_cover = trim((string) ($item['cover_image'] ?? ''));
        $order_items[] = [
            'product_id' => $product_id,
            'name' => $name,
            'sku_name' => $sku_name,
            'price' => $unit_price,
            'quantity' => $quantity,
            'cover_image' => $item_cover,
            'fulfillment_type' => $ft_name,
        ];

        // SELECT FOR UPDATE：在事务中锁定行，避免并发超卖
        $stmt_lock = $pdo->prepare("SELECT id, name, stock FROM `{$prefix}products` WHERE id = ? FOR UPDATE");
        $stmt_lock->execute([$product_id]);
        $locked = $stmt_lock->fetch(PDO::FETCH_ASSOC);
        if (!is_array($locked)) {
            throw new RuntimeException('商品信息不存在，无法下单。');
        }

        // 判断该发货方式是否允许零库存购买（预售）
        $allow_zero = false;
        if ($ft_type_id > 0) {
            $ftRow = shop_get_fulfillment_type_by_id($ft_type_id);
            if ($ftRow !== null && (int) ($ftRow['allow_zero_stock'] ?? 0) === 1) {
                $allow_zero = true;
            }
        }

        if (!$allow_zero && (int) ($locked['stock'] ?? 0) < $quantity) {
            throw new RuntimeException(sprintf(
                '商品「%s」库存不足（剩余 %d 件），无法下单。',
                (string) ($locked['name'] ?? ''),
                (int) ($locked['stock'] ?? 0)
            ));
        }

        // 有库存时正常扣减；预售零库存时仅增加销量
        $current_stock = (int) ($locked['stock'] ?? 0);
        $deduct = min($quantity, $current_stock);
        if ($deduct > 0) {
            $stmt_stock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - ?, sales = sales + ? WHERE id = ?");
            $stmt_stock->execute([$deduct, $quantity, $product_id]);
        } else {
            // 纯预售：仅增加销量
            $stmt_sales = $pdo->prepare("UPDATE `{$prefix}products` SET sales = sales + ? WHERE id = ?");
            $stmt_sales->execute([$quantity, $product_id]);
        }
    }

    // ── 优惠券二次验证（事务内，防过期/超限）──
    $coupon_code_final = null;
    $coupon_discount_final = 0.0;
    $appliedCouponSession = $_SESSION['applied_coupon'] ?? null;
    if (is_array($appliedCouponSession) && !empty($appliedCouponSession['code'])) {
        require_once __DIR__ . '/../data/coupons.php';
        $recheck = shop_validate_coupon((string) $appliedCouponSession['code'], $total);
        if ($recheck['valid'] && shop_apply_coupon((string) $appliedCouponSession['code'])) {
            $coupon_code_final = (string) $appliedCouponSession['code'];
            $coupon_discount_final = shop_calculate_discount($recheck['coupon'], $total);
            $total = max(0.01, $total - $coupon_discount_final);
        }
        // 验证失败静默跳过（不阻断下单，用户仍按原价支付）
    }

    $stmt = $pdo->prepare(
        "INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, coupon_code, coupon_discount, time)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt->execute([
        $order_no,
        $user_id,
        $customer_name,
        $customer_phone,
        $customer_address,
        'pending',
        $pay_method,
        $total,
        shop_encode_order_items($order_items),
        $coupon_code_final,
        $coupon_discount_final,
    ]);

    $pdo->commit();

    $_SESSION['cart'] = [];
    unset($_SESSION['checkout_selected_pay_method']);
    unset($_SESSION['applied_coupon']);
    if (!isset($_SESSION['my_orders']) || !is_array($_SESSION['my_orders'])) {
        $_SESSION['my_orders'] = [];
    }
    $_SESSION['my_orders'][] = $order_no;
    $_SESSION['flash_message'] = '订单已提交，请等待商家确认收款。';

    // 触发下单通知
    try {
        require_once __DIR__ . '/../includes/notification.php';
        $notifyOrder = [
            'order_no' => $order_no, 'total' => $total, 'customer' => $customer_name,
            'phone' => $customer_phone, 'address' => $customer_address,
            'user_id' => $user_id, 'items_data' => $order_items,
        ];
        shop_notify_order_event('created', $notifyOrder);
    } catch (\Throwable $e) {
        shop_log('warning', '下单通知触发失败', ['order_no' => $order_no]);
    }

    header('Location: index.php?page=orders');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    shop_log('error', '结算下单失败', ['message' => $exception->getMessage()]);
    $_SESSION['flash_message'] = $exception instanceof RuntimeException ? $exception->getMessage() : '订单提交失败，请稍后再试。';
    header('Location: index.php?page=cart');
    exit;
}
