<?php
declare(strict_types=1);

$showFooter = $showFooter ?? true;
?>
<!-- templates/footer.php -->

<?php if ($showFooter): ?>
<?php
require_once __DIR__ . '/../data/pages.php';
$footerPages = shop_get_page_slugs();
?>
<footer class="site-footer">
    <?php if (!empty($footerPages)): ?>
    <nav class="site-footer-links">
        <?php foreach ($footerPages as $fp): ?>
            <a href="index.php?page=page&slug=<?php echo shop_e((string) $fp['slug']); ?>"><?php echo shop_e((string) $fp['title']); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <p>&copy; 2026 魔女小店，愿你轻松挑到喜欢的商品</p>
</footer>
<?php endif; ?>

<script src="assets/js/site.js"></script>

</body>
</html>
