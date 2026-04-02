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

<main class="page-shell">
    <?php if ($flash_message !== ''): ?>
        <div style="max-width: 960px; margin: 0 auto 18px; padding: 14px 16px; border-radius: 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
            <?php echo shop_e($flash_message); ?>
        </div>
    <?php endif; ?>

    <section style="max-width: 960px; margin: 0 auto 24px; display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
        <div style="background: #ffffff; padding: 18px; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);">
            <div style="font-size: 13px; color: #64748b;">待处理订单</div>
            <div style="margin-top: 10px; font-size: 30px; font-weight: 700;"><?php echo $pending_count; ?></div>
        </div>
        <div style="background: #ffffff; padding: 18px; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);">
            <div style="font-size: 13px; color: #64748b;">已发货 / 已完成</div>
            <div style="margin-top: 10px; font-size: 30px; font-weight: 700;"><?php echo $done_count; ?></div>
        </div>
        <div style="background: #ffffff; padding: 18px; border-radius: 16px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);">
            <div style="font-size: 13px; color: #64748b;">今日新增</div>
            <div style="margin-top: 10px; font-size: 30px; font-weight: 700;"><?php echo $today_count; ?></div>
        </div>
    </section>

    <section style="max-width: 960px; margin: 0 auto; background: #ffffff; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 18px;">
            <div>
                <h1 style="font-size: 28px; margin: 0;">我的订单</h1>
                <p style="margin: 8px 0 0; color: #64748b;">共 <?php echo count($orders); ?> 笔订单</p>
            </div>
        </div>

        <?php if ($orders === []): ?>
            <div style="text-align: center; padding: 48px 0; color: #64748b;">暂无订单记录。</div>
        <?php else: ?>
            <div style="display: grid; gap: 16px;">
                <?php foreach ($orders as $order): ?>
                    <?php
                    $status_key = shop_normalize_order_status((string) ($order['status'] ?? ''));
                    $status_meta = $order_status_options[$status_key] ?? [
                        'label' => (string) ($order['status'] ?? '未知状态'),
                        'badge_background' => '#e2e8f0',
                        'badge_color' => '#475569',
                    ];
                    ?>
                    <article style="border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                            <div>
                                <div style="font-size: 18px; font-weight: 700;">订单号 <?php echo shop_e((string) $order['order_no']); ?></div>
                                <div style="margin-top: 8px; color: #64748b;">下单时间：<?php echo shop_e(shop_short_datetime((string) $order['time'])); ?></div>
                                <div style="margin-top: 8px; color: #475569;">商品：<?php echo shop_e((string) $order['items_summary']); ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div style="display: inline-block; padding: 6px 12px; border-radius: 999px; background: <?php echo shop_e((string) $status_meta['badge_background']); ?>; color: <?php echo shop_e((string) $status_meta['badge_color']); ?>;"><?php echo shop_e((string) $status_meta['label']); ?></div>
                                <div style="margin-top: 10px; font-size: 22px; font-weight: 700; color: #dc2626;"><?php echo shop_format_price((float) $order['total']); ?></div>
                                <a href="index.php?page=order_detail&order_no=<?php echo urlencode((string) $order['order_no']); ?>" style="display: inline-block; margin-top: 10px; color: #2563eb; text-decoration: none;">查看详情</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
