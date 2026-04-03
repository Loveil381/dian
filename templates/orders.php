<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';

$pageTitle = '我的订单';
$currentPage = 'orders';
$flash_message = trim((string) ($_SESSION['flash_message'] ?? ''));
unset($_SESSION['flash_message']);

$all_orders = shop_get_orders();
$my_order_nos = $_SESSION['my_orders'] ?? [];
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$orders = [];

foreach ($all_orders as $order) {
    if (shop_user_can_view_order($order, $user_id, $my_order_nos)) {
        $orders[] = $order;
    }
}

$pending_count = 0;
$done_count = 0;
$today_count = 0;
$today = date('Y-m-d');
$order_status_options = shop_order_status_options();

foreach ($orders as $order) {
    $status = shop_normalize_order_status((string) ($order['status'] ?? ''));
    if (in_array($status, ['pending', 'paid'], true)) {
        $pending_count++;
    }
    if (in_array($status, ['shipped', 'completed'], true)) {
        $done_count++;
    }
    if (str_starts_with((string) ($order['time'] ?? ''), $today)) {
        $today_count++;
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell orders-page">
    <?php if ($flash_message !== ''): ?>
        <div class="flash success orders-flash">
            <?php echo shop_e($flash_message); ?>
        </div>
    <?php endif; ?>

    <section class="card orders-header">
        <div class="orders-header-copy">
            <span class="badge badge-primary">订单中心</span>
            <h1 class="orders-title">我的订单</h1>
            <p class="orders-subtitle">这里会集中展示你当前可查看的订单状态和总览信息。</p>
        </div>
        <div class="badge badge-primary orders-count-badge">共 <?php echo count($orders); ?> 笔订单</div>
    </section>

    <section class="orders-metrics" aria-label="订单统计">
        <article class="card orders-metric">
            <span class="orders-metric-label">待处理订单</span>
            <strong class="text-display"><?php echo $pending_count; ?></strong>
        </article>
        <article class="card orders-metric">
            <span class="orders-metric-label">已发货 / 已完成</span>
            <strong class="text-display"><?php echo $done_count; ?></strong>
        </article>
        <article class="card orders-metric">
            <span class="orders-metric-label">今日新增</span>
            <strong class="text-display"><?php echo $today_count; ?></strong>
        </article>
    </section>

    <section class="card orders-shell">
        <?php if ($orders === []): ?>
            <div class="orders-empty">
                <span class="material-symbols-outlined orders-empty-icon" aria-hidden="true">receipt_long</span>
                <h2 class="orders-empty-title">还没有订单记录</h2>
                <p class="orders-empty-copy">先去挑几件喜欢的商品吧，完成下单后这里会显示你的订单状态。</p>
                <a href="index.php?page=products" class="btn-primary orders-empty-action">去逛商品</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $status_key = shop_normalize_order_status((string) ($order['status'] ?? ''));
                    $status_meta = $order_status_options[$status_key] ?? [
                        'label' => (string) ($order['status'] ?? '未知状态'),
                        'badge_background' => '#e2e8f0',
                        'badge_color' => '#475569',
                    ];
                    ?>
                    <article class="card orders-item">
                        <div class="orders-item-main">
                            <div class="orders-item-heading">
                                <div class="orders-item-title-wrap">
                                    <h2 class="orders-item-title">订单号 <?php echo shop_e((string) $order['order_no']); ?></h2>
                                    <p class="text-muted orders-item-time"><?php echo shop_e(shop_short_datetime((string) $order['time'])); ?></p>
                                </div>
                                <span class="badge orders-status-badge orders-status-badge--<?php echo shop_e($status_key); ?>"><?php echo shop_e((string) $status_meta['label']); ?></span>
                            </div>
                            <p class="orders-item-summary"><?php echo shop_e((string) $order['items_summary']); ?></p>
                        </div>
                        <div class="orders-item-side">
                            <div class="text-price orders-item-total"><?php echo shop_format_price((float) $order['total']); ?></div>
                            <a href="index.php?page=order_detail&order_no=<?php echo urlencode((string) $order['order_no']); ?>" class="orders-detail-link">查看详情</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
