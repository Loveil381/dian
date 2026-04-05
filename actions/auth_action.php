<?php
declare(strict_types=1);

/**
 * 用户登录 / 注册 POST 处理。
 *
 * 登录成功时会 redirect + exit。
 * 其他情况设置 $error / $success 并 return，由模板继续渲染。
 *
 * 依赖（由调用方保证已加载）：
 *   session 已启动
 *   includes/db.php, includes/csrf.php, includes/logger.php, data/products.php
 * 输入：
 *   $action — 'login' | 'register'
 * 输出（写入调用方作用域）：
 *   $error, $success, $action
 */

csrf_verify();

$pdo = get_db_connection();
if (!$pdo instanceof PDO) {
    $error = '数据库连接失败，请稍后重试。';
    return;
}

$prefix = get_db_prefix();

if ($action === 'register') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $password_confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || $name === '' || $email === '' || $password === '') {
        $error = '请输入完整必填注册信息。';
    } elseif (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]{2,19}$/', $username)) {
        $error = '用户名需以英文字母开头，仅允许字母、数字、下划线和连字符，长度 3-20 位。';
    } elseif (strlen($password) < ($minPwdLen = (int) shop_get_setting('min_password_length', '8'))) {
        $error = '密码至少需要 ' . $minPwdLen . ' 位。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '请输入正确的邮箱地址。';
    } elseif ($password !== $password_confirm) {
        $error = '两次输入的密码不一致。';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = '用户名或邮箱已被注册使用。';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, email, password_hash, status, level, created_at, updated_at) VALUES (?, ?, ?, ?, 'active', 'member', NOW(), NOW())");
                $stmt->execute([$username, $name, $email, $hash]);

                $success = '注册成功，请使用新账号登录。';
                $action = 'login';
            }
        } catch (PDOException $exception) {
            shop_log('error', '用户注册失败', ['message' => $exception->getMessage()]);
            $error = '注册失败，请稍后重试。';
        }
    }
    return;
}

// ── 登录 ──
$login_id = trim((string) ($_POST['login_id'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($login_id === '' || $password === '') {
    $error = '请输入登录账号与密码。';
    return;
}

// 5 次 / 5 分钟限速（防暴力破解）
if (!shop_rate_limit('user_login', 5, 300)) {
    $error = '登录失败次数过多，请 5 分钟后再试。';
    return;
}

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
        if (trim((string) ($user['status'] ?? '')) === 'banned') {
            $error = '该账号已被禁用，如有疑问请联系客服。';
            return;
        }

        shop_rate_limit_reset('user_login');

        $update_stmt = $pdo->prepare("UPDATE `{$prefix}users` SET last_login = NOW() WHERE id = ?");
        $update_stmt->execute([(int) ($user['id'] ?? 0)]);

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['user_name'] = (string) ($user['name'] ?? '');
        $_SESSION['user_username'] = (string) ($user['username'] ?? '');

        header('Location: index.php?page=profile');
        exit;
    }

    $error = '登录信息不正确。';
} catch (PDOException $exception) {
    shop_log('error', '用户登录失败', ['message' => $exception->getMessage()]);
    $error = '登录失败，请稍后重试。';
}
