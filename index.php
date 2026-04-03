<?php
declare(strict_types=1);

$page = $_GET['page'] ?? 'home';
$routes = [
    'home' => __DIR__ . '/templates/index.php',
    'products' => __DIR__ . '/templates/products.php',
    'product_detail' => __DIR__ . '/templates/product_detail.php',
    'orders' => __DIR__ . '/templates/orders.php',
    'order_detail' => __DIR__ . '/templates/order_detail.php',
    'profile' => __DIR__ . '/templates/profile.php',
    'auth' => __DIR__ . '/templates/auth.php',
    'forgot_password' => __DIR__ . '/templates/forgot_password.php',
    'reset_password' => __DIR__ . '/templates/reset_password.php',
    'admin' => __DIR__ . '/admin/index.php',
    'admin_login' => __DIR__ . '/admin/login.php',
    'admin_setup' => __DIR__ . '/admin/setup.php',
    'cart' => __DIR__ . '/templates/cart.php',
    'checkout' => __DIR__ . '/templates/checkout.php',
];

if (isset($routes[$page])) {
    $currentPage = $page;
    require $routes[$page];
} else {
    http_response_code(404);
    require __DIR__ . '/includes/error_handler.php';
    shop_error_page(404, '您访问的页面不存在，请检查链接后重试。');
    exit;
}
