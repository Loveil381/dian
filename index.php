<?php
declare(strict_types=1);

$page = $_GET['page'] ?? 'home';
$routes = [
    'home' => __DIR__ . '/templates/index.php',
    'products' => __DIR__ . '/templates/products.php',
    'product_detail' => __DIR__ . '/templates/product_detail.php',
    'create_order' => __DIR__ . '/templates/create_order.php',
    'orders' => __DIR__ . '/templates/orders.php',
    'profile' => __DIR__ . '/templates/profile.php',
    'auth' => __DIR__ . '/templates/auth.php',
    'admin' => __DIR__ . '/admin/index.php',
    'admin_login' => __DIR__ . '/admin/login.php',
    'admin_setup' => __DIR__ . '/admin/setup.php',
    'cart' => __DIR__ . '/templates/cart.php',
    'checkout' => __DIR__ . '/templates/checkout.php',
];
$currentPage = array_key_exists($page, $routes) ? $page : 'home';
require $routes[$currentPage];