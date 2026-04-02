<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

$pageTitle = '魔女小店 - 商品页';
$currentPage = 'products';
$showFooter = false;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$allProducts = shop_get_products();
$visibleProducts = shop_filter_products($allProducts, $keyword);
$groupedProducts = shop_group_products_by_category($visibleProducts, 'page_sort');
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
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['category_count']); ?></strong>
                    <span class="stat-label">分类数量</span>
                </div>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['page_priority_count']); ?></strong>
                    <span class="stat-label">商品页固定</span>
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
            当前关键词为「<?php echo shop_e($keyword); ?>」，共找到 <?php echo count($visibleProducts); ?> 件商品，商品页排序规则正在生效。
        </div>
    <?php endif; ?>

        <?php if (empty($groupedProducts)): ?>
            <div class="empty-state">
                <strong>没有找到商品</strong>
                <p>请先在后台添加商品，或者清空搜索关键词后再查看商品页。</p>
            </div>
        <?php else: ?>
            <?php foreach ($groupedProducts as $group): ?>
                <section class="category-section" style="--section-accent: <?php echo shop_e((string) ($group['accent'] ?? '#3b82f6')); ?>;">
                    <div class="category-header">
                        <div>
                            <h2 class="category-title"><?php echo shop_e((string) ($group['emoji'] ?? '🛍️') . ' ' . (string) ($group['name'] ?? '')); ?></h2>
                            <p class="category-desc"><?php echo shop_e((string) ($group['description'] ?? '')); ?></p>
                        </div>
                        <span class="category-count"><?php echo count($group['products'] ?? []); ?> 件</span>
                    </div>

                    <div class="category-track">
                        <?php foreach ($group['products'] as $product): ?>
                            <?php $categoryInfo = shop_get_category_info((string) ($product['category'] ?? '')); ?>
                            <article class="product-card product-card--compact" style="--card-accent: <?php echo shop_e((string) ($categoryInfo['accent'] ?? '#e2e8f0')); ?>; cursor: pointer;" onclick="window.location.href='index.php?page=product_detail&id=<?php echo $product['id']; ?>'">
                                <?php 
                                $displayImg = !empty($product['cover_image']) ? $product['cover_image'] : (!empty($product['images']) ? $product['images'][0] : '');
                                if ($displayImg): 
                                ?>
                                <div style="width: 100%; height: 140px; overflow: hidden; border-radius: 12px 12px 0 0;">
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
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
