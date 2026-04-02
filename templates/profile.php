<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/../includes/csrf.php';

$pageTitle = '魔女小店 - 个人中心';
$currentPage = 'profile';

$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? '访客';
$userUsername = $_SESSION['user_username'] ?? '';
$userId = $_SESSION['user_id'] ?? '';

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <section class="page-hero">
        <div class="hero-panel">
            <?php if ($isLoggedIn): ?>
                <span class="hero-kicker">个人中心</span>
                <h1 class="hero-title">欢迎回来，<?php echo shop_e($userName); ?></h1>
                <p style="color: #64748b; margin-top: 10px; line-height: 1.6;">您的订单已与账号绑定同步。</p>
            <?php else: ?>
                <span class="hero-kicker">游客中心</span>
                <h1 class="hero-title">欢迎来到魔女小店</h1>
                <p style="color: #64748b; margin-top: 10px; line-height: 1.6;">目前为访客模式。<br>您可以注册账号来永久保存订单记录。</p>
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
                        <div class="profile-meta">ID: <?php echo shop_e((string)$userId); ?> | 用户名: <?php echo shop_e($userUsername); ?></div>
                    </div>
                <?php else: ?>
                    <a href="index.php?page=auth&action=register" style="text-decoration: none; display: flex; align-items: center; gap: 16px; cursor: pointer;">
                        <div class="profile-avatar" style="background: #e2e8f0; color: #475569;">游</div>
                        <div>
                            <div class="profile-name" style="color: #0f172a;">访客 (点击注册/登录)</div>
                            <div class="profile-meta">IP: <?php echo shop_e($_SERVER['REMOTE_ADDR'] ?? '未知'); ?></div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel">
            <h2 class="section-title">收货信息</h2>
            
            <?php
            $pdo = get_db_connection();
            $prefix = get_db_prefix();
            $userPhone = '';
            $userAddress = '';
            $userName = $_SESSION['user_name'] ?? '';
            
            if ($isLoggedIn && $pdo) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
                    csrf_verify();
                    $userName = trim($_POST['name'] ?? '');
                    $userPhone = trim($_POST['phone'] ?? '');
                    $userAddress = trim($_POST['address'] ?? '');
                    
                    try {
                        $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET name=?, phone=?, address=? WHERE id=?");
                        $stmt->execute([$userName, $userPhone, $userAddress, $_SESSION['user_id']]);
                        $_SESSION['user_name'] = $userName; // 同步更新会话中的昵称。
                        echo '<div style="margin-top:16px; padding: 10px; background: #ecfdf5; color: #047857; border-radius: 8px; font-size: 14px;">收货信息已保存</div>';
                    } catch (PDOException $e) {}
                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id=?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $row = $stmt->fetch();
                        if ($row) {
                            $userPhone = $row['phone'] ?? '';
                            $userAddress = $row['address'] ?? '';
                        }
                    } catch (PDOException $e) {}
                }
            } else if (!$isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
                 csrf_verify();
                 // 游客提交时仅给出提示，不落库存储。
                 echo '<div style="margin-top:16px; padding: 10px; background: #fffbeb; color: #d97706; border-radius: 8px; font-size: 14px;">访客状态下无法永久保存收货信息，请注册账号。</div>';
            }
            ?>
            
            <form method="post" style="margin-top: 16px; display: flex; flex-direction: column; gap: 12px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="save_profile">
                <input type="text" name="name" value="<?php echo shop_e($userName); ?>" placeholder="收货人姓名" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <input type="text" name="phone" value="<?php echo shop_e($userPhone); ?>" placeholder="手机号" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px;">
                <textarea name="address" placeholder="详细收货地址 (省市区/街道/门牌号)" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-height: 60px; resize: vertical;"><?php echo shop_e($userAddress); ?></textarea>
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
