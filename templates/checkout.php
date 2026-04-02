<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $customer_name = trim((string) ($_POST['customer_name'] ?? ''));
    $customer_phone = trim((string) ($_POST['customer_phone'] ?? ''));
    $customer_address = trim((string) ($_POST['customer_address'] ?? ''));
    $pay_method = trim((string) ($_POST['pay_method'] ?? ''));

    $user_id = $_SESSION['user_id'] ?? null;
    if ($customer_name === '') {
        $customer_name = '游客';
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        die('数据库连接失败');
    }

    $cart = $_SESSION['cart'] ?? [];
    $pdo->beginTransaction();

    $success = true;
    $error_message = '';
    $order_no = date('YmdHis') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $total = 0;
    $items_desc = [];

    foreach ($cart as $item) {
        $quantity = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $product_id = (int) ($item['product_id'] ?? 0);
        $name = (string) ($item['name'] ?? '');
        $sku_name = (string) ($item['sku_name'] ?? '');

        $total += ($price * $quantity);
        $items_desc[] = $name . ' (' . $sku_name . ') ×' . $quantity;

        $stmt_stock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt_stock->execute([$quantity, $product_id, $quantity]);
        if ($stmt_stock->rowCount() === 0) {
            $success = false;
            $error_message = '商品 ' . $name . ' 库存不足，无法下单';
            break;
        }
    }

    if ($success) {
        $item_name_combined = implode(', ', $items_desc);
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
            $item_name_combined,
        ]);

        $pdo->commit();
        $_SESSION['cart'] = [];
        if (!isset($_SESSION['my_orders']) || !is_array($_SESSION['my_orders'])) {
            $_SESSION['my_orders'] = [];
        }
        $_SESSION['my_orders'][] = $order_no;
        $_SESSION['flash_message'] = '订单已创建，等待商家发货';

        header('Location: index.php?page=orders');
        exit;
    }

    $pdo->rollBack();
    $_SESSION['flash_message'] = $error_message;
    header('Location: index.php?page=cart');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php?page=cart');
    exit;
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
$wechat_qr = '';
$alipay_qr = '';
$require_address = false;
$user_phone = '';
$user_address = '';
$user_name = (string) ($_SESSION['user_name'] ?? '');
$is_logged_in = isset($_SESSION['user_id']);

if ($pdo) {
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
    } catch (PDOException $e) {
    }
}

$has_payment = ($wechat_qr !== '' || $alipay_qr !== '');
$pageTitle = '结算';
$currentPage = 'checkout';
include __DIR__ . '/header.php';

$total_price = 0;
foreach ($cart as $item) {
    $total_price += ((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0));
}
?>

<main class="page-shell">
    <div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="font-size: 24px; margin-top: 0; margin-bottom: 20px;">结算</h1>

        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">商品信息</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($cart as $item): ?>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <img src="<?php echo shop_e((string) ($item['cover_image'] ?? '')); ?>" alt="商品封面" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 15px;"><?php echo shop_e((string) ($item['name'] ?? '')); ?></h3>
                            <div style="color: #6b7280; font-size: 13px; margin-top: 5px;">规格：<?php echo shop_e((string) ($item['sku_name'] ?? '')); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; color: #4b5563;">× <?php echo (int) ($item['quantity'] ?? 0); ?></div>
                            <div style="color: #dc2626; font-weight: bold; margin-top: 5px;"><?php echo shop_format_price((float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0)); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: right; margin-top: 20px; font-size: 18px;">
                总价：<span style="color: #dc2626; font-weight: bold; font-size: 24px;"><?php echo shop_format_price($total_price); ?></span>
            </div>
        </div>

        <form method="post" id="checkoutForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pay_method" id="payMethodInput" value="">

            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">收货信息</h2>

                <?php if ($is_logged_in && $user_phone !== '' && $user_address !== ''): ?>
                    <input type="hidden" name="customer_name" value="<?php echo shop_e($user_name); ?>">
                    <input type="hidden" name="customer_phone" value="<?php echo shop_e($user_phone); ?>">
                    <input type="hidden" name="customer_address" value="<?php echo shop_e($user_address); ?>">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo shop_e($user_name); ?> <span style="font-weight: normal; color: #6b7280; margin-left: 10px;"><?php echo shop_e($user_phone); ?></span></div>
                        <div style="color: #4b5563; font-size: 14px;"><?php echo shop_e($user_address); ?></div>
                        <div style="margin-top: 10px; font-size: 13px;"><a href="index.php?page=profile" style="color: #2563eb; text-decoration: none;">去个人中心修改默认地址</a></div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">收货人姓名 <?php echo $require_address ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_name" value="<?php echo shop_e($user_name); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">手机号码 <?php echo $require_address ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_phone" value="<?php echo shop_e($user_phone); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">详细地址 <?php echo $require_address ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_address" value="<?php echo shop_e($user_address); ?>" <?php echo $require_address ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">支付方式</h2>

                <?php if (!$has_payment): ?>
                    <div style="padding: 20px; text-align: center; background: #fffbeb; color: #b45309; border-radius: 8px; border: 1px solid #fde68a;">
                        当前未配置支付方式，请联系管理员
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <?php if ($wechat_qr): ?>
                            <div class="pay-method-btn" onclick="selectPayment('wechat', this)" style="flex: 1; min-width: 120px; padding: 15px; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                <div style="font-weight: bold; color: #10b981;">微信支付</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($alipay_qr): ?>
                            <div class="pay-method-btn" onclick="selectPayment('alipay', this)" style="flex: 1; min-width: 120px; padding: 15px; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                                <div style="font-weight: bold; color: #0ea5e9;">支付宝</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="qrContainer" style="display: none; text-align: center; margin-bottom: 20px;">
                        <p style="margin-bottom: 10px; color: #4b5563;">请扫码支付：<strong style="color: #dc2626; font-size: 20px;"><?php echo shop_format_price($total_price); ?></strong></p>
                        <div id="wechatQR" style="display: none; width: 200px; height: 200px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px;">
                            <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div id="alipayQR" style="display: none; width: 200px; height: 200px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px;">
                            <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" id="submitOrderBtn" onclick="submitOrder()" style="width: 100%; padding: 15px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; <?php echo !$has_payment ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
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
</script>

<?php include __DIR__ . '/footer.php'; ?>
