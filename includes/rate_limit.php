<?php
declare(strict_types=1);

/**
 * 简单 Session 限速工具。
 *
 * shop_rate_limit(key, max_attempts, window_seconds)
 *   → true  = 允许请求（未超限）
 *   → false = 已超限
 *
 * shop_rate_limit_reset(key) — 成功后重置计数
 *
 * Session 键格式：_rl_{key}，存储 ['count' => int, 'expires' => timestamp]
 */

function shop_rate_limit(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool
{
    $sessionKey = '_rl_' . $key;
    $now = time();

    if (!isset($_SESSION[$sessionKey]) || $_SESSION[$sessionKey]['expires'] <= $now) {
        $_SESSION[$sessionKey] = ['count' => 0, 'expires' => $now + $windowSeconds];
    }

    $_SESSION[$sessionKey]['count']++;

    return $_SESSION[$sessionKey]['count'] <= $maxAttempts;
}

function shop_rate_limit_remaining(string $key, int $maxAttempts = 5): int
{
    $sessionKey = '_rl_' . $key;
    $count = (int) ($_SESSION[$sessionKey]['count'] ?? 0);
    return max(0, $maxAttempts - $count);
}

function shop_rate_limit_reset(string $key): void
{
    unset($_SESSION['_rl_' . $key]);
}
