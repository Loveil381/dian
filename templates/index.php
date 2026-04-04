<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../data/products.php';

$pageTitle = '魔女小店 - 首页';
$currentPage = 'home';
$showFooter = false;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$homePage = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 20;

$metrics = ['count' => 0, 'category_count' => 0, 'sales' => 0, 'home_priority_count' => 0];
$sortedProducts = [];
$productTotal = 0;
$pagination = shop_paginate(0, $perPage, 1);
$homeCategories = array_slice(shop_get_categories(), 0, 4);
$homeCatIcons = ['spa', 'medication', 'face_retouching_natural', 'auto_awesome'];

$pdo = get_db_connection();
if ($pdo instanceof PDO) {
    $prefix = get_db_prefix();
    try {
        $metricsStmt = $pdo->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(sales), 0) AS total_sales, SUM(CASE WHEN home_sort > 0 THEN 1 ELSE 0 END) AS home_priority, COUNT(DISTINCT category) AS cat_count FROM `{$prefix}products` WHERE status = 'on_sale'");
        $m = $metricsStmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($m)) {
            $metrics = [
                'count' => (int) ($m['cnt'] ?? 0),
                'category_count' => (int) ($m['cat_count'] ?? 0),
                'sales' => (int) ($m['total_sales'] ?? 0),
                'home_priority_count' => (int) ($m['home_priority'] ?? 0),
            ];
        }

        $where_clauses = ["status = 'on_sale'"];
        $params = [];
        if ($keyword !== '') {
            $where_clauses[] = '(name LIKE ? OR description LIKE ? OR category LIKE ? OR tag LIKE ? OR sku LIKE ?)';
            $like = '%' . $keyword . '%';
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }
        $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}products`" . $where_sql);
        $countStmt->execute($params);
        $productTotal = (int) $countStmt->fetchColumn();
        $pagination = shop_paginate($productTotal, $perPage, $homePage);

        $order_sql = 'ORDER BY CASE WHEN home_sort > 0 THEN 0 ELSE 1 END ASC, CASE WHEN home_sort > 0 THEN home_sort ELSE 999999 END ASC, sales DESC, published_at DESC, id DESC';
        $sql = "SELECT * FROM `{$prefix}products`" . $where_sql . ' ' . $order_sql . ' LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $bindIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIndex, (int) $pagination['limit'], PDO::PARAM_INT);
        $stmt->bindValue($bindIndex + 1, (int) $pagination['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $sortedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        shop_log_exception('首页商品查询失败', $e);
    }
}

$paginationBaseUrl = 'index.php?page=home';
if ($keyword !== '') {
    $paginationBaseUrl .= '&keyword=' . urlencode($keyword);
}
$paginationBaseUrl .= '&p=';

include __DIR__ . '/header.php';
?>

<main class="page-shell home-shell">
    <section class="card-hero home-hero">
        <div class="home-hero-overlay"></div>
        <div class="home-hero-copy">
            <span class="home-hero-badge">店长推荐</span>
            <h1 class="home-hero-title">把喜欢的商品带回家</h1>
            <p class="home-hero-note">这里会优先展示首页排序靠前的商品，你也可以直接搜索关键字，快速找到想看的内容。</p>
        </div>

        <div class="home-metrics-grid">
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales((int) $metrics['count']); ?></strong>
                <span class="home-metric-label">商品总数</span>
            </div>
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales($productTotal); ?></strong>
                <span class="home-metric-label">当前展示</span>
            </div>
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong>
                <span class="home-metric-label">首页推荐</span>
            </div>
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales((int) $metrics['sales']); ?></strong>
                <span class="home-metric-label">累计销量</span>
            </div>
        </div>
    </section>

    <?php if (!empty($homeCategories)): ?>
    <nav class="home-quick-categories" aria-label="品类快捷入口">
        <?php foreach ($homeCategories as $i => $cat): ?>
            <a class="home-cat-item"
               href="index.php?page=products&category=<?php echo urlencode((string) ($cat['name'] ?? '')); ?>">
                <span class="home-cat-icon" style="background:<?php echo shop_e((string) ($cat['accent'] ?? '#cbd5e1')); ?>20;" aria-hidden="true"><?php echo shop_e((string) ($cat['emoji'] ?? '🛍️')); ?></span>
                <span class="home-cat-name"><?php echo shop_e((string) ($cat['name'] ?? '')); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($keyword !== ''): ?>
        <div class="home-search-result">
            <span class="badge badge-primary">搜索关键字：<?php echo shop_e($keyword); ?></span>
            <span class="home-search-result-text">共找到 <?php echo $productTotal; ?> 个商品。</span>
        </div>
    <?php endif; ?>

    <section class="home-section">
        <div class="home-section-heading">
            <div class="home-section-copy">
                <h2 class="home-section-title">首页商品推荐</h2>
                <p class="home-section-note">以下内容会根据首页排序优先展示，方便你快速浏览当前主推商品。</p>
            </div>
            <span class="badge badge-primary"><?php echo $productTotal; ?> 个</span>
        </div>

        <?php if (empty($sortedProducts)): ?>
            <div class="home-empty-state">
                <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                <strong class="home-empty-title">暂无商品</strong>
                <p class="home-empty-note text-muted">当前还没有上架商品，稍后再来看看吧。</p>
            </div>
        <?php else: ?>
            <div class="home-product-grid">
                <?php foreach ($sortedProducts as $product): ?>
                    <article class="card home-product-card">
                        <a class="home-product-link" href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>">
                            <?php
                            $displayImg = !empty($product['cover_image']) ? (string) $product['cover_image'] : (!empty($product['images']) && is_array($product['images']) ? (string) ($product['images'][0] ?? '') : '');
                            if ($displayImg !== ''):
                            ?>
                                <div class="home-product-cover">
                                    <img class="home-product-cover-image" src="<?php echo shop_e($displayImg); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="home-product-body">
                                <div class="home-product-meta-top">
                                    <span class="badge badge-primary"><?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></span>
                                </div>

                                <h3 class="home-product-title"><?php echo shop_e((string) ($product['name'] ?? '')); ?></h3>
                                <p class="home-product-date text-muted">上架时间：<?php echo shop_short_date((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></p>

                                <div class="home-product-meta">
                                    <div class="home-product-meta-group">
                                        <span class="text-price"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></span>
                                        <span class="text-muted">库存：<?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件</span>
                                    </div>

                                    <span class="text-muted">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php echo shop_render_pagination($pagination, $paginationBaseUrl); ?>
        <?php endif; ?>
    </section>

    <section class="home-pharmacist-card" aria-label="药师在线小课堂">
        <div class="home-pharmacist-copy">
            <h5 class="home-pharmacist-title">药师在线小课堂</h5>
            <p class="home-pharmacist-note">每一个细微的变化都值得被温柔对待。如果您对用药有任何疑问，请随时咨询我们的在线药师。</p>
        </div>
        <div class="home-pharmacist-avatar" aria-hidden="true">
            <span class="material-symbols-outlined">medical_services</span>
        </div>
    </section>
</main>

<a href="#" class="home-consult-fab" aria-label="在线咨询" title="联系药师">
    <span class="material-symbols-outlined" aria-hidden="true">chat_bubble</span>
</a>

<?php include __DIR__ . '/footer.php'; ?>
