<?php
declare(strict_types=1);

/**
 * 应用版本号。
 *
 * 每次发布新版本时更新此常量。
 * 更新中心依赖此值与 GitHub Releases 比对。
 */

const SHOP_APP_VERSION = '1.5.2';

function shop_app_version(): string
{
    return SHOP_APP_VERSION;
}
