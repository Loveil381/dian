<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header('Location: index.php?page=cart');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../data/products.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/csrf.php';
    csrf_verify();
    
    $customerName = trim((string)($_POST['customer_name'] ?? ''));
    $customerPhone = trim((string)($_POST['customer_phone'] ?? ''));
    $customerAddress = trim((string)($_POST['customer_address'] ?? ''));
    $payMethod = trim((string)($_POST['pay_method'] ?? ''));
    
    $userId = $_SESSION['user_id'] ?? null;
    if ($customerName === '') {
        $customerName = '访客';
    }
    
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    
    if (!$pdo) {
        die('数据库连接失败');
    }
    
    $pdo->beginTransaction();
    
    $success = true;
    $errorMsg = '';
    
    $orderNo = date('YmdHis') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    $total = 0;
    $itemsDesc = [];
    
    foreach ($cart as $item) {
        $total += ($item['price'] * $item['quantity']);
        $itemsDesc[] = $item['name'] . ' (' . $item['sku_name'] . ') × ' . $item['quantity'];
        
        $stmtStock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmtStock->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        if ($stmtStock->rowCount() === 0) {
            $success = false;
            $errorMsg = '商品 ' . $item['name'] . ' 的库存不足，下单失败';
            break;
        }
    }
    
    if ($success) {
        $itemNameCombined = implode(', ', $itemsDesc);
        
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $orderNo,
            $userId,
            $customerName,
            $customerPhone,
            $customerAddress,
            '已支付 待确认 未发货',
            $payMethod,
            $total,
            $itemNameCombined
        ]);
        
        $pdo->commit();
        
        $_SESSION['cart'] = [];
        if (!isset($_SESSION['my_orders'])) {
            $_SESSION['my_orders'] = [];
        }
        $_SESSION['my_orders'][] = $orderNo;
        
        $_SESSION['flash_message'] = '订单已提交，请完成支付';
        header('Location: index.php?page=orders');
        exit;
    } else {
        $pdo->rollBack();
        $_SESSION['flash_message'] = $errorMsg;
        header('Location: index.php?page=cart');
        exit;
    }
}

$pdo = get_db_connection();
$prefix = get_db_prefix();

$wechatQr = '';
$alipayQr = '';
$requireAddress = false;
$userPhone = '';
$userAddress = '';
$userName = $_SESSION['user_name'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('wechat_qr', 'alipay_qr', 'require_address')");
        while ($row = $stmt->fetch()) {
            if ($row['key'] === 'wechat_qr') $wechatQr = $row['value'];
            if ($row['key'] === 'alipay_qr') $alipayQr = $row['value'];
            if ($row['key'] === 'require_address') $requireAddress = ($row['value'] === '1');
        }
        
        if ($isLoggedIn) {
            $stmtUser = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $userData = $stmtUser->fetch();
            if ($userData) {
                $userPhone = $userData['phone'] ?? '';
                $userAddress = $userData['address'] ?? '';
            }
        }
    } catch (PDOException $e) {}
}

$hasPayment = ($wechatQr !== '' || $alipayQr !== '');

$pageTitle = '确认订单';
$currentPage = 'checkout';
include __DIR__ . '/header.php';

$totalPrice = 0;
foreach ($cart as $item) {
    $totalPrice += ($item['price'] * $item['quantity']);
}
?>

<main class="page-shell">
    <div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="font-size: 24px; margin-top: 0; margin-bottom: 20px;">确认订单</h1>
        
        <div style="margin-bottom: 30px;">
            <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">商品清单</h2>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($cart as $item): ?>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <img src="<?php echo shop_e($item['cover_image'] ?? ''); ?>" alt="cover" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 15px;"><?php echo shop_e($item['name'] ?? ''); ?></h3>
                            <div style="color: #6b7280; font-size: 13px; margin-top: 5px;">规格: <?php echo shop_e($item['sku_name'] ?? ''); ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 14px; color: #4b5563;">&times; <?php echo (int)$item['quantity']; ?></div>
                            <div style="color: #dc2626; font-weight: bold; margin-top: 5px;"><?php echo shop_format_price((float)$item['price'] * $item['quantity']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align: right; margin-top: 20px; font-size: 18px;">
                合计: <span style="color: #dc2626; font-weight: bold; font-size: 24px;"><?php echo shop_format_price($totalPrice); ?></span>
            </div>
        </div>

        <form method="post" id="checkoutForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pay_method" id="payMethodInput" value="">
            
            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">收货信息</h2>
                
                <?php if ($isLoggedIn && !empty($userPhone) && !empty($userAddress)): ?>
                    <input type="hidden" name="customer_name" value="<?php echo shop_e($userName); ?>">
                    <input type="hidden" name="customer_phone" value="<?php echo shop_e($userPhone); ?>">
                    <input type="hidden" name="customer_address" value="<?php echo shop_e($userAddress); ?>">
                    <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <div style="font-weight: bold; margin-bottom: 5px;"><?php echo shop_e($userName); ?> <span style="font-weight: normal; color: #6b7280; margin-left: 10px;"><?php echo shop_e($userPhone); ?></span></div>
                        <div style="color: #4b5563; font-size: 14px;"><?php echo shop_e($userAddress); ?></div>
                        <div style="margin-top: 10px; font-size: 13px;"><a href="index.php?page=profile" style="color: #2563eb; text-decoration: none;">如需修改，请前往个人中心</a></div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">收货人姓名 <?php echo $requireAddress ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_name" value="<?php echo shop_e($userName); ?>" <?php echo $requireAddress ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">手机号码 <?php echo $requireAddress ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_phone" value="<?php echo shop_e($userPhone); ?>" <?php echo $requireAddress ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; font-size: 14px; color: #4b5563;">详细地址 <?php echo $requireAddress ? '<span style="color:red">*</span>' : ''; ?></label>
                            <input type="text" name="customer_address" value="<?php echo shop_e($userAddress); ?>" <?php echo $requireAddress ? 'required' : ''; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h2 style="font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb;">支付方式</h2>
                
                <?php if (!$hasPayment): ?>
                    <div style="padding: 20px; text-align: center; background: #fffbeb; color: #b45309; border-radius: 8px; border: 1px solid #fde68a;">
                        店主未配置收款方式，请联系店主
                    </div>
                <?php else: ?>
                    <div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                        <?php if ($wechatQr): ?>
                        <div class="pay-method-btn" onclick="selectPayment('wechat', this)" style="flex: 1; min-width: 120px; padding: 15px; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <div style="font-weight: bold; color: #10b981;">微信支付</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($alipayQr): ?>
                        <div class="pay-method-btn" onclick="selectPayment('alipay', this)" style="flex: 1; min-width: 120px; padding: 15px; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                            <div style="font-weight: bold; color: #0ea5e9;">支付宝</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="qrContainer" style="display: none; text-align: center; margin-bottom: 20px;">
                        <p style="margin-bottom: 10px; color: #4b5563;">请扫码支付 <strong style="color: #dc2626; font-size: 20px;"><?php echo shop_format_price($totalPrice); ?></strong></p>
                        <div id="wechatQR" style="display: none; width: 200px; height: 200px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px;">
                            <img src="<?php echo shop_e($wechatQr); ?>" alt="微信收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div id="alipayQR" style="display: none; width: 200px; height: 200px; margin: 0 auto; border: 1px solid #e5e7eb; padding: 10px; border-radius: 8px;">
                            <img src="<?php echo shop_e($alipayQr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" id="submitOrderBtn" onclick="submitOrder()" style="width: 100%; padding: 15px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; <?php echo !$hasPayment ? 'opacity: 0.5; pointer-events: none;' : ''; ?>">
                确认支付并提交订单
            </button>
        </form>
    </div>
</main>

<script>
function selectPayment(method, element) {
    document.querySelectorAll('.pay-method-btn').forEach(btn => {
        btn.style.borderColor = '#e5e7eb';
        btn.style.backgroundColor = 'transparent';
    });
    
    element.style.borderColor = method === 'wechat' ? '#10b981' : '#0ea5e9';
    element.style.backgroundColor = method === 'wechat' ? '#ecfdf5' : '#f0f9ff';
    
    document.getElementById('payMethodInput').value = method;
    document.getElementById('qrContainer').style.display = 'block';
    
    if (method === 'wechat') {
        document.getElementById('wechatQR').style.display = 'block';
        document.getElementById('alipayQR').style.display = 'none';
    } else {
        document.getElementById('wechatQR').style.display = 'none';
        document.getElementById('alipayQR').style.display = 'block';
    }
}

function submitOrder() {
    const payMethod = document.getElementById('payMethodInput').value;
    if (!payMethod) {
        alert('请先选择支付方式并扫码支付');
        return;
    }
    
    // Check required fields
    const form = document.getElementById('checkoutForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    if (confirm('确认已完成支付并提交订单？')) {
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
