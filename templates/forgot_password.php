<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = '找回密码';
$currentPage = 'profile';
$error = '';
$success = '';
$reset_link = '';
$submitted_email = '';
$mail_warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $submitted_email = trim((string) ($_POST['email'] ?? ''));
    if ($submitted_email === '') {
        $error = '请输入注册邮箱。';
    } elseif (!filter_var($submitted_email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入正确的邮箱地址。';
    } else {
        $now = time();
        $last = (int) ($_SESSION['fp_last_request'] ?? 0);
        if ($now - $last < 60) {
            $wait = 60 - ($now - $last);
            $error = "操作过于频繁，请 {$wait} 秒后再试。";
        } else {
            $pdo = get_db_connection();
            if (!$pdo) {
                $error = '数据库连接失败，请稍后再试。';
            } else {
                $prefix = get_db_prefix();

                try {
                    $stmt = $pdo->prepare("SELECT id, email FROM `{$prefix}users` WHERE email = ? LIMIT 1");
                    $stmt->execute([$submitted_email]);
                    $user = $stmt->fetch();

                    $success = '如果该邮箱已注册，系统会发送重置链接；当前页面也会提供一份可直接使用的链接。';
                    if ($user) {
                        $token = bin2hex(random_bytes(32));
                        $hashed = hash('sha256', $token);
                        $update_stmt = $pdo->prepare("UPDATE `{$prefix}users` SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                        $update_stmt->execute([$hashed, (int) ($user['id'] ?? 0)]);

                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                        $script_dir = trim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
                        $base_path = $script_dir === '' ? '' : '/' . $script_dir;
                        $reset_link = $scheme . '://' . $host . $base_path . '/index.php?page=reset_password&token=' . urlencode($token);

                        $mail_subject = '魔女小店 — 密码重置';
                        $mail_body = "你好，\n\n";
                        $mail_body .= "请在 1 小时内打开以下链接完成密码重置：\n";
                        $mail_body .= $reset_link . "\n\n";
                        $mail_body .= "如果这不是你的操作，请忽略这封邮件。";

                        if (!shop_send_mail((string) ($user['email'] ?? ''), $mail_subject, $mail_body)) {
                            $mail_warning = '邮件发送失败，请直接使用下方链接';
                        }
                    }

                    $_SESSION['fp_last_request'] = $now;
                } catch (Throwable $exception) {
                    error_log('[shop] 处理找回密码请求失败: ' . $exception->getMessage());
                    $error = '密码重置链接生成失败，请稍后再试。';
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell auth-page">
    <section class="auth-card auth-card--wide">
        <h1 class="auth-title">找回密码</h1>
        <p class="auth-description">请输入注册邮箱。系统会优先尝试发送密码重置邮件；如果当前环境未配置邮件服务，页面下方也会显示一份可直接打开的重置链接。</p>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert--error"><?php echo shop_e($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert--success"><?php echo shop_e($success); ?></div>
        <?php endif; ?>

        <?php if ($mail_warning !== ''): ?>
            <div class="auth-alert auth-alert--warning"><?php echo shop_e($mail_warning); ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?page=forgot_password" class="auth-form">
            <?php echo csrf_field(); ?>
            <label class="auth-label" for="forgot_email">注册邮箱</label>
            <input class="auth-input" id="forgot_email" type="email" name="email" value="<?php echo shop_e($submitted_email); ?>" required placeholder="请输入注册邮箱">
            <button class="auth-btn" type="submit">发送重置链接</button>
        </form>

        <?php if ($reset_link !== ''): ?>
            <div class="auth-helper-box">
                <div class="auth-helper-title">密码重置链接</div>
                <div class="auth-helper-content">
                    <a class="auth-link" href="<?php echo shop_e($reset_link); ?>"><?php echo shop_e($reset_link); ?></a>
                </div>
            </div>
        <?php endif; ?>

        <div class="auth-links">
            <a class="auth-link" href="index.php?page=auth&action=login">返回登录</a>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
