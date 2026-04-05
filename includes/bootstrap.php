<?php
declare(strict_types=1);

/**
 * 应用启动引导：错误配置 + Session 安全参数统一设置。
 * 在 index.php 和所有可直接访问的入口文件中 require。
 */

error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'samesite' => 'Lax',
    ]);
    session_start();
}
