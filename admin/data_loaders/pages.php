<?php
declare(strict_types=1);

/**
 * Pages tab 数据加载。
 * 依赖父作用域：（无特殊依赖）
 * 设置变量：$pageRows
 */

require_once __DIR__ . '/../../data/pages.php';

$pageRows = shop_get_all_pages();
