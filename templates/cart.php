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
$flash_message = trim((string) ($_SESSION['flash_message'] ?? ''));
if ($flash_message === '') {
    $flash_message = trim((string) ($_SESSION['flash'] ?? ''));
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
        <div style="max-width: 800px; margin: 0 auto 18px; padding: 14px 16px; border-radius: 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
            <?php echo shop_e($flash_message); ?>
        </div>
    <?php endif; ?>

    <div style="max-width: 800px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 18px; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);">
        <h1 style="font-size: 28px; margin: 0 0 20px;">购物车</h1>

        <?php if ($cart === []): ?>
            <div style="text-align: center; padding: 48px 0; color: #64748b;">
                <p style="margin: 0 0 18px;">购物车还是空的，先去挑几件喜欢的商品吧。</p>
                <a href="index.php?page=products" style="display: inline-block; padding: 12px 20px; border-radius: 999px; background: #2563eb; color: #ffffff; text-decoration: none;">继续购物</a>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 14px;">
                <?php foreach ($cart as $index => $item): ?>
                    <div style="display: flex; gap: 14px; align-items: center; padding: 14px; border: 1px solid #e5e7eb; border-radius: 14px;">
                        <img src="<?php echo shop_e((string) ($item['cover_image'] ?? '')); ?>" alt="商品封面" style="width: 84px; height: 84px; object-fit: cover; border-radius: 12px;">
                        <div style="flex: 1;">
                            <a href="index.php?page=product_detail&id=<?php echo (int) ($item['product_id'] ?? 0); ?>" style="font-size: 17px; font-weight: 600; color: #0f172a; text-decoration: none;">
                                <?php echo shop_e((string) ($item['name'] ?? '')); ?>
                            </a>
                            <div style="margin-top: 6px; color: #64748b;">规格：<?php echo shop_e((string) ($item['sku_name'] ?? '')); ?></div>
                            <div style="margin-top: 6px; color: #dc2626; font-weight: 700;"><?php echo shop_format_price((float) ($item['price'] ?? 0)); ?></div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                            <form method="post" style="display: flex; align-items: center; gap: 6px;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="update">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <input type="number" name="quantity" min="0" value="<?php echo (int) ($item['quantity'] ?? 1); ?>" style="width: 68px; padding: 6px 8px; border-radius: 10px; border: 1px solid #cbd5e1; text-align: center;">
                                <button type="submit" style="padding: 6px 10px; border: none; border-radius: 10px; background: #e2e8f0; cursor: pointer;">更新</button>
                            </form>

                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="remove">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <button type="submit" style="padding: 6px 10px; border: none; border-radius: 10px; background: #fee2e2; color: #dc2626; cursor: pointer;">删除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 24px; padding-top: 18px; border-top: 1px dashed #cbd5e1; display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: center;">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="clear">
                    <button type="submit" style="padding: 10px 16px; border: none; border-radius: 999px; background: #fee2e2; color: #dc2626; cursor: pointer;">清空购物车</button>
                </form>

                <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                    <div style="font-size: 18px;">总价：<strong style="font-size: 26px; color: #dc2626;"><?php echo shop_format_price($total_price); ?></strong></div>
                    <a href="index.php?page=checkout" style="display: inline-block; padding: 12px 22px; border-radius: 999px; background: #2563eb; color: #ffffff; text-decoration: none; font-weight: 700;">去结算</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
