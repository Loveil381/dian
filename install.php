<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/csrf.php';

$config_path = __DIR__ . '/config/database.php';
$lock_path = __DIR__ . '/config/installed.lock';
$schema_path = __DIR__ . '/database/schema.sql';

if (file_exists($lock_path) || file_exists($config_path)) {
    http_response_code(200);
    ?>
    <!DOCTYPE html>
    <html lang="zh-CN">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>魔女小店已安装</title>
        <link rel="stylesheet" href="assets/css/site.css">
    </head>
    <body class="auth-page">
    <main class="page-shell">
        <section class="auth-card auth-card--wide">
            <h1 class="auth-title">系统已安装</h1>
            <p class="auth-description">检测到已有安装配置文件。如需重新安装，请先删除 <code>config/database.php</code> 和 <code>config/installed.lock</code>。</p>
            <div class="auth-links">
                <a class="auth-link" href="index.php">返回首页</a>
            </div>
        </section>
    </main>
    </body>
    </html>
    <?php
    exit;
}

$error = '';
$success = '';
$defaults = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => '',
    'user' => 'root',
    'password' => '',
    'prefix' => 'shop_',
    'admin_user' => 'admin',
    'admin_password' => '',
];
$form = $defaults;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($form as $key => $value) {
        $form[$key] = trim((string) ($_POST[$key] ?? $value));
    }

    if ($form['host'] === '' || $form['name'] === '' || $form['user'] === '') {
        $error = '请填写数据库主机、数据库名和用户名。';
    } elseif ($form['prefix'] !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $form['prefix'])) {
        $error = '表前缀只能包含字母、数字和下划线。';
    } elseif ($form['admin_user'] === '' || strlen($form['admin_password']) < 6) {
        $error = '请设置管理员用户名，并确保管理员密码至少 6 位。';
    } elseif (!file_exists($schema_path)) {
        $error = '未找到数据库结构文件 database/schema.sql。';
    } else {
        $pdo = null;

        try {
            $host = strtolower($form['host']) === 'localhost' ? '127.0.0.1' : $form['host'];
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $host,
                (int) $form['port'],
                $form['name']
            );

            $pdo = new PDO($dsn, $form['user'], $form['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $schema_sql = (string) file_get_contents($schema_path);
            $schema_sql = ltrim($schema_sql, "\xEF\xBB\xBF");
            $schema_sql = preg_replace('/^\s*--.*$/m', '', $schema_sql) ?? '';
            $schema_sql = str_replace('{prefix}', $form['prefix'], $schema_sql);

            if (str_contains($schema_sql, '{prefix}')) {
                throw new RuntimeException('表前缀替换失败，请检查 database/schema.sql 是否为 UTF-8 无 BOM 格式。');
            }

            $statements = array_filter(array_map('trim', explode(';', $schema_sql)));

            $pdo->beginTransaction();
            foreach ($statements as $statement) {
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }

                $pdo->exec($statement);
            }

            $admin_hash = password_hash($form['admin_password'], PASSWORD_DEFAULT);
            $admin_stmt = $pdo->prepare("INSERT INTO `{$form['prefix']}admin_users` (username, name, password_hash, role) VALUES (?, ?, ?, 'super_admin')");
            $admin_stmt->execute([$form['admin_user'], $form['admin_user'], $admin_hash]);
            $pdo->commit();

            $config = [
                'driver' => 'mysql',
                'host' => $host,
                'port' => (int) $form['port'],
                'name' => $form['name'],
                'user' => $form['user'],
                'password' => $form['password'],
                'prefix' => $form['prefix'],
                'charset' => 'utf8mb4',
            ];

            $config_content = "<?php\n";
            $config_content .= "declare(strict_types=1);\n\n";
            $config_content .= 'return ' . var_export($config, true) . ";\n";

            if (file_put_contents($config_path, $config_content) === false) {
                throw new RuntimeException('写入数据库配置文件失败。');
            }

            if (file_put_contents($lock_path, "installed_at=" . date('c') . "\n") === false) {
                throw new RuntimeException('写入安装锁文件失败。');
            }

            $success = '安装成功，数据库和管理员账号已经创建完成。';
        } catch (Throwable $exception) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = '安装失败：' . $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>魔女小店安装向导</title>
    <link rel="stylesheet" href="assets/css/site.css">
</head>
<body class="auth-page">
<main class="page-shell">
    <section class="auth-card auth-card--wide">
        <h1 class="auth-title">安装向导</h1>
        <p class="auth-description">填写数据库连接信息和管理员账号，提交后系统会自动完成初始化。</p>

        <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert--error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert--success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="auth-links">
                <a class="auth-link" href="index.php">返回首页</a>
                <a class="auth-link" href="admin/index.php">进入后台</a>
            </div>
        <?php else: ?>
            <form method="post" class="auth-form">
                <?php echo csrf_field(); ?>

                <label class="auth-label" for="host">数据库主机</label>
                <input class="auth-input" id="host" name="host" value="<?php echo htmlspecialchars($form['host'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label class="auth-label" for="port">端口</label>
                <input class="auth-input" id="port" name="port" value="<?php echo htmlspecialchars($form['port'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label class="auth-label" for="name">数据库名</label>
                <input class="auth-input" id="name" name="name" value="<?php echo htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label class="auth-label" for="user">数据库用户名</label>
                <input class="auth-input" id="user" name="user" value="<?php echo htmlspecialchars($form['user'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label class="auth-label" for="password">数据库密码</label>
                <input class="auth-input" id="password" type="password" name="password" value="<?php echo htmlspecialchars($form['password'], ENT_QUOTES, 'UTF-8'); ?>">

                <label class="auth-label" for="prefix">表前缀</label>
                <input class="auth-input" id="prefix" name="prefix" value="<?php echo htmlspecialchars($form['prefix'], ENT_QUOTES, 'UTF-8'); ?>">

                <div style="margin: 24px 0; border-top: 1px solid #e5e7eb;"></div>

                <label class="auth-label" for="admin_user">管理员用户名</label>
                <input class="auth-input" id="admin_user" name="admin_user" value="<?php echo htmlspecialchars($form['admin_user'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label class="auth-label" for="admin_password">管理员密码</label>
                <input class="auth-input" id="admin_password" type="password" name="admin_password" value="<?php echo htmlspecialchars($form['admin_password'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <button class="auth-btn" type="submit">开始安装</button>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
