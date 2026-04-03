<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
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

if ($pdo instanceof PDO && $token !== '') {
    try {
        $hashed = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT id, email, reset_expires FROM `{$prefix}users` WHERE reset_token = ? AND reset_expires IS NOT NULL AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$hashed]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        shop_log('error', '查询重置密码 token 失败', ['message' => $exception->getMessage()]);
        $error = '重置链接校验失败，请稍后重试。';
    }
} elseif ($token === '') {
    $error = '重置链接无效，请重新申请。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    csrf_verify();

    $new_password = (string) ($_POST['password'] ?? '');
    $confirm_password = (string) ($_POST['password_confirm'] ?? '');

    if (!$pdo instanceof PDO) {
        $error = '数据库连接失败，请稍后重试。';
    } elseif (!is_array($user)) {
        $error = '重置链接已失效，请重新申请。';
    } elseif ($new_password === '' || $confirm_password === '') {
        $error = '请输入并确认新密码。';
    } elseif (strlen($new_password) < 6) {
        $error = '新密码长度不能少于 6 位。';
    } elseif (!hash_equals($new_password, $confirm_password)) {
        $error = '两次输入的密码不一致。';
    } else {
        try {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET password_hash = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$password_hash, (int) ($user['id'] ?? 0)]);

            $_SESSION['auth_flash'] = [
                'type' => 'success',
                'message' => '密码重置成功，请使用新密码登录。',
            ];
            header('Location: index.php?page=auth&action=login');
            exit;
        } catch (Throwable $exception) {
            shop_log('error', '重置密码失败', ['message' => $exception->getMessage()]);
            $error = '密码重置失败，请稍后重试。';
        }
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell auth-page reset-password-page">
    <section class="card auth-card auth-card--wide reset-password-card">
        <div class="auth-shell reset-password-shell">
            <div class="auth-brand reset-password-brand">
                <div class="auth-brand-mark reset-password-mark">
                    <span class="material-symbols-outlined auth-brand-icon" aria-hidden="true">shield_lock</span>
                </div>
                <p class="auth-brand-note">Reset Access</p>
                <h1 class="auth-title">重置密码</h1>
                <?php if (is_array($user)): ?>
                    <p class="auth-description">正在为邮箱 <strong><?php echo shop_e((string) ($user['email'] ?? '')); ?></strong> 设置新的登录密码。</p>
                <?php else: ?>
                    <p class="auth-description">重置链接需要在有效期内使用，如果提示失效，请重新申请新的重置链接。</p>
                <?php endif; ?>
            </div>

            <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert--error"><?php echo shop_e($error); ?></div>
            <?php endif; ?>

            <?php if (is_array($user)): ?>
                <div class="reset-password-panel">
                    <form method="post" action="index.php?page=reset_password&token=<?php echo urlencode($token); ?>" class="auth-form reset-password-form">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="token" value="<?php echo shop_e($token); ?>">

                        <div class="auth-field">
                            <label class="font-label auth-label" for="reset_password">新密码</label>
                            <div class="auth-field-control">
                                <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">lock</span>
                                <input class="input auth-input" id="reset_password" type="password" name="password" required placeholder="请输入新密码">
                            </div>
                        </div>

                        <div class="auth-field">
                            <label class="font-label auth-label" for="reset_password_confirm">确认新密码</label>
                            <div class="auth-field-control">
                                <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">verified_user</span>
                                <input class="input auth-input" id="reset_password_confirm" type="password" name="password_confirm" required placeholder="请再次输入新密码">
                            </div>
                        </div>

                        <button class="btn-primary auth-btn reset-password-submit" type="submit">
                            <span class="material-symbols-outlined" aria-hidden="true">magic_button</span>
                            <span>确认重置密码</span>
                        </button>
                    </form>

                    <div class="reset-password-tips">
                        <div class="reset-password-tip">
                            <span class="material-symbols-outlined" aria-hidden="true">password</span>
                            <span>密码长度至少 6 位</span>
                        </div>
                        <div class="reset-password-tip">
                            <span class="material-symbols-outlined" aria-hidden="true">timer</span>
                            <span>请在链接有效期内完成操作</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card reset-password-empty">
                    <span class="material-symbols-outlined reset-password-empty-icon" aria-hidden="true">error</span>
                    <h2 class="reset-password-empty-title">链接无法使用</h2>
                    <p class="reset-password-empty-copy">当前重置链接无效、已过期或已使用，请重新申请新的重置链接。</p>
                </div>
            <?php endif; ?>

            <div class="auth-links auth-links--center">
                <a class="auth-link" href="index.php?page=forgot_password">重新申请重置链接</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
