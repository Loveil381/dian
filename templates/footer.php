<!-- templates/footer.php -->
<?php $showFooter = $showFooter ?? true; ?>

<?php if ($showFooter): ?>
<footer style="padding: 20px; text-align: center; color: #999; font-size: 14px; margin-top: 40px;">
    &copy; 2026 魔女小店 . 版权所有.
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
                console.log('菜单图标被点击');
                alert('菜单功能待实现');
            });
        }

        if (searchForm) {
            searchForm.addEventListener('submit', (event) => {
                const keyword = searchInput ? searchInput.value.trim() : '';

                if (keyword) {
                    console.log(`搜索关键词：${keyword}`);
                    return;
                } else {
                    event.preventDefault();
                    console.log('搜索框为空');
                    alert('请输入搜索内容');
                }
            });
        }

        if (cartBtn) {
            cartBtn.addEventListener('click', () => {
                console.log('购物车图标被点击');
                alert('购物车功能待实现');
            });
        }

        window.addEventListener('resize', () => {
            console.log(`当前窗口宽度: ${window.innerWidth}px`);
        });

        console.log('页面加载完成，导航栏验证就绪。');
    });
</script>

</body>
</html>