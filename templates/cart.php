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
$total_quantity = 0;
foreach ($cart as $item) {
    $qty = max(1, (int) ($item['quantity'] ?? 1));
    $total_price += (float) ($item['price'] ?? 0) * $qty;
    $total_quantity += $qty;
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
        <?php if ($cart === []): ?>
            <!-- ── 空购物车 ── -->
            <div class="cart-empty">
                <div class="cart-empty-icon-wrap">
                    <span class="material-symbols-outlined cart-empty-icon" aria-hidden="true">shopping_cart</span>
                </div>
                <strong class="cart-empty-title">购物车是空的</strong>
                <p class="cart-empty-note">去挑几件喜欢的商品吧</p>
                <a class="btn-primary cart-empty-action" href="index.php?page=products">去逛逛</a>
            </div>
        <?php else: ?>
            <!-- ── 标题栏：返回 + 标题(数量) + 清空 ── -->
            <div class="cart-header">
                <a class="cart-back" href="index.php?page=products">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_back</span>
                    <span>继续购物</span>
                </a>
                <h1 class="cart-title font-headline">购物车<span class="cart-count">(<?php echo $total_quantity; ?>)</span></h1>
                <form method="post" class="cart-clear-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="clear">
                    <button class="cart-clear-link" type="submit" data-confirm-click="确定要清空购物车吗？此操作不可撤销。">
                        <span class="material-symbols-outlined" aria-hidden="true">delete_sweep</span>
                        <span>清空</span>
                    </button>
                </form>
            </div>

            <!-- ── 商品列表 ── -->
            <div class="cart-list">
                <?php foreach ($cart as $index => $item):
                    $item_qty = max(1, (int) ($item['quantity'] ?? 1));
                    $item_price = (float) ($item['price'] ?? 0);
                    $item_subtotal = $item_price * $item_qty;
                ?>
                    <article class="card cart-item">
                        <div class="cart-item-body">
                            <a class="cart-item-cover" href="index.php?page=product_detail&id=<?php echo (int) ($item['product_id'] ?? 0); ?>">
                                <?php $cover_src = trim((string) ($item['cover_image'] ?? '')); ?>
                                <?php if ($cover_src !== ''): ?>
                                    <img class="cart-item-image" src="<?php echo shop_e($cover_src); ?>" alt="<?php echo shop_e((string) ($item['name'] ?? '')); ?>" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <span class="cart-item-placeholder" style="display:none" aria-hidden="true">
                                        <span class="material-symbols-outlined">image</span>
                                    </span>
                                <?php else: ?>
                                    <span class="cart-item-placeholder" aria-hidden="true">
                                        <span class="material-symbols-outlined">image</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="cart-item-info">
                                <div class="cart-item-top-row">
                                    <a class="cart-item-name" href="index.php?page=product_detail&id=<?php echo (int) ($item['product_id'] ?? 0); ?>">
                                        <?php echo shop_e((string) ($item['name'] ?? '')); ?>
                                    </a>
                                    <form method="post" class="cart-item-delete-form">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="cart_action" value="remove">
                                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                                        <button class="cart-item-delete" type="submit" title="删除此商品" data-confirm-click="确定要删除这件商品吗？">
                                            <span class="material-symbols-outlined" aria-hidden="true">close</span>
                                        </button>
                                    </form>
                                </div>
                                <span class="badge cart-item-sku"><?php echo shop_e((string) ($item['sku_name'] ?? '默认规格')); ?></span>
                                <div class="cart-item-bottom-row">
                                    <span class="cart-item-price text-price"><?php echo shop_format_price($item_price); ?></span>
                                    <div class="cart-qty-stepper">
                                        <form method="post" class="cart-qty-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="cart_action" value="update">
                                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                                            <input type="hidden" name="quantity" class="cart-qty-value" value="<?php echo $item_qty; ?>">
                                            <button type="button" class="cart-qty-btn cart-qty-dec" aria-label="减少数量">
                                                <span class="material-symbols-outlined">remove</span>
                                            </button>
                                            <span class="cart-qty-display"><?php echo $item_qty; ?></span>
                                            <button type="button" class="cart-qty-btn cart-qty-inc" aria-label="增加数量">
                                                <span class="material-symbols-outlined">add</span>
                                            </button>
                                        </form>
                                    </div>
                                    <span class="cart-item-subtotal text-price"><?php echo shop_format_price($item_subtotal); ?></span>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- ── 底部结算栏（仿淘宝/京东） ── -->
            <div class="cart-bar">
                <div class="cart-bar-total">
                    <span class="cart-bar-label">合计</span>
                    <span class="text-price cart-bar-price font-headline"><?php echo shop_format_price($total_price); ?></span>
                </div>
                <a class="btn-primary cart-bar-checkout" href="index.php?page=checkout">
                    去结算(<?php echo $total_quantity; ?>)
                </a>
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
