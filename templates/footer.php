<!-- templates/footer.php -->
<footer style="padding: 20px; text-align: center; color: #999; font-size: 14px; margin-top: 40px;">
    &copy; 2026 魔女小店 . 版权所有.
</footer>

<script>
    // 验证：点击控制台输出
    document.getElementById('menuBtn').addEventListener('click', () => {
        console.log('菜单图标被点击');
        alert('菜单功能待实现');
    });

    document.getElementById('searchBtn').addEventListener('click', () => {
        console.log('搜索图标被点击');
        alert('搜索功能待实现');
    });

    document.getElementById('cartBtn').addEventListener('click', () => {
        console.log('购物车图标被点击');
        alert('购物车功能待实现');
    });

    // 响应式测试日志
    window.addEventListener('resize', () => {
        console.log(`当前窗口宽度: ${window.innerWidth}px`);
    });

    console.log('页面加载完成，导航栏验证就绪。');
</script>

</body>
</html>