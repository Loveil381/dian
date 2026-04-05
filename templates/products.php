<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../data/products.php';

$currentPage = 'products';
$pageTitle = '全部商品';
$pageDescription = '浏览魔女小店的全部上架商品，支持关键字和分类筛选。';

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$selected_category = trim((string) ($_GET['category'] ?? ''));
$is_ajax = isset($_GET['ajax']);
$productsPage = max(1, (int) ($_GET['p'] ?? 1));
$perPage = (int) shop_get_setting('items_per_page', '20');
$categories = [];
$products = [];
$productTotal = 0;
$pagination = shop_paginate(0, $perPage, 1);

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

        $where_sql = ' WHERE ' . implode(' AND ', $where_clauses);

        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}products`" . $where_sql);
        $count_stmt->execute($params);
        $productTotal = (int) $count_stmt->fetchColumn();
        $pagination = shop_paginate($productTotal, $perPage, $productsPage);

        $sql = "SELECT * FROM `{$prefix}products`" . $where_sql . ' ORDER BY id DESC LIMIT ? OFFSET ?';
        $stmt = $pdo->prepare($sql);
        $bindIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($bindIndex, (int) $pagination['limit'], PDO::PARAM_INT);
        $stmt->bindValue($bindIndex + 1, (int) $pagination['offset'], PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        shop_log('error', '商品筛选查询失败', ['message' => $exception->getMessage()]);
        $categories = [];
        $products = [];
    }
}

$paginationBaseUrl = 'index.php?page=products';
if ($selected_category !== '') {
    $paginationBaseUrl .= '&category=' . urlencode($selected_category);
}
if ($keyword !== '') {
    $paginationBaseUrl .= '&keyword=' . urlencode($keyword);
}
$paginationBaseUrl .= '&p=';

ob_start();
?>
<main class="page-shell products-page">
    <section class="card products-hero">
        <div class="products-hero-copy">
            <span class="badge badge-primary">好物精选</span>
            <h1 class="products-title">全部商品</h1>
            <p class="products-subtitle">寻找属于你的蜕变良方 ✨</p>
        </div>
    </section>

    <?php if (!empty($categories)): ?>
    <nav class="products-cat-pills" aria-label="分类筛选">
        <a class="products-cat-pill <?php echo $selected_category === '' ? 'products-cat-pill--active' : ''; ?>"
           href="index.php?page=products<?php echo $keyword !== '' ? '&keyword=' . urlencode($keyword) : ''; ?>">全部</a>
        <?php foreach ($categories as $category): ?>
            <?php $cat_name = (string) ($category['name'] ?? ''); ?>
            <a class="products-cat-pill <?php echo $selected_category === $cat_name ? 'products-cat-pill--active' : ''; ?>"
               href="index.php?page=products&category=<?php echo urlencode($cat_name); ?><?php echo $keyword !== '' ? '&keyword=' . urlencode($keyword) : ''; ?>">
                <?php echo shop_e($cat_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <section class="products-results">
        <div class="products-results-head">
            <div>
                <h2 class="products-section-title">商品列表</h2>
                <p class="products-section-note">共找到 <?php echo shop_e((string) $productTotal); ?> 份专属好物。</p>
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
                    <article class="products-card-v2">
                        <a class="products-card-v2-link" href="<?php echo shop_e($detail_url); ?>">
                            <div class="products-card-v2-media">
                                <?php if ($cover_image !== ''): ?>
                                    <img class="products-card-v2-img" src="<?php echo shop_e($cover_image); ?>" alt="<?php echo shop_e($product_name); ?>">
                                <?php else: ?>
                                    <div class="products-card-v2-placeholder">
                                        <span class="material-symbols-outlined" aria-hidden="true">photo</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h3 class="products-card-v2-title" title="<?php echo shop_e($product_name); ?>"><?php echo shop_e($product_name); ?></h3>
                            <div class="products-card-v2-footer">
                                <span class="products-card-v2-price"><?php echo shop_format_price($product_price); ?></span>
                                <button class="products-card-v2-cart-btn" aria-label="查看并购买">
                                    <span class="material-symbols-outlined" aria-hidden="true">add_shopping_cart</span>
                                </button>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
            <?php echo shop_render_pagination($pagination, $paginationBaseUrl); ?>
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
