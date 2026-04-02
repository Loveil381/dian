<?php
declare(strict_types=1);

/**
 * 统一写入项目日志，失败时回退到 PHP 原生日志。
 */
function shop_log(string $level, string $message, array $context = []): void
{
    $root_path = dirname(__DIR__);
    $logs_dir = $root_path . '/logs';
    $log_file = $logs_dir . '/app.log';
    $timestamp = date(DATE_ATOM);
    $normalized_level = strtoupper(trim($level) !== '' ? $level : 'INFO');
    $context_json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($context_json === false) {
        $context_json = '{}';
    }

    $line = sprintf('[%s] [%s] %s %s', $timestamp, $normalized_level, $message, $context_json) . PHP_EOL;

    if (!is_dir($logs_dir) && !@mkdir($logs_dir, 0755, true) && !is_dir($logs_dir)) {
        error_log($line);
        return;
    }

    if (@file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX) === false) {
        error_log($line);
    }
}
