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
$pageDescription = '查看商品详情、规格、价格与库存，并可直接加入购物车或立即购买。';
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
        $_SESSION['flash_message'] = '支付配置读取失败，请稍后再试。';
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

$hideBottomNav = true;
include __DIR__ . '/header.php';
?>

<main class="page-shell product-detail-page product-detail-page--has-bar">
    <?php if (!empty($_SESSION['flash'])): ?>
        <div class="product-detail-flash-wrap">
            <div class="flash success product-detail-flash">
                <?php echo shop_e((string) $_SESSION['flash']); ?>
            </div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <div class="product-detail-back-row">
        <a class="product-detail-back" href="index.php?page=products" data-back="true">
            <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
            <span>返回</span>
        </a>
    </div>

    <article class="product-detail">
        <section class="product-detail-gallery" aria-label="商品图片区域">
            <div class="product-detail-gallery-frame">
                <?php if ($display_image !== ''): ?>
                    <img id="productMainImage" class="product-detail-main-image" src="<?php echo shop_e($display_image); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '商品')); ?>">
                <?php else: ?>
                    <div class="product-detail-image-empty">
                        <span class="material-symbols-outlined" aria-hidden="true">image</span>
                    </div>
                <?php endif; ?>
            </div>

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
        </section>

        <section class="product-detail-info">
            <div class="product-detail-heading">
                <span class="badge badge-primary product-detail-category">分类：<?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></span>
                <div class="product-detail-sales text-muted">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>
            </div>

            <h1 class="product-detail-title"><?php echo shop_e((string) ($product['name'] ?? '商品')); ?></h1>

            <div class="product-detail-price-row">
                <span id="mainPriceDisplay" class="product-detail-price text-price"><?php echo shop_format_price((float) ($default_sku['price'] ?? 0)); ?></span>
                <span id="soldOutBadge" class="badge badge-error product-detail-soldout <?php echo (int) ($default_sku['stock'] ?? 0) <= 0 ? 'product-detail-soldout--visible' : ''; ?>">暂时售罄</span>
            </div>

            <?php if ($show_sku_selector): ?>
                <section class="product-detail-sku" aria-label="规格选择">
                    <div class="product-detail-sku-title-row">
                        <strong class="product-detail-sku-title">选择规格</strong>
                        <span class="product-detail-sku-tip text-muted">切换规格后会同步更新价格和库存。</span>
                    </div>
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
                </section>
            <?php endif; ?>

            <div class="product-detail-stock text-muted">库存：<span id="stockDisplay"><?php echo shop_format_sales((int) ($default_sku['stock'] ?? 0)); ?></span> 件</div>

            <section class="product-detail-qty" aria-label="数量选择">
                <strong class="product-detail-qty-title">购买数量</strong>
                <div class="product-detail-qty-stepper">
                    <button type="button" class="qty-stepper-btn qty-stepper-dec" id="qtyDec" aria-label="减少数量">
                        <span class="material-symbols-outlined" aria-hidden="true">remove</span>
                    </button>
                    <span class="qty-stepper-display" id="qtyDisplay">1</span>
                    <button type="button" class="qty-stepper-btn qty-stepper-inc" id="qtyInc" aria-label="增加数量">
                        <span class="material-symbols-outlined" aria-hidden="true">add</span>
                    </button>
                </div>
            </section>

            <section class="product-detail-desc">
                <div class="product-detail-section-heading">
                    <span class="material-symbols-outlined" aria-hidden="true">description</span>
                    <h2 class="product-detail-section-title">商品描述</h2>
                </div>
                <div class="product-detail-desc-content">
                    <?php echo nl2br(shop_e((string) ($product['description'] ?? ''))); ?>
                </div>
            </section>

            <script>
            let currentPrice = <?php echo (float) ($default_sku['price'] ?? 0); ?>;
            let currentSkuName = <?php echo json_encode($default_sku_name, JSON_UNESCAPED_UNICODE); ?>;
            let initialStock = <?php echo (int) ($default_sku['stock'] ?? 0); ?>;
            let requireAddress = <?php echo json_encode($require_address); ?>;
            let hasPayment = <?php echo json_encode($has_payment); ?>;
            let hasUserInfo = <?php echo json_encode($user_name !== '' && $user_phone !== '' && $user_address !== ''); ?>;
            let initialPayMethod = '';
            </script>
        </section>
    </article>
</main>

<footer class="product-detail-bottom-bar">
    <nav class="product-detail-bar-nav" aria-label="快速导航">
        <a href="index.php?page=home" class="product-detail-bar-nav-link" aria-label="首页">
            <span class="material-symbols-outlined" aria-hidden="true">home</span>
        </a>
        <a href="index.php?page=cart" class="product-detail-bar-nav-link" aria-label="购物车">
            <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
        </a>
        <a href="index.php?page=profile" class="product-detail-bar-nav-link" aria-label="我的">
            <span class="material-symbols-outlined" aria-hidden="true">person</span>
        </a>
    </nav>
    <div class="product-detail-bar-actions">
        <form method="post" action="index.php?page=cart" class="product-detail-cart-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cart_action" value="add">
            <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
            <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
            <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
            <input type="hidden" name="sku_name" id="cartSkuName" value="<?php echo shop_e($default_sku_name); ?>">
            <input type="hidden" name="sku_price" id="cartSkuPrice" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
            <input type="hidden" name="quantity" id="cartQtyInput" value="1">
            <button id="cartBtnSubmit" type="submit" class="product-detail-bar-add-btn">加入购物车</button>
        </form>
        <button type="button" class="product-detail-bar-buy-btn" id="buyBtn" data-action="show-payment-popup">
            立即购买
        </button>
    </div>
</footer>

<div id="alertPopup" class="modal-overlay product-detail-modal product-detail-hidden">
    <div class="product-detail-alert">
        <h3 class="product-detail-modal-title">提示</h3>
        <p id="alertMsg" class="product-detail-modal-text"></p>
        <div class="product-detail-modal-actions">
            <button type="button" data-action="hide-alert" class="btn-secondary">关闭</button>
            <a href="index.php?page=profile" class="btn-primary">前往个人中心</a>
        </div>
    </div>
</div>

<div id="paymentPopup" class="modal-overlay product-detail-modal product-detail-modal--payment product-detail-hidden">
    <div class="drawer is-open product-detail-drawer">
        <div class="product-detail-drawer-head">
            <div>
                <p class="product-detail-modal-eyebrow text-muted">确认订单</p>
                <h2 class="product-detail-modal-title">选择支付方式</h2>
            </div>
            <button type="button" data-action="hide-payment-popup" class="product-detail-drawer-close btn-ghost" aria-label="关闭支付弹窗">
                <span class="material-symbols-outlined" aria-hidden="true">close</span>
            </button>
        </div>

        <?php if (!$has_payment): ?>
            <p class="product-detail-modal-note">当前还没有可用的支付配置，请稍后再试或联系管理员。</p>
        <?php else: ?>
            <div class="popup-pay-options product-detail-pay-options">
                <?php if ($wechat_qr !== ''): ?>
                    <button type="button" data-action="show-qr" data-pay-method="wechat" class="popup-pay-btn pay-method-btn popup-pay-btn--wechat">微信支付</button>
                <?php endif; ?>
                <?php if ($alipay_qr !== ''): ?>
                    <button type="button" data-action="show-qr" data-pay-method="alipay" class="popup-pay-btn pay-method-btn popup-pay-btn--alipay">支付宝</button>
                <?php endif; ?>
            </div>

            <div id="qrContainer" class="product-detail-qr-section product-detail-hidden">
                <p class="product-detail-modal-eyebrow text-muted">扫码付款</p>
                <p id="popupPriceDisplay" class="popup-price text-price"></p>

                <div class="product-detail-qr-wrapper">
                    <div id="wechatQR" class="popup-qr-box product-detail-hidden">
                        <img src="<?php echo shop_e($wechat_qr); ?>" alt="微信支付二维码" class="popup-qr-image">
                    </div>
                    <div id="alipayQR" class="popup-qr-box product-detail-hidden">
                        <img src="<?php echo shop_e($alipay_qr); ?>" alt="支付宝二维码" class="popup-qr-image">
                    </div>
                </div>
            </div>

            <form method="post" action="index.php?page=checkout" id="paidForm" class="product-detail-paid-form product-detail-hidden">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="checkout_action" value="quick_buy">
                <input type="hidden" name="product_id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                <input type="hidden" name="name" value="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                <input type="hidden" name="cover_image" value="<?php echo shop_e($display_image); ?>">
                <input type="hidden" name="sku_name" id="selectedSkuInput" value="<?php echo shop_e($default_sku_name); ?>">
                <input type="hidden" name="sku_price" id="selectedPriceInput" value="<?php echo (float) ($default_sku['price'] ?? 0); ?>">
                <input type="hidden" name="quantity" id="buyQtyInput" value="1">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">
                <button type="submit" class="popup-submit-btn btn-primary">确认已支付并提交订单</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
