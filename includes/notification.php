<?php
declare(strict_types=1);

/**
 * 订单通知引擎。
 *
 * 在订单状态变更时发送邮件通知给管理员和/或客户。
 * 通知开关和管理员邮箱从 settings 表读取。
 * 依赖：includes/db.php, includes/mailer.php, includes/logger.php
 */

/**
 * 触发订单事件通知。
 *
 * @param string $event 事件名：created / paid / shipped / completed
 * @param array  $order 订单数据（需包含 order_no, total, customer, items_data 等）
 */
function shop_notify_order_event(string $event, array $order): void
{
    $validEvents = ['created', 'paid', 'shipped', 'completed'];
    if (!in_array($event, $validEvents, true)) {
        return;
    }

    $settings = shop_get_settings([
        'notify_admin_created', 'notify_admin_paid',
        'notify_customer_created', 'notify_customer_shipped', 'notify_customer_completed',
        'notify_admin_email',
    ]);

    $orderNo = (string) ($order['order_no'] ?? '');
    $total = shop_format_price((float) ($order['total'] ?? 0));
    $customer = (string) ($order['customer'] ?? '顾客');

    // ── 管理员通知 ──
    $adminEmail = trim((string) ($settings['notify_admin_email'] ?? ''));
    if ($adminEmail !== '') {
        $adminKey = 'notify_admin_' . $event;
        if (($settings[$adminKey] ?? '0') === '1') {
            $subject = shop_notification_subject($event, 'admin', $orderNo);
            $body = shop_notification_body($event, 'admin', $order);
            try {
                shop_send_mail($adminEmail, $subject, $body);
            } catch (\Throwable $e) {
                shop_log('warning', '管理员通知发送失败', ['event' => $event, 'order_no' => $orderNo, 'message' => $e->getMessage()]);
            }
        }
    }

    // ── 客户通知 ──
    $customerEmail = shop_get_customer_email($order);
    if ($customerEmail !== '') {
        $customerKey = 'notify_customer_' . $event;
        if (($settings[$customerKey] ?? '0') === '1') {
            $subject = shop_notification_subject($event, 'customer', $orderNo);
            $body = shop_notification_body($event, 'customer', $order);
            try {
                shop_send_mail($customerEmail, $subject, $body);
            } catch (\Throwable $e) {
                shop_log('warning', '客户通知发送失败', ['event' => $event, 'order_no' => $orderNo, 'message' => $e->getMessage()]);
            }
        }
    }
}

/**
 * 生成通知邮件主题。
 */
function shop_notification_subject(string $event, string $audience, string $orderNo): string
{
    $storeName = shop_get_setting('store_name', '魔女的小店');

    if ($audience === 'admin') {
        $map = [
            'created' => '[新订单] #' . $orderNo,
            'paid'    => '[收款通知] 订单 #' . $orderNo . ' 已支付',
        ];
    } else {
        $map = [
            'created'   => '【' . $storeName . '】订单提交成功 #' . $orderNo,
            'shipped'   => '【' . $storeName . '】您的订单已发货 #' . $orderNo,
            'completed' => '【' . $storeName . '】订单已完成 #' . $orderNo,
        ];
    }

    return $map[$event] ?? '【' . $storeName . '】订单通知 #' . $orderNo;
}

/**
 * 生成通知邮件正文（纯文本）。
 */
function shop_notification_body(string $event, string $audience, array $order): string
{
    $orderNo = (string) ($order['order_no'] ?? '');
    $total = shop_format_price((float) ($order['total'] ?? 0));
    $customer = (string) ($order['customer'] ?? '顾客');
    $phone = (string) ($order['phone'] ?? '');
    $address = (string) ($order['address'] ?? '');
    $storeName = shop_get_setting('store_name', '魔女的小店');

    // 商品摘要
    $itemsSummary = '';
    $itemsData = $order['items_data'] ?? [];
    if (is_array($itemsData)) {
        foreach ($itemsData as $item) {
            $itemsSummary .= '  - ' . ($item['name'] ?? '') . ' ×' . ($item['quantity'] ?? 1) . "\n";
        }
    }
    if ($itemsSummary === '') {
        $itemsSummary = (string) ($order['items_summary'] ?? '商品详情请查看订单');
    }

    if ($audience === 'admin') {
        if ($event === 'created') {
            return "您好，有新订单需要处理：\n\n"
                . "订单号：{$orderNo}\n"
                . "客户：{$customer}\n"
                . "电话：{$phone}\n"
                . "地址：{$address}\n"
                . "金额：{$total}\n\n"
                . "商品：\n{$itemsSummary}\n"
                . "请及时确认并处理。";
        }
        return "订单 #{$orderNo} 状态已变更为：{$event}\n客户：{$customer}\n金额：{$total}";
    }

    // 客户通知
    if ($event === 'created') {
        return "{$customer} 您好，\n\n"
            . "感谢在{$storeName}下单！您的订单已成功提交。\n\n"
            . "订单号：{$orderNo}\n"
            . "金额：{$total}\n\n"
            . "商品：\n{$itemsSummary}\n"
            . "我们会尽快为您处理，请耐心等待。";
    }
    if ($event === 'shipped') {
        $express = (string) ($order['express_company'] ?? '');
        $tracking = (string) ($order['tracking_numbers'] ?? '');
        return "{$customer} 您好，\n\n"
            . "您的订单 #{$orderNo} 已发货。\n\n"
            . ($express !== '' ? "快递公司：{$express}\n" : '')
            . ($tracking !== '' ? "快递单号：{$tracking}\n" : '')
            . "\n请注意查收，祝您购物愉快！";
    }
    if ($event === 'completed') {
        return "{$customer} 您好，\n\n"
            . "您的订单 #{$orderNo} 已完成。\n"
            . "感谢您的购买，欢迎再次光临{$storeName}！";
    }

    return "订单 #{$orderNo} 通知：{$event}";
}

/**
 * 根据订单的 user_id 查找客户邮箱。
 */
function shop_get_customer_email(array $order): string
{
    $userId = $order['user_id'] ?? null;
    if ($userId === null || (int) $userId <= 0) {
        return '';
    }

    $pdo = get_db_connection();
    $prefix = get_db_prefix();
    if (!$pdo) {
        return '';
    }

    try {
        $stmt = $pdo->prepare("SELECT `email` FROM `{$prefix}users` WHERE `id` = ? LIMIT 1");
        $stmt->execute([(int) $userId]);
        $email = $stmt->fetchColumn();
        return is_string($email) ? trim($email) : '';
    } catch (PDOException $e) {
        return '';
    }
}
