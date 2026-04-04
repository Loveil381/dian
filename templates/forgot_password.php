<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/rate_limit.php';
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
    require __DIR__ . '/../actions/forgot_password_action.php';
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