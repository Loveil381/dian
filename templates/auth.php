<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

session_start();

$action = $_GET['action'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pdo = get_db_connection();
    if (!$pdo) {
        $error = '数据库连接失败';
    } else {
        if ($action === 'register') {
            $username = trim($_POST['username'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if ($username === '' || $name === '' || $password === '') {
                $error = '所有字段均为必填';
            } else {
                try {
                    $prefix = get_db_prefix();
                    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = '用户名已被使用';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, name, password_hash, status, level, created_at, updated_at) VALUES (?, ?, ?, 'active', '普通会员', NOW(), NOW())");
                        $stmt->execute([$username, $name, $hash]);
                        
                        $success = '注册成功，请登录';
                        $action = 'login'; // 注册成功后跳转到登录表单
                    }
                } catch (PDOException $e) {
                    $error = '注册失败: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'login') {
            $loginId = trim($_POST['login_id'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if ($loginId === '' || $password === '') {
                $error = '请输入账号和密码';
            } else {
                try {
                    $prefix = get_db_prefix();
                    // 支持 ID 或 用户名 登录
                    if (is_numeric($loginId)) {
                        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}users` WHERE id = ? OR username = ? LIMIT 1");
                        $stmt->execute([$loginId, $loginId]);
                    } else {
                        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}users` WHERE username = ? LIMIT 1");
                        $stmt->execute([$loginId]);
                    }
                    
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password_hash'])) {
                        // 更新最后登录时间
                        $updateStmt = $pdo->prepare("UPDATE `{$prefix}users` SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_username'] = $user['username'];
                        
                        header('Location: index.php?page=profile');
                        exit;
                    } else {
                        $error = '账号或密码错误';
                    }
                } catch (PDOException $e) {
                    $error = '登录失败: ' . $e->getMessage();
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

$pageTitle = $action === 'login' ? '魔女小店 - 登录' : '魔女小店 - 注册';
$currentPage = 'profile';

include __DIR__ . '/header.php';
?>

<main class="page-shell" style="display: flex; justify-content: center; align-items: center; min-height: 70vh; padding: 20px;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <h1 style="text-align: center; margin-bottom: 20px; font-size: 24px; color: #0f172a;">
            <?php echo $action === 'login' ? '用户登录' : '账号注册'; ?>
        </h1>
        
        <?php if ($error): ?>
            <div style="background: #fef2f2; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 14px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div style="background: #ecfdf5; color: #047857; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #10b981; font-size: 14px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'login'): ?>
            <form method="post" action="index.php?page=auth&action=login" style="display: flex; flex-direction: column; gap: 15px;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">ID 或 用户名</label>
                    <input type="text" name="login_id" required placeholder="请输入您的 ID 或用户名" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">密码</label>
                    <input type="password" name="password" required placeholder="请输入密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 10px;">登录</button>
                <div style="text-align: center; margin-top: 10px; font-size: 14px; color: #64748b;">
                    还没有账号？ <a href="index.php?page=auth&action=register" style="color: #2563eb; text-decoration: none;">立即注册</a>
                </div>
            </form>
        <?php else: ?>
            <form method="post" action="index.php?page=auth&action=register" style="display: flex; flex-direction: column; gap: 15px;">
                <?php echo csrf_field(); ?>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">用户名</label>
                    <input type="text" name="username" required placeholder="用于登录的唯一英文/数字名" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">昵称</label>
                    <input type="text" name="name" required placeholder="显示在个人中心的昵称" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #475569; font-size: 14px;">密码</label>
                    <input type="password" name="password" required placeholder="请设置密码" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 16px;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 10px;">注册</button>
                <div style="text-align: center; margin-top: 10px; font-size: 14px; color: #64748b;">
                    已有账号？ <a href="index.php?page=auth&action=login" style="color: #2563eb; text-decoration: none;">直接登录</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>
