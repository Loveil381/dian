<?php
declare(strict_types=1);

// This file handles initial site configuration for the admin panel

session_start();

$configPath = __DIR__ . '/../config/database.php';
$hasConfig = file_exists($configPath) && (!empty((require $configPath)['host']) || getenv('DB_HOST'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'setup_db') {
        $host = trim($_POST['db_host'] ?? '127.0.0.1');
        if (strtolower($host) === 'localhost') {
            $host = '127.0.0.1';
        }
        $port = (int) ($_POST['db_port'] ?? 3306);
        $dbname = trim($_POST['db_name'] ?? '');
        $username = trim($_POST['db_user'] ?? '');
        $password = $_POST['db_password'] ?? '';
        $prefix = trim($_POST['db_prefix'] ?? '');
        $adminUser = trim($_POST['admin_user'] ?? 'admin');
        $adminPass = $_POST['admin_password'] ?? '';
        
        // Test connection
        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            // 连接时不指定 dbname，以便能够创建数据库或者处理 dbname 错误
            $dsn_no_db = "mysql:host={$host};port={$port};charset=utf8mb4";
            
            // 这里为了处理密码问题，可以尝试关闭错误异常进行一次握手，看是否密码本身被拒绝
            try {
                $pdo = new PDO($dsn_no_db, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 3 // 设置较短的超时时间
                ]);
            } catch (PDOException $e) {
                // 如果账号密码本身都错误，直接向外抛出
                throw new PDOException("数据库账号或密码错误: " . $e->getMessage());
            }
            
            // Try to create database if not exists (may fail without privileges, which is fine if db already exists)
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                // Ignore access denied, will just try to use the database below
            }
            
            // 尝试使用数据库，这步能确认数据库是否存在且有权限访问
            try {
                $pdo->exec("USE `{$dbname}`");
            } catch (PDOException $e) {
                throw new PDOException("无法访问数据库 '{$dbname}'，请确认数据库是否存在且当前用户有权限访问: " . $e->getMessage());
            }
            
            // Import schema
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                if ($sql) {
                    $sql = str_replace('{PREFIX}', $prefix, $sql);
                    
                    // Split and execute multiple statements
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $statement) {
                        if (!empty($statement)) {
                            $pdo->exec($statement);
                        }
                    }
                }
            }
            
            // Check if admin user exists, if not create default
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$prefix}admin_users` WHERE username = " . $pdo->quote($adminUser));
            if ($stmt->fetchColumn() == 0) {
                $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `{$prefix}admin_users` (username, password_hash, role) VALUES (?, ?, 'admin')");
                $stmt->execute([$adminUser, $hashedPass]);
            }
            
            // Save config
            require_once __DIR__ . '/../includes/db.php';
            if (update_db_config($host, $port, $dbname, $username, $password, $prefix)) {
                $_SESSION['setup_success'] = '数据库配置成功并已初始化！';
                header('Location: index.php?page=admin_login');
                exit;
            } else {
                $error = '配置保存失败，请检查 config/database.php 权限。';
            }
            
        } catch (PDOException $e) {
            $error = '数据库连接失败: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>魔女小店 - 系统安装</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f6f7fb; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .setup-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h1 { margin-top: 0; font-size: 24px; text-align: center; color: #111827; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #4b5563; font-size: 14px; }
        input[type="text"], input[type="password"], input[type="number"] { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #1d4ed8; }
        .error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        .note { font-size: 12px; color: #6b7280; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>系统安装</h1>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <input type="hidden" name="action" value="setup_db">
            
            <div class="form-group">
                <label>数据库主机 (Host)</label>
                <input type="text" name="db_host" value="127.0.0.1" required>
            </div>
            
            <div class="form-group">
                <label>数据库端口 (Port)</label>
                <input type="number" name="db_port" value="3306" required>
            </div>
            
            <div class="form-group">
                <label>数据库名称 (Database)</label>
                <input type="text" name="db_name">
            </div>
            
            <div class="form-group">
                <label>数据库用户名 (Username)</label>
                <input type="text" name="db_user">
            </div>
            
            <div class="form-group">
                <label>数据库密码 (Password)</label>
                <input type="password" name="db_password">
            </div>
            
            <div class="form-group">
                <label>数据表前缀 (Prefix)</label>
                <input type="text" name="db_prefix" placeholder="留空则无前缀">
            </div>
            
            <div style="margin: 25px 0; border-top: 1px solid #e5e7eb;"></div>
            
            <div class="form-group">
                <label>超级管理员账号</label>
                <input type="text" name="admin_user" value="admin" required>
            </div>
            
            <div class="form-group">
                <label>超级管理员密码</label>
                <input type="password" name="admin_password">
            </div>
            
            <button type="submit">初始化系统</button>
        </form>
    </div>
</body>
</html>