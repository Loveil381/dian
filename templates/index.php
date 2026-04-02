<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

$pageTitle = '魔女小店 - 首页';
$pageDescription = '魔女小店首页，精选推荐热门商品与最新上架内容。';
$currentPage = 'home';
$showFooter = false;

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$all_products = shop_get_products();
$visible_products = shop_filter_products($all_products, $keyword);
$sorted_products = shop_sort_products_for_home($visible_products);
$metrics = shop_product_dashboard_metrics($all_products);

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
                    <strong class="stat-value"><?php echo shop_format_sales(count($sorted_products)); ?></strong>
                    <span class="stat-label">当前展示</span>
                </div>
                <div class="stat-card">
                    <strong class="stat-value"><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong>
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
            搜索关键词“<?php echo shop_e($keyword); ?>”，当前首页共展示 <?php echo count($sorted_products); ?> 款商品。
        </div>
    <?php endif; ?>

    <section class="page-section">
        <div class="section-heading">
            <div>
                <h2 class="section-title">首页推荐商品</h2>
                <p class="section-note">优先展示推荐商品，也会兼顾最新上架与销量表现。</p>
            </div>
            <span class="section-badge"><?php echo count($sorted_products); ?> 款</span>
        </div>

        <?php if (empty($sorted_products)): ?>
            <div class="empty-state">
                <strong>暂时还没有商品</strong>
                <p>可以先去后台添加商品，或者稍后再来看看。</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($sorted_products as $product): ?>
                    <?php
                    $category_info = shop_get_category_info((string) ($product['category'] ?? ''));
                    $display_img = !empty($product['cover_image']) ? $product['cover_image'] : (!empty($product['images']) ? $product['images'][0] : '');
                    ?>
                    <a class="product-card product-card-link" href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>" style="--card-accent: <?php echo shop_e((string) ($category_info['accent'] ?? '#e2e8f0')); ?>;">
                        <?php if ($display_img): ?>
                            <div class="product-card-image-wrap">
                                <img class="product-card-image" src="<?php echo shop_e($display_img); ?>" alt="<?php echo shop_e((string) ($product['name'] ?? '')); ?>">
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
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
