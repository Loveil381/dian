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
    <section class="home-hero-wrap">
        <div class="home-hero-frame">
            <img class="home-hero-img" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAaqnbdArYaRRqXXEl77TSGrRkeTTYleFy5bHz7GzhyDE7lk2HWlQtPPj8-QbqIQyDz1iymA0mLqkLXXhPwZd04XBN4B1qsr56bS8hYfAJuoZCKHUlDjHp_GBVsqf3xkVtmYBm8u65QeA0RXmurJ-YgPAoJX4w9O7ZVZuOlZioAQRlOWo3VtCUMM3USo4Hbcy5F2MFGmZ5TXLthRdZgVVTh3Z7STH35N1T9K0k6gufNenWwXqKxZBH2vRuf07s5AmZmQDjm2lEVgrs" alt="Hero Banner">
            <div class="home-hero-mask">
                <div class="home-hero-text">
                    <span class="home-hero-tag">店长推荐</span>
                    <h2 class="home-hero-headline">温暖身心的<br>魔法处方</h2>
                </div>
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
        <div class="home-section-heading home-section-heading--updated">
            <h3 class="home-section-title-updated">
                <span class="home-section-title-main">热门商品</span>
                <span class="home-section-title-sub">✨ 精选好物</span>
            </h3>
            <a href="index.php?page=products" class="home-section-view-all">查看全部</a>
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
                    <article class="home-card-v2">
                        <a class="home-card-v2-link" href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>">
                            <div class="home-card-v2-media">
                                <?php
                                $displayImg = !empty($product['cover_image']) ? (string) $product['cover_image'] : (!empty($product['images']) && is_array($product['images']) ? (string) ($product['images'][0] ?? '') : '');
                                if ($displayImg !== ''):
                                ?>
                                    <img class="home-card-v2-img" src="<?php echo shop_e($displayImg); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
                                <?php else: ?>
                                    <div class="home-card-v2-img-placeholder"></div>
                                <?php endif; ?>
                                <button class="home-card-v2-fav-btn" aria-label="收藏" onclick="event.preventDefault();">
                                    <span class="material-symbols-outlined" aria-hidden="true" style="font-variation-settings: 'FILL' 1;">favorite</span>
                                </button>
                            </div>
                            <div class="home-card-v2-body">
                                <h4 class="home-card-v2-title" title="<?php echo shop_e((string) ($product['name'] ?? '')); ?>"><?php echo shop_e((string) ($product['name'] ?? '')); ?></h4>
                                <p class="home-card-v2-cat"><?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></p>
                                <div class="home-card-v2-footer">
                                    <span class="home-card-v2-price"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></span>
                                    <button class="home-card-v2-cart-btn" aria-label="加入购物车" onclick="event.preventDefault();">
                                        <span class="material-symbols-outlined" aria-hidden="true">add_shopping_cart</span>
                                    </button>
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
