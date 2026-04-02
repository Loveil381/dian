<?php
declare(strict_types=1);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception): void {
    http_response_code(500);
    error_log('[shop] 全局异常: ' . $exception->getMessage());
    $errorCode = 500;
    $errorMessage = '系统暂时不可用，请稍后再试。';
    require __DIR__ . '/templates/error.php';
});

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

if (!array_key_exists($page, $routes)) {
    http_response_code(404);
    $errorCode = 404;
    $errorMessage = '你访问的页面不存在。';
    require __DIR__ . '/templates/error.php';
    exit;
}

$currentPage = $page;
require $routes[$currentPage];
