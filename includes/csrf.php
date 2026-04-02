<?php
declare(strict_types=1);

// CSRF Token 生成与验证

/**
 * 生成或获取当前会话的 CSRF Token
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 输出隐藏的 CSRF 表单字段
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * 验证请求中的 CSRF Token
 * 验证失败时终止请求并返回 403
 */
function csrf_verify(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !hash_equals(($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(403);
        die('安全验证失败，请刷新页面后重试。');
    }
}
