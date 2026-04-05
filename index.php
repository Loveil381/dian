<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

// 维护模式：更新期间前台显示 503，后台不受影响
$page = $_GET['page'] ?? 'home';
if (file_exists(__DIR__ . '/storage/maintenance.flag') && !str_starts_with($page, 'admin')) {
    http_response_code(503);
    header('Retry-After: 60');
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>维护中</title></head>';
    echo '<body style="display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;font-family:-apple-system,sans-serif;background:#f8fafc;color:#334155;text-align:center;"><div><h1 style="font-size:1.5rem;margin-bottom:.5rem;">网站维护中</h1><p style="color:#64748b;">系统正在更新，请稍后刷新页面。</p></div></body></html>';
    exit;
}
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
    'page' => __DIR__ . '/templates/page.php',
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
