<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';

$action = trim((string) ($_POST['cart_action'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    require_once __DIR__ . '/../includes/csrf.php';
    csrf_verify();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action === 'add') {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $sku_name = trim((string) ($_POST['sku_name'] ?? ''));
        $product = shop_get_product_by_id($product_id);

        if ($product === null) {
            $_SESSION['flash_message'] = '商品不存在或已下架，请重新选择。';
            header('Location: index.php?page=products');
            exit;
        }

        $verified_price = (float) ($product['price'] ?? 0);
        $decoded_skus = json_decode((string) ($product['sku'] ?? '[]'), true);
        if (is_array($decoded_skus)) {
            foreach ($decoded_skus as $sku) {
                if ((string) ($sku['name'] ?? '') === $sku_name) {
                    $verified_price = (float) ($sku['price'] ?? $verified_price);
                    break;
                }
            }
        }

        if ($sku_name === '') {
            $sku_name = (string) ($product['name'] ?? '默认规格');
        }

        $cover_image = trim((string) ($product['cover_image'] ?? ''));
        if ($cover_image === '' && !empty($product['images']) && is_array($product['images'])) {
            $cover_image = (string) ($product['images'][0] ?? '');
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ((int) ($item['product_id'] ?? 0) === $product_id && (string) ($item['sku_name'] ?? '') === $sku_name) {
                $item['quantity'] = max(1, (int) ($item['quantity'] ?? 0) + 1);
                $item['price'] = $verified_price;
                $item['sku_price'] = $verified_price;
                $item['name'] = (string) ($product['name'] ?? '');
                $item['cover_image'] = $cover_image;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'name' => (string) ($product['name'] ?? ''),
                'price' => $verified_price,
                'sku_name' => $sku_name,
                'sku_price' => $verified_price,
                'quantity' => 1,
                'cover_image' => $cover_image,
            ];
        }

        $_SESSION['flash'] = '已成功加入购物车！';
        header('Location: index.php?page=product_detail&id=' . urlencode((string) $product_id));
        exit;
    }

    if ($action === 'update') {
        $index = (int) ($_POST['index'] ?? -1);
        $quantity = (int) ($_POST['quantity'] ?? 0);

        if (isset($_SESSION['cart'][$index])) {
            if ($quantity <= 0) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
            } else {
                $_SESSION['cart'][$index]['quantity'] = $quantity;
            }
        }

        header('Location: index.php?page=cart');
        exit;
    }

    if ($action === 'remove') {
        $index = (int) ($_POST['index'] ?? -1);
        if (isset($_SESSION['cart'][$index])) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }

        header('Location: index.php?page=cart');
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header('Location: index.php?page=cart');
        exit;
    }
}

$pageTitle = '购物车';
$currentPage = 'cart';
$cart = $_SESSION['cart'] ?? [];
$flash_class = 'success';
$flash_message = trim((string) ($_SESSION['flash_message'] ?? ''));
if ($flash_message === '') {
    $flash_message = trim((string) ($_SESSION['flash'] ?? ''));
} else {
    $flash_class = 'error';
}
unset($_SESSION['flash_message'], $_SESSION['flash']);

$total_price = 0;
foreach ($cart as $item) {
    $total_price += (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <?php if ($flash_message !== ''): ?>
        <div class="flash <?php echo shop_e($flash_class); ?> cart-flash">
            <?php echo shop_e($flash_message); ?>
        </div>
    <?php endif; ?>

    <div class="cart-shell">
        <div class="cart-heading">
            <h1 class="cart-title font-headline">购物车</h1>
            <p class="cart-subtitle">核对心仪商品后，就可以前往结算啦。</p>
        </div>

        <?php if ($cart === []): ?>
            <div class="home-empty-state cart-empty-state">
                <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
                <div class="cart-empty-copy">
                    <strong class="home-empty-title">购物车还是空的</strong>
                    <p class="home-empty-note">先去挑几件喜欢的商品吧。</p>
                </div>
                <a class="btn-primary cart-empty-action" href="index.php?page=products">继续购物</a>
            </div>
        <?php else: ?>
            <div class="cart-list">
                <?php foreach ($cart as $index => $item): ?>
                    <article class="card cart-item">
                        <div class="cart-item-main">
                            <a class="cart-item-media" href="index.php?page=product_detail&id=<?php echo (int) ($item['product_id'] ?? 0); ?>">
                                <img class="cart-item-image" src="<?php echo shop_e((string) ($item['cover_image'] ?? '')); ?>" alt="商品封面">
                            </a>
                            <div class="cart-item-content">
                                <a class="cart-item-name font-headline" href="index.php?page=product_detail&id=<?php echo (int) ($item['product_id'] ?? 0); ?>">
                                    <?php echo shop_e((string) ($item['name'] ?? '')); ?>
                                </a>
                                <span class="badge badge-primary cart-item-sku">规格：<?php echo shop_e((string) ($item['sku_name'] ?? '')); ?></span>
                                <div class="cart-item-price text-price"><?php echo shop_format_price((float) ($item['price'] ?? 0)); ?></div>
                            </div>
                        </div>

                        <div class="cart-item-actions">
                            <form method="post" class="cart-quantity-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="update">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <label class="sr-only" for="cart-quantity-<?php echo $index; ?>">数量</label>
                                <input class="input cart-quantity-input" id="cart-quantity-<?php echo $index; ?>" type="number" name="quantity" min="0" value="<?php echo (int) ($item['quantity'] ?? 1); ?>">
                                <button class="btn-ghost cart-action-btn" type="submit">
                                    <span class="material-symbols-outlined" aria-hidden="true">sync</span>
                                    <span>更新</span>
                                </button>
                            </form>

                            <form method="post" class="cart-remove-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="remove">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <button class="btn-ghost cart-action-btn cart-action-btn--error" type="submit">
                                    <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                                    <span>删除</span>
                                </button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="card cart-summary">
                <div class="cart-summary-meta">
                    <span class="cart-summary-label">总价</span>
                    <strong class="text-price text-h1 font-headline"><?php echo shop_format_price($total_price); ?></strong>
                </div>

                <div class="cart-summary-actions">
                    <form method="post" class="cart-clear-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="cart_action" value="clear">
                        <button class="btn-danger cart-summary-btn" type="submit">
                            <span class="material-symbols-outlined" aria-hidden="true">delete_sweep</span>
                            <span>清空购物车</span>
                        </button>
                    </form>

                    <a class="btn-primary cart-summary-btn" href="index.php?page=checkout">
                        <span>去结算</span>
                        <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
