<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

$pageTitle = '魔女小店 - 订单';
$currentPage = 'orders';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$allOrders = shop_get_orders();
$myOrderNos = $_SESSION['my_orders'] ?? [];
$userId = $_SESSION['user_id'] ?? null;

$orders = [];
foreach ($allOrders as $order) {
    $isMyOrder = false;
    if ($userId !== null && $order['user_id'] !== null && (int)$order['user_id'] === (int)$userId) {
        $isMyOrder = true;
    } elseif (in_array($order['order_no'], $myOrderNos, true)) {
        $isMyOrder = true;
    }

    if ($isMyOrder) {
        $statusClass = 'shipping';
        if ($order['status'] === '已支付(待确认)') {
            $statusClass = 'shipping';
        } elseif (strpos($order['status'], '已发货') !== false || $order['status'] === '已完成') {
            $statusClass = 'done';
        }
        
        $orders[] = [
            'no' => $order['order_no'],
            'status' => $order['status'],
            'status_class' => $statusClass,
            'time' => $order['time'],
            'items' => $order['items'] ?? '未知商品',
            'total' => (float)$order['total'],
            'tracking_numbers' => $order['tracking_numbers'] ?? '',
            'express_company' => $order['express_company'] ?? '',
        ];
    }
}

$pendingCount = 0;
$doneCount = 0;
$todayCount = 0;
$today = date('Y-m-d');

foreach ($orders as $order) {
    if (strpos($order['status'], '待确认') !== false || strpos($order['status'], '待发货') !== false) {
        $pendingCount++;
    } elseif (strpos($order['status'], '已发货') !== false || $order['status'] === '已完成') {
        $doneCount++;
    }
    
    if (strpos($order['time'], $today) === 0) {
        $todayCount++;
    }
}

$orderStats = [
    ['value' => (string)$pendingCount, 'label' => '待发货/确认'],
    ['value' => (string)$doneCount, 'label' => '已发货/完成'],
    ['value' => (string)$todayCount, 'label' => '今日订单'],
];

$flashMessage = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <?php if ($flashMessage): ?>
    <div style="background: #ecfdf5; color: #047857; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #10b981;">
        <?php echo shop_e($flashMessage); ?>
    </div>
    <?php endif; ?>

    <section class="page-hero">
        <div class="hero-panel">
            <span class="hero-kicker">订单中心</span>
            <h1 class="hero-title">查看订单状态与发货进度</h1>
        </div>

        <div class="hero-stats">
            <?php foreach ($orderStats as $stat): ?>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_e($stat['value']); ?></strong>
                    <span class="stat-label"><?php echo shop_e($stat['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="order-layout">
        <section class="panel">
            <div class="section-heading">
                <div>
                    <h2 class="section-title">最近订单</h2>
                </div>
                <span class="section-badge"><?php echo count($orders); ?> 条示例</span>
            </div>

            <div class="order-list">
                <?php foreach ($orders as $order): ?>
                    <article class="order-item">
                        <div>
                            <div class="order-title">订单号 <?php echo shop_e($order['no']); ?></div>
                            <div class="section-note">下单时间 <?php echo shop_short_datetime((string) $order['time']); ?></div>
                            <div class="section-note">商品：<?php echo shop_e($order['items']); ?></div>
                        </div>

                        <div style="text-align: right;">
                            <span class="order-status order-status--<?php echo shop_e((string) $order['status_class']); ?>"><?php echo shop_e($order['status']); ?></span>
                            <div class="product-price" style="margin-top: 8px;"><?php echo shop_format_price((float) $order['total']); ?></div>
                            <?php if (!empty($order['tracking_numbers']) || !empty($order['express_company'])): ?>
                                <div style="margin-top: 8px; font-size: 12px; color: #6b7280; text-align: left;">
                                    <?php if (!empty($order['express_company'])): ?>
                                        <strong><?php echo shop_e($order['express_company']); ?></strong><br>
                                    <?php endif; ?>
                                    <?php if (!empty($order['tracking_numbers'])): ?>
                                        快递单号: <br>
                                        <?php echo nl2br(shop_e($order['tracking_numbers'])); ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <aside class="panel">
            <h2 class="section-title">订单看板</h2>

            <div class="order-summary-grid">
                <div class="status-card">
                    <strong><?php echo $pendingCount; ?></strong>
                    <span>待发货订单</span>
                </div>
                <div class="status-card">
                    <strong><?php echo $doneCount; ?></strong>
                    <span>已完成订单</span>
                </div>
                <div class="status-card">
                    <strong><?php echo count($orders); ?></strong>
                    <span>累计订单</span>
                </div>
            </div>
        </aside>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
