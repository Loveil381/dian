<?php
declare(strict_types=1);

/**
 * 个人中心 POST action handler（保存收货信息）。
 * 由 templates/profile.php 在 POST 时 require，处理完后 redirect + exit。
 */

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/logger.php';

csrf_verify();

$isLoggedIn = isset($_SESSION['user_id']);

if (!$isLoggedIn) {
    $_SESSION['profile_flash'] = ['type' => 'warning', 'message' => '访客状态下无法永久保存收货信息，请注册账号。'];
    header('Location: index.php?page=profile');
    exit;
}

$pdo = get_db_connection();
$prefix = get_db_prefix();

if (!$pdo instanceof PDO) {
    $_SESSION['profile_flash'] = ['type' => 'error', 'message' => '数据库连接失败，请稍后重试。'];
    header('Location: index.php?page=profile');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$phone = trim((string) ($_POST['phone'] ?? ''));
$address = trim((string) ($_POST['address'] ?? ''));

try {
    $stmt = $pdo->prepare("UPDATE `{$prefix}users` SET name = ?, phone = ?, address = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);
    $_SESSION['user_name'] = $name;
    $_SESSION['profile_flash'] = ['type' => 'success', 'message' => '收货信息已保存。'];
} catch (PDOException $e) {
    shop_log('error', '保存收货信息失败', ['message' => $e->getMessage()]);
    $_SESSION['profile_flash'] = ['type' => 'error', 'message' => '保存失败，请稍后重试。'];
}

header('Location: index.php?page=profile');
exit;
