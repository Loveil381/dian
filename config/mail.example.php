<?php
declare(strict_types=1);

/**
 * 邮件配置示例。
 * 可通过环境变量注入，切勿提交真实凭证。
 *
 * SMTP_HOST=smtp.example.com
 * SMTP_PORT=25
 * SMTP_USER=mailer@example.com
 * SMTP_PASS=your-password
 * SMTP_FROM=no-reply@example.com
 */

return [
    'SMTP_HOST' => 'smtp.example.com',
    'SMTP_PORT' => '25',
    'SMTP_USER' => 'mailer@example.com',
    'SMTP_PASS' => 'your-password',
    'SMTP_FROM' => 'no-reply@example.com',
];
