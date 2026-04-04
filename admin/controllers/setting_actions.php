<?php
declare(strict_types=1);

/**
 * 系统设置 action handlers（支付配置、密码修改、权限管理）。
 *
 * 依赖函数：
 *   shop_admin_post_string/checked(), shop_admin_flash() — admin/includes/helpers.php
 *   get_db_connection(), get_db_prefix() — includes/db.php
 */

function handle_save_payment(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();

    $wechatQr = shop_admin_post_string('wechat_qr');
    $alipayQr = shop_admin_post_string('alipay_qr');
    $requireAddress = shop_admin_post_checked('require_address') ? '1' : '0';

    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    try {
        $stmt = $pdo->prepare("REPLACE INTO `{$prefix}settings` (`key`, `value`) VALUES ('wechat_qr', ?), ('alipay_qr', ?), ('require_address', ?)");
        $stmt->execute([$wechatQr, $alipayQr, $requireAddress]);
        return ['支付配置已更新。', 'success'];
    } catch (PDOException $e) {
        return ['支付配置保存失败: ' . $e->getMessage(), 'error'];
    }
}

/**
 * 修改管理员密码。成功后会 destroy session 并 redirect，不会返回。
 */
function handle_change_password(): array
{
    $new_username = shop_admin_post_string('new_username');
    $new_password = shop_admin_post_string('new_password');
    if (mb_strlen($new_password, 'UTF-8') < 8) {
        return ['密码长度不能少于8位', 'error'];
    }

    $admin_id = (int)($_SESSION['admin_id'] ?? 0);
    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo || $admin_id <= 0) {
        return ['数据库连接或鉴权失败', 'error'];
    }

    try {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        if ($new_username !== '') {
            $stmt = $pdo->prepare("UPDATE `{$prefix}admin_users` SET username = ?, password_hash = ? WHERE id = ?");
            $stmt->execute([$new_username, $password_hash, $admin_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `{$prefix}admin_users` SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $admin_id]);
        }
        session_destroy();
        session_start();
        shop_admin_flash('密码已更新，请用新密码重新登录。', 'success');
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        return ['修改密码失败: ' . $e->getMessage(), 'error'];
    }
}

function handle_save_role(): array
{
    return ['权限管理功能开发中，敬请期待。', 'info'];
}
