<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../data/products.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$productId = (int) ($_POST['product_id'] ?? 0);
$payMethod = $_POST['pay_method'] ?? '';
$skuName = $_POST['sku_name'] ?? '';
$skuPrice = (float)($_POST['sku_price'] ?? 0);

$allProducts = shop_get_products();
$product = shop_find_product($allProducts, $productId);

if (!$product) {
    die('商品不存在');
}

$pdo = get_db_connection();
$prefix = get_db_prefix();
if (!$pdo) {
    die('数据库连接失败');
}
$pdo->beginTransaction();

$orderNo = date('YmdHis') . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT);
$total = $skuPrice > 0 ? $skuPrice : (float)$product['price'];
$itemName = $product['name'] . ($skuName ? " ({$skuName})" : '') . ' × 1';

try {
    $userId = $_SESSION['user_id'] ?? null;
    
    $customerName = $_SESSION['user_name'] ?? '访客';
    $customerPhone = '';
    $customerAddress = '';
    
    if ($userId) {
        $stmtUser = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userData = $stmtUser->fetch();
        if ($userData) {
            $customerPhone = $userData['phone'] ?? '';
            $customerAddress = $userData['address'] ?? '';
        }
    }

    $stmt = $pdo->prepare("INSERT INTO `{$prefix}orders` (order_no, user_id, customer, phone, address, status, pay_method, total, items, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $orderNo,
        $userId,
        $customerName,
        $customerPhone,
        $customerAddress,
        '已支付 待确认 未发货',
        $payMethod,
        $total,
        $itemName
    ]);

    $stmtStock = $pdo->prepare("UPDATE `{$prefix}products` SET stock = stock - 1 WHERE id = ? AND stock > 0");
    $stmtStock->execute([$productId]);
    if ($stmtStock->rowCount() === 0) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = '库存不足，下单失败';
        header('Location: index.php?page=product_detail&id=' . urlencode((string)$productId));
        exit;
    }

    $pdo->commit();

    if (!isset($_SESSION['my_orders'])) {
        $_SESSION['my_orders'] = [];
    }
    $_SESSION['my_orders'][] = $orderNo;
    
    $_SESSION['flash_message'] = '订单已创建并标记为已支付，等待后台确认。';
    header('Location: index.php?page=orders');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die('创建订单失败: ' . $e->getMessage());
}
