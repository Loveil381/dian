<?php
declare(strict_types=1);

$currentPage = $currentPage ?? 'home';
$currentKeyword = (string) ($_GET['keyword'] ?? '');
$cartCount = isset($_SESSION['cart']) ? array_reduce($_SESSION['cart'], fn($sum, $item) => $sum + ($item['quantity'] ?? 0), 0) : 0;
$pageTitle = $pageTitle ?? '魔女小店';
$pageDescription = $pageDescription ?? '魔女小店 — 轻量在线商城';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$canonicalParams = [];
$pageParam = trim((string) ($_GET['page'] ?? ''));
$idParam = trim((string) ($_GET['id'] ?? ''));
if ($pageParam !== '') {
    $canonicalParams['page'] = $pageParam;
}
if ($idParam !== '') {
    $canonicalParams['id'] = $idParam;
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
    <link rel="stylesheet" href="assets/css/design-tokens.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/site.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 500, 'GRAD' 0, 'opsz' 24;
            font-size: 24px;
            line-height: 1;
        }

        body.has-fixed-header {
            padding-top: calc(var(--space-2xl) + var(--space-xl) + var(--space-lg));
        }

        .site-header {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 50;
            background: var(--glass-bg);
            backdrop-filter: var(--glass-blur);
            -webkit-backdrop-filter: var(--glass-blur);
            box-shadow: var(--shadow-ethereal);
        }

        .header-bar {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            align-items: center;
            gap: var(--space-sm);
            padding: var(--space-md);
        }

        .header-icon-btn {
            min-height: 0;
            width: calc(var(--space-xl) + var(--space-md));
            height: calc(var(--space-xl) + var(--space-md));
            padding: 0;
            border-radius: var(--radius-full);
            color: var(--color-primary);
        }

        .header-search {
            min-width: 0;
        }

        .header-search-field {
            position: relative;
            display: flex;
            align-items: center;
        }

        .header-search-icon {
            position: absolute;
            left: var(--space-md);
            color: var(--color-primary);
            pointer-events: none;
            font-size: 20px;
        }

        .header-search .input-search {
            padding-right: calc(var(--space-xl) + var(--space-md));
        }

        .header-search .search-submit {
            position: absolute;
            top: 50%;
            right: var(--space-xs);
            transform: translateY(-50%);
            box-shadow: none;
        }

        .header-search .search-submit:hover,
        .header-search .search-submit:active {
            transform: translateY(-50%);
            box-shadow: none;
        }

        .header-cart-link {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: calc(var(--space-xl) + var(--space-md));
            height: calc(var(--space-xl) + var(--space-md));
            border-radius: var(--radius-full);
            color: var(--color-primary);
            background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface-container-lowest));
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: transform var(--transition-fast), box-shadow var(--transition-normal), background var(--transition-normal);
        }

        .header-cart-link:hover {
            transform: translateY(calc(var(--space-xs) * -1));
            box-shadow: var(--shadow-md);
            background: color-mix(in srgb, var(--color-primary) 16%, var(--color-surface-container-lowest));
        }

        .header-cart-link:active {
            transform: scale(0.98);
        }

        .site-header .page-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-md) var(--space-md);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-lg);
            background: transparent;
        }

        .site-header .page-link {
            padding: var(--space-xs) 0;
            border-radius: 0;
            background: transparent;
            color: var(--color-on-surface-variant);
            font-family: var(--font-label);
            font-size: var(--text-body);
            font-weight: 500;
        }

        .site-header .page-link:hover {
            background: transparent;
            color: var(--color-primary);
        }

        .site-header .page-link.active,
        .site-header .page-link.is-active {
            background: transparent;
            color: var(--color-primary);
            font-weight: 600;
        }

        .search-ajax-results {
            position: relative;
            z-index: 45;
        }

        .app-bottom-nav {
            display: none;
        }

        .bottom-nav-link {
            display: inline-flex;
            flex: 1 1 0;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: var(--space-xs);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-full);
            color: var(--color-on-surface-variant);
            font-family: var(--font-label);
            font-size: var(--text-badge);
            font-weight: 600;
            text-decoration: none;
            transition: background var(--transition-normal), color var(--transition-normal), transform var(--transition-fast);
        }

        .bottom-nav-link .material-symbols-outlined {
            font-size: 22px;
        }

        .bottom-nav-link:hover {
            color: var(--color-primary);
            background: color-mix(in srgb, var(--color-primary) 10%, var(--color-surface-container-lowest));
        }

        .bottom-nav-link.active,
        .bottom-nav-link.is-active,
        .bottom-nav-link[aria-current="page"] {
            color: var(--color-primary);
            background: color-mix(in srgb, var(--color-primary) 14%, var(--color-surface-container-lowest));
            box-shadow: var(--shadow-sm);
        }

        .bottom-nav-link.active .material-symbols-outlined,
        .bottom-nav-link.is-active .material-symbols-outlined,
        .bottom-nav-link[aria-current="page"] .material-symbols-outlined {
            font-variation-settings: 'FILL' 1, 'wght' 600, 'GRAD' 0, 'opsz' 24;
        }

        @media (max-width: 768px) {
            body.has-fixed-header {
                padding-top: calc(var(--space-2xl) * 2 + var(--space-lg));
                padding-bottom: calc(var(--space-2xl) + var(--space-lg) + env(safe-area-inset-bottom));
            }

            .header-bar {
                padding: var(--space-sm) var(--space-md);
            }

            .site-header .page-nav {
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: var(--space-xs);
                padding: 0 var(--space-md) var(--space-md);
            }

            body.mobile-nav-open .site-header .page-nav {
                display: flex !important;
            }

            .site-header .page-link {
                justify-content: flex-start;
            }

            .app-bottom-nav {
                display: flex;
            }
        }
    </style>
</head>
<body class="has-fixed-header">

<header class="glass-nav site-header">
    <div class="header-bar">
        <div class="nav-left">
            <button class="btn-ghost header-icon-btn" aria-label="打开导航" aria-expanded="false" aria-controls="siteNav" id="menuBtn" type="button">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
        </div>

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

        <div class="nav-right">
            <a href="index.php?page=cart" class="cart-link header-cart-link" aria-label="购物车" id="cartBtn">
                <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
                <?php if ($cartCount > 0): ?>
                    <span class="badge badge-cart"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="page-nav" id="siteNav" aria-label="主导航">
        <a class="nav-link page-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">首页</a>
        <a class="nav-link page-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">商品</a>
        <a class="nav-link page-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" href="index.php?page=orders">订单</a>
        <a class="nav-link page-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">我的</a>
    </nav>
</header>

<div id="searchAjaxResults" class="search-ajax-results" hidden></div>

<nav class="bottom-nav app-bottom-nav" aria-label="底部导航">
    <a class="bottom-nav-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">
        <span class="material-symbols-outlined" aria-hidden="true">home</span>
        <span>首页</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">
        <span class="material-symbols-outlined" aria-hidden="true">category</span>
        <span>商品</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'cart' ? 'active' : ''; ?>" href="index.php?page=cart">
        <span class="material-symbols-outlined" aria-hidden="true">shopping_cart</span>
        <span>购物车</span>
    </a>
    <a class="bottom-nav-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">
        <span class="material-symbols-outlined" aria-hidden="true">person</span>
        <span>我的</span>
    </a>
</nav>
