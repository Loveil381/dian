<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../data/products.php';

$id = (int) ($_GET['id'] ?? 0);
$all_products = shop_get_products();
$product = shop_find_product($all_products, $id);

if ($product === null) {
    shop_error_page(404, '商品不存在或已下架。');
}

$pageTitle = '商品详情 - ' . (string) ($product['name'] ?? '');
$currentPage = 'product_detail';
$showFooter = true;

$images = $product['images'] ?? [];
if (!is_array($images)) {
    $images = [];
}

if ($images === [] && !empty($product['cover_image'])) {
    $images[] = (string) $product['cover_image'];
}

$display_image = trim((string) ($product['cover_image'] ?? ''));
if ($display_image === '') {
    $display_image = (string) ($images[0] ?? '');
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
            $user_data = $stmt_user->fetch();
            if (is_array($user_data)) {
                $user_phone = trim((string) ($user_data['phone'] ?? ''));
                $user_address = trim((string) ($user_data['address'] ?? ''));
            }
        }
    } catch (PDOException $exception) {
        $_SESSION['flash_message'] = '支付配置读取失败，请稍后再试。';
        error_log('[shop] 商品详情读取支付配置失败: ' . $exception->getMessage());
    }
}

$has_payment = ($wechat_qr !== '' || $alipay_qr !== '');
$skus = [];
if (!empty($product['sku'])) {
    $decoded_skus = json_decode((string) $product['sku'], true);
    if (is_array($decoded_skus)) {
        $skus = $decoded_skus;
    }
}

if ($skus === []) {
    $skus = [[
        'name' => '默认规格',
        'stock' => (int) ($product['stock'] ?? 0),
        'price' => (float) ($product['price'] ?? 0),
    ]];
}

$default_sku = $skus[0];

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <div style="max-width: 960px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 18px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08); display: flex; gap: 32px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 320px;">
            <?php if ($display_image !== ''): ?>
                <img id="productMainImage" src="<?php echo shop_e($display_image); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '商品')); ?>" style="width: 100%; border-radius: 12px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 320px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; color: #94a3b8;">暂无图片</div>
            <?php endif; ?>

            <?php if (count($images) > 1): ?>
                <div id="productThumbList" style="display: flex; gap: 10px; margin-top: 12px; overflow-x: auto;">
                    <?php foreach ($images as $image): ?>
                        <?php $is_active_thumb = ((string) $image === $display_image); ?>
                        <img
                            src="<?php echo shop_e((string) $image); ?>"
                            alt="商品缩略图"
                            data-product-thumb="1"
                            style="width: 68px; height: 68px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid <?php echo $is_active_thumb ? '#2563eb' : 'transparent'; ?>;"
                        >
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div style="flex: 1; min-width: 320px;">
            <div style="font-size: 13px; color: #64748b; margin-bottom: 10px;">分类：<?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></div>
            <h1 style="font-size: 30px; line-height: 1.3; margin: 0 0 12px;"><?php echo shop_e((string) ($product['name'] ?? '商品')); ?></h1>
            <div style="font-size: 14px; color: #6b7280; margin-bottom: 18px;">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>

            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 22px;">
                <span id="mainPriceDisplay" style="font-size: 32px; font-weight: 700; color: #dc2626;"><?php echo shop_format_price((float) ($default_sku['price'] ?? 0)); ?></span>
                <span id="soldOutBadge" style="display: <?php echo (int) ($default_sku['stock'] ?? 0) <= 0 ? 'inline-flex' : 'none'; ?>; padding: 4px 10px; border-radius: 999px; background: #fee2e2; color: #dc2626; font-size: 13px;">已售罄</span>
            </div>

            <div style="margin-bottom: 18px;">
                <strong style="display: block; margin-bottom: 10px;">选择规格</strong>
                <div id="skuOptions" style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($skus as $index => $sku): ?>
                        <?php
                        $sku_name = (string) ($sku['name'] ?? '默认规格');
                        $sku_price = (float) ($sku['price'] ?? 0);
                        $sku_stock = (int) ($sku['stock'] ?? 0);
                        ?>
                        <button
                            type="button"
                            class="sku-btn"
                            onclick="selectSku(<?php echo $index; ?>, <?php echo json_encode($sku_name, JSON_UNESCAPED_UNICODE); ?>, <?php echo $sku_price; ?>, <?php echo $sku_stock; ?>)"
                            style="padding: 8px 16px; border-radius: 999px; border: 2px solid <?php echo $index === 0 ? '#2563eb' : '#e5e7eb'; ?>; background: #ffffff; color: <?php echo $index === 0 ? '#2563eb' : '#334155'; ?>; cursor: pointer; <?php echo $sku_stock <= 0 ? 'opacity: 0.45; pointer-events: none;' : ''; ?>"
                        >
                            <?php echo shop_e($sku_name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-bottom: 18px; color: #475569;">
                库存：<span id="stockDisplay"><?php echo shop_format_sales((int) ($default_sku['stock'] ?? 0)); ?></span> 件
            </div>

            <div style="padding: 16px; border-radius: 12px; background: #f8fafc; color: #475569; line-height: 1.8; margin-bottom: 24px;">
                <?php echo nl2br(shop_e((string) ($product['description'] ?? ''))); ?>
            </div>

            <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                <button id="buyBtn" type="button" onclick="showPaymentPopup()" style="flex: 1; min-width: 180px; padding: 15px; border: none; border-radius: 12px; background: #2563eb; color: #ffffff; font-size: 17px; font-weight: 700; cursor: pointer;">
                    立即购买 <?php echo shop_format_price((float) ($default_sku['price'] ?? 0)); ?>
                </button>

                <form method="post" action="index.php?page=cart" style="flex: 1; min-width: 180px;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                    <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                    <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                    <input type="hidden" name="sku_name" id="cartSkuName" value="<?php echo shop_e((string) ($default_sku['name'] ?? '默认规格')); ?>">
                    <input type="hidden" name="sku_price" id="cartSkuPrice" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
                    <button id="cartBtnSubmit" type="submit" style="width: 100%; padding: 15px; border: none; border-radius: 12px; background: #f59e0b; color: #ffffff; font-size: 17px; font-weight: 700; cursor: pointer;">加入购物车</button>
                </form>
            </div>

            <script>
            let currentPrice = <?php echo (float) ($default_sku['price'] ?? 0); ?>;
            let currentSkuName = <?php echo json_encode((string) ($default_sku['name'] ?? '默认规格'), JSON_UNESCAPED_UNICODE); ?>;
            let initialStock = <?php echo (int) ($default_sku['stock'] ?? 0); ?>;
            let requireAddress = <?php echo json_encode($require_address); ?>;
            let hasPayment = <?php echo json_encode($has_payment); ?>;
            let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
            let initialPayMethod = '';
            </script>
        </div>
    </div>
</main>

<div id="alertPopup" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 3000; align-items: center; justify-content: center;">
    <div style="width: calc(100% - 32px); max-width: 340px; background: #ffffff; border-radius: 18px; padding: 24px; text-align: center;">
        <h3 style="margin: 0 0 12px; font-size: 20px;">提示</h3>
        <p id="alertMsg" style="margin: 0 0 20px; color: #475569; line-height: 1.7;"></p>
        <div style="display: flex; gap: 10px;">
            <button type="button" onclick="hideAlert()" style="flex: 1; padding: 10px; border: none; border-radius: 10px; background: #e2e8f0; color: #334155; cursor: pointer;">关闭</button>
            <a href="index.php?page=profile" style="flex: 1; padding: 10px; border-radius: 10px; background: #2563eb; color: #ffffff; text-decoration: none;">去完善资料</a>
        </div>
    </div>
</div>

<div id="paymentPopup" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 2000; align-items: center; justify-content: center;">
    <div style="width: calc(100% - 32px); max-width: 420px; background: #ffffff; border-radius: 18px; padding: 28px; position: relative; text-align: center;">
        <button type="button" onclick="hidePaymentPopup()" style="position: absolute; top: 12px; right: 14px; border: none; background: transparent; font-size: 22px; cursor: pointer;">&times;</button>
        <h2 style="margin-top: 0;">选择支付方式</h2>

        <?php if (!$has_payment): ?>
            <p style="color: #64748b; margin: 24px 0 0;">当前暂未配置收款码，请稍后再试。</p>
        <?php else: ?>
            <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin: 20px 0;">
                <?php if ($wechat_qr !== ''): ?>
                    <button type="button" onclick="showQR('wechat')" style="padding: 10px 20px; border: none; border-radius: 999px; background: #10b981; color: #ffffff; cursor: pointer;">微信支付</button>
                <?php endif; ?>
                <?php if ($alipay_qr !== ''): ?>
                    <button type="button" onclick="showQR('alipay')" style="padding: 10px 20px; border: none; border-radius: 999px; background: #0ea5e9; color: #ffffff; cursor: pointer;">支付宝</button>
                <?php endif; ?>
            </div>

            <div id="qrContainer" style="display: none; margin: 20px 0;">
                <p id="popupPriceDisplay" style="font-size: 28px; font-weight: 700; color: #dc2626; margin-bottom: 16px;"></p>

                <div id="wechatQR" style="display: none; width: 220px; height: 220px; margin: 0 auto; border-radius: 14px; background: #f8fafc; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div id="alipayQR" style="display: none; width: 220px; height: 220px; margin: 0 auto; border-radius: 14px; background: #f8fafc; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </div>

            <form method="post" action="index.php?page=checkout" id="paidForm" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="checkout_action" value="quick_buy">
                <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                <input type="hidden" name="sku_name" id="selectedSkuInput" value="<?php echo shop_e((string) ($default_sku['name'] ?? '默认规格')); ?>">
                <input type="hidden" name="sku_price" id="selectedPriceInput" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">
                <button type="submit" style="width: 100%; margin-top: 20px; padding: 12px; border: none; border-radius: 12px; background: #2563eb; color: #ffffff; font-size: 16px; cursor: pointer;">去结算</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
