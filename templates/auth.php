<?php declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
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
    if (!$pdo) {
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
                        $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, password_hash, status, level, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', '普通会员', NOW(), NOW())");
                        $stmt->execute([$username, $name, $email, $hash]);

                        $success = '注册成功，请使用账号登录。';
                        $action = 'login';
                    }
                } catch (PDOException $exception) {
                    error_log('[shop] 注册失败: ' . $exception->getMessage());
                    $error = '注册失败，请稍后再试。';
                }
            }
        } elseif ($action === 'login') {
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

                    $user = $stmt->fetch();
                    if ($user && password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                        $update_stmt = $pdo->prepare("UPDATE `{$prefix}users` SET last_login = NOW() WHERE id = ?");
                        $update_stmt->execute([(int) ($user['id'] ?? 0)]);

                        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
                        $_SESSION['user_name'] = (string) ($user['name'] ?? '');
                        $_SESSION['user_username'] = (string) ($user['username'] ?? '');

                        header('Location: index.php?page=profile');
                        exit;
                    }

                    $error = '账号或密码不正确。';
                } catch (PDOException $exception) {
                    error_log('[shop] 登录失败: ' . $exception->getMessage());
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

$pageTitle = $action === 'login' ? '账号中心 - 登录' : '账号中心 - 注册';
$currentPage = 'profile';

include __DIR__ . '/header.php';
?>

<main class="page-shell" style="display: flex; justify-content: center; align-items: center; min-height: 70vh; padding: 20px;">
    <div style="background: #ffffff; padding: 30px; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);">
        <h1 style="text-align: center; margin-bottom: 20px; font-size: 24px; color: #0f172a;">
            <?php echo $action === 'login' ? '用户登录' : '账号注册'; ?>
        </h1>

        <?php if (is_array($flash) && ($flash['message'] ?? '') !== ''): ?>
            <div style="background: <?php echo ($flash['type'] ?? 'success') === 'error' ? '#fef2f2' : '#ecfdf5'; ?>; color: <?php echo ($flash['type'] ?? 'success') === 'error' ? '#b91c1c' : '#047857'; ?>; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid <?php echo ($flash['type'] ?? 'success') === 'error' ? '#fecaca' : '#10b981'; ?>; font-size: 14px;">
                <?php echo shop_e((string) ($flash['message'] ?? '')); ?>
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div style="background: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 14px;">
                <?php echo shop_e($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div style="background: #ecfdf5; color: #047857; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #10b981; font-size: 14px;">
                <?php echo shop_e($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'login'): ?>
            <form method="post" action="index.php?page=auth&action=login" style="display: flex; flex-direction: column; gap: 15px;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">账号 / 邮箱 / ID</label>
                    <input type="text" name="login_id" required placeholder="请输入账号、邮箱或 ID" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">密码</label>
                    <input type="password" name="password" required placeholder="请输入密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 10px;">登录</button>
                <div style="display: flex; justify-content: space-between; gap: 12px; margin-top: 4px; font-size: 14px; color: #64748b;">
                    <a href="index.php?page=forgot_password" style="color: #2563eb; text-decoration: none;">忘记密码？</a>
                    <span>还没有账号？<a href="index.php?page=auth&action=register" style="color: #2563eb; text-decoration: none;">立即注册</a></span>
                </div>
            </form>
        <?php else: ?>
            <form method="post" action="index.php?page=auth&action=register" style="display: flex; flex-direction: column; gap: 15px;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">用户名</label>
                    <input type="text" name="username" required placeholder="请输入用于登录的用户名" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">昵称</label>
                    <input type="text" name="name" required placeholder="请输入展示昵称" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">注册邮箱</label>
                    <input type="email" name="email" required placeholder="请输入可用于找回密码的邮箱" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">密码</label>
                    <input type="password" name="password" required placeholder="请设置登录密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #2563eb; color: #ffffff; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 10px;">注册</button>
                <div style="text-align: center; margin-top: 10px; font-size: 14px; color: #64748b;">
                    已有账号？<a href="index.php?page=auth&action=login" style="color: #2563eb; text-decoration: none;">返回登录</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
