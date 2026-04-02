<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = '重置密码';
$currentPage = 'profile';
$error = '';
$token = trim((string) ($_GET['token'] ?? ($_POST['token'] ?? '')));

$pdo = get_db_connection();
$prefix = get_db_prefix();
$user = null;

if ($pdo && $token !== '') {
    try {
        $hashed = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id, email, reset_expires FROM `{$prefix}users` WHERE reset_token = ? AND reset_expires IS NOT NULL AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$hashed]);
        $user = $stmt->fetch();
    } catch (Throwable $exception) {
        error_log('[shop] 校验重置密码 token 失败: ' . $exception->getMessage());
        $error = '重置链接校验失败，请稍后再试。';
    }
} elseif ($token === '') {
    $error = '重置链接无效，请重新申请。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    csrf_verify();

    $new_password = (string) ($_POST['password'] ?? '');
    $confirm_password = (string) ($_POST['password_confirm'] ?? '');

    if (!$pdo) {
        $error = '数据库连接失败，请稍后再试。';
    } elseif (!$user) {
        $error = '重置链接已失效，请重新申请。';
    } elseif ($new_password === '' || $confirm_password === '') {
        $error = '请完整填写新密码。';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码至少需要 6 位。';
    } elseif (!hash_equals($new_password, $confirm_password)) {
        $error = '两次输入的密码不一致。';
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET password_hash = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$password_hash, (int) ($user['id'] ?? 0)]);

            $_SESSION['auth_flash'] = [
                'type' => 'success',
                'message' => '密码已重置成功，请使用新密码登录。',
            ];
            header('Location: index.php?page=auth&action=login');
            exit;
        } catch (Throwable $exception) {
            error_log('[shop] 重置密码失败: ' . $exception->getMessage());
            $error = '密码重置失败，请稍后再试。';
        }
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell auth-page">
    <section class="auth-card auth-card--wide">
        <h1 class="auth-title">重置密码</h1>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert--error"><?php echo shop_e($error); ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p class="auth-description">正在为邮箱 <strong><?php echo shop_e((string) ($user['email'] ?? '')); ?></strong> 重置密码。</p>

            <form method="post" action="index.php?page=reset_password&token=<?php echo urlencode($token); ?>" class="auth-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo shop_e($token); ?>">

                <label class="auth-label" for="reset_password">新密码</label>
                <input class="auth-input" id="reset_password" type="password" name="password" required placeholder="请输入新密码">

                <label class="auth-label" for="reset_password_confirm">确认新密码</label>
                <input class="auth-input" id="reset_password_confirm" type="password" name="password_confirm" required placeholder="请再次输入新密码">

                <button class="auth-btn" type="submit">确认重置密码</button>
            </form>
        <?php else: ?>
            <div class="auth-description">当前链接不可用，请返回找回密码页面重新申请新的重置链接。</div>
        <?php endif; ?>

        <div class="auth-links">
            <a class="auth-link" href="index.php?page=forgot_password">重新获取链接</a>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
