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
        shop_admin_log('save_payment', 'settings', 0, '更新支付设置');
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
        shop_admin_log('change_password', 'settings', 0, '修改管理员密码');
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

/**
 * 保存在线咨询设置。
 */
function handle_save_consult(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();

    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    $enabled = shop_admin_post_checked('consult_enabled') ? '1' : '0';
    $title = shop_admin_post_string('consult_title');
    $greeting = shop_admin_post_string('consult_greeting');
    $wechat_qr = shop_admin_post_string('consult_wechat_qr');
    $wechat_id = shop_admin_post_string('consult_wechat_id');
    $phone = shop_admin_post_string('consult_phone');
    $notice = shop_admin_post_string('consult_notice');

    try {
        $stmt = $pdo->prepare(
            "REPLACE INTO `{$prefix}settings` (`key`, `value`) VALUES ('consult_enabled', ?), ('consult_title', ?), ('consult_greeting', ?), ('consult_wechat_qr', ?), ('consult_wechat_id', ?), ('consult_phone', ?), ('consult_notice', ?)"
        );
        $stmt->execute([$enabled, $title, $greeting, $wechat_qr, $wechat_id, $phone, $notice]);
        shop_admin_log('save_consult', 'settings', 0, '更新在线咨询设置');
        return ['在线咨询设置已更新。', 'success'];
    } catch (PDOException $e) {
        return ['在线咨询设置保存失败: ' . $e->getMessage(), 'error'];
    }
}

/**
 * 保存订单通知设置。
 */
function handle_save_notification(): array
{
    $pdo = get_db_connection();
    $prefix = get_db_prefix();

    if (!$pdo) {
        return ['数据库连接失败', 'error'];
    }

    $adminEmail = shop_admin_post_string('notify_admin_email');
    if ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return ['请输入有效的邮箱地址。', 'error'];
    }

    $keys = [
        'notify_admin_created'      => shop_admin_post_checked('notify_admin_created') ? '1' : '0',
        'notify_admin_paid'         => shop_admin_post_checked('notify_admin_paid') ? '1' : '0',
        'notify_customer_created'   => shop_admin_post_checked('notify_customer_created') ? '1' : '0',
        'notify_customer_shipped'   => shop_admin_post_checked('notify_customer_shipped') ? '1' : '0',
        'notify_customer_completed' => shop_admin_post_checked('notify_customer_completed') ? '1' : '0',
        'notify_admin_email'        => $adminEmail,
    ];

    try {
        foreach ($keys as $key => $value) {
            shop_set_setting($key, $value);
        }
        shop_admin_log('save_notification', 'settings', 0, '更新订单通知设置');
        return ['通知设置已更新。', 'success'];
    } catch (\Throwable $e) {
        return ['通知设置保存失败: ' . $e->getMessage(), 'error'];
    }
}
