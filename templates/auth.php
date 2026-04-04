<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$action = (string) ($_GET['action'] ?? 'login');
$error = '';
$success = '';
$flash = $_SESSION['auth_flash'] ?? null;
unset($_SESSION['auth_flash']);

if (isset($_GET['checkout_blocked'])) {
    $error = '您的账号已被禁用，无法完成下单，请联系客服。';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/../actions/auth_action.php';
}

if ($action === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_username']);
    header('Location: index.php?page=profile');
    exit;
}

if ($action !== 'register') {
    $action = 'login';
}

$pageTitle = $action === 'login' ? '用户登录 - 魔女小店' : '用户注册 - 魔女小店';
$currentPage = 'profile';

include __DIR__ . '/header.php';
?>

<main class="page-shell auth-page">
    <section class="card-hero auth-card">
        <div class="auth-shell">
            <div class="auth-brand">
                <div class="auth-brand-mark">
                    <span class="material-symbols-outlined auth-brand-icon" aria-hidden="true">auto_awesome</span>
                </div>
                <p class="auth-brand-note">欢迎来到魔女小店</p>
                <h1 class="auth-title"><?php echo $action === 'login' ? '欢迎回来' : '创建你的购物账号'; ?></h1>
                <p class="auth-description"><?php echo $action === 'login' ? '登录后即可查看购物车、订单记录并保存收货信息。' : '注册账号后可同步订单状态、保存配送信息，并获得更稳定的购物体验。'; ?></p>
            </div>

            <div class="auth-tabs" aria-label="登录注册切换">
                <a href="index.php?page=auth&action=login" class="<?php echo $action === 'login' ? 'btn-primary' : 'btn-ghost'; ?> auth-tab">登录</a>
                <a href="index.php?page=auth&action=register" class="<?php echo $action === 'register' ? 'btn-primary' : 'btn-ghost'; ?> auth-tab">注册</a>
            </div>

            <div class="auth-messages">
                <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
                    <div class="flash <?php echo ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success'; ?>">
                        <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="flash error"><?php echo shop_e($error); ?></div>
                <?php endif; ?>

                <?php if ($success !== ''): ?>
                    <div class="flash success"><?php echo shop_e($success); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($action === 'login'): ?>
                <form method="post" action="index.php?page=auth&action=login" class="auth-form">
                    <?php echo csrf_field(); ?>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="login_id">用户名 / 邮箱 / ID</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">person</span>
                            <input class="input auth-input" id="login_id" type="text" name="login_id" required placeholder="请输入用户名、邮箱或 ID">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="password">密码</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">lock</span>
                            <input class="input auth-input" id="password" type="password" name="password" required placeholder="请输入密码">
                        </div>
                    </div>

                    <div class="auth-links auth-links--split auth-links--actions">
                        <label class="auth-remember">
                            <input class="auth-checkbox-input" type="checkbox">
                            <span class="auth-checkbox"></span>
                            <span>记住我</span>
                        </label>
                        <a class="auth-link" href="index.php?page=forgot_password">忘记密码？</a>
                    </div>

                    <button class="btn-primary auth-btn auth-submit" type="submit">立即登录</button>

                    <div class="auth-links auth-links--center">
                        <span>还没有账号？ <a class="auth-link" href="index.php?page=auth&action=register">立即注册</a></span>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" action="index.php?page=auth&action=register" class="auth-form">
                    <?php echo csrf_field(); ?>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="username">用户名</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">person</span>
                            <input class="input auth-input" id="username" type="text" name="username" required placeholder="请输入用户名">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="name">昵称</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">badge</span>
                            <input class="input auth-input" id="name" type="text" name="name" required placeholder="请输入昵称">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="email">邮箱</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">mail</span>
                            <input class="input auth-input" id="email" type="email" name="email" required placeholder="请输入邮箱">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="register_password">密码</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">lock</span>
                            <input class="input auth-input" id="register_password" type="password" name="password" required placeholder="请输入密码">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label class="font-label auth-label" for="register_password_confirm">确认密码</label>
                        <div class="auth-field-control">
                            <span class="material-symbols-outlined auth-field-icon" aria-hidden="true">verified_user</span>
                            <input class="input auth-input" id="register_password_confirm" type="password" name="password_confirm" required placeholder="请再次输入密码">
                        </div>
                    </div>

                    <button class="btn-primary auth-btn auth-submit" type="submit">注册账号</button>

                    <div class="auth-links auth-links--center">
                        <span>已有账号？ <a class="auth-link" href="index.php?page=auth&action=login">返回登录</a></span>
                    </div>
                </form>
            <?php endif; ?>

            <footer class="auth-footer">
                <p class="auth-privacy">提交即表示你同意平台用于账号登录、订单同步与收货信息保存所需的基础处理。</p>
            </footer>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>