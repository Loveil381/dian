<?php declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = '重置密码';
$currentPage = 'profile';
$error = '';
$success = '';
$token = trim((string) ($_GET['token'] ?? ($_POST['token'] ?? '')));

$pdo = get_db_connection();
$prefix = get_db_prefix();
$user = null;

if ($pdo && $token !== '') {
    try {
        $stmt = $pdo->prepare("SELECT id, email, reset_expires FROM `{$prefix}users` WHERE reset_token = ? AND reset_expires IS NOT NULL AND reset_expires > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
    } catch (Throwable $exception) {
        error_log('[shop] 校验重置密码 token 失败: ' . $exception->getMessage());
        $error = '重置链接校验失败，请稍后再试。';
    }
} elseif ($token === '') {
    $error = '重置链接无效，请重新生成。';
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
                'message' => '密码已重置，请使用新密码登录。',
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

<main class="page-shell" style="display: flex; justify-content: center; align-items: center; min-height: 70vh; padding: 20px;">
    <div style="background: #ffffff; padding: 30px; border-radius: 12px; width: 100%; max-width: 520px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
        <h1 style="margin: 0 0 14px; font-size: 28px; color: #0f172a;">重置密码</h1>

        <?php if ($error !== ''): ?>
            <div style="background: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 14px;">
                <?php echo shop_e($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <p style="margin: 0 0 20px; color: #64748b; line-height: 1.7;">当前正在为邮箱 <strong><?php echo shop_e((string) ($user['email'] ?? '')); ?></strong> 重置密码。</p>

            <form method="post" action="index.php?page=reset_password&token=<?php echo urlencode($token); ?>" style="display: flex; flex-direction: column; gap: 15px;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="token" value="<?php echo shop_e($token); ?>">
                <div>
                    <label style="display: block; margin-bottom: 6px; color: #475569; font-size: 14px;">新密码</label>
                    <input type="password" name="password" required placeholder="请输入新的登录密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 6px; color: #475569; font-size: 14px;">确认新密码</label>
                    <input type="password" name="password_confirm" required placeholder="请再次输入新密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <button type="submit" style="padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">确认重置密码</button>
            </form>
        <?php else: ?>
            <div style="color: #64748b; line-height: 1.7;">
                当前链接无法继续使用，你可以重新申请新的重置链接。
            </div>
        <?php endif; ?>

        <div style="margin-top: 18px; font-size: 14px; color: #64748b;">
            <a href="index.php?page=forgot_password" style="color: #2563eb; text-decoration: none;">重新生成链接</a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
