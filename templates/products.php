<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../data/products.php';

$currentPage = 'products';
$pageTitle = '全部商品';
$pageDescription = '浏览魔女小店的全部上架商品，支持关键字和分类筛选。';

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$selected_category = trim((string) ($_GET['category'] ?? ''));
$is_ajax = isset($_GET['ajax']);
$categories = [];
$products = [];

$pdo = get_db_connection();
if ($pdo instanceof PDO) {
    $prefix = get_db_prefix();

    try {
        $category_stmt = $pdo->query("SELECT id, name FROM `{$prefix}categories` ORDER BY sort ASC, id DESC");
        $categories = $category_stmt ? $category_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        $where_clauses = ["status = 'on_sale'"];
        $params = [];

        if ($selected_category !== '') {
            $where_clauses[] = 'category = ?';
            $params[] = $selected_category;
        }

        if ($keyword !== '') {
            $where_clauses[] = '(name LIKE ? OR description LIKE ?)';
            $like_keyword = '%' . $keyword . '%';
            $params[] = $like_keyword;
            $params[] = $like_keyword;
        }

        $sql = "SELECT * FROM `{$prefix}products`";
        if ($where_clauses !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where_clauses);
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        shop_log('error', '商品筛选查询失败', ['message' => $exception->getMessage()]);
        $categories = [];
        $products = [];
    }
}

ob_start();
?>
<main class="page-shell products-page">
    <section class="card products-hero">
        <div class="products-hero-copy">
            <span class="badge badge-primary">Product Gallery</span>
            <h1 class="products-title">全部商品</h1>
            <p class="products-subtitle">浏览当前所有上架商品，也可以按关键字或分类快速筛选。</p>
        </div>
        <div class="products-hero-stats">
            <div class="products-stat card">
                <span class="products-stat-label">当前结果</span>
                <strong class="products-stat-value"><?php echo shop_e((string) count($products)); ?></strong>
            </div>
            <div class="products-stat card">
                <span class="products-stat-label">分类数量</span>
                <strong class="products-stat-value"><?php echo shop_e((string) count($categories)); ?></strong>
            </div>
        </div>
    </section>

    <section class="card products-filter-panel">
        <div class="products-filter-head">
            <div>
                <h2 class="products-section-title">筛选商品</h2>
                <p class="products-section-note">输入关键字或切换分类，帮助你更快找到想看的商品。</p>
            </div>
            <div class="products-filter-tags">
                <span class="badge <?php echo $selected_category !== '' ? 'badge-primary' : ''; ?>"><?php echo $selected_category !== '' ? shop_e($selected_category) : '全部分类'; ?></span>
                <span class="badge"><?php echo $keyword !== '' ? '关键字：' . shop_e($keyword) : '未输入关键字'; ?></span>
            </div>
        </div>

        <form method="get" action="index.php" class="products-filter-form">
            <input type="hidden" name="page" value="products">

            <div class="products-filter-field">
                <label class="font-label products-filter-label" for="productKeyword">关键字</label>
                <div class="products-filter-control">
                    <span class="material-symbols-outlined products-filter-icon" aria-hidden="true">search</span>
                    <input class="input products-filter-input" id="productKeyword" type="text" name="keyword" value="<?php echo shop_e($keyword); ?>" placeholder="搜索商品名称或描述">
                </div>
            </div>

            <div class="products-filter-field">
                <label class="font-label products-filter-label" for="productCategory">分类</label>
                <div class="products-filter-control products-filter-control--select">
                    <span class="material-symbols-outlined products-filter-icon" aria-hidden="true">tune</span>
                    <select class="input products-filter-select" id="productCategory" name="category">
                        <option value="">全部分类</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo shop_e((string) ($category['name'] ?? '')); ?>" <?php echo $selected_category === (string) ($category['name'] ?? '') ? 'selected' : ''; ?>>
                                <?php echo shop_e((string) ($category['name'] ?? '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="products-filter-actions">
                <button type="submit" class="btn-primary">应用筛选</button>
                <a href="index.php?page=products" class="btn-ghost">清空条件</a>
            </div>
        </form>
    </section>

    <section class="products-results">
        <div class="products-results-head">
            <div>
                <h2 class="products-section-title">商品列表</h2>
                <p class="products-section-note">共找到 <?php echo shop_e((string) count($products)); ?> 个可购买商品。</p>
            </div>
        </div>

        <?php if ($products === []): ?>
            <div class="card products-empty empty-state">
                <span class="material-symbols-outlined products-empty-icon" aria-hidden="true">inventory_2</span>
                <strong>暂无符合条件的商品</strong>
                <p>可以尝试修改关键字，或者清空分类条件后重新查看。</p>
            </div>
        <?php else: ?>
            <div class="product-grid products-catalog-grid" id="productsGrid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $product_id = (int) ($product['id'] ?? 0);
                    $product_name = (string) ($product['name'] ?? '');
                    $cover_image = (string) ($product['cover_image'] ?? '');
                    $product_price = (float) ($product['price'] ?? 0);
                    $product_sales = (int) ($product['sales'] ?? 0);
                    $detail_url = 'index.php?page=product_detail&id=' . $product_id;
                    $product_category = (string) ($product['category'] ?? '未分类');
                    ?>
                    <article class="card product-card products-card">
                        <a class="product-card-link products-card-link" href="<?php echo shop_e($detail_url); ?>">
                            <div class="products-card-media">
                                <?php if ($cover_image !== ''): ?>
                                    <img class="products-card-image" src="<?php echo shop_e($cover_image); ?>" alt="<?php echo shop_e($product_name); ?>">
                                <?php else: ?>
                                    <div class="products-card-placeholder">
                                        <span class="material-symbols-outlined" aria-hidden="true">photo</span>
                                        <span>暂无图片</span>
                                    </div>
                                <?php endif; ?>
                                <span class="badge badge-primary products-card-badge"><?php echo shop_e($product_category); ?></span>
                            </div>
                            <div class="product-body products-card-body">
                                <div class="products-card-title-row">
                                    <h3 class="product-title products-card-title"><?php echo shop_e($product_name); ?></h3>
                                </div>
                                <p class="products-card-note">点击查看商品详情、规格、价格与下单入口。</p>
                                <div class="product-meta products-card-meta">
                                    <div>
                                        <div class="product-price"><?php echo shop_format_price($product_price); ?></div>
                                        <div class="product-sales"><?php echo shop_format_sales($product_sales); ?></div>
                                    </div>
                                    <span class="products-card-arrow material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                                </div>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php
$products_content = trim((string) ob_get_clean());

if ($is_ajax) {
    echo $products_content;
    return;
}

include __DIR__ . '/header.php';
echo $products_content;
include __DIR__ . '/footer.php';
