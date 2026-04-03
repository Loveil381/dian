<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

$pageTitle = '魔女小店 - 首页';
$currentPage = 'home';
$showFooter = false;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$allProducts = shop_get_products();
$visibleProducts = shop_filter_products($allProducts, $keyword);
$sortedProducts = shop_sort_products_for_home($visibleProducts);
$metrics = shop_product_dashboard_metrics($allProducts);
$homeCategories = array_slice(shop_get_categories(), 0, 4);
$homeCatIcons = ['spa', 'medication', 'face_retouching_natural', 'auto_awesome'];

include __DIR__ . '/header.php';
?>

<main class="page-shell home-shell">
    <section class="card-hero home-hero">
        <div class="home-hero-copy">
            <span class="badge badge-primary">首页精选</span>
            <h1 class="home-hero-title">把喜欢的商品带回家</h1>
            <p class="home-hero-note">这里会优先展示首页排序靠前的商品，你也可以直接搜索关键字，快速找到想看的内容。</p>
        </div>

        <div class="home-metrics-grid">
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales((int) $metrics['count']); ?></strong>
                <span class="home-metric-label">商品总数</span>
            </div>
            <div class="card-metric home-metric-card">
                <strong class="metric-value"><?php echo shop_format_sales(count($sortedProducts)); ?></strong>
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
            <a class="home-quick-cat-item"
               href="index.php?page=products&category=<?php echo urlencode((string) ($cat['name'] ?? '')); ?>">
                <span class="home-quick-cat-icon home-quick-cat-icon--<?php echo $i % 4; ?> material-symbols-outlined" aria-hidden="true"><?php echo shop_e($homeCatIcons[$i] ?? 'category'); ?></span>
                <span class="home-quick-cat-label"><?php echo shop_e((string) ($cat['name'] ?? '')); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($keyword !== ''): ?>
        <div class="home-search-result">
            <span class="badge badge-primary">搜索关键字：<?php echo shop_e($keyword); ?></span>
            <span class="home-search-result-text">共找到 <?php echo count($sortedProducts); ?> 个商品。</span>
        </div>
    <?php endif; ?>

    <section class="home-section">
        <div class="home-section-heading">
            <div class="home-section-copy">
                <h2 class="home-section-title">首页商品推荐</h2>
                <p class="home-section-note">以下内容会根据首页排序优先展示，方便你快速浏览当前主推商品。</p>
            </div>
            <span class="badge badge-primary"><?php echo count($sortedProducts); ?> 个</span>
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
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
