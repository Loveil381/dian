<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$pageTitle = '个人中心 - 魔女的小店';
$currentPage = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'save_profile') {
    require __DIR__ . '/../actions/profile_action.php';
    // profile_action.php 总是 redirect + exit，不会到达这里
}

$isLoggedIn = isset($_SESSION['user_id']);
$userName = (string) ($_SESSION['user_name'] ?? '游客');
$userUsername = (string) ($_SESSION['user_username'] ?? '');
$userId = (string) ($_SESSION['user_id'] ?? '');
$userPhone = '';
$userAddress = '';

$flash = $_SESSION['profile_flash'] ?? null;
unset($_SESSION['profile_flash']);
$messageType = (string) ($flash['type'] ?? '');
$messageText = (string) ($flash['message'] ?? '');

$pdo = get_db_connection();
$prefix = get_db_prefix();

if ($isLoggedIn && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $userPhone = (string) ($row['phone'] ?? '');
            $userAddress = (string) ($row['address'] ?? '');
        }
    } catch (PDOException $e) {
        shop_log('error', '个人中心数据查询失败', ['message' => $e->getMessage()]);
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell profile-page">
    <section class="card profile-hero">
        <div class="profile-hero-panel">
            <?php if ($isLoggedIn): ?>
                <div class="profile-hero-avatar">
                    <?php echo shop_e(mb_substr($userName, 0, 1)); ?>
                </div>
                <div class="profile-hero-copy">
                    <span class="badge badge-primary">个人中心</span>
                    <h1 class="profile-hero-title"><?php echo shop_e($userName); ?></h1>
                    <p class="profile-hero-meta">ID: <?php echo shop_e($userId); ?> | 用户名：<?php echo shop_e($userUsername); ?></p>
                </div>
            <?php else: ?>
                <a href="index.php?page=auth&action=login" class="profile-guest-entry">
                    <div class="profile-hero-avatar profile-hero-avatar--guest">
                        <span class="material-symbols-outlined" aria-hidden="true">person</span>
                    </div>
                    <div class="profile-hero-copy">
                        <span class="badge">访客模式</span>
                        <h1 class="profile-hero-title">游客用户</h1>
                        <p class="profile-hero-meta">IP: <?php echo shop_e((string) ($_SERVER['REMOTE_ADDR'] ?? '未知')); ?></p>
                    </div>
                    <span class="material-symbols-outlined profile-guest-arrow" aria-hidden="true">arrow_forward</span>
                </a>
            <?php endif; ?>
        </div>
    </section>

    <div class="profile-layout profile-layout--single">
        <section class="card profile-card">
            <div class="profile-section-heading">
                <div class="profile-section-title-wrap">
                    <span class="material-symbols-outlined profile-section-icon" aria-hidden="true">home_pin</span>
                    <h2 class="profile-section-title">收货信息</h2>
                </div>
            </div>

            <?php if ($messageText !== ''): ?>
                <div class="flash <?php echo shop_e($messageType); ?>">
                    <?php echo shop_e($messageText); ?>
                </div>
            <?php endif; ?>

            <form method="post" class="profile-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save_profile">

                <div class="profile-field">
                    <label class="font-label profile-field-label" for="profile_name">收货人</label>
                    <input class="input" id="profile_name" type="text" name="name" value="<?php echo shop_e($userName); ?>" placeholder="请输入收货人姓名">
                </div>

                <div class="profile-field">
                    <label class="font-label profile-field-label" for="profile_phone">手机号</label>
                    <input class="input" id="profile_phone" type="text" name="phone" value="<?php echo shop_e($userPhone); ?>" placeholder="请输入联系电话">
                </div>

                <div class="profile-field">
                    <label class="font-label profile-field-label" for="profile_address">地址</label>
                    <textarea class="input" id="profile_address" name="address" placeholder="请输入详细收货地址"><?php echo shop_e($userAddress); ?></textarea>
                </div>

                <button class="btn-primary profile-submit" type="submit">保存收货信息</button>
            </form>
        </section>

        <section class="card profile-card">
            <div class="profile-section-heading">
                <div class="profile-section-title-wrap">
                    <span class="material-symbols-outlined profile-section-icon" aria-hidden="true">bolt</span>
                    <h2 class="profile-section-title">快捷入口</h2>
                </div>
            </div>

            <div class="profile-shortcuts">
                <a href="index.php?page=products" class="profile-shortcut">
                    <div class="profile-shortcut-copy">
                        <strong>继续购物</strong>
                        <span>浏览全部商品并继续下单</span>
                    </div>
                    <span class="material-symbols-outlined profile-shortcut-arrow" aria-hidden="true">arrow_forward</span>
                </a>
                <a href="index.php?page=orders" class="profile-shortcut">
                    <div class="profile-shortcut-copy">
                        <strong>我的订单</strong>
                        <span>查看订单状态与历史记录</span>
                    </div>
                    <span class="material-symbols-outlined profile-shortcut-arrow" aria-hidden="true">arrow_forward</span>
                </a>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="profile-actions">
                    <a href="index.php?page=auth&action=logout" class="btn-ghost profile-logout">退出登录</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
