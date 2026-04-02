<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $checkout_action = trim((string) ($_POST['checkout_action'] ?? 'submit_order'));

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($checkout_action === 'quick_buy') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $sku_name = trim((string) ($_POST['sku_name'] ?? ''));
        $sku_price = max(0, (float) ($_POST['sku_price'] ?? 0));
        $cover_image = trim((string) ($_POST['cover_image'] ?? ''));
        $pay_method = trim((string) ($_POST['pay_method'] ?? ''));

        if ($product_id <= 0 || $name === '' || $sku_name === '' || $sku_price <= 0) {
            $_SESSION['flash_message'] = '商品信息不完整，请重新选择规格后再试。';
            header('Location: index.php?page=products');
            exit;
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ((int) ($item['product_id'] ?? 0) === $product_id && (string) ($item['sku_name'] ?? '') === $sku_name) {
                $item['quantity'] = 1;
                $item['price'] = $sku_price;
                $item['sku_price'] = $sku_price;
                $item['cover_image'] = $cover_image;
                $item['name'] = $name;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => $name,
                'price' => $sku_price,
                'sku_name' => $sku_name,
                'sku_price' => $sku_price,
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
        $_SESSION['flash_message'] = '购物车为空，请先选择商品。';
        header('Location: index.php?page=cart');
        exit;
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
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
            $price = max(0, (float) ($item['price'] ?? 0));
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
                throw new RuntimeException('商品 ' . ($name !== '' ? $name : '未知商品') . ' 库存不足，无法下单。');
            }
        }

        $stmt = $pdo->prepare("INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $order_no,
            $user_id,
            $customer_name,
            $customer_phone,
            $customer_address,
            '已支付，待发货',
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
        $_SESSION['flash_message'] = '订单已创建，等待商家发货。';

        header('Location: index.php?page=orders');
        exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[shop] 结算下单失败: ' . $exception->getMessage());
        $_SESSION['flash_message'] = $exception instanceof RuntimeException ? $exception->getMessage() : '订单提交失败，请稍后再试。';
        header('Location: index.php?page=cart');
        exit;
    }
}

$cart = $_SESSION['cart'] ?? [];
if ($cart === []) {
    header('Location: index.php?page=cart');
    exit;
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
if (!$pdo) {
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
    while ($row = $stmt->fetch()) {
        if ($row['key'] === 'wechat_qr') {
            $wechat_qr = (string) $row['value'];
        }
        if ($row['key'] === 'alipay_qr') {
            $alipay_qr = (string) $row['value'];
        }
        if ($row['key'] === 'require_address') {
            $require_address = ($row['value'] === '1');
        }
    }

    if ($is_logged_in) {
        $stmt_user = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch();
        if ($user_data) {
            $user_phone = (string) ($user_data['phone'] ?? '');
            $user_address = (string) ($user_data['address'] ?? '');
        }
    }
} catch (Throwable $exception) {
    error_log('[shop] 读取结算配置失败: ' . $exception->getMessage());
    $_SESSION['flash_message'] = '结算信息加载失败，请稍后重试。';
    header('Location: index.php?page=cart');
    exit;
}

$has_payment = ($wechat_qr !== '' || $alipay_qr !== '');
$pageTitle = '确认订单';
$currentPage = 'checkout';
$showFooter = true;

$total_price = 0;
foreach ($cart as $item) {
    $total_price += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <div style="max-width: 860px; margin: 0 auto; background: #ffffff; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);">
        <h1 style="font-size: 28px; margin: 0 0 20px;">确认订单</h1>

        <div style="margin-bottom: 28px;">
            <h2 style="font-size: 18px; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">商品信息</h2>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($cart as $item): ?>
                    <div style="display: flex; gap: 14px; align-items: center; padding: 14px; border: 1px solid #e5e7eb; border-radius: 12px;">
                        <img src="<?php echo shop_e((string) ($item['cover_image'] ?? '')); ?>" alt="商品封面" style="width: 72px; height: 72px; object-fit: cover; border-radius: 10px;">
                        <div style="flex: 1;">
                            <div style="font-size: 16px; font-weight: 600;"><?php echo shop_e((string) ($item['name'] ?? '')); ?></div>
                            <div style="font-size: 13px; color: #64748b; margin-top: 6px;">规格：<?php echo shop_e((string) ($item['sku_name'] ?? '')); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 13px; color: #64748b;">× <?php echo (int) ($item['quantity'] ?? 0); ?></div>
                            <div style="font-size: 18px; font-weight: 700; color: #dc2626; margin-top: 6px;"><?php echo shop_format_price((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0)); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 18px; text-align: right; font-size: 18px;">
                总价：<strong style="font-size: 26px; color: #dc2626;"><?php echo shop_format_price($total_price); ?></strong>
            </div>
        </div>

        <form method="post" id="checkoutForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="checkout_action" value="submit_order">
            <input type="hidden" name="pay_method" id="payMethodInput" value="">

            <div style="margin-bottom: 28px;">
                <h2 style="font-size: 18px; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">收货信息</h2>

                <?php if ($is_logged_in && $user_phone !== '' && $user_address !== ''): ?>
                    <input type="hidden" name="customer_name" value="<?php echo shop_e($user_name); ?>">
                    <input type="hidden" name="customer_phone" value="<?php echo shop_e($user_phone); ?>">
                    <input type="hidden" name="customer_address" value="<?php echo shop_e($user_address); ?>">
                    <div style="padding: 16px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0;">
                        <div style="font-size: 16px; font-weight: 600;"><?php echo shop_e($user_name); ?> <span style="margin-left: 10px; color: #64748b; font-weight: 400;"><?php echo shop_e($user_phone); ?></span></div>
                        <div style="margin-top: 8px; color: #475569;"><?php echo shop_e($user_address); ?></div>
                        <div style="margin-top: 10px;"><a href="index.php?page=profile" style="color: #2563eb; text-decoration: none;">去个人中心修改默认地址</a></div>
                    </div>
                <?php else: ?>
                    <div style="display: grid; gap: 14px;">
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: #475569;">收货人姓名 <?php echo $require_address ? '<span style="color:#dc2626">*</span>' : ''; ?></label>
                            <input type="text" name="customer_name" value="<?php echo shop_e($user_name); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: #475569;">手机号码 <?php echo $require_address ? '<span style="color:#dc2626">*</span>' : ''; ?></label>
                            <input type="text" name="customer_phone" value="<?php echo shop_e($user_phone); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 6px; color: #475569;">详细地址 <?php echo $require_address ? '<span style="color:#dc2626">*</span>' : ''; ?></label>
                            <input type="text" name="customer_address" value="<?php echo shop_e($user_address); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 28px;">
                <h2 style="font-size: 18px; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">支付方式</h2>

                <?php if (!$has_payment): ?>
                    <div style="padding: 16px; border-radius: 12px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">当前未配置支付方式，请联系管理员。</div>
                <?php else: ?>
                    <div style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 18px;">
                        <?php if ($wechat_qr !== ''): ?>
                            <button type="button" class="pay-method-btn" data-pay-method="wechat" onclick="selectPayment('wechat', this)" style="flex: 1; min-width: 180px; padding: 16px; border-radius: 12px; border: 2px solid #e5e7eb; background: #ffffff; cursor: pointer;">
                                <strong style="color: #10b981;">微信支付</strong>
                            </button>
                        <?php endif; ?>

                        <?php if ($alipay_qr !== ''): ?>
                            <button type="button" class="pay-method-btn" data-pay-method="alipay" onclick="selectPayment('alipay', this)" style="flex: 1; min-width: 180px; padding: 16px; border-radius: 12px; border: 2px solid #e5e7eb; background: #ffffff; cursor: pointer;">
                                <strong style="color: #0ea5e9;">支付宝</strong>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div id="qrContainer" style="display: none; text-align: center; padding: 20px; border-radius: 16px; background: #f8fafc;">
                        <p style="margin: 0 0 14px; color: #475569;">请扫码完成支付：<strong style="font-size: 24px; color: #dc2626;"><?php echo shop_format_price($total_price); ?></strong></p>
                        <div id="wechatQR" style="display: none; width: 220px; height: 220px; margin: 0 auto;">
                            <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div id="alipayQR" style="display: none; width: 220px; height: 220px; margin: 0 auto;">
                            <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" id="submitOrderBtn" onclick="submitOrder()" style="width: 100%; padding: 15px; border: none; border-radius: 12px; background: #2563eb; color: #ffffff; font-size: 18px; font-weight: 700; cursor: pointer; <?php echo !$has_payment ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                确认已支付并提交订单
            </button>
        </form>
    </div>
</main>

<script>
// 由 PHP 动态生成的初始变量
let requireAddress = <?php echo json_encode($require_address); ?>;
let hasPayment = <?php echo json_encode($has_payment); ?>;
let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
let initialPayMethod = <?php echo json_encode($initial_pay_method); ?>;
</script>

<?php include __DIR__ . '/footer.php'; ?>
