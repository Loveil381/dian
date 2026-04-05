<?php declare(strict_types=1); ?>
<?php
$tabLabels = [
    'dashboard' => '仪表盘',
    'products' => '商品管理',
    'categories' => '分类管理',
    'inventory' => '库存管理',
    'orders' => '订单管理',
    'users' => '用户管理',
    'pages' => '页面管理',
    'coupons' => '优惠券',
    'payment' => '支付设置',
    'settings' => '系统设置',
    'logs' => '操作日志',
    'updates' => '更新中心',
];
$currentTabLabel = $tabLabels[$currentTab] ?? '后台';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo shop_e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-tokens.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
</head>
<body class="admin-body">
<header class="glass-nav admin-topbar">
    <div class="admin-topbar-left">
        <button class="btn-ghost admin-menu-btn" id="menuBtn" type="button" aria-label="打开后台导航">
            <span class="material-symbols-outlined" aria-hidden="true">menu</span>
        </button>
        <div class="admin-topbar-brand">
            <span class="badge badge-primary">Admin Control</span>
            <div class="admin-topbar-copy">
                <strong class="admin-topbar-title">魔女小店后台</strong>
                <span class="admin-topbar-subtitle"><?php echo shop_e($currentTabLabel); ?></span>
            </div>
        </div>
    </div>

    <div class="admin-topbar-right">
        <span class="badge admin-topbar-badge"><?php echo shop_e($currentTabLabel); ?></span>
        <a class="btn-ghost admin-topbar-link" href="index.php?page=home">
            <span class="material-symbols-outlined" aria-hidden="true">storefront</span>
            <span>返回前台</span>
        </a>
    </div>
</header>

<div class="admin-shell">
    <div class="admin-overlay" id="overlay"></div>

    <aside class="admin-sidebar" id="sidebar" aria-label="后台导航">
        <div class="admin-sidebar-head">
            <h1 class="admin-sidebar-title">魔女小店</h1>
            <p class="admin-sidebar-note">后台管理</p>
        </div>

        <nav class="admin-sidebar-nav">
            <div class="admin-nav-group">
                <p class="admin-nav-group-title">概览</p>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'dashboard' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=dashboard" <?php echo $currentTab === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">space_dashboard</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>仪表盘</strong>
                        <small>Dashboard</small>
                    </span>
                </a>
            </div>

            <div class="admin-nav-group">
                <p class="admin-nav-group-title">商品与库存</p>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'products' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=products" <?php echo $currentTab === 'products' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">inventory_2</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>商品管理</strong>
                        <small>Products</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'categories' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=categories" <?php echo $currentTab === 'categories' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">category</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>分类管理</strong>
                        <small>Categories</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'inventory' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=inventory" <?php echo $currentTab === 'inventory' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">warehouse</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>库存管理</strong>
                        <small>Inventory</small>
                    </span>
                </a>
            </div>

            <div class="admin-nav-group">
                <p class="admin-nav-group-title">订单与用户</p>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'orders' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=orders" <?php echo $currentTab === 'orders' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">receipt_long</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>订单管理</strong>
                        <small>Orders</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'users' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=users" <?php echo $currentTab === 'users' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">group</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>用户管理</strong>
                        <small>Users</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'coupons' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=coupons" <?php echo $currentTab === 'coupons' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">confirmation_number</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>优惠券</strong>
                        <small>Coupons</small>
                    </span>
                </a>
            </div>

            <div class="admin-nav-group">
                <p class="admin-nav-group-title">配置</p>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'pages' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=pages" <?php echo $currentTab === 'pages' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">description</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>页面管理</strong>
                        <small>Pages</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'payment' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=payment" <?php echo $currentTab === 'payment' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">payments</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>支付设置</strong>
                        <small>Payment</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'settings' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=settings" <?php echo $currentTab === 'settings' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">settings</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>系统设置</strong>
                        <small>Settings</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'logs' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=logs" <?php echo $currentTab === 'logs' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">policy</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>操作日志</strong>
                        <small>Audit Log</small>
                    </span>
                </a>
                <a class="nav-link admin-sidebar-link <?php echo $currentTab === 'updates' ? 'is-active active' : ''; ?>" href="index.php?page=admin&tab=updates" <?php echo $currentTab === 'updates' ? 'aria-current="page"' : ''; ?>>
                    <span class="material-symbols-outlined" aria-hidden="true">system_update_alt</span>
                    <span class="admin-sidebar-link-copy">
                        <strong>更新中心</strong>
                        <small>Updates</small>
                    </span>
                    <?php if (!empty($updateAvailable)): ?>
                        <span style="display:inline-block;width:8px;height:8px;background:var(--color-error,#ef4444);border-radius:50%;margin-left:auto;flex-shrink:0;" title="有新版本可用"></span>
                    <?php endif; ?>
                </a>
            </div>
        </nav>

        <div class="admin-sidebar-foot">
            <a class="btn-secondary admin-sidebar-store-link" href="index.php?page=home">
                <span class="material-symbols-outlined" aria-hidden="true">north_west</span>
                <span>查看前台首页</span>
            </a>
        </div>
    </aside>

    <main class="admin-main">
        <div class="admin-main-inner">
            <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
                <div class="flash admin-flash <?php echo shop_e((string) ($flash['type'] ?? 'success')); ?>">
                    <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
                </div>
            <?php endif; ?>

            <?php
            $viewFile = __DIR__ . '/' . $currentTab . '.php';
            if (file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo '<div class="flash error admin-flash">未找到后台页面：' . shop_e($currentTab) . '</div>';
            }
            ?>
        </div>
    </main>
</div>

<script src="assets/js/admin.js"></script>
</body>
</html>
