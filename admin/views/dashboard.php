<?php declare(strict_types=1); ?>
<?php
/* 向后兼容旧数据加载器变量 */
$metrics             = $metrics             ?? ['count' => 0, 'category_count' => 0, 'sales' => 0, 'home_priority_count' => 0];
$storageState        = $storageState        ?? '未知';
$todayRevenue        = $todayRevenue        ?? 0.0;
$todayOrders         = $todayOrders         ?? 0;
$weekRevenue         = $weekRevenue         ?? 0.0;
$monthRevenue        = $monthRevenue        ?? 0.0;
$weekTrend           = $weekTrend           ?? [];
$topProducts         = $topProducts         ?? [];
$statusDistribution  = $statusDistribution  ?? [];
?>

<!-- ══ Section 1: KPI 卡片 ══ -->
<section class="admin-dash-kpi-grid">

    <!-- 今日营收 -->
    <div class="admin-dash-kpi-card">
        <div class="admin-dash-kpi-icon admin-dash-kpi-icon--primary">
            <span class="material-symbols-outlined">payments</span>
        </div>
        <div class="admin-dash-kpi-body">
            <span class="admin-dash-kpi-label">今日营收</span>
            <strong class="admin-dash-kpi-value"><?php echo shop_format_price($todayRevenue); ?></strong>
        </div>
    </div>

    <!-- 今日订单 -->
    <div class="admin-dash-kpi-card">
        <div class="admin-dash-kpi-icon admin-dash-kpi-icon--secondary">
            <span class="material-symbols-outlined">shopping_cart</span>
        </div>
        <div class="admin-dash-kpi-body">
            <span class="admin-dash-kpi-label">今日订单</span>
            <strong class="admin-dash-kpi-value"><?php echo shop_format_sales($todayOrders); ?></strong>
        </div>
    </div>

    <!-- 近 7 日营收 -->
    <div class="admin-dash-kpi-card">
        <div class="admin-dash-kpi-icon admin-dash-kpi-icon--success">
            <span class="material-symbols-outlined">trending_up</span>
        </div>
        <div class="admin-dash-kpi-body">
            <span class="admin-dash-kpi-label">近 7 日营收</span>
            <strong class="admin-dash-kpi-value"><?php echo shop_format_price($weekRevenue); ?></strong>
        </div>
    </div>

    <!-- 近 30 日营收 -->
    <div class="admin-dash-kpi-card">
        <div class="admin-dash-kpi-icon admin-dash-kpi-icon--info">
            <span class="material-symbols-outlined">calendar_month</span>
        </div>
        <div class="admin-dash-kpi-body">
            <span class="admin-dash-kpi-label">近 30 日营收</span>
            <strong class="admin-dash-kpi-value"><?php echo shop_format_price($monthRevenue); ?></strong>
        </div>
    </div>

</section>

<!-- ══ Section 2: 7 日趋势柱状图 ══ -->
<section class="card">
    <h3 class="admin-dash-section-title">
        <span class="material-symbols-outlined">show_chart</span>
        近 7 日营收趋势
    </h3>
    <?php if (empty($weekTrend)): ?>
        <p class="admin-dash-empty">暂无订单数据</p>
    <?php else: ?>
        <?php $maxRev = max(1, max(array_column($weekTrend, 'revenue'))); ?>
        <div class="admin-dash-chart">
            <?php foreach ($weekTrend as $day): ?>
                <?php $pct = round((float) $day['revenue'] / $maxRev * 100); ?>
                <div class="admin-dash-chart-col">
                    <span class="admin-dash-chart-val"><?php echo shop_format_price((float) $day['revenue']); ?></span>
                    <div class="admin-dash-chart-bar" style="height:<?php echo max(4, $pct); ?>%"></div>
                    <span class="admin-dash-chart-label"><?php echo shop_e(date('m/d', strtotime((string) $day['date']))); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- ══ Section 3: 热销排行 + 订单状态（60/40 双列）══ -->
<div class="admin-dash-two-col">

    <!-- 左：热销排行表格 -->
    <div class="card">
        <h3 class="admin-dash-section-title">
            <span class="material-symbols-outlined">emoji_events</span>
            热销排行
        </h3>
        <?php if (empty($topProducts)): ?>
            <p class="admin-dash-empty">暂无销售数据</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>排名</th>
                        <th>商品名称</th>
                        <th>销量</th>
                        <th>营收</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topProducts as $i => $prod): ?>
                    <?php $rank = $i + 1; ?>
                    <tr>
                        <td>
                            <?php if ($rank <= 3): ?>
                                <span class="admin-dash-rank admin-dash-rank--<?php echo $rank; ?>"><?php echo $rank; ?></span>
                            <?php else: ?>
                                <span class="admin-dash-rank"><?php echo $rank; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo shop_e((string) $prod['name']); ?></td>
                        <td><?php echo shop_format_sales((int) $prod['sales']); ?></td>
                        <td><strong style="color:var(--color-primary)"><?php echo shop_format_price((float) $prod['price'] * (int) $prod['sales']); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- 右：订单状态堆叠 -->
    <div class="admin-dash-status-stack">
        <h3 class="admin-dash-section-title">
            <span class="material-symbols-outlined">donut_large</span>
            订单状态
        </h3>
        <?php
        /* 状态图标和颜色映射 */
        $statusIcons = [
            'pending'   => 'schedule',
            'paid'      => 'check_circle',
            'shipped'   => 'local_shipping',
            'completed' => 'done_all',
            'cancelled' => 'cancel',
        ];
        $statusColors = [
            'pending'   => 'var(--color-warning)',
            'paid'      => 'var(--color-info)',
            'shipped'   => 'var(--color-success)',
            'completed' => 'var(--color-primary)',
            'cancelled' => 'var(--color-error)',
        ];

        /* 将 $statusDistribution 整理为 key => count 字典 */
        $statusCounts = [];
        foreach ($statusDistribution as $sd) {
            $statusCounts[(string) $sd['status']] = (int) $sd['count'];
        }

        foreach (shop_order_status_options() as $key => $opt):
            $count = $statusCounts[$key] ?? 0;
            $icon  = $statusIcons[$key]  ?? 'info';
            $color = $statusColors[$key] ?? 'var(--color-outline)';
        ?>
            <div class="admin-dash-status-card" style="--status-color:<?php echo $color; ?>">
                <div class="admin-dash-status-left">
                    <span class="material-symbols-outlined" style="color:var(--status-color)"><?php echo $icon; ?></span>
                    <span><?php echo shop_e((string) $opt['label']); ?></span>
                </div>
                <strong class="admin-dash-status-count"><?php echo $count; ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- ══ Section 4: 系统状态底栏 ══ -->
<footer class="admin-dash-footer">
    <div class="admin-dash-footer-item">
        <span class="material-symbols-outlined">database</span>
        数据库:
        <span class="admin-dash-dot admin-dash-dot--<?php echo $storageState === '已连接' ? 'ok' : 'err'; ?>"></span>
        <?php echo shop_e($storageState); ?>
    </div>
    <div class="admin-dash-footer-item">
        <span class="material-symbols-outlined">inventory_2</span>
        商品总数:
        <strong style="color:var(--color-on-surface)"><?php echo shop_format_sales((int) $metrics['count']); ?></strong>
    </div>
    <div class="admin-dash-footer-item">
        <span class="material-symbols-outlined">monitoring</span>
        累计销量:
        <strong style="color:var(--color-on-surface)"><?php echo shop_format_sales((int) $metrics['sales']); ?></strong>
    </div>
</footer>
