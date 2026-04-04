<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string) ($_POST['cart_action'] ?? '')) !== '') {
    require __DIR__ . '/../actions/cart_action.php';
    // cart_action.php 总是 redirect + exit，不会到达这里
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
                            <div class="cart-qty-stepper">
                                <form method="post" class="cart-qty-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="cart_action" value="update">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="hidden" name="quantity" class="cart-qty-value" value="<?php echo (int) ($item['quantity'] ?? 1); ?>">
                                    <button type="button" class="cart-qty-btn cart-qty-dec" aria-label="减少数量">
                                        <span class="material-symbols-outlined">remove</span>
                                    </button>
                                    <span class="cart-qty-display"><?php echo (int) ($item['quantity'] ?? 1); ?></span>
                                    <button type="button" class="cart-qty-btn cart-qty-inc" aria-label="增加数量">
                                        <span class="material-symbols-outlined">add</span>
                                    </button>
                                </form>
                            </div>

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

<script>
(function(){
    document.querySelectorAll('.cart-qty-stepper').forEach(function(stepper){
        var form = stepper.querySelector('.cart-qty-form');
        var input = stepper.querySelector('.cart-qty-value');
        var display = stepper.querySelector('.cart-qty-display');
        var dec = stepper.querySelector('.cart-qty-dec');
        var inc = stepper.querySelector('.cart-qty-inc');

        dec.addEventListener('click', function(){
            var val = parseInt(input.value) || 1;
            if (val > 1) {
                input.value = val - 1;
                display.textContent = val - 1;
                form.submit();
            }
        });

        inc.addEventListener('click', function(){
            var val = parseInt(input.value) || 1;
            input.value = val + 1;
            display.textContent = val + 1;
            form.submit();
        });
    });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
