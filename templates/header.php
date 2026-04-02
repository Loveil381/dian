
<!-- templates/header.php -->
<?php
$currentPage = $currentPage ?? 'home';
$currentKeyword = (string) ($_GET['keyword'] ?? '');
$cartCount = isset($_SESSION['cart']) ? array_reduce($_SESSION['cart'], fn($sum, $item) => $sum + ($item['quantity'] ?? 0), 0) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '魔女小店'; ?></title>
    <link rel="stylesheet" href="assets/css/site.css">
    <style>
        :root {
            --nav-bg: #ffffff;
            --icon-color: #333333;
            --icon-hover: #000000;
            --divider-color: rgba(230, 233, 238, 0.9);
            --badge-bg: #ff4d4f;
            --badge-text: #ffffff;
            --link-color: #666666;
            --link-active-bg: #f0f2f5;
            --link-hover-bg: #f5f6f8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #fafafa;
        }

        header {
            background-color: var(--nav-bg);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 8px rgba(20, 20, 20, 0.04);
        }

        .page-nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 12px 16px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .page-link {
            text-align: center;
            text-decoration: none;
            color: var(--link-color);
            padding: 12px 6px;
            font-size: 14px;
            border-radius: 12px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .page-link:hover {
            color: #111;
            background-color: var(--link-hover-bg);
        }

        .page-link.active {
            color: #111;
            font-weight: 600;
            background-color: var(--link-active-bg);
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 768px) {
            header {
                padding: 0;
            }

            .page-nav {
                padding: 10px 12px;
                gap: 6px;
            }

            .page-link {
                font-size: 13px;
                padding: 10px 3px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="nav-container">
        <div class="nav-left">
            <button class="icon-btn" aria-label="打开菜单" id="menuBtn" type="button">
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
            <a href="index.php?page=cart" class="icon-btn" aria-label="查看购物车" id="cartBtn" style="position: relative; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <?php if ($cartCount > 0): ?>
                <span class="cart-badge" style="position: absolute; top: -5px; right: -5px; background: var(--badge-bg, #ff4d4f); color: white; border-radius: 10px; padding: 2px 6px; font-size: 10px; min-width: 14px; text-align: center;"><?php echo $cartCount; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </div>

    <nav class="page-nav" aria-label="站点主导航">
        <a class="page-link <?php echo $currentPage === 'home' ? 'active' : ''; ?>" href="index.php?page=home">首页</a>
        <a class="page-link <?php echo $currentPage === 'products' ? 'active' : ''; ?>" href="index.php?page=products">商品页</a>
        <a class="page-link <?php echo $currentPage === 'orders' ? 'active' : ''; ?>" href="index.php?page=orders">订单</a>
        <a class="page-link <?php echo $currentPage === 'profile' ? 'active' : ''; ?>" href="index.php?page=profile">个人</a>
    </nav>
</header>