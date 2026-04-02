<?php declare(strict_types=1); ?>
<?php
$current_page = 'products';
$currentPage = $current_page;
$page_title = '全部商品';
$pageTitle = $page_title;
$pageDescription = '魔女小店全部商品，支持分类浏览与关键词搜索。';

$keyword = trim((string)($_GET['keyword'] ?? ''));
$selected_category = trim((string)($_GET['category'] ?? ''));
$is_ajax = isset($_GET['ajax']);

try {
    $pdo = get_pdo();
    $prefix = get_db_prefix();

    $category_stmt = $pdo->query("SELECT id, name FROM `{$prefix}categories` ORDER BY sort_order ASC, id DESC");
    $categories = $category_stmt ? $category_stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $where = ["status = 'on_sale'"];
    $params = [];

    if ($selected_category !== '') {
        $where[] = 'category = ?';
        $params[] = $selected_category;
    }

    if ($keyword !== '') {
        $where[] = '(name LIKE ? OR description LIKE ?)';
        $like_keyword = '%' . $keyword . '%';
        $params[] = $like_keyword;
        $params[] = $like_keyword;
    }

    $sql = "SELECT * FROM `{$prefix}products`";
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log('商品列表查询失败: ' . $exception->getMessage());
    $categories = [];
    $products = [];
}

ob_start();
?>
<section class="page-section">
  <div class="page-header">
    <div>
      <div class="page-kicker">Product Gallery</div>
      <h1 class="page-title">全部商品</h1>
      <p class="page-subtitle">探索魔女小店精选好物，支持分类浏览与关键词搜索。</p>
    </div>
  </div>

  <div class="filter-card">
    <form method="get" action="index.php" class="filter-form">
      <input type="hidden" name="page" value="products">
      <div class="filter-field">
        <label for="productKeyword">关键词</label>
        <input id="productKeyword" type="text" name="keyword" value="<?php echo shop_e($keyword); ?>" placeholder="输入商品名称或描述">
      </div>
      <div class="filter-field">
        <label for="productCategory">分类</label>
        <select id="productCategory" name="category">
          <option value="">全部分类</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?php echo shop_e((string)$category['name']); ?>" <?php echo $selected_category === (string)$category['name'] ? 'selected' : ''; ?>>
              <?php echo shop_e((string)$category['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary">筛选商品</button>
    </form>
  </div>

  <div class="products-grid products-grid--compact" id="searchAjaxResults">
    <?php if ($products === []): ?>
      <div class="empty-state">
        <h3>未找到相关商品</h3>
        <p>可以尝试调整关键词或切换分类。</p>
      </div>
    <?php else: ?>
      <?php foreach ($products as $product): ?>
        <?php
          $product_id = (int)($product['id'] ?? 0);
          $product_name = (string)($product['name'] ?? '');
          $cover_image = (string)($product['cover_image'] ?? '');
          $product_price = (float)($product['price'] ?? 0);
          $product_sales = (int)($product['sales'] ?? 0);
          $detail_url = 'index.php?page=product_detail&id=' . $product_id;
        ?>
        <a class="product-card product-card--compact" href="<?php echo shop_e($detail_url); ?>">
          <div class="product-card__media">
            <?php if ($cover_image !== ''): ?>
              <img src="<?php echo shop_e($cover_image); ?>" alt="<?php echo shop_e($product_name); ?>">
            <?php else: ?>
              <div class="product-card__placeholder">暂无图片</div>
            <?php endif; ?>
          </div>
          <div class="product-card__body">
            <h3><?php echo shop_e($product_name); ?></h3>
            <div class="product-card__meta">
              <span class="product-card__price"><?php echo shop_format_price($product_price); ?></span>
              <span class="product-card__sales"><?php echo shop_format_sales($product_sales); ?></span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php
$products_content = trim((string)ob_get_clean());

if ($is_ajax) {
    echo $products_content;
    return;
}

include __DIR__ . '/header.php';
echo $products_content;
include __DIR__ . '/footer.php';
