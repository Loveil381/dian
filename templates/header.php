<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
$currentKeyword = (string) ($_GET['keyword'] ?? '');
$cartCount = isset($_SESSION['cart']) ? array_reduce($_SESSION['cart'], fn($sum, $item) => $sum + ($item['quantity'] ?? 0), 0) : 0;
$pageDescription = $pageDescription ?? '魔女小店 — 轻量在线商城';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/index.php');
$canonicalUrl = $scheme . '://' . $host . $requestUri;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '魔女小店'; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars((string) $pageDescription, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
</head>
<body>

<header>
    <div class="nav-container">
        <div class="nav-left">
            <button class="icon-btn" aria-label="打开导航" aria-expanded="false" aria-controls="siteNav" id="menuBtn" type="button">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>

        <form class="search-form" id="searchForm" method="get" action="index.php" role="search">
            <label class="sr-only" for="searchInput">搜索商品</label>
            <input type="hidden" name="page" value="products">
            <input class="search-input" type="search" id="searchInput" name="keyword" placeholder="搜索商品..." autocomplete="off" value="<?php echo htmlspecialchars($currentKeyword, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="icon-btn search-submit" aria-label="提交搜索" id="searchBtn" type="submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </form>

        <div class="nav-right">
            <a href="index.php?page=cart" class="icon-btn cart-link" aria-label="购物车" id="cartBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="page-nav" id="siteNav" aria-label="主导航">
        <a class="page-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">首页</a>
        <a class="page-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">商品</a>
        <a class="page-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" href="index.php?page=orders">订单</a>
        <a class="page-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">我的</a>
    </nav>
</header>

<div id="searchAjaxResults" class="search-ajax-results" hidden></div>
