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

// 收集所有订单商品的 product_id，批量查找封面图（兼容旧订单无 cover_image）
$all_product_ids = [];
foreach ($orders as $order) {
    foreach (($order['items_data'] ?? []) as $it) {
        $pid = (int) ($it['product_id'] ?? 0);
        if ($pid > 0) {
            $all_product_ids[$pid] = true;
        }
    }
}
$product_covers = [];
if ($all_product_ids !== []) {
    $pdo_covers = get_db_connection();
    if ($pdo_covers instanceof PDO) {
        $prefix_covers = get_db_prefix();
        $id_list = implode(',', array_map('intval', array_keys($all_product_ids)));
        $stmt_covers = $pdo_covers->query("SELECT id, cover_image, images FROM `{$prefix_covers}products` WHERE id IN ({$id_list})");
        if ($stmt_covers) {
            while ($row = $stmt_covers->fetch(PDO::FETCH_ASSOC)) {
                $cover = trim((string) ($row['cover_image'] ?? ''));
                if ($cover === '' && !empty($row['images'])) {
                    $imgs = is_string($row['images']) ? json_decode($row['images'], true) : $row['images'];
                    if (is_array($imgs) && $imgs !== []) {
                        $cover = trim((string) ($imgs[0] ?? ''));
                    }
                }
                $product_covers[(int) $row['id']] = $cover;
            }
        }
    }
}

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

    <nav class="orders-filter-tabs" aria-label="订单状态过滤">
        <button class="orders-filter-tab is-active" data-filter="all" type="button">全部</button>
        <button class="orders-filter-tab" data-filter="pending" type="button">待付款</button>
        <button class="orders-filter-tab" data-filter="shipped" type="button">待收货</button>
        <button class="orders-filter-tab" data-filter="completed" type="button">已完成</button>
    </nav>

    <section class="card orders-shell">
        <?php if ($orders === []): ?>
            <div class="orders-empty">
                <span class="material-symbols-outlined orders-empty-icon" aria-hidden="true">receipt_long</span>
                <h2 class="orders-empty-title">还没有订单记录</h2>
                <p class="orders-empty-copy">先去挑几件喜欢的商品吧，完成下单后这里会显示你的订单状态。</p>
                <a href="index.php?page=products" class="btn-primary orders-empty-action">去逛商品</a>
            </div>
        <?php else: ?>
            <div class="orders-list" id="orders-list">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $status_key = shop_normalize_order_status((string) ($order['status'] ?? ''));
                    $status_meta = $order_status_options[$status_key] ?? [
                        'label' => (string) ($order['status'] ?? '未知状态'),
                        'badge_background' => '#e2e8f0',
                        'badge_color' => '#475569',
                    ];
                    $filter_group = match ($status_key) {
                        'pending', 'paid' => 'pending',
                        'shipped'         => 'shipped',
                        'completed'       => 'completed',
                        default           => 'other',
                    };
                    $items_data = $order['items_data'] ?? [];
                    $first_item = $items_data[0] ?? null;
                    $thumb_url = '';
                    if ($first_item !== null) {
                        $thumb_url = trim((string) ($first_item['cover_image'] ?? ''));
                        if ($thumb_url === '' && ($first_item['product_id'] ?? 0) > 0) {
                            $thumb_url = $product_covers[(int) $first_item['product_id']] ?? '';
                        }
                    }
                    $detail_url = 'index.php?page=order_detail&order_no=' . urlencode((string) $order['order_no']);
                    ?>
                    <article class="card orders-item" data-status="<?php echo shop_e($filter_group); ?>">
                        <div class="orders-item-header">
                            <div class="orders-item-header-left">
                                <p class="orders-item-no">订单号: <?php echo shop_e((string) $order['order_no']); ?></p>
                                <p class="text-muted orders-item-time"><?php echo shop_e(shop_short_datetime((string) $order['time'])); ?></p>
                            </div>
                            <span class="badge orders-status-badge orders-status-badge--<?php echo shop_e($status_key); ?>"><?php echo shop_e((string) $status_meta['label']); ?></span>
                        </div>

                        <div class="orders-item-body">
                            <div class="orders-item-thumb">
                                <?php if ($thumb_url !== ''): ?>
                                    <img src="<?php echo shop_e($thumb_url); ?>" alt="<?php echo shop_e($first_item['name'] ?? ''); ?>" class="orders-item-img" loading="lazy">
                                <?php else: ?>
                                    <span class="material-symbols-outlined orders-item-img-fallback" aria-hidden="true">package_2</span>
                                <?php endif; ?>
                            </div>
                            <div class="orders-item-info">
                                <h2 class="orders-item-name"><?php echo shop_e($first_item['name'] ?? '商品'); ?></h2>
                                <?php if (($first_item['sku_name'] ?? '') !== ''): ?>
                                    <p class="orders-item-sku"><?php echo shop_e($first_item['sku_name']); ?></p>
                                <?php endif; ?>
                                <?php if (count($items_data) > 1): ?>
                                    <p class="orders-item-extra">等 <?php echo count($items_data); ?> 件商品</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php
                        $o_express = trim((string) ($order['express_company'] ?? ''));
                        $o_tracking = trim((string) ($order['tracking_numbers'] ?? ''));
                        if ($o_express !== '' || $o_tracking !== ''):
                            $o_first_tracking = $o_tracking !== '' ? explode("\n", $o_tracking)[0] : '';
                        ?>
                        <div class="orders-item-tracking">
                            <span class="material-symbols-outlined" style="font-size: 1rem; color: var(--color-primary);" aria-hidden="true">local_shipping</span>
                            <span class="text-muted"><?php echo shop_e($o_express); ?></span>
                            <?php if ($o_first_tracking !== ''): ?>
                                <code style="font-size: var(--text-caption);"><?php echo shop_e(trim($o_first_tracking)); ?></code>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="orders-item-footer">
                            <p class="text-price orders-item-total">合计: <?php echo shop_format_price((float) $order['total']); ?></p>
                            <div class="orders-item-actions">
                                <?php if ($filter_group === 'pending'): ?>
                                    <a href="<?php echo $detail_url; ?>" class="btn-ghost orders-action-btn orders-action-outline">查看详情</a>
                                <?php elseif ($filter_group === 'shipped'): ?>
                                    <a href="<?php echo $detail_url; ?>" class="btn-ghost orders-action-btn orders-action-secondary">查看物流</a>
                                <?php elseif ($filter_group === 'completed'): ?>
                                    <a href="index.php?page=products" class="btn-ghost orders-action-btn orders-action-muted">再次购买</a>
                                    <a href="<?php echo $detail_url; ?>" class="btn-ghost orders-action-btn orders-action-outline">查看详情</a>
                                <?php else: ?>
                                    <a href="<?php echo $detail_url; ?>" class="btn-ghost orders-action-btn orders-action-outline">查看详情</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
(function () {
    'use strict';
    var tabs = document.querySelectorAll('.orders-filter-tab');
    var items = document.querySelectorAll('#orders-list .orders-item');

    if (!tabs.length || !items.length) { return; }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var filter = tab.getAttribute('data-filter');

            // 更新 Tab active 状态
            tabs.forEach(function (t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');

            // 显示 / 隐藏订单卡片
            items.forEach(function (item) {
                if (filter === 'all' || item.getAttribute('data-status') === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
}());
</script>

<?php include __DIR__ . '/footer.php'; ?>
