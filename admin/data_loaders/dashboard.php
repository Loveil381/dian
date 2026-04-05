<?php
declare(strict_types=1);

/**
 * Dashboard tab 数据加载。
 * 依赖父作用域：$products, $pdo
 * 设置变量：
 *   $metrics, $homePreview, $pagePreview, $storageState, $fileState（向后兼容）
 *   $todayRevenue, $todayOrders, $weekRevenue, $monthRevenue（销售 KPI）
 *   $weekTrend（7 日趋势数组）, $topProducts（热销排行），$statusDistribution（订单状态分布）
 */

/* ── 旧有变量（保持向后兼容）── */
$metrics      = shop_product_dashboard_metrics($products);
$homePreview  = array_slice(shop_sort_products_for_home($products), 0, 6);
$pagePreview  = array_slice(shop_sort_products_for_page($products), 0, 6);
$storageState = $pdo !== null ? '已连接' : '连接失败';
$fileState    = '基于数据库';

/* ── 销售 KPI：从订单表聚合 ── */
$todayRevenue       = 0.0;
$todayOrders        = 0;
$weekRevenue        = 0.0;
$monthRevenue       = 0.0;
$weekTrend          = [];
$topProducts        = [];
$statusDistribution = [];

if ($pdo !== null) {
    $prefix = get_db_prefix();

    try {
        /* 今日营收与订单量（仅统计已支付/已发货/已完成） */
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS rev
             FROM `{$prefix}orders`
             WHERE DATE(created_at) = CURDATE()
               AND status IN ('paid', 'shipped', 'completed')"
        );
        $stmt->execute();
        $row          = $stmt->fetch(PDO::FETCH_ASSOC);
        $todayOrders  = (int)   ($row['cnt'] ?? 0);
        $todayRevenue = (float) ($row['rev'] ?? 0);

        /* 近 7 日营收 */
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) AS rev
             FROM `{$prefix}orders`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               AND status IN ('paid', 'shipped', 'completed')"
        );
        $stmt->execute();
        $weekRevenue = (float) ($stmt->fetchColumn() ?? 0);

        /* 近 30 日营收 */
        $stmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total), 0) AS rev
             FROM `{$prefix}orders`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
               AND status IN ('paid', 'shipped', 'completed')"
        );
        $stmt->execute();
        $monthRevenue = (float) ($stmt->fetchColumn() ?? 0);

        /* 近 7 日逐日趋势（含无订单日填 0）*/
        $stmt = $pdo->prepare(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS orders,
                    COALESCE(SUM(total), 0) AS revenue
             FROM `{$prefix}orders`
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               AND status IN ('paid', 'shipped', 'completed')
             GROUP BY day
             ORDER BY day ASC"
        );
        $stmt->execute();
        $trendRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* 构建以日期字符串为键的字典，再按 7 天顺序补全 */
        $trendByDay = [];
        foreach ($trendRows as $r) {
            $trendByDay[(string) $r['day']] = [
                'revenue' => (float) $r['revenue'],
                'orders'  => (int)   $r['orders'],
            ];
        }
        for ($i = 6; $i >= 0; $i--) {
            $dateKey = date('Y-m-d', strtotime("-{$i} days"));
            $weekTrend[] = [
                'date'    => $dateKey,
                'revenue' => $trendByDay[$dateKey]['revenue'] ?? 0.0,
                'orders'  => $trendByDay[$dateKey]['orders']  ?? 0,
            ];
        }

        /* 热销排行（按销量降序，取前 10）*/
        $stmt = $pdo->prepare(
            "SELECT name, sales, price
             FROM `{$prefix}products`
             WHERE status = 'on_sale' AND sales > 0
             ORDER BY sales DESC
             LIMIT 10"
        );
        $stmt->execute();
        $topProducts = array_map(static function (array $row): array {
            return [
                'name'  => (string) $row['name'],
                'sales' => (int)    $row['sales'],
                'price' => (float)  $row['price'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

        /* 订单状态分布 */
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) AS cnt
             FROM `{$prefix}orders`
             GROUP BY status"
        );
        $stmt->execute();
        $statusDistribution = array_map(static function (array $row): array {
            return [
                'status' => (string) $row['status'],
                'count'  => (int)    $row['cnt'],
            ];
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    } catch (PDOException $e) {
        shop_log_exception('Dashboard 数据加载失败', $e);
    }
}
