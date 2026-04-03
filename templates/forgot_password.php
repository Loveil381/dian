<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = '忘记密码';
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
            $error = "请求过于频繁，请 {$wait} 秒后重试。";
        } else {
            $pdo = get_db_connection();
            if (!$pdo instanceof PDO) {
                $error = '数据库连接失败，请稍后重试。';
            } else {
                $prefix = get_db_prefix();

                try {
                    $stmt = $pdo->prepare("SELECT id, email FROM `{$prefix}users` WHERE email = ? LIMIT 1");
                    $stmt->execute([$submitted_email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    $success = '如果该邮箱已注册，我们会发送一封密码重置邮件。为保护账号安全，页面不会提示邮箱是否存在。';
                    if (is_array($user)) {
                        $token = bin2hex(random_bytes(32));
                        $hashed = hash('sha256', $token);

                        $update_stmt = $pdo->prepare("UPDATE `{$prefix}users` SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
                        $update_stmt->execute([$hashed, (int) ($user['id'] ?? 0)]);

                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                        $script_dir = trim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
                        $base_path = $script_dir === '' ? '' : '/' . $script_dir;
                        $reset_link = $scheme . '://' . $host . $base_path . '/index.php?page=reset_password&token=' . urlencode($token);

                        $mail_subject = '魔女小店 - 密码重置';
                        $mail_body = "你好，\n\n";
                        $mail_body .= "请在 1 小时内使用下面的链接重置密码：\n";
                        $mail_body .= $reset_link . "\n\n";
                        $mail_body .= "如果这不是你的操作，请忽略本邮件。";

                        if (!shop_send_mail((string) ($user['email'] ?? ''), $mail_subject, $mail_body)) {
                            $mail_warning = '邮件发送失败，请使用页面显示的重置链接。';
                        }
                    }

                    $_SESSION['fp_last_request'] = $now;
                } catch (Throwable $exception) {
                    shop_log('error', '处理忘记密码请求失败', ['message' => $exception->getMessage()]);
                    $error = '重置邮件发送失败，请稍后重试。';
                }
            }
        }
    }
}

include __DIR__ . '/header.php';
?>

<main class="page-shell auth-page forgot-password-page">
    <section class="card auth-card auth-card--wide forgot-password-card">
        <div class="auth-shell forgot-password-shell">
            <div class="auth-brand forgot-password-brand">
                <div class="auth-brand-mark forgot-password-mark">
                    <span class="material-symbols-outlined auth-brand-icon" aria-hidden="true">auto_fix_high</span>
                </div>
                <p class="auth-brand-note">Password Recovery</p>
                <h1 class="auth-title">忘记密码</h1>
                <p class="auth-description">输入注册邮箱，我们会为你生成密码重置链接。若邮件发送失败，页面仍会显示可用的重置地址。</p>
            </div>

            <div class="forgot-password-panel">
                <?php if ($error !== ''): ?>
                    <div class="auth-alert auth-alert--error"><?php echo shop_e($error); ?></div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="auth-alert auth-alert--success"><?php echo shop_e($success); ?></div>
                <?php endif; ?>

                <?php if ($mail_warning !== ''): ?>
                    <div class="auth-alert auth-alert--warning"><?php echo shop_e($mail_warning); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php?page=forgot_password" class="auth-form forgot-password-form">
                    <?php echo csrf_field(); ?>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="forgot_email">注册邮箱</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">mail</span>
                            <input class="input auth-input" id="forgot_email" type="email" name="email" value="<?php echo shop_e($submitted_email); ?>" required placeholder="请输入注册邮箱">
                        </div>
                    </div>

                    <button class="btn-primary auth-btn forgot-password-submit" type="submit">
                        <span class="material-symbols-outlined" aria-hidden="true">send</span>
                        <span>发送重置链接</span>
                    </button>
                </form>

                <?php if ($reset_link !== ''): ?>
                    <div class="auth-helper-box forgot-password-helper">
                        <div class="auth-helper-title">密码重置链接</div>
                        <div class="auth-helper-content">
                            <a class="auth-link" href="<?php echo shop_e($reset_link); ?>"><?php echo shop_e($reset_link); ?></a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="forgot-password-meta">
                <div class="forgot-password-meta-item">
                    <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
                    <span>安全验证</span>
                </div>
                <div class="forgot-password-meta-item">
                    <span class="material-symbols-outlined" aria-hidden="true">shield_lock</span>
                    <span>隐私保护</span>
                </div>
                <div class="forgot-password-meta-item">
                    <span class="material-symbols-outlined" aria-hidden="true">schedule</span>
                    <span>一小时有效</span>
                </div>
            </div>

            <div class="auth-links auth-links--center">
                <a class="auth-link" href="index.php?page=auth&action=login">返回登录</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>