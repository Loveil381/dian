<?php
declare(strict_types=1);

/**
 * 忘记密码 POST 处理：校验邮箱、生成 token、发送重置邮件。
 *
 * 输出（写入调用方作用域）：
 *   $error, $success, $reset_link, $mail_warning
 *
 * 依赖（由调用方保证已加载）：
 *   session 已启动
 *   includes/db.php, includes/csrf.php, includes/logger.php, includes/mailer.php, data/products.php
 */

csrf_verify();

$submitted_email = trim((string) ($_POST['email'] ?? ''));
if ($submitted_email === '') {
    $error = '请输入注册邮箱。';
    return;
}

if (!filter_var($submitted_email, FILTER_VALIDATE_EMAIL)) {
    $error = '请输入正确的邮箱地址。';
    return;
}

// 3 次 / 10 分钟限速（防邮件轰炸）
if (!shop_rate_limit('forgot_password', 3, 600)) {
    $error = '请求过于频繁，请 10 分钟后再试。';
    return;
}

$pdo = get_db_connection();
if (!$pdo instanceof PDO) {
    $error = '数据库连接失败，请稍后重试。';
    return;
}

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

} catch (Throwable $exception) {
    shop_log('error', '处理忘记密码请求失败', ['message' => $exception->getMessage()]);
    $error = '重置邮件发送失败，请稍后重试。';
}
