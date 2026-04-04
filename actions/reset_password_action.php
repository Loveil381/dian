<?php
declare(strict_types=1);

/**
 * 重置密码 POST action handler。
 * 由 templates/reset_password.php 在 POST 且 token 有效时 require。
 * 成功则 redirect + exit；失败则设 $error 并 return（由模板渲染错误）。
 */

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';

// $pdo, $prefix, $user, $token 由调用方（reset_password.php）提供
csrf_verify();

$new_password = (string) ($_POST['password'] ?? '');
$confirm_password = (string) ($_POST['password_confirm'] ?? '');

if (!$pdo instanceof PDO) {
    $error = '数据库连接失败，请稍后重试。';
    return;
}

if (!is_array($user)) {
    $error = '重置链接已失效，请重新申请。';
    return;
}

if ($new_password === '' || $confirm_password === '') {
    $error = '请输入并确认新密码。';
    return;
}

if (strlen($new_password) < 8) {
    $error = '新密码长度不能少于 8 位。';
    return;
}

if (!hash_equals($new_password, $confirm_password)) {
    $error = '两次输入的密码不一致。';
    return;
}

try {
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET password_hash = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$password_hash, (int) ($user['id'] ?? 0)]);

    $_SESSION['auth_flash'] = [
        'type' => 'success',
        'message' => '密码重置成功，请使用新密码登录。',
    ];
    header('Location: index.php?page=auth&action=login');
    exit;
} catch (Throwable $exception) {
    shop_log('error', '重置密码失败', ['message' => $exception->getMessage()]);
    $error = '密码重置失败，请稍后重试。';
}
