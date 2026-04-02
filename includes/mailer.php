<?php
declare(strict_types=1);

require_once __DIR__ . '/logger.php';

/**
 * 发送邮件，优先使用 SMTP，未配置时回退到 mail()。
 */
function shop_send_mail(string $to, string $subject, string $body): bool
{
    $smtp_host = trim((string) getenv('SMTP_HOST'));
    if ($smtp_host !== '') {
        return shop_send_mail_via_smtp($to, $subject, $body);
    }

    $from = trim((string) getenv('SMTP_FROM'));
    if ($from === '') {
        $from = 'no-reply@localhost';
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $from,
    ];

    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

function shop_send_mail_via_smtp(string $to, string $subject, string $body): bool
{
    $host = trim((string) getenv('SMTP_HOST'));
    $port = max(1, (int) (getenv('SMTP_PORT') ?: 25));
    $user = trim((string) getenv('SMTP_USER'));
    $pass = (string) getenv('SMTP_PASS');
    $from = trim((string) getenv('SMTP_FROM'));

    if ($host === '' || $from === '') {
        return false;
    }

    $socket = @stream_socket_client('tcp://' . $host . ':' . $port, $errno, $errstr, 10);
    if (!is_resource($socket)) {
        shop_log('error', 'SMTP 连接失败', [
            'message' => $errstr,
            'code' => $errno,
        ]);
        return false;
    }

    stream_set_timeout($socket, 10);

    try {
        shop_smtp_expect($socket, [220]);
        shop_smtp_command($socket, 'EHLO localhost', [250]);

        if ($user !== '' && $pass !== '') {
            shop_smtp_command($socket, 'AUTH LOGIN', [334]);
            shop_smtp_command($socket, base64_encode($user), [334]);
            shop_smtp_command($socket, base64_encode($pass), [235]);
        }

        shop_smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250]);
        shop_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        shop_smtp_command($socket, 'DATA', [354]);

        $headers = [
            'From: ' . $from,
            'To: ' . $to,
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.";
        fwrite($socket, $message . "\r\n");
        shop_smtp_expect($socket, [250]);
        shop_smtp_command($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    } catch (RuntimeException $exception) {
        shop_log('error', 'SMTP 发送失败', ['message' => $exception->getMessage()]);
        fclose($socket);
        return false;
    }
}

/**
 * 发送 SMTP 指令并校验返回码。
 *
 * @param int[] $expected_codes
 */
function shop_smtp_command($socket, string $command, array $expected_codes): void
{
    fwrite($socket, $command . "\r\n");
    shop_smtp_expect($socket, $expected_codes);
}

/**
 * 读取 SMTP 响应并校验状态码。
 *
 * @param int[] $expected_codes
 */
function shop_smtp_expect($socket, array $expected_codes): void
{
    $response = '';

    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (isset($line[3]) && $line[3] === ' ') {
            break;
        }
    }

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expected_codes, true)) {
        throw new RuntimeException(trim($response) !== '' ? trim($response) : 'SMTP 响应异常');
    }
}
