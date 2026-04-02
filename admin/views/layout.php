<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo shop_e($pageTitle); ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
</head>
<body>
<header class="topbar">
    <button class="menu-btn" id="menuBtn" type="button" aria-label="打开管理菜单">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="icon" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <strong>魔女小店 / 管理后台</strong>
</header>

<div class="shell">
    <div class="overlay" id="overlay"></div>

    <aside class="sidebar" id="sidebar" aria-label="管理菜单">
        <div class="sidebar-header">
            <div class="sidebar-title">管理后台</div>
            <div class="sidebar-sub">首页看板、商品、分类、库存、订单、用户、插件和配置模块都已整理好。</div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-group">
                <h3>总览</h3>
                <a class="nav-link <?php echo $currentTab === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=admin&tab=dashboard"><span>首页看板</span><span>Dashboard</span></a>
            </div>
            <div class="nav-group">
                <h3>商品管理</h3>
                <a class="nav-link <?php echo $currentTab === 'products' ? 'active' : ''; ?>" href="index.php?page=admin&tab=products"><span>商品维护</span><span>Products</span></a>
                <a class="nav-link <?php echo $currentTab === 'categories' ? 'active' : ''; ?>" href="index.php?page=admin&tab=categories"><span>分类管理</span><span>Categories</span></a>
                <a class="nav-link <?php echo $currentTab === 'inventory' ? 'active' : ''; ?>" href="index.php?page=admin&tab=inventory"><span>库存管理</span><span>Inventory</span></a>
            </div>
            <div class="nav-group">
                <h3>业务模块</h3>
                <a class="nav-link <?php echo $currentTab === 'orders' ? 'active' : ''; ?>" href="index.php?page=admin&tab=orders"><span>订单管理</span><span>Orders</span></a>
                <a class="nav-link <?php echo $currentTab === 'users' ? 'active' : ''; ?>" href="index.php?page=admin&tab=users"><span>用户管理</span><span>Users</span></a>
            </div>
            <div class="nav-group">
                <h3>系统设置</h3>
                <a class="nav-link <?php echo $currentTab === 'payment' ? 'active' : ''; ?>" href="index.php?page=admin&tab=payment"><span>支付管理</span><span>Payment</span></a>
                <a class="nav-link <?php echo $currentTab === 'settings' ? 'active' : ''; ?>" href="index.php?page=admin&tab=settings"><span>系统状态</span><span>Settings</span></a>
            </div>
        </nav>
    </aside>

    <main class="main">
        <?php if (is_array($flash)): ?>
            <div class="flash <?php echo shop_e((string) ($flash['type'] ?? 'success')); ?>">
                <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <?php
        $viewFile = __DIR__ . '/' . $currentTab . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo '<div class="flash error">未知标签页: ' . shop_e($currentTab) . '</div>';
        }
        ?>
    </main>
</div>

<script src="assets/js/admin.js"></script>
</body>
</html>
