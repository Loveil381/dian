<?php
declare(strict_types=1);

/**
 * 动态站点地图生成器。
 *
 * 输出 XML sitemap，包含首页、商品列表、商品详情、静态页面等公开 URL。
 * robots.txt 中引用 /sitemap.xml，通过 .htaccess 重写到本文件。
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 未安装时直接返回空 sitemap
if (!file_exists(__DIR__ . '/config/database.php')) {
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/data/products.php';

$pdo = get_db_connection();
if (!$pdo instanceof PDO) {
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

$prefix = get_db_prefix();
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$baseUrl = $scheme . '://' . $host;

header('Content-Type: application/xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// ── 首页 ──
echo '<url><loc>' . htmlspecialchars($baseUrl . '/index.php', ENT_XML1, 'UTF-8') . '</loc>';
echo '<changefreq>daily</changefreq><priority>1.0</priority></url>' . "\n";

// ── 商品列表页 ──
echo '<url><loc>' . htmlspecialchars($baseUrl . '/index.php?page=products', ENT_XML1, 'UTF-8') . '</loc>';
echo '<changefreq>daily</changefreq><priority>0.8</priority></url>' . "\n";

// ── 分类列表页 ──
try {
    $stmt = $pdo->query("SELECT `name` FROM `{$prefix}categories` ORDER BY `sort` ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($categories as $cat) {
        $url = $baseUrl . '/index.php?page=products&category=' . rawurlencode((string) $cat);
        echo '<url><loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
        echo '<changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
    }
} catch (PDOException $e) {
    // 分类查询失败不影响整体 sitemap 输出
}

// ── 在售商品详情页 ──
try {
    $stmt = $pdo->query(
        "SELECT `id`, `updated_at` FROM `{$prefix}products` WHERE `status` = 'on_sale' ORDER BY `id` ASC"
    );
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $baseUrl . '/index.php?page=product_detail&id=' . (int) $row['id'];
        $lastmod = '';
        if (!empty($row['updated_at'])) {
            $lastmod = '<lastmod>' . date('Y-m-d', strtotime((string) $row['updated_at'])) . '</lastmod>';
        }
        echo '<url><loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
        echo $lastmod . '<changefreq>weekly</changefreq><priority>0.6</priority></url>' . "\n";
    }
} catch (PDOException $e) {
    // 商品查询失败不影响整体 sitemap 输出
}

// ── 静态页面 ──
try {
    $stmt = $pdo->query("SELECT `slug`, `updated_at` FROM `{$prefix}pages` WHERE `status` = 'published' ORDER BY `id` ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $baseUrl . '/index.php?page=page&slug=' . rawurlencode((string) $row['slug']);
        $lastmod = '';
        if (!empty($row['updated_at'])) {
            $lastmod = '<lastmod>' . date('Y-m-d', strtotime((string) $row['updated_at'])) . '</lastmod>';
        }
        echo '<url><loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
        echo $lastmod . '<changefreq>monthly</changefreq><priority>0.4</priority></url>' . "\n";
    }
} catch (PDOException $e) {
    // pages 表可能不存在（旧版本），忽略
}

// ── 认证页面（登录/注册）──
echo '<url><loc>' . htmlspecialchars($baseUrl . '/index.php?page=auth', ENT_XML1, 'UTF-8') . '</loc>';
echo '<changefreq>monthly</changefreq><priority>0.3</priority></url>' . "\n";

echo '</urlset>' . "\n";
