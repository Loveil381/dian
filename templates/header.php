<!-- templates/header.php -->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? '魔女小店'; ?></title>
    <style>
        :root {
            --nav-bg: #ffffff;
            --icon-color: #333333;
            --icon-hover: #000000;
            --divider-color: rgba(240, 240, 240, 0.6);
            --badge-bg: #ff4d4f;
            --badge-text: #ffffff;
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
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .nav-left, .nav-right {
            display: flex;
            align-items: center;
        }

        .nav-right {
            gap: 16px;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            color: var(--icon-color);
            position: relative;
        }

        .icon-btn:hover {
            color: var(--icon-hover);
        }

        .icon-btn svg {
            display: block;
        }

        .cart-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background-color: var(--badge-bg);
            color: var(--badge-text);
            font-size: 10px;
            font-weight: bold;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .nav-divider {
            height: 1px;
            background-color: var(--divider-color);
            margin-top: 8px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (max-width: 768px) {
            header {
                padding: 10px 12px;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="nav-container">
        <div class="nav-left">
            <button class="icon-btn" aria-label="打开菜单" id="menuBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="nav-right">
            <button class="icon-btn" aria-label="搜索商品" id="searchBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
            <button class="icon-btn" aria-label="查看购物车" id="cartBtn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                <span class="cart-badge">3</span>
            </button>
        </div>
    </div>
    <div class="nav-divider"></div>
</header>