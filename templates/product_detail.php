<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

$id = (int) ($_GET['id'] ?? 0);
$all_products = shop_get_products();
$product = shop_find_product($all_products, $id);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    echo '商品不存在';
    exit;
}

$pageTitle = '商品详情 - ' . shop_e($product['name']);
$currentPage = 'product_detail';
$showFooter = true;

$images = $product['images'] ?? [];
if (empty($images) && !empty($product['cover_image'])) {
    $images[] = $product['cover_image'];
}

$display_image = !empty($product['cover_image']) ? $product['cover_image'] : ($images[0] ?? '');

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
$skus = [];
if (!empty($product['sku'])) {
    $skus = json_decode((string) $product['sku'], true) ?: [];
}
if (empty($skus)) {
    $skus = [[
        'name' => '默认规格',
        'stock' => (int) $product['stock'],
        'price' => (float) $product['price'],
    ]];
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <?php if ($display_image): ?>
                <img src="<?php echo shop_e($display_image); ?>" alt="<?php echo shop_e($product['name']); ?>" style="width: 100%; border-radius: 8px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">暂无图片</div>
            <?php endif; ?>

            <?php if (count($images) > 1): ?>
                <div style="display: flex; gap: 10px; margin-top: 10px; overflow-x: auto;">
                    <?php foreach ($images as $image): ?>
                        <img src="<?php echo shop_e($image); ?>" alt="商品缩略图" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid <?php echo $image === $display_image ? '#2563eb' : 'transparent'; ?>;" onclick="document.querySelector('img[alt=\'<?php echo shop_e($product['name']); ?>\']').src = this.src; this.parentElement.querySelectorAll('img').forEach(el => el.style.borderColor = 'transparent'); this.style.borderColor = '#2563eb';">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="flex: 1; min-width: 300px;">
            <h1 style="font-size: 24px; margin-top: 0;"><?php echo shop_e($product['name']); ?></h1>
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">分类：<?php echo shop_e($product['category']); ?> | 销量：<?php echo shop_format_sales((int) $product['sales']); ?></p>

            <div style="font-size: 28px; color: #dc2626; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span id="mainPriceDisplay"><?php echo shop_format_price((float) $product['price']); ?></span>
                <span id="soldOutBadge" style="display: <?php echo (int) $skus[0]['stock'] <= 0 ? 'inline-block' : 'none'; ?>; padding: 4px 8px; background: #fee2e2; color: #dc2626; font-size: 14px; border-radius: 4px; font-weight: normal;">已售罄</span>
            </div>

            <div style="margin-bottom: 20px;">
                <strong style="display: block; margin-bottom: 10px;">规格选择：</strong>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;" id="skuOptions">
                    <?php foreach ($skus as $index => $sku): ?>
                        <div class="sku-btn <?php echo $index === 0 ? 'active' : ''; ?>"
                             style="padding: 8px 16px; border: 2px solid <?php echo $index === 0 ? '#2563eb' : '#e5e7eb'; ?>; border-radius: 6px; cursor: pointer; color: <?php echo $index === 0 ? '#2563eb' : '#4b5563'; ?>; <?php echo (int) $sku['stock'] <= 0 ? 'opacity: 0.5; pointer-events: none;' : ''; ?>"
                             onclick="selectSku(<?php echo $index; ?>, <?php echo json_encode((string) $sku['name']); ?>, <?php echo (float) $sku['price']; ?>, <?php echo (int) $sku['stock']; ?>)">
                            <?php echo shop_e((string) $sku['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <strong>库存：</strong> <span id="stockDisplay"><?php echo shop_format_sales((int) $skus[0]['stock']); ?></span> 件
            </div>

            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 30px; font-size: 14px; line-height: 1.6; color: #4b5563;">
                <?php echo nl2br(shop_e((string) $product['description'])); ?>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button id="buyBtn" onclick="showPaymentPopup()" style="flex: 1; padding: 15px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer;">
                    立即购买 <?php echo shop_format_price((float) $skus[0]['price']); ?>
                </button>
                <form method="post" action="index.php?page=cart" style="flex: 1; display: flex;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                    <input type="hidden" name="name" value="<?php echo shop_e((string) $product['name']); ?>">
                    <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                    <input type="hidden" name="sku_name" id="cartSkuName" value="<?php echo shop_e((string) $skus[0]['name']); ?>">
                    <input type="hidden" name="sku_price" id="cartSkuPrice" value="<?php echo (float) $skus[0]['price']; ?>">
                    <button id="cartBtnSubmit" type="submit" style="width: 100%; padding: 15px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer;">
                        加入购物车
                    </button>
                </form>
            </div>

            <script>
            // 由 PHP 动态生成的初始变量
            let currentPrice = <?php echo (float) $skus[0]['price']; ?>;
            let currentSkuName = <?php echo json_encode((string) $skus[0]['name']); ?>;
            let initialStock = <?php echo (int) $skus[0]['stock']; ?>;
            let requireAddress = <?php echo json_encode($require_address); ?>;
            let hasPayment = <?php echo json_encode($has_payment); ?>;
            let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
            </script>
        </div>
    </div>
</main>

<div id="alertPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 3000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 320px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #4b5563; margin-bottom: 15px;">提示</h3>
        <p id="alertMsg" style="color: #4b5563; margin-bottom: 25px; line-height: 1.5;"></p>
        <div style="display: flex; gap: 10px;">
            <button onclick="hideAlert()" style="flex: 1; padding: 10px; background: #e5e7eb; color: #4b5563; border: none; border-radius: 6px; cursor: pointer;">取消</button>
            <a href="index.php?page=profile" style="flex: 1; padding: 10px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; display: inline-block;">去完善</a>
        </div>
    </div>
</div>

<div id="paymentPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; text-align: center;">
        <button onclick="hidePaymentPopup()" style="position: absolute; top: 10px; right: 10px; border: none; background: transparent; font-size: 20px; cursor: pointer;">&times;</button>
        <h2 style="margin-top: 0;">选择支付方式</h2>

        <?php if (!$has_payment): ?>
            <p style="color: #6b7280; padding: 20px;">支付系统维护中</p>
        <?php else: ?>
            <div style="display: flex; gap: 20px; justify-content: center; margin: 20px 0;">
                <?php if ($wechat_qr): ?>
                    <button onclick="showQR('wechat')" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">微信支付</button>
                <?php endif; ?>
                <?php if ($alipay_qr): ?>
                    <button onclick="showQR('alipay')" style="padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer;">支付宝</button>
                <?php endif; ?>
            </div>

            <div id="qrContainer" style="display: none; margin: 20px 0;">
                <p id="popupPriceDisplay" style="font-size: 28px; font-weight: bold; color: #dc2626; margin-bottom: 15px;"></p>

                <div id="wechatQR" style="display: none; width: 200px; height: 200px; background: #e5e7eb; margin: 0 auto; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div id="alipayQR" style="display: none; width: 200px; height: 200px; background: #e5e7eb; margin: 0 auto; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </div>

            <form method="post" action="index.php?page=create_order" id="paidForm" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="sku_name" id="selectedSkuInput" value="<?php echo shop_e((string) $skus[0]['name']); ?>">
                <input type="hidden" name="sku_price" id="selectedPriceInput" value="<?php echo (float) $skus[0]['price']; ?>">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">
                <button type="submit" onclick="hidePaymentPopup()" style="width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 20px;">我已支付</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
