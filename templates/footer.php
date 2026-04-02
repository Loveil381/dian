<!-- templates/footer.php -->
<?php $showFooter = $showFooter ?? true; ?>

<?php if ($showFooter): ?>
<footer style="padding: 20px; text-align: center; color: #999; font-size: 14px; margin-top: 40px;">
    &copy; 2026 魔女小店. 保留所有权利
</footer>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const menuBtn = document.getElementById('menuBtn');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        const cartBtn = document.getElementById('cartBtn');

        if (menuBtn) {
            menuBtn.addEventListener('click', () => {
                console.log('菜单按钮点击，后续可接入侧边栏');
                alert('菜单功能暂未开放');
            });
        }

        if (searchForm) {
            searchForm.addEventListener('submit', (event) => {
                const keyword = searchInput ? searchInput.value.trim() : '';

                if (keyword !== '') {
                    console.log(`搜索关键词：${keyword}`);
                    return;
                }

                event.preventDefault();
                console.log('搜索词为空');
                alert('请输入搜索关键词');
            });
        }

        if (cartBtn) {
            cartBtn.addEventListener('click', () => {
                console.log('购物车入口已点击');
            });
        }

        window.addEventListener('resize', () => {
            console.log(`当前窗口宽度：${window.innerWidth}px`);
        });

        console.log('页面公共脚本已加载');
    });
</script>

<script src="assets/js/site.js"></script>

</body>
</html>
