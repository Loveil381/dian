<?php
declare(strict_types=1);

/**
 * 购物车 POST action handler。
 * 由 templates/cart.php 在 POST 时 require，处理完后 redirect + exit。
 */

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/csrf.php';

csrf_verify();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = trim((string) ($_POST['cart_action'] ?? ''));

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

// 未知 action，回到购物车
header('Location: index.php?page=cart');
exit;
