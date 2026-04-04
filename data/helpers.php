<?php
declare(strict_types=1);

/**
 * 通用工具函数（格式化、转义、日期处理等）。
 */

require_once __DIR__ . '/../includes/logger.php';

function shop_log_exception(string $context, Throwable $exception): void
{
    shop_log('error', $context, ['message' => $exception->getMessage()]);
}

function shop_e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function shop_format_price(float $price): string
{
    $formatted = number_format($price, 2, '.', ',');
    $formatted = rtrim(rtrim($formatted, '0'), '.');
    return '￥' . $formatted;
}

function shop_format_sales(int $sales): string
{
    return number_format($sales, 0, '.', ',');
}

function shop_short_date(string $datetime): string
{
    return date('m-d', strtotime($datetime));
}

function shop_short_datetime(string $datetime): string
{
    return date('m-d H:i', strtotime($datetime));
}

function shop_to_input_datetime(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) return '';
    return date('Y-m-d\TH:i', $timestamp);
}

function shop_from_input_datetime(string $value): string
{
    $timestamp = strtotime($value);
    if ($timestamp === false) return date('Y-m-d H:i:s');
    return date('Y-m-d H:i:s', $timestamp);
}
