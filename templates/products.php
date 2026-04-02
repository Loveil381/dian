<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

function shop_render_products_fragment(array $grouped_products): void
{
    if (empty($grouped_products)) {
        ?>
        <div class="empty-state">
            <strong>未找到相关商品</strong>
            <p>试试调整关键词，或者先看看正在热卖的商品。</p>
        </div>
        <?php
        return;
    }

    foreach ($grouped_products as $group) {
        ?>
        <section class="category-section" style="--section-accent: <?php echo shop_e((string) ($group['accent'] ?? '#3b82f6')); ?>;">
            <div class="category-header">
                <div>
                    <h2 class="category-title"><?php echo shop_e((string) ($group['emoji'] ?? '🪄') . ' ' . (string) ($group['name'] ?? '')); ?></h2>
                    <p class="category-desc"><?php echo shop_e((string) ($group['description'] ?? '')); ?></p>
                </div>
                <span class="category-count"><?php echo count($group['products'] ?? []); ?> 款</span>
            </div>

            <div class="category-track">
                <?php foreach ($group['products'] as $product): ?>
                    <?php $category_info = shop_get_category_info((string) ($product['category'] ?? '')); ?>
                    <article class="product-card product-card--compact" style="--card-accent: <?php echo shop_e((string) ($category_info['accent'] ?? '#e2e8f0')); ?>; cursor: pointer;" onclick="window.location.href='index.php?page=product_detail&id=<?php echo $product['id']; ?>'">
                        <?php
                        $display_img = !empty($product['cover_image']) ? $product['cover_image'] : (!empty($product['images']) ? $product['images'][0] : '');
                        if ($display_img):
                            ?>
                            <div style="width: 100%; height: 140px; overflow: hidden; border-radius: 12px 12px 0 0;">
                                <img src="<?php echo shop_e($display_img); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '')); ?>" style="width: 100%; height: 100%; object-fit: cover;">
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
                                    <div class="product-stock">库存：<?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件</div>
                                </div>

                                <span class="product-sales">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
    }
}

$pageTitle = '全部商品 - 魔女小店';
$currentPage = 'products';
$showFooter = false;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$is_ajax = isset($_GET['ajax']);
$allProducts = shop_get_products();
$visibleProducts = shop_filter_products($allProducts, $keyword);
$groupedProducts = shop_group_products_by_category($visibleProducts, 'page_sort');
$metrics = shop_product_dashboard_metrics($allProducts);

if ($is_ajax) {
    shop_render_products_fragment($groupedProducts);
    return;
}

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
                    <span class="stat-label">首页推荐</span>
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
            搜索关键词 “<?php echo shop_e($keyword); ?>”，共找到 <?php echo count($visibleProducts); ?> 款商品。你也可以继续修改关键词重新搜索。
        </div>
    <?php endif; ?>

    <?php shop_render_products_fragment($groupedProducts); ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>
