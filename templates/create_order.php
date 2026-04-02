<?php declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

csrf_verify();

$product_id = (int) ($_POST['product_id'] ?? 0);
$pay_method = (string) ($_POST['pay_method'] ?? '');
$sku_name = (string) ($_POST['sku_name'] ?? '');
$sku_price = (float) ($_POST['sku_price'] ?? 0);

$all_products = shop_get_products();
$product = shop_find_product($all_products, $product_id);
if (!$product) {
    die('商品不存在');
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
if (!$pdo) {
    die('数据库连接失败');
}

$pdo->beginTransaction();
$order_no = date('YmdHis') . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
$total = $sku_price > 0 ? $sku_price : (float) $product['price'];
$item_name = (string) $product['name'] . ($sku_name !== '' ? " ({$sku_name})" : '') . ' ×1';

try {
    $user_id = $_SESSION['user_id'] ?? null;
    $customer_name = (string) ($_SESSION['user_name'] ?? '游客');
    $customer_phone = '';
    $customer_address = '';

    if ($user_id) {
        $stmt_user = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user_data = $stmt_user->fetch();
        if ($user_data) {
            $customer_phone = (string) ($user_data['phone'] ?? '');
            $customer_address = (string) ($user_data['address'] ?? '');
        }
    }

    $stmt = $pdo->prepare("INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $order_no,
        $user_id,
        $customer_name,
        $customer_phone,
        $customer_address,
        '已支付，待发货',
        $pay_method,
        $total,
        $item_name,
    ]);

    $stmt_stock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - 1 WHERE id = ? AND stock > 0");
    $stmt_stock->execute([$product_id]);
    if ($stmt_stock->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = '库存不足，无法下单';
        header('Location: index.php?page=product_detail&id=' . urlencode((string) $product_id));
        exit;
    }

    $pdo->commit();

    if (!isset($_SESSION['my_orders']) || !is_array($_SESSION['my_orders'])) {
        $_SESSION['my_orders'] = [];
    }
    $_SESSION['my_orders'][] = $order_no;
    $_SESSION['flash_message'] = '订单创建成功，商家将尽快发货';

    header('Location: index.php?page=orders');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die('创建订单失败: ' . $e->getMessage());
}
