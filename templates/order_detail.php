<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../data/products.php';

$order_no = trim((string) ($_GET['order_no'] ?? ''));
if ($order_no === '') {
    shop_error_page(404, '订单号不能为空。');
}

$orders = shop_get_orders();
$order = shop_find_order_by_no($orders, $order_no);
if ($order === null) {
    shop_error_page(404, '未找到对应订单，请确认订单号是否正确。');
}

$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$my_order_nos = $_SESSION['my_orders'] ?? [];
if (!shop_user_can_view_order($order, $user_id, $my_order_nos)) {
    shop_error_page(403, '你没有权限查看这笔订单。');
}

$pay_method_label = match ((string) ($order['pay_method'] ?? '')) {
    'wechat' => '微信支付',
    'alipay' => '支付宝',
    default => (string) (($order['pay_method'] ?? '') !== '' ? $order['pay_method'] : '未记录'),
};

$order_status_options = shop_order_status_options();
$status_key = shop_normalize_order_status((string) ($order['status'] ?? ''));
$status_meta = $order_status_options[$status_key] ?? [
    'label' => (string) ($order['status'] ?? '未知状态'),
    'badge_background' => '#e2e8f0',
    'badge_color' => '#475569',
];

$pageTitle = '订单详情';
$currentPage = 'orders';
include __DIR__ . '/header.php';
?>

<main class="page-shell order-detail-page">
    <section class="card order-detail-shell">
        <header class="order-detail-header">
            <a href="index.php?page=orders" class="order-detail-back">
                <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                返回订单列表
            </a>
            <div class="order-detail-header-main">
                <div class="order-detail-title-wrap">
                    <p class="order-detail-kicker">订单详情</p>
                    <h1 class="order-detail-title">订单号 <?php echo shop_e((string) $order['order_no']); ?></h1>
                </div>
                <div class="order-detail-header-side">
                    <span class="badge order-detail-status orders-status-badge--<?php echo shop_e($status_key); ?>"><?php echo shop_e((string) $status_meta['label']); ?></span>
                    <div class="text-price text-h1 order-detail-total"><?php echo shop_format_price((float) $order['total']); ?></div>
                </div>
            </div>
        </header>

        <section class="card checkout-section order-detail-section">
            <div class="checkout-section-heading">
                <div class="checkout-section-title-wrap">
                    <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">shopping_bag</span>
                    <h2 class="checkout-section-title">商品明细</h2>
                </div>
            </div>

            <div class="checkout-items">
                <?php foreach ($order['items_data'] as $item): ?>
                    <article class="card checkout-item order-detail-item">
                        <div class="checkout-item-cover order-detail-item-cover">
                            <span class="material-symbols-outlined order-detail-item-icon" aria-hidden="true">inventory_2</span>
                        </div>
                        <div class="checkout-item-content">
                            <div class="checkout-item-title"><?php echo shop_e((string) $item['name']); ?></div>
                            <div class="checkout-item-meta-row">
                                <span class="badge"><?php echo shop_e((string) (($item['sku_name'] ?? '') !== '' ? $item['sku_name'] : '默认规格')); ?></span>
                                <span class="text-muted">商品 ID: <?php echo (int) $item['product_id']; ?></span>
                                <span class="text-muted">数量 × <?php echo (int) $item['quantity']; ?></span>
                            </div>
                        </div>
                        <div class="text-price checkout-item-price"><?php echo shop_format_price((float) $item['price']); ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="order-meta-cards">
            <div class="order-meta-card">
                <span class="material-symbols-outlined order-meta-icon" aria-hidden="true">schedule</span>
                <div>
                    <div class="order-meta-label">下单时间</div>
                    <div class="order-meta-value"><?php echo shop_e(shop_short_datetime((string) ($order['time'] ?? ''))); ?></div>
                </div>
            </div>
            <div class="order-meta-card">
                <span class="material-symbols-outlined order-meta-icon" aria-hidden="true">payment</span>
                <div>
                    <div class="order-meta-label">支付方式</div>
                    <div class="order-meta-value"><?php echo shop_e($pay_method_label); ?></div>
                </div>
            </div>
            <div class="order-meta-card">
                <span class="material-symbols-outlined order-meta-icon" aria-hidden="true">person</span>
                <div>
                    <div class="order-meta-label">收货人</div>
                    <div class="order-meta-value"><?php echo shop_e((string) (($order['customer'] ?? '') !== '' ? $order['customer'] : '游客')); ?></div>
                </div>
            </div>
        </div>

        <section class="card checkout-section order-detail-section">
            <div class="checkout-section-heading">
                <div class="checkout-section-title-wrap">
                    <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">home_pin</span>
                    <h2 class="checkout-section-title">收货信息</h2>
                </div>
            </div>

            <div class="order-detail-info-card">
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">收货人</span>
                    <span class="order-detail-info-value"><?php echo shop_e((string) (($order['customer'] ?? '') !== '' ? $order['customer'] : '游客')); ?></span>
                </div>
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">手机号</span>
                    <span class="order-detail-info-value"><?php echo shop_e((string) (($order['phone'] ?? '') !== '' ? $order['phone'] : '未填写')); ?></span>
                </div>
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">地址</span>
                    <span class="order-detail-info-value"><?php echo shop_e((string) (($order['address'] ?? '') !== '' ? $order['address'] : '未填写')); ?></span>
                </div>
            </div>
        </section>

        <section class="card checkout-section order-detail-section">
            <div class="checkout-section-heading">
                <div class="checkout-section-title-wrap">
                    <span class="material-symbols-outlined checkout-section-icon" aria-hidden="true">receipt_long</span>
                    <h2 class="checkout-section-title">订单信息</h2>
                </div>
            </div>

            <div class="order-detail-info-card">
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">订单号</span>
                    <span class="order-detail-info-value"><?php echo shop_e((string) $order['order_no']); ?></span>
                </div>
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">下单时间</span>
                    <span class="order-detail-info-value"><?php echo shop_e((string) $order['time']); ?></span>
                </div>
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">支付方式</span>
                    <span class="order-detail-info-value"><?php echo shop_e($pay_method_label); ?></span>
                </div>
                <div class="order-detail-info-row">
                    <span class="order-detail-info-label">总金额</span>
                    <span class="order-detail-info-value text-price"><?php echo shop_format_price((float) $order['total']); ?></span>
                </div>
            </div>
        </section>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
