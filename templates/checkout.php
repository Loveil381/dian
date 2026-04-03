<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../data/products.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $checkout_action = trim((string) ($_POST['checkout_action'] ?? 'submit_order'));

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($checkout_action === 'quick_buy') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $sku_name = trim((string) ($_POST['sku_name'] ?? ''));
        $pay_method = trim((string) ($_POST['pay_method'] ?? ''));

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

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ((int) ($item['product_id'] ?? 0) === $product_id && (string) ($item['sku_name'] ?? '') === $sku_name) {
                $item['quantity'] = 1;
                $item['price'] = $verified_price;
                $item['sku_price'] = $verified_price;
                $item['cover_image'] = $cover_image;
                $item['name'] = (string) ($product['name'] ?? '');
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
                'quantity' => 1,
                'cover_image' => $cover_image,
            ];
        }

        $_SESSION['checkout_selected_pay_method'] = $pay_method;
        header('Location: index.php?page=checkout');
        exit;
    }

    $customer_name = trim((string) ($_POST['customer_name'] ?? ''));
    $customer_phone = trim((string) ($_POST['customer_phone'] ?? ''));
    $customer_address = trim((string) ($_POST['customer_address'] ?? ''));
    $pay_method = trim((string) ($_POST['pay_method'] ?? ''));
    $user_id = $_SESSION['user_id'] ?? null;
    $cart = $_SESSION['cart'] ?? [];

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

        foreach ($cart as $item) {
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $price = max(0, (float) ($item['sku_price'] ?? $item['price'] ?? 0));
            $product_id = (int) ($item['product_id'] ?? 0);
            $name = trim((string) ($item['name'] ?? ''));
            $sku_name = trim((string) ($item['sku_name'] ?? ''));

            $total += $price * $quantity;
            $order_items[] = [
                'product_id' => $product_id,
                'name' => $name,
                'sku_name' => $sku_name,
                'price' => $price,
                'quantity' => $quantity,
            ];

            $stmt_stock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - ? WHERE id = ? AND stock >= ?");
            $stmt_stock->execute([$quantity, $product_id, $quantity]);
            if ($stmt_stock->rowCount() === 0) {
                throw new RuntimeException('商品库存不足，无法下单。');
            }
        }

        $stmt = $pdo->prepare("INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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
        ]);

        $pdo->commit();

        $_SESSION['cart'] = [];
        unset($_SESSION['checkout_selected_pay_method']);
        if (!isset($_SESSION['my_orders']) || !is_array($_SESSION['my_orders'])) {
            $_SESSION['my_orders'] = [];
        }
        $_SESSION['my_orders'][] = $order_no;
        $_SESSION['flash_message'] = '订单已提交，请等待商家确认收款。';

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
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['flash'] = '购物车为空，请先选购商品。';
    $_SESSION['flash_message'] = '购物车为空，请先选购商品。';
    header('Location: index.php?page=cart');
    exit;
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
if (!$pdo instanceof PDO) {
    $_SESSION['flash_message'] = '数据库连接失败，请稍后重试。';
    header('Location: index.php?page=cart');
    exit;
}

$wechat_qr = '';
$alipay_qr = '';
$require_address = false;
$user_phone = '';
$user_address = '';
$user_name = (string) ($_SESSION['user_name'] ?? '');
$is_logged_in = isset($_SESSION['user_id']);
$initial_pay_method = trim((string) ($_SESSION['checkout_selected_pay_method'] ?? ''));
unset($_SESSION['checkout_selected_pay_method']);

try {
    $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('wechat_qr', 'alipay_qr', 'require_address')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (($row['key'] ?? '') === 'wechat_qr') {
            $wechat_qr = (string) ($row['value'] ?? '');
        }
        if (($row['key'] ?? '') === 'alipay_qr') {
            $alipay_qr = (string) ($row['value'] ?? '');
        }
        if (($row['key'] ?? '') === 'require_address') {
            $require_address = ((string) ($row['value'] ?? '0')) === '1';
        }
    }

    if ($is_logged_in) {
        $stmt_user = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if (is_array($user_data)) {
            $user_phone = trim((string) ($user_data['phone'] ?? ''));
            $user_address = trim((string) ($user_data['address'] ?? ''));
        }
    }
} catch (Throwable $exception) {
    shop_log('error', '读取结算配置失败', ['message' => $exception->getMessage()]);
    $_SESSION['flash_message'] = '结算信息加载失败，请稍后重试。';
    header('Location: index.php?page=cart');
    exit;
}

$has_payment = ($wechat_qr !== '' || $alipay_qr !== '');
$pageTitle = '确认订单';
$currentPage = 'checkout';
$showFooter = true;
$flash_message = trim((string) ($_SESSION['flash_message'] ?? ''));
unset($_SESSION['flash_message']);

$total_price = 0;
foreach ($cart as $item) {
    $total_price += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <div class="checkout-shell">
        <?php if ($flash_message !== ''): ?>
            <div class="flash warning checkout-flash">
                <?php echo shop_e($flash_message); ?>
            </div>
        <?php endif; ?>

        <div class="card checkout-panel">
            <div class="checkout-header">
                <span class="badge badge-primary checkout-kicker">
                    <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                    安全结算
                </span>
                <h1 class="checkout-title">确认订单</h1>
                <p class="checkout-note">逐项确认商品、收货信息与支付方式后，再提交订单完成购买。</p>
            </div>

            <section class="checkout-section">
                <div class="checkout-section-heading">
                    <div class="checkout-section-title-wrap">
                        <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">shopping_bag</span>
                        <h2 class="checkout-section-title">商品信息</h2>
                    </div>
                </div>

                <div class="checkout-items">
                    <?php foreach ($cart as $item): ?>
                        <article class="card checkout-item">
                            <div class="checkout-item-cover">
                                <img class="checkout-item-image" src="<?php echo shop_e((string) ($item['cover_image'] ?? '')); ?>" alt="商品封面">
                            </div>

                            <div class="checkout-item-content">
                                <div class="checkout-item-title"><?php echo shop_e((string) ($item['name'] ?? '')); ?></div>
                                <div class="checkout-item-meta-row">
                                    <span class="badge badge-primary">规格：<?php echo shop_e((string) ($item['sku_name'] ?? '')); ?></span>
                                    <span class="badge badge-success">× <?php echo (int) ($item['quantity'] ?? 0); ?></span>
                                </div>
                            </div>

                            <div class="checkout-item-price text-price">
                                <?php echo shop_format_price((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0)); ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="checkout-total-row">
                    <span class="font-label checkout-total-label">总价</span>
                    <strong class="text-price text-h1 checkout-total-price"><?php echo shop_format_price($total_price); ?></strong>
                </div>
            </section>

            <form method="post" id="checkoutForm" class="checkout-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="checkout_action" value="submit_order">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">

                <section class="checkout-section">
                    <div class="checkout-section-heading">
                        <div class="checkout-section-title-wrap">
                            <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">local_shipping</span>
                            <h2 class="checkout-section-title">收货信息</h2>
                        </div>
                    </div>

                    <?php if ($is_logged_in && $user_phone !== '' && $user_address !== ''): ?>
                        <input type="hidden" name="customer_name" value="<?php echo shop_e($user_name); ?>">
                        <input type="hidden" name="customer_phone" value="<?php echo shop_e($user_phone); ?>">
                        <input type="hidden" name="customer_address" value="<?php echo shop_e($user_address); ?>">
                        <div class="card checkout-address-card">
                            <div class="checkout-address-badges">
                                <span class="badge badge-success">已保存</span>
                                <span class="badge badge-primary"><?php echo shop_e($user_name); ?></span>
                                <span class="badge badge-primary"><?php echo shop_e($user_phone); ?></span>
                            </div>
                            <p class="checkout-address-text"><?php echo shop_e($user_address); ?></p>
                            <a href="index.php?page=profile" class="checkout-inline-link">去个人中心修改默认地址</a>
                        </div>
                    <?php else: ?>
                        <div class="checkout-fields">
                            <div class="checkout-field">
                                <label class="font-label checkout-field-label">收货人姓名 <?php echo $require_address ? '<span class="checkout-required">*</span>' : ''; ?></label>
                                <input class="input" type="text" name="customer_name" value="<?php echo shop_e($user_name); ?>" <?php echo $require_address ? 'required' : ''; ?>>
                            </div>
                            <div class="checkout-field">
                                <label class="font-label checkout-field-label">手机号码 <?php echo $require_address ? '<span class="checkout-required">*</span>' : ''; ?></label>
                                <input class="input" type="text" name="customer_phone" value="<?php echo shop_e($user_phone); ?>" <?php echo $require_address ? 'required' : ''; ?>>
                            </div>
                            <div class="checkout-field">
                                <label class="font-label checkout-field-label">详细地址 <?php echo $require_address ? '<span class="checkout-required">*</span>' : ''; ?></label>
                                <input class="input" type="text" name="customer_address" value="<?php echo shop_e($user_address); ?>" <?php echo $require_address ? 'required' : ''; ?>>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="checkout-section">
                    <div class="checkout-section-heading">
                        <div class="checkout-section-title-wrap">
                            <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">payments</span>
                            <h2 class="checkout-section-title">支付方式</h2>
                        </div>
                    </div>

                    <?php if (!$has_payment): ?>
                        <div class="flash warning checkout-payment-warning">
                            商家尚未配置支付方式，暂时无法下单。请联系商家。
                        </div>
                    <?php else: ?>
                        <div class="checkout-payment-grid">
                            <?php if ($wechat_qr !== ''): ?>
                                <button type="button" class="pay-method-btn checkout-pay-btn checkout-pay-btn--wechat" data-action="select-payment" data-pay-method="wechat">
                                    <span class="material-symbols-outlined checkout-pay-icon" aria-hidden="true">chat</span>
                                    <span class="checkout-pay-copy">
                                        <strong class="checkout-pay-name">微信支付</strong>
                                        <span class="checkout-pay-note">扫码完成付款</span>
                                    </span>
                                </button>
                            <?php endif; ?>

                            <?php if ($alipay_qr !== ''): ?>
                                <button type="button" class="pay-method-btn checkout-pay-btn checkout-pay-btn--alipay" data-action="select-payment" data-pay-method="alipay">
                                    <span class="material-symbols-outlined checkout-pay-icon" aria-hidden="true">account_balance_wallet</span>
                                    <span class="checkout-pay-copy">
                                        <strong class="checkout-pay-name">支付宝</strong>
                                        <span class="checkout-pay-note">扫码完成付款</span>
                                    </span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div id="qrContainer" class="surface-container-low checkout-qr">
                            <p class="checkout-qr-summary">请扫码完成支付：<strong class="text-price checkout-qr-price"><?php echo shop_format_price($total_price); ?></strong></p>
                            <div id="wechatQR" class="checkout-qr-code">
                                <img class="checkout-qr-image" src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码">
                            </div>
                            <div id="alipayQR" class="checkout-qr-code">
                                <img class="checkout-qr-image" src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码">
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

                <button type="button" id="submitOrderBtn" data-action="submit-order" class="btn-primary checkout-submit<?php echo !$has_payment ? ' is-disabled' : ''; ?>">
                    <span class="material-symbols-outlined" aria-hidden="true">lock</span>
                    确认已支付并提交订单
                </button>
            </form>
        </div>
    </div>
</main>

<script>
let requireAddress = <?php echo json_encode($require_address); ?>;
let hasPayment = <?php echo json_encode($has_payment); ?>;
let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
let initialPayMethod = <?php echo json_encode($initial_pay_method); ?>;
</script>

<?php include __DIR__ . '/footer.php'; ?>
