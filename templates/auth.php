<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../data/products.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$action = (string) ($_GET['action'] ?? 'login');
$error = '';
$success = '';
$flash = $_SESSION['auth_flash'] ?? null;
unset($_SESSION['auth_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $pdo = get_db_connection();
    if (!$pdo instanceof PDO) {
        $error = '数据库连接失败，请稍后再试。';
    } else {
        $prefix = get_db_prefix();

        if ($action === 'register') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($username === '' || $name === '' || $email === '' || $password === '') {
                $error = '请完整填写注册信息。';
            } elseif (strlen($password) < 6) {
                $error = '密码至少需要 6 位。';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '请输入正确的邮箱地址。';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ? OR email = ? LIMIT 1");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetch()) {
                        $error = '用户名或邮箱已被使用。';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, password_hash, status, level, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', 'member', NOW(), NOW())");
                        $stmt->execute([$username, $name, $email, $hash]);

                        $success = '注册成功，请使用新账号登录。';
                        $action = 'login';
                    }
                } catch (PDOException $exception) {
                    shop_log('error', '用户注册失败', ['message' => $exception->getMessage()]);
                    $error = '注册失败，请稍后再试。';
                }
            }
        } else {
            $login_id = trim((string) ($_POST['login_id'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            if ($login_id === '' || $password === '') {
                $error = '请输入登录账号和密码。';
            } else {
                try {
                    if (is_numeric($login_id)) {
                        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}users` WHERE id = ? OR username = ? OR email = ? LIMIT 1");
                        $stmt->execute([$login_id, $login_id, $login_id]);
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}users` WHERE username = ? OR email = ? LIMIT 1");
                        $stmt->execute([$login_id, $login_id]);
                    }

                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($user && password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                        $update_stmt = $pdo->prepare("UPDATE `{$prefix}users` SET last_login = NOW() WHERE id = ?");
                        $update_stmt->execute([(int) ($user['id'] ?? 0)]);

                        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
                        $_SESSION['user_name'] = (string) ($user['name'] ?? '');
                        $_SESSION['user_username'] = (string) ($user['username'] ?? '');

                        header('Location: index.php?page=profile');
                        exit;
                    }

                    $error = '登录信息不正确。';
                } catch (PDOException $exception) {
                    shop_log('error', '用户登录失败', ['message' => $exception->getMessage()]);
                    $error = '登录失败，请稍后再试。';
                }
            }
        }
    }
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
    <section class="auth-card">
        <h1 class="auth-title"><?php echo $action === 'login' ? '用户登录' : '用户注册'; ?></h1>

        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
            <div class="auth-alert <?php echo ($flash['type'] ?? 'success') === 'error' ? 'auth-alert--error' : 'auth-alert--success'; ?>">
                <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert--error"><?php echo shop_e($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert--success"><?php echo shop_e($success); ?></div>
        <?php endif; ?>

        <?php if ($action === 'login'): ?>
            <form method="post" action="index.php?page=auth&action=login" class="auth-form">
                <?php echo csrf_field(); ?>
                <label class="auth-label" for="login_id">用户名 / 邮箱 / ID</label>
                <input class="auth-input" id="login_id" type="text" name="login_id" required placeholder="请输入用户名、邮箱或 ID">

                <label class="auth-label" for="password">密码</label>
                <input class="auth-input" id="password" type="password" name="password" required placeholder="请输入密码">

                <button class="auth-btn" type="submit">登录</button>

                <div class="auth-links auth-links--split">
                    <a class="auth-link" href="index.php?page=forgot_password">忘记密码？</a>
                    <span>还没有账号？<a class="auth-link" href="index.php?page=auth&action=register">立即注册</a></span>
                </div>
            </form>
        <?php else: ?>
            <form method="post" action="index.php?page=auth&action=register" class="auth-form">
                <?php echo csrf_field(); ?>
                <label class="auth-label" for="username">用户名</label>
                <input class="auth-input" id="username" type="text" name="username" required placeholder="请输入用户名">

                <label class="auth-label" for="name">昵称</label>
                <input class="auth-input" id="name" type="text" name="name" required placeholder="请输入昵称">

                <label class="auth-label" for="email">邮箱</label>
                <input class="auth-input" id="email" type="email" name="email" required placeholder="请输入邮箱">

                <label class="auth-label" for="register_password">密码</label>
                <input class="auth-input" id="register_password" type="password" name="password" required placeholder="请输入密码">

                <button class="auth-btn" type="submit">注册</button>

                <div class="auth-links">
                    <span>已有账号？<a class="auth-link" href="index.php?page=auth&action=login">返回登录</a></span>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>
