<?php declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../data/products.php';

$order_no = trim((string) ($_GET['order_no'] ?? ''));
if ($order_no === '') {
    shop_error_page(404, '订单不存在或已被移除。');
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
    default => (string) ($order['pay_method'] !== '' ? $order['pay_method'] : '未记录'),
};

$status_label = match ((string) ($order['status'] ?? '')) {
    'pending' => '待支付',
    'paid', '已支付，待发货' => '待发货',
    'shipped', '已发货' => '已发货',
    'completed', '已完成' => '已完成',
    'cancelled', '已取消' => '已取消',
    default => (string) ($order['status'] ?? '未知状态'),
};

$pageTitle = '订单详情';
$currentPage = 'orders';
include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <section style="max-width: 920px; margin: 0 auto; background: #ffffff; border-radius: 18px; padding: 24px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);">
        <div style="display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 22px;">
            <div>
                <div style="font-size: 13px; color: #64748b;">订单详情</div>
                <h1 style="margin: 8px 0 0; font-size: 30px;">订单号 <?php echo shop_e((string) $order['order_no']); ?></h1>
            </div>
            <div style="text-align: right;">
                <div style="display: inline-block; padding: 6px 12px; border-radius: 999px; background: #eff6ff; color: #2563eb;"><?php echo shop_e($status_label); ?></div>
                <div style="margin-top: 10px; font-size: 28px; font-weight: 700; color: #dc2626;"><?php echo shop_format_price((float) $order['total']); ?></div>
            </div>
        </div>

        <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 24px;">
            <div style="padding: 16px; border-radius: 14px; background: #f8fafc;">
                <div style="font-size: 13px; color: #64748b;">下单时间</div>
                <div style="margin-top: 8px; font-weight: 600;"><?php echo shop_e((string) $order['time']); ?></div>
            </div>
            <div style="padding: 16px; border-radius: 14px; background: #f8fafc;">
                <div style="font-size: 13px; color: #64748b;">支付方式</div>
                <div style="margin-top: 8px; font-weight: 600;"><?php echo shop_e($pay_method_label); ?></div>
            </div>
            <div style="padding: 16px; border-radius: 14px; background: #f8fafc;">
                <div style="font-size: 13px; color: #64748b;">收货人</div>
                <div style="margin-top: 8px; font-weight: 600;"><?php echo shop_e((string) ($order['customer'] !== '' ? $order['customer'] : '游客')); ?></div>
            </div>
        </div>

        <section style="margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 14px;">商品列表</h2>
            <div style="display: grid; gap: 12px;">
                <?php foreach ($order['items_data'] as $item): ?>
                    <div style="padding: 14px 16px; border: 1px solid #e5e7eb; border-radius: 14px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                        <div>
                            <div style="font-size: 16px; font-weight: 600;"><?php echo shop_e((string) $item['name']); ?></div>
                            <div style="margin-top: 6px; color: #64748b;">规格：<?php echo shop_e((string) ($item['sku_name'] !== '' ? $item['sku_name'] : '默认规格')); ?></div>
                            <div style="margin-top: 6px; color: #64748b;">商品 ID：<?php echo (int) $item['product_id']; ?></div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 16px; font-weight: 700; color: #dc2626;"><?php echo shop_format_price((float) $item['price']); ?></div>
                            <div style="margin-top: 6px; color: #475569;">数量：<?php echo (int) $item['quantity']; ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section style="margin-bottom: 24px;">
            <h2 style="font-size: 20px; margin: 0 0 14px;">收货信息</h2>
            <div style="padding: 16px; border-radius: 14px; background: #f8fafc; line-height: 1.8; color: #475569;">
                <div>收货人：<?php echo shop_e((string) ($order['customer'] !== '' ? $order['customer'] : '游客')); ?></div>
                <div>手机号：<?php echo shop_e((string) ($order['phone'] !== '' ? $order['phone'] : '未填写')); ?></div>
                <div>地址：<?php echo shop_e((string) ($order['address'] !== '' ? $order['address'] : '未填写')); ?></div>
            </div>
        </section>

        <div style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
            <a href="index.php?page=orders" style="display: inline-block; padding: 12px 18px; border-radius: 999px; background: #e2e8f0; color: #334155; text-decoration: none;">返回订单列表</a>
            <div style="font-size: 22px;">订单总价：<strong style="color: #dc2626;"><?php echo shop_format_price((float) $order['total']); ?></strong></div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
