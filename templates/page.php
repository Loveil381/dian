<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/pages.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$pageData = $slug !== '' ? shop_get_page_by_slug($slug) : null;

if ($pageData === null) {
    http_response_code(404);
    require_once __DIR__ . '/../includes/error_handler.php';
    shop_error_page(404, '您访问的页面不存在，请检查链接后重试。');
    exit;
}

$pageTitle = (string) $pageData['title'] . ' - 魔女小店';
$pageDescription = strip_tags(mb_substr((string) $pageData['content'], 0, 120)) . '...';
$currentPage = 'page';
$allPages = shop_get_page_slugs();

require __DIR__ . '/header.php';
?>

<main class="page-shell static-page">
    <section class="static-page-hero">
        <nav class="static-page-breadcrumb" aria-label="面包屑导航">
            <a href="index.php?page=home">首页</a>
            <span class="static-page-breadcrumb-sep" aria-hidden="true">›</span>
            <span><?php echo shop_e((string) $pageData['title']); ?></span>
        </nav>
        <h1 class="static-page-title"><?php echo shop_e((string) $pageData['title']); ?></h1>
        <?php if (!empty($pageData['updated_at'])): ?>
            <p class="static-page-updated">最后更新：<?php echo shop_e(date('Y年n月j日', strtotime((string) $pageData['updated_at']))); ?></p>
        <?php endif; ?>
    </section>

    <article class="static-page-body">
        <?php echo (string) $pageData['content']; ?>
    </article>

    <?php if (count($allPages) > 1): ?>
    <nav class="static-page-nav" aria-label="其他页面">
        <?php foreach ($allPages as $p): ?>
            <?php if ($p['slug'] !== $slug): ?>
                <a href="index.php?page=page&slug=<?php echo shop_e((string) $p['slug']); ?>">
                    <?php echo shop_e((string) $p['title']); ?>
                </a>
            <?php else: ?>
                <a class="is-current" aria-current="page" href="index.php?page=page&slug=<?php echo shop_e((string) $p['slug']); ?>">
                    <?php echo shop_e((string) $p['title']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</main>

<?php require __DIR__ . '/footer.php'; ?>
