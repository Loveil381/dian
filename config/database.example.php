<?php
declare(strict_types=1);

/**
 * 生产环境数据库配置示例。
 * 推荐优先使用环境变量：DB_HOST、DB_PORT、DB_NAME、DB_USER、DB_PASSWORD、DB_CHARSET。
 */
return [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'name' => 'shop_demo',
    'user' => 'root',
    'password' => '',
    'prefix' => 'shop_',
    'charset' => 'utf8mb4',
];
