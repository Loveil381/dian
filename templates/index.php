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

<main class="page-shell">
    <section class="page-hero">
        <div class="hero-panel">
            <div class="hero-stats">
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['count']); ?></strong>
                    <span class="stat-label">商品总数</span>
                </div>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales(count($sortedProducts)); ?></strong>
                    <span class="stat-label">当前展示</span>
                </div>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong>
                    <span class="stat-label">首页固定</span>
                </div>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['sales']); ?></strong>
                    <span class="stat-label">累计销量</span>
                </div>
            </div>
        </div>
    </section>

    <?php if ($keyword !== ''): ?>
        <div class="filter-bar">
            当前关键词为「<?php echo shop_e($keyword); ?>」，共找到 <?php echo count($sortedProducts); ?> 件商品，首页排序规则仍然优先。
        </div>
    <?php endif; ?>

    <section class="page-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">首页商品列表</h2>
                <p class="section-note">固定排序优先，其余商品按销量排列。</p>
            </div>
            <span class="section-badge"><?php echo count($sortedProducts); ?> 件</span>
        </div>

        <?php if (empty($sortedProducts)): ?>
            <div class="empty-state">
                <strong>没有找到商品</strong>
                <p>请先在后台添加商品，或者清空关键词筛选后再查看首页。</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($sortedProducts as $product): ?>
                    <?php $categoryInfo = shop_get_category_info((string) ($product['category'] ?? '')); ?>
                    <article class="product-card" style="--card-accent: <?php echo shop_e((string) ($categoryInfo['accent'] ?? '#e2e8f0')); ?>; cursor: pointer;" onclick="window.location.href='index.php?page=product_detail&id=<?php echo $product['id']; ?>'">
                        <?php 
                        $displayImg = !empty($product['cover_image']) ? $product['cover_image'] : (!empty($product['images']) ? $product['images'][0] : '');
                        if ($displayImg): 
                        ?>
                        <div style="width: 100%; height: 180px; overflow: hidden; border-radius: 12px 12px 0 0;">
                            <img src="<?php echo shop_e($displayImg); ?>" alt="<?php echo shop_e($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <?php endif; ?>
                        <div class="product-body">
                            <div class="product-title-row">
                                <h3 class="product-title"><?php echo shop_e((string) ($product['name'] ?? '')); ?></h3>
                            </div>

                            <p class="product-subtitle"><?php echo shop_e((string) ($product['category'] ?? '')); ?> · 上新 <?php echo shop_short_date((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></p>

                            <div class="product-meta">
                                <div>
                                    <div class="product-price"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                    <div class="product-stock">库存 <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件</div>
                                </div>

                                <span class="product-sales">销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
