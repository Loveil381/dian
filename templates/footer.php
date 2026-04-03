<!-- templates/footer.php -->
<?php $showFooter = $showFooter ?? true; ?>

<style>
    .site-footer {
        background: var(--color-surface-container-low);
        color: var(--color-on-surface-variant);
        padding: var(--space-lg);
        padding-bottom: 80px;
        text-align: center;
        font-size: var(--text-caption);
    }
</style>

<?php if ($showFooter): ?>
<footer class="site-footer">
    &copy; 2026 魔女小店，保留所有权利
</footer>
<?php endif; ?>

<script src="assets/js/site.js"></script>

</body>
</html>
