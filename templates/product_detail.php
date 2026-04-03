<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../data/products.php';

$id = (int) ($_GET['id'] ?? 0);
$product = shop_get_product_by_id($id);

if ($product === null) {
    shop_error_page(404, '商品不存在或已下架。');
}

$pageTitle = '商品详情 - ' . (string) ($product['name'] ?? '');
$pageDescription = '查看商品详情、规格与价格信息，支持立即购买和加入购物车。';
$ogType = 'product';
$display_image = trim((string) ($product['cover_image'] ?? ''));
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
if ($display_image !== '' && !preg_match('#^https?://#i', $display_image)) {
    $ogImage = $scheme . '://' . $host . '/' . ltrim($display_image, '/');
} elseif ($display_image !== '') {
    $ogImage = $display_image;
}
$currentPage = 'product_detail';
$showFooter = true;

$images = $product['images'] ?? [];
if (!is_array($images)) {
    $images = [];
}
if ($images === [] && $display_image !== '') {
    $images[] = $display_image;
}
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

if ($pdo instanceof PDO) {
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
    } catch (PDOException $exception) {
        $_SESSION['flash_message'] = '支付配置读取失败，请稍后重试。';
        shop_log('error', '商品详情读取支付配置失败', ['message' => $exception->getMessage()]);
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
        'name' => (string) ($product['name'] ?? '默认规格'),
        'stock' => (int) ($product['stock'] ?? 0),
        'price' => (float) ($product['price'] ?? 0),
    ]];
}

$default_sku = $skus[0];
$default_sku_name = (string) ($default_sku['name'] ?? (string) ($product['name'] ?? '默认规格'));
$show_sku_selector = count($skus) > 1 || (count($skus) === 1 && $default_sku_name !== (string) ($product['name'] ?? ''));

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div style="max-width: 1100px; margin: 0 auto 18px; padding: 14px 16px; border-radius: 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
            <?php echo shop_e((string) $_SESSION['flash']); ?>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="product-detail">
        <div class="product-detail-gallery">
            <?php if ($display_image !== ''): ?>
                <img id="productMainImage" class="product-detail-main-image" src="<?php echo shop_e($display_image); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '商品')); ?>">
            <?php else: ?>
                <div class="product-detail-image-empty">暂无图片</div>
            <?php endif; ?>

            <?php if (count($images) > 1): ?>
                <div id="productThumbList" class="product-detail-thumbs">
                    <?php foreach ($images as $image): ?>
                        <?php $is_active_thumb = ((string) $image === $display_image); ?>
                        <img
                            src="<?php echo shop_e((string) $image); ?>"
                            alt="商品缩略图"
                            data-product-thumb="1"
                            class="product-detail-thumb <?php echo $is_active_thumb ? 'product-detail-thumb--active' : ''; ?>"
                        >
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-detail-info">
            <div class="product-detail-category">分类：<?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></div>
            <h1 class="product-detail-title"><?php echo shop_e((string) ($product['name'] ?? '商品')); ?></h1>
            <div class="product-detail-sales">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>

            <div class="product-detail-price-row">
                <span id="mainPriceDisplay" class="product-detail-price"><?php echo shop_format_price((float) ($default_sku['price'] ?? 0)); ?></span>
                <span id="soldOutBadge" class="product-detail-soldout" style="display: <?php echo (int) ($default_sku['stock'] ?? 0) <= 0 ? 'inline-flex' : 'none'; ?>;">暂时缺货</span>
            </div>

            <?php if ($show_sku_selector): ?>
                <div class="product-detail-sku">
                    <strong class="product-detail-sku-title">选择规格</strong>
                    <div id="skuOptions" class="product-detail-sku-options">
                        <?php foreach ($skus as $index => $sku): ?>
                            <?php
                            $sku_name = (string) ($sku['name'] ?? '默认规格');
                            $sku_price = (float) ($sku['price'] ?? 0);
                            $sku_stock = (int) ($sku['stock'] ?? 0);
                            $sku_classes = 'sku-btn';
                            if ($index === 0) {
                                $sku_classes .= ' sku-btn--selected';
                            }
                            if ($sku_stock <= 0) {
                                $sku_classes .= ' sku-btn--disabled';
                            }
                            ?>
                            <button
                                type="button"
                                class="<?php echo $sku_classes; ?>"
                                data-sku-index="<?php echo $index; ?>"
                                data-sku-name="<?php echo shop_e($sku_name); ?>"
                                data-sku-price="<?php echo $sku_price; ?>"
                                data-sku-stock="<?php echo $sku_stock; ?>"
                            >
                                <?php echo shop_e($sku_name); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="product-detail-stock">库存：<span id="stockDisplay"><?php echo shop_format_sales((int) ($default_sku['stock'] ?? 0)); ?></span> 件</div>

            <div class="product-detail-desc">
                <?php echo nl2br(shop_e((string) ($product['description'] ?? ''))); ?>
            </div>

            <div class="product-detail-actions">
                <button id="buyBtn" type="button" data-action="show-payment-popup" class="product-detail-buy-btn">
                    立即购买 <?php echo shop_format_price((float) ($default_sku['price'] ?? 0)); ?>
                </button>

                <form method="post" action="index.php?page=cart" class="product-detail-cart-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                    <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                    <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                    <input type="hidden" name="sku_name" id="cartSkuName" value="<?php echo shop_e($default_sku_name); ?>">
                    <input type="hidden" name="sku_price" id="cartSkuPrice" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
                    <button id="cartBtnSubmit" type="submit" class="product-detail-cart-btn">加入购物车</button>
                </form>
            </div>

            <script>
            let currentPrice = <?php echo (float) ($default_sku['price'] ?? 0); ?>;
            let currentSkuName = <?php echo json_encode($default_sku_name, JSON_UNESCAPED_UNICODE); ?>;
            let initialStock = <?php echo (int) ($default_sku['stock'] ?? 0); ?>;
            let requireAddress = <?php echo json_encode($require_address); ?>;
            let hasPayment = <?php echo json_encode($has_payment); ?>;
            let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
            let initialPayMethod = '';
            </script>
        </div>
    </div>
</main>

<div id="alertPopup" class="popup-overlay" style="display: none;">
    <div class="popup-card popup-card--sm">
        <h3 class="popup-title">提示</h3>
        <p id="alertMsg" class="popup-text"></p>
        <div class="popup-actions">
            <button type="button" data-action="hide-alert" class="popup-secondary-btn">关闭</button>
            <a href="index.php?page=profile" class="popup-primary-link">前往个人中心</a>
        </div>
    </div>
</div>

<div id="paymentPopup" class="popup-overlay" style="display: none;">
    <div class="popup-card">
        <button type="button" data-action="hide-payment-popup" class="popup-close">&times;</button>
        <h2 class="popup-title">选择支付方式</h2>

        <?php if (!$has_payment): ?>
            <p class="popup-text popup-text--top-space">商家尚未配置支付方式，暂时无法下单。请联系商家。</p>
        <?php else: ?>
            <div class="popup-pay-options">
                <?php if ($wechat_qr !== ''): ?>
                    <button type="button" data-action="show-qr" data-pay-method="wechat" class="popup-pay-btn popup-pay-btn--wechat">微信支付</button>
                <?php endif; ?>
                <?php if ($alipay_qr !== ''): ?>
                    <button type="button" data-action="show-qr" data-pay-method="alipay" class="popup-pay-btn popup-pay-btn--alipay">支付宝</button>
                <?php endif; ?>
            </div>

            <div id="qrContainer" class="popup-qr-container" style="display: none;">
                <p id="popupPriceDisplay" class="popup-price"></p>

                <div id="wechatQR" class="popup-qr-box" style="display: none;">
                    <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付收款码" class="popup-qr-image">
                </div>
                <div id="alipayQR" class="popup-qr-box" style="display: none;">
                    <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝收款码" class="popup-qr-image">
                </div>
            </div>

            <form method="post" action="index.php?page=checkout" id="paidForm" class="popup-submit-form" style="display: none;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="checkout_action" value="quick_buy">
                <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                <input type="hidden" name="sku_name" id="selectedSkuInput" value="<?php echo shop_e($default_sku_name); ?>">
                <input type="hidden" name="sku_price" id="selectedPriceInput" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">
                <button type="submit" class="popup-submit-btn">确认支付后提交订单</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
