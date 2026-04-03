<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';

$pageTitle = '个人中心 - 魔女小店';
$currentPage = 'profile';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = (string) ($_SESSION['user_name'] ?? '游客');
$userUsername = (string) ($_SESSION['user_username'] ?? '');
$userId = (string) ($_SESSION['user_id'] ?? '');
$userPhone = '';
$userAddress = '';
$messageHtml = '';

$pdo = get_db_connection();
$prefix = get_db_prefix();

if ($isLoggedIn && $pdo instanceof PDO) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'save_profile') {
        csrf_verify();
        $userName = trim((string) ($_POST['name'] ?? ''));
        $userPhone = trim((string) ($_POST['phone'] ?? ''));
        $userAddress = trim((string) ($_POST['address'] ?? ''));

        try {
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$userName, $userPhone, $userAddress, $_SESSION['user_id']]);
            $_SESSION['user_name'] = $userName;
            $messageHtml = '<div style="margin-top:16px; padding: 10px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 14px;">收货信息已保存。</div>';
        } catch (PDOException $e) {
            shop_log('error', '保存收货信息失败', ['message' => $e->getMessage()]);
            $messageHtml = '<div style="margin-top:16px; padding: 10px; background: #fef2f2; color: #b91c1c; border-radius: 8px; font-size: 14px;">保存失败，请稍后重试。</div>';
        }
    }

    try {
        $stmt = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row)) {
            $userPhone = (string) ($row['phone'] ?? '');
            $userAddress = (string) ($row['address'] ?? '');
        }
    } catch (PDOException $e) {
        shop_log('error', '个人中心数据操作失败', ['message' => $e->getMessage()]);
    }
}

if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'save_profile') {
    csrf_verify();
    $messageHtml = '<div style="margin-top:16px; padding: 10px; background: #fffbeb; color: #d97706; border-radius: 8px; font-size: 14px;">访客状态下无法永久保存收货信息，请注册账号。</div>';
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <section class="page-hero">
        <div class="hero-panel">
            <?php if ($isLoggedIn): ?>
                <span class="hero-kicker">个人中心</span>
                <h1 class="hero-title">欢迎回来，<?php echo shop_e($userName); ?></h1>
                <p style="color: #64748b; margin-top: 10px; line-height: 1.6;">您的订单和收货信息会与账号同步保存。</p>
            <?php else: ?>
                <span class="hero-kicker">游客中心</span>
                <h1 class="hero-title">欢迎来到魔女小店</h1>
                <p style="color: #64748b; margin-top: 10px; line-height: 1.6;">当前是访客模式。您可以注册账号来永久保存订单记录。</p>
            <?php endif; ?>
        </div>
    </section>

    <div class="profile-layout">
        <section class="panel">
            <div class="profile-identity">
                <?php if ($isLoggedIn): ?>
                    <div class="profile-avatar" style="background: #2563eb; color: #fff;">
                        <?php echo shop_e(mb_substr($userName, 0, 1)); ?>
                    </div>
                    <div>
                        <div class="profile-name"><?php echo shop_e($userName); ?></div>
                        <div class="profile-meta">ID: <?php echo shop_e($userId); ?> | 用户名: <?php echo shop_e($userUsername); ?></div>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=auth&action=register" style="text-decoration: none; display: flex; align-items: center; gap: 16px; cursor: pointer;">
                        <div class="profile-avatar" style="background: #e2e8f0; color: #475569;">游</div>
                        <div>
                            <div class="profile-name" style="color: #0f172a;">访客（点击注册或登录）</div>
                            <div class="profile-meta">IP: <?php echo shop_e((string) ($_SERVER['REMOTE_ADDR'] ?? '未知')); ?></div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <h2 class="section-title">收货信息</h2>
            <?php echo $messageHtml; ?>

            <form method="post" style="margin-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save_profile">
                <input type="text" name="name" value="<?php echo shop_e($userName); ?>" placeholder="收货人姓名" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <input type="text" name="phone" value="<?php echo shop_e($userPhone); ?>" placeholder="手机号" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <textarea name="address" placeholder="详细收货地址（省市区/街道/门牌号）" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-height: 60px; resize: vertical;"><?php echo shop_e($userAddress); ?></textarea>
                <button type="submit" style="padding: 10px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; font-weight: bold;">保存默认收货信息</button>
            </form>

            <div style="margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 12px; color: #0f172a;">其他操作</h3>
                <ul class="link-list" style="margin: 0;">
                    <li><a href="index.php?page=products">继续购物</a></li>
                    <?php if ($isLoggedIn): ?>
                        <li><a href="index.php?page=auth&action=logout" style="color: #dc2626;">退出登录</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
