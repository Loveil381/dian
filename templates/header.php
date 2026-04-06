<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
$currentKeyword = (string) ($_GET['keyword'] ?? '');
$cartCount = isset($_SESSION['cart']) ? array_reduce($_SESSION['cart'], fn ($sum, $item) => $sum + ($item['quantity'] ?? 0), 0) : 0;
$pageTitle = $pageTitle ?? '魔女的小店';
$pageDescription = $pageDescription ?? '魔女的小店，发现喜欢的商品并轻松下单。';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$canonicalParams = [];
$pageParam = trim((string) ($_GET['page'] ?? ''));
$idParam = trim((string) ($_GET['id'] ?? ''));
$slugParam = trim((string) ($_GET['slug'] ?? ''));
if ($pageParam !== '') {
    $canonicalParams['page'] = $pageParam;
}
if ($idParam !== '') {
    $canonicalParams['id'] = $idParam;
}
if ($slugParam !== '') {
    $canonicalParams['slug'] = $slugParam;
}
$canonicalPath = '/index.php';
$canonicalQuery = $canonicalParams === [] ? '' : '?' . http_build_query($canonicalParams);
$canonicalUrl = $scheme . '://' . $host . $canonicalPath . $canonicalQuery;
$ogTitle = $ogTitle ?? $pageTitle;
$ogDescription = $ogDescription ?? $pageDescription;
$ogType = $ogType ?? 'website';
$defaultOgImage = $scheme . '://' . $host . '/assets/favicon.svg';
$ogImage = $ogImage ?? $defaultOgImage;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo shop_e($pageTitle); ?></title>
    <meta name="description" content="<?php echo shop_e($pageDescription); ?>">
    <link rel="canonical" href="<?php echo shop_e($canonicalUrl); ?>">
    <meta property="og:title" content="<?php echo shop_e($ogTitle); ?>">
    <meta property="og:description" content="<?php echo shop_e($ogDescription); ?>">
    <meta property="og:url" content="<?php echo shop_e($canonicalUrl); ?>">
    <meta property="og:type" content="<?php echo shop_e($ogType); ?>">
    <meta property="og:image" content="<?php echo shop_e($ogImage); ?>">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <?php
    $cssVersion = function(string $file): string {
        $path = __DIR__ . '/../' . $file;
        return $file . '?v=' . (file_exists($path) ? filemtime($path) : time());
    };
    ?>
    <link rel="stylesheet" href="<?php echo $cssVersion('assets/css/design-tokens.css'); ?>">
    <link rel="stylesheet" href="<?php echo $cssVersion('assets/css/components.css'); ?>">
    <link rel="stylesheet" href="<?php echo $cssVersion('assets/css/site.css'); ?>">
    <link rel="stylesheet" href="<?php echo $cssVersion('assets/css/mobile.css'); ?>">
</head>
<body class="has-fixed-header">

<header class="glass-nav site-header">
    <div class="header-bar">
        <!-- 左侧：移动端汉堡菜单 / PC 端品牌名 -->
        <div class="nav-left">
            <button class="btn-ghost header-icon-btn header-menu-btn" aria-label="打开导航" aria-expanded="false" aria-controls="mobileExpandNav" id="menuBtn" type="button">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
            <a href="index.php?page=home" class="header-brand" aria-label="魔女的小店 - 返回首页">
                <span class="header-brand-icon" aria-hidden="true">🔮</span>
                <span class="header-brand-name">魔女的小店</span>
            </a>
        </div>

        <!-- 中间：PC 端主导航 + 搜索栏 -->
        <nav class="header-main-nav" id="siteNav" aria-label="主导航">
            <a class="nav-link page-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">首页</a>
            <a class="nav-link page-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">商品</a>
        </nav>

        <form class="search-form header-search" id="searchForm" method="get" action="index.php" role="search">
            <label class="sr-only" for="searchInput">搜索商品</label>
            <input type="hidden" name="page" value="products">
            <div class="header-search-field">
                <span class="material-symbols-outlined header-search-icon" aria-hidden="true">search</span>
                <input class="input input-search search-input" type="search" id="searchInput" name="keyword" placeholder="搜索商品..." autocomplete="off" value="<?php echo shop_e($currentKeyword); ?>">
                <button class="btn-ghost header-icon-btn search-submit" aria-label="提交搜索" id="searchBtn" type="submit">
                    <span class="material-symbols-outlined" aria-hidden="true">arrow_forward</span>
                </button>
            </div>
        </form>

        <!-- 右侧：购物车 + 用户入口 -->
        <div class="nav-right">
            <a href="index.php?page=cart" class="cart-link header-cart-link" aria-label="购物车" id="cartBtn">
                <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
                <?php if ($cartCount > 0): ?>
                    <span class="badge badge-cart"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
            <a href="index.php?page=<?php echo isset($_SESSION['user_id']) ? 'profile' : 'auth'; ?>" class="header-user-link" aria-label="<?php echo isset($_SESSION['user_id']) ? '个人中心' : '登录'; ?>">
                <span class="material-symbols-outlined" aria-hidden="true"><?php echo isset($_SESSION['user_id']) ? 'person' : 'login'; ?></span>
            </a>
        </div>
    </div>

    <!-- 移动端展开导航（汉堡菜单） -->
    <nav class="mobile-expand-nav" id="mobileExpandNav" aria-label="移动端导航">
        <a class="nav-link page-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">首页</a>
        <a class="nav-link page-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">商品</a>
        <a class="nav-link page-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" href="index.php?page=orders">订单</a>
        <a class="nav-link page-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">我的</a>
    </nav>
</header>

<div id="searchAjaxResults" class="search-ajax-results" hidden></div>

<nav class="bottom-nav app-bottom-nav<?php echo !empty($hideBottomNav) ? ' is-hidden' : ''; ?>" aria-label="底部导航">
    <a class="bottom-nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">
        <span class="material-symbols-outlined" aria-hidden="true">home</span>
        <span>首页</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">
        <span class="material-symbols-outlined" aria-hidden="true">category</span>
        <span>商品</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'cart' ? 'active' : ''; ?>" href="index.php?page=cart">
        <span class="bottom-nav-icon-wrap">
            <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
            <?php if ($cartCount > 0): ?>
                <span class="badge badge-cart"><?php echo $cartCount; ?></span>
            <?php endif; ?>
        </span>
        <span>购物车</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">
        <span class="material-symbols-outlined" aria-hidden="true">person</span>
        <span>我的</span>
    </a>
</nav>
