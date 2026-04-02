<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_POST['cart_action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== '') {
    require_once __DIR__ . '/../includes/csrf.php';
    csrf_verify();

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $skuName = (string)($_POST['sku_name'] ?? '');
        $skuPrice = (float)($_POST['sku_price'] ?? 0);
        $coverImage = (string)($_POST['cover_image'] ?? '');
        $name = (string)($_POST['name'] ?? '');

        // Find if already exists
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] === $productId && $item['sku_name'] === $skuName) {
                $item['quantity']++;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $productId,
                'name' => $name,
                'price' => $skuPrice,
                'sku_name' => $skuName,
                'sku_price' => $skuPrice,
                'quantity' => 1,
                'cover_image' => $coverImage
            ];
        }
        
        $redirectUrl = 'index.php?page=product_detail&id=' . urlencode((string)$productId);
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'update') {
        $index = (int)($_POST['index'] ?? -1);
        $quantity = (int)($_POST['quantity'] ?? 0);
        
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
        $index = (int)($_POST['index'] ?? -1);
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

$pageTitle = '我的购物车';
$currentPage = 'cart';
include __DIR__ . '/header.php';

$cart = $_SESSION['cart'] ?? [];
$totalPrice = 0;
foreach ($cart as $item) {
    $totalPrice += ($item['price'] * $item['quantity']);
}
?>

<main class="page-shell">
    <div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="font-size: 24px; margin-top: 0; margin-bottom: 20px;">我的购物车</h1>
        
        <?php if (empty($cart)): ?>
            <div style="text-align: center; padding: 40px 0; color: #6b7280;">
                <p style="margin-bottom: 20px;">购物车是空的</p>
                <a href="index.php?page=products" style="display: inline-block; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px;">去逛逛</a>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($cart as $index => $item): ?>
                    <div style="display: flex; gap: 15px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; align-items: center;">
                        <img src="<?php echo shop_e($item['cover_image'] ?? ''); ?>" alt="cover" style="width: 80px; height: 80px; object-fit: cover; border-radius: 6px;">
                        <div style="flex: 1;">
                            <h3 style="margin: 0; font-size: 16px;"><a href="index.php?page=product_detail&id=<?php echo $item['product_id']; ?>" style="color: inherit; text-decoration: none;"><?php echo shop_e($item['name'] ?? ''); ?></a></h3>
                            <div style="color: #6b7280; font-size: 14px; margin-top: 5px;">规格: <?php echo shop_e($item['sku_name'] ?? ''); ?></div>
                            <div style="color: #dc2626; font-weight: bold; margin-top: 5px;"><?php echo shop_format_price((float)$item['price']); ?></div>
                        </div>
                        
                        <div style="display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
                            <form method="post" style="display: flex; gap: 5px; align-items: center;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="update">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <input type="number" name="quantity" value="<?php echo (int)$item['quantity']; ?>" min="0" style="width: 60px; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;">
                                <button type="submit" style="padding: 4px 8px; background: #e5e7eb; border: none; border-radius: 4px; cursor: pointer;">更新</button>
                            </form>
                            
                            <form method="post">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="cart_action" value="remove">
                                <input type="hidden" name="index" value="<?php echo $index; ?>">
                                <button type="submit" style="padding: 4px 8px; background: #fee2e2; color: #dc2626; border: none; border-radius: 4px; cursor: pointer;">删除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px dashed #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <form method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="clear">
                    <button type="submit" style="padding: 10px 15px; background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; cursor: pointer;">清空购物车</button>
                </form>
                
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="font-size: 18px;">
                        合计: <span style="color: #dc2626; font-weight: bold; font-size: 24px;"><?php echo shop_format_price($totalPrice); ?></span>
                    </div>
                    <a href="index.php?page=checkout" style="padding: 12px 25px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">去结算</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
