<?php
$pageTitle = "简洁卖货平台 - 首页";
include 'templates/header.php';
?>

<main style="padding: 40px 20px; text-align: center; color: #666;">
    <h1 style="margin-bottom: 20px;">欢迎来到魔女小店</h1>
    <p>这是一个演示页面，用于验证导航栏功能和响应式设计。</p>
    
    <div style="background-color: #333; color: #fff; padding: 40px; margin-top: 40px; border-radius: 8px;">
        <p>在此深色背景下测试分割线可见性（上方导航栏底部）</p>
        <p style="font-size: 14px; color: #ccc; margin-top: 10px;">
            分割线颜色为 #f0f0f0，透明度 0.6，在深色背景下应清晰可见且柔和。
        </p>
    </div>

    <div style="margin-top: 40px; text-align: left; max-width: 800px; margin-left: auto; margin-right: auto; line-height: 1.6;">
        <h3>验证项：</h3>
        <ul>
            <li><strong>图标点击：</strong>点击顶部菜单、搜索、购物车图标，控制台将输出日志。</li>
            <li><strong>分割线：</strong>导航栏下方 1px 高的线，颜色 #f0f0f0 (0.6 opacity)。</li>
            <li><strong>响应式：</strong>支持 320px - 1920px 宽度。</li>
            <li><strong>语义化：</strong>使用了 &lt;header&gt;, &lt;main&gt;, &lt;footer&gt;, &lt;button&gt; 及 aria-label。</li>
        </ul>
    </div>
</main>

<?php include 'templates/footer.php'; ?>