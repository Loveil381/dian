<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/db.php';
$pdo = get_db_connection();

if (!$pdo) {
    header('Location: index.php?page=admin_setup');
    exit;
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php?page=admin');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $prefix = get_db_prefix();

    $stmt = $pdo->prepare("SELECT id, password_hash FROM `{$prefix}admin_users` WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $username;
        
        $pdo->prepare("UPDATE `{$prefix}admin_users` SET last_login_at = NOW() WHERE id = ?")->execute([$admin['id']]);
        
        header('Location: index.php?page=admin');
        exit;
    } else {
        $error = '用户名或密码错误';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>魔女小店 - 后台登录</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f7fb; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h1 { margin-top: 0; font-size: 24px; text-align: center; color: #111827; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #4b5563; font-size: 14px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #1d4ed8; }
        .error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; text-align: center;}
        .success { color: #047857; background: #ecfdf5; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; text-align: center;}
    </style>
</head>
<body>
    <div class="login-box">
        <h1>后台管理登录</h1>
        <?php if (isset($_SESSION['setup_success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_SESSION['setup_success']); unset($_SESSION['setup_success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>