<section class="section" id="admin-dashboard">
    <div class="section-head">
        <div>
            <span class="kicker">首页看板 / 管理后台</span>
            <h1 class="title">数据大盘与运行状态</h1>
            <p class="desc">查看商店的基础统计数据，以及数据库连接状态。</p>
        </div>
    </div>

    <div class="status-grid" aria-label="概览卡片">
        <article class="card"><strong><?php echo shop_format_sales((int) $metrics['count']); ?></strong><span>商品总数</span></article>
        <article class="card"><strong><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong><span>首页固定排序商品</span></article>
        <article class="card"><strong><?php echo shop_format_sales((int) $metrics['page_priority_count']); ?></strong><span>商品页固定排序商品</span></article>
        <article class="card"><strong><?php echo shop_format_sales((int) $metrics['sales']); ?></strong><span>全站累计销量</span></article>
    </div>
</section>

<section class="grid">
    <div class="panel section" id="admin-platform">
        <div class="section-head">
            <div>
                <h2 class="section-title">平台状态</h2>
                <p class="section-note">存储状态、排序规则和数据模式都能在这里快速查看。</p>
            </div>
            <span class="badge">Status</span>
        </div>

        <div class="status-grid two">
            <article class="card"><strong><?php echo shop_e($storageState); ?></strong><span>数据库连接</span><small>MySQL 数据库连接状态</small></article>
            <article class="card"><strong><?php echo shop_e($fileState); ?></strong><span>数据驱动</span><small>数据已由 MySQL 数据库接管</small></article>
        </div>

        <ul class="simple-list">
            <li>首页排序：0 = 按销量排序，非 0 = 固定排序，数字越小越靠前。</li>
            <li>商品页排序：page_sort 与首页排序互不影响。</li>
            <li>保存商品后会写入数据库，前台页面会立即按新排序展示。</li>
        </ul>
        
        <form method="post" class="actions" onsubmit="return confirm('确定要恢复示例数据吗？已保存的商品会被示例数据覆盖。');" style="margin-top: 16px;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="reset_products">
            <button class="btn btn-danger" type="submit">恢复内置示例数据</button>
            <span class="help">如果你想重新测试，可以一键恢复默认示例商品。</span>
        </form>
    </div>
</section>
