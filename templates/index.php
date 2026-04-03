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

include __DIR__ . '/header.php';
?>

<style>
    /* 首页局部布局 */
    .home-shell {
        display: grid;
        gap: var(--space-xl);
    }

    .home-hero {
        display: grid;
        gap: var(--space-lg);
        padding: var(--space-xl);
        background: var(--color-surface-container-low);
    }

    .home-hero-copy {
        display: grid;
        gap: var(--space-sm);
    }

    .home-hero-title {
        margin: 0;
        color: var(--color-on-surface);
        font-family: var(--font-headline);
        font-size: clamp(var(--text-h1), 4vw, calc(var(--text-display) + 0.375rem));
        line-height: 1.2;
    }

    .home-hero-note {
        margin: 0;
        color: var(--color-on-surface-variant);
        font-size: var(--text-body);
        line-height: 1.8;
    }

    .home-metrics-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--space-md);
    }

    .home-metric-card {
        min-height: 9rem;
        background: var(--color-surface-container-lowest);
    }

    .home-metric-label {
        color: var(--color-on-surface-variant);
        font-size: var(--text-caption);
        font-weight: 600;
    }

    .home-search-result {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }

    .home-search-result-text {
        color: var(--color-on-surface-variant);
        font-size: var(--text-body);
    }

    .home-section {
        display: grid;
        gap: var(--space-lg);
    }

    .home-section-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: var(--space-md);
    }

    .home-section-copy {
        display: grid;
        gap: var(--space-xs);
    }

    .home-section-title {
        margin: 0;
        color: var(--color-on-surface);
        font-family: var(--font-headline);
        font-size: var(--text-h1);
        line-height: 1.25;
    }

    .home-section-note {
        margin: 0;
        color: var(--color-on-surface-variant);
        font-size: var(--text-body);
        line-height: 1.7;
    }

    .home-product-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--space-md);
    }

    .home-product-card {
        overflow: hidden;
    }

    .home-product-link {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        color: inherit;
        text-decoration: none;
    }

    .home-product-cover {
        overflow: hidden;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        background: var(--color-surface-container-low);
        aspect-ratio: 4 / 3.2;
    }

    .home-product-cover-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform var(--transition-slow);
    }

    .home-product-card:hover .home-product-cover-image {
        transform: scale(1.04);
    }

    .home-product-body {
        display: grid;
        gap: var(--space-sm);
        padding: var(--space-md);
    }

    .home-product-meta-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }

    .home-product-title {
        margin: 0;
        color: var(--color-on-surface);
        font-family: var(--font-headline);
        font-size: var(--text-h2);
        line-height: 1.35;
    }

    .home-product-date {
        font-size: var(--text-caption);
        line-height: 1.6;
    }

    .home-product-meta {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: var(--space-md);
    }

    .home-product-meta-group {
        display: grid;
        gap: var(--space-xs);
    }

    .home-empty-state {
        display: grid;
        place-items: center;
        gap: var(--space-sm);
        padding: var(--space-2xl) var(--space-lg);
        border-radius: var(--radius-lg);
        background: var(--color-surface-container-low);
        text-align: center;
    }

    .home-empty-state .material-symbols-outlined {
        font-size: calc(var(--space-2xl) + var(--space-lg));
        color: var(--color-outline);
    }

    .home-empty-title {
        color: var(--color-on-surface);
        font-family: var(--font-headline);
        font-size: var(--text-h2);
        font-weight: 700;
    }

    .home-empty-note {
        max-width: 28rem;
        font-size: var(--text-body);
        line-height: 1.7;
    }

    @media (min-width: 900px) {
        .home-metrics-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .home-product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 1280px) {
        .home-product-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .home-hero {
            padding: var(--space-lg);
        }

        .home-section-heading,
        .home-product-meta {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>

<main class="page-shell home-shell">
    <section class="card-hero home-hero">
        <div class="home-hero-copy">
            <span class="badge badge-primary">首页概览</span>
            <h1 class="home-hero-title">轻盈选品，安心下单</h1>
            <p class="home-hero-note">按首页排序与销量优先展示，帮助顾客更快找到热门商品，也让搜索结果更清晰地呈现在眼前。</p>
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
                <span class="home-metric-label">总销量</span>
            </div>
        </div>
    </section>

    <?php if ($keyword !== ''): ?>
        <div class="home-search-result">
            <span class="badge badge-primary">搜索关键词：<?php echo shop_e($keyword); ?></span>
            <span class="home-search-result-text">共找到 <?php echo count($sortedProducts); ?> 件商品。</span>
        </div>
    <?php endif; ?>

    <section class="home-section">
        <div class="home-section-heading">
            <div class="home-section-copy">
                <h2 class="home-section-title">首页商品推荐</h2>
                <p class="home-section-note">按首页排序与销量优先展示，帮助顾客更快找到热门商品。</p>
            </div>
            <span class="badge badge-primary"><?php echo count($sortedProducts); ?> 件</span>
        </div>

        <?php if (empty($sortedProducts)): ?>
            <div class="home-empty-state">
                <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                <strong class="home-empty-title">暂无商品</strong>
                <p class="home-empty-note text-muted">商家正在上架中，请稍后再来。</p>
            </div>
        <?php else: ?>
            <div class="home-product-grid">
                <?php foreach ($sortedProducts as $product): ?>
                    <?php $categoryInfo = shop_get_category_info((string) ($product['category'] ?? '')); ?>
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
                                <p class="home-product-date text-muted">上架于 <?php echo shop_short_date((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></p>

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
