<?php declare(strict_types=1); ?>
<?php
$homePreview = $homePreview ?? [];
$pagePreview = $pagePreview ?? [];
?>
<div class="admin-dashboard" id="admin-dashboard">
    <section class="card-hero admin-dashboard-hero">
        <div class="admin-dashboard-hero-copy">
            <span class="badge badge-primary">Phase 3 Ready</span>
            <h1 class="admin-dashboard-title">后台运营概览</h1>
            <p class="admin-dashboard-desc">集中查看商品、排序、文件存储与后台运行状态，便于继续推进后台管理改版。</p>
        </div>
        <div class="admin-dashboard-hero-meta">
            <span class="badge"><?php echo shop_e($storageState); ?></span>
            <span class="badge"><?php echo shop_e($fileState); ?></span>
        </div>
    </section>

    <section class="admin-dashboard-metrics" aria-label="后台核心指标">
        <article class="card admin-dashboard-metric">
            <span class="material-symbols-outlined admin-dashboard-metric-icon" aria-hidden="true">inventory_2</span>
            <strong><?php echo shop_format_sales((int) $metrics['count']); ?></strong>
            <span>商品总数</span>
        </article>
        <article class="card admin-dashboard-metric">
            <span class="material-symbols-outlined admin-dashboard-metric-icon" aria-hidden="true">home_pin</span>
            <strong><?php echo shop_format_sales((int) $metrics['home_priority_count']); ?></strong>
            <span>首页优先商品</span>
        </article>
        <article class="card admin-dashboard-metric">
            <span class="material-symbols-outlined admin-dashboard-metric-icon" aria-hidden="true">grid_view</span>
            <strong><?php echo shop_format_sales((int) $metrics['page_priority_count']); ?></strong>
            <span>列表优先商品</span>
        </article>
        <article class="card admin-dashboard-metric">
            <span class="material-symbols-outlined admin-dashboard-metric-icon" aria-hidden="true">monitoring</span>
            <strong><?php echo shop_format_sales((int) $metrics['sales']); ?></strong>
            <span>累计销量</span>
        </article>
    </section>

    <div class="admin-dashboard-grid">
        <section class="card admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <h2 class="admin-dashboard-section-title">当前状态</h2>
                    <p class="admin-dashboard-section-note">这里显示数据库与文件目录状态，方便确认后台运行环境是否正常。</p>
                </div>
                <span class="badge badge-primary">Status</span>
            </div>

            <div class="admin-dashboard-status-grid">
                <article class="admin-dashboard-status-card">
                    <span class="material-symbols-outlined" aria-hidden="true">database</span>
                    <div>
                        <strong><?php echo shop_e($storageState); ?></strong>
                        <p>数据库连接状态</p>
                    </div>
                </article>
                <article class="admin-dashboard-status-card">
                    <span class="material-symbols-outlined" aria-hidden="true">folder_managed</span>
                    <div>
                        <strong><?php echo shop_e($fileState); ?></strong>
                        <p>上传目录状态</p>
                    </div>
                </article>
            </div>

            <ul class="admin-dashboard-rule-list">
                <li>首页排序使用 `home_sort`，大于 0 的商品会进入首页优先区域。</li>
                <li>列表排序使用 `page_sort`，大于 0 的商品会优先展示。</li>
                <li>修改商品后可以在右侧预览区快速查看排序效果。</li>
            </ul>
        </section>

        <section class="card admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <h2 class="admin-dashboard-section-title">首页预览</h2>
                    <p class="admin-dashboard-section-note">展示当前首页优先的前 6 个商品。</p>
                </div>
                <span class="badge"><?php echo shop_e((string) count($homePreview)); ?> 项</span>
            </div>

            <?php if ($homePreview === []): ?>
                <div class="admin-dashboard-empty">
                    <span class="material-symbols-outlined" aria-hidden="true">home</span>
                    <p>暂未设置首页优先商品。</p>
                </div>
            <?php else: ?>
                <ul class="admin-dashboard-preview-list">
                    <?php foreach ($homePreview as $product): ?>
                        <li class="admin-dashboard-preview-item">
                            <div>
                                <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                <p><?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></p>
                            </div>
                            <div class="admin-dashboard-preview-meta">
                                <span><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></span>
                                <small><?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card admin-dashboard-panel">
            <div class="admin-dashboard-panel-head">
                <div>
                    <h2 class="admin-dashboard-section-title">列表预览</h2>
                    <p class="admin-dashboard-section-note">展示当前商品页优先的前 6 个商品。</p>
                </div>
                <span class="badge"><?php echo shop_e((string) count($pagePreview)); ?> 项</span>
            </div>

            <?php if ($pagePreview === []): ?>
                <div class="admin-dashboard-empty">
                    <span class="material-symbols-outlined" aria-hidden="true">grid_view</span>
                    <p>暂未设置列表优先商品。</p>
                </div>
            <?php else: ?>
                <ul class="admin-dashboard-preview-list">
                    <?php foreach ($pagePreview as $product): ?>
                        <li class="admin-dashboard-preview-item">
                            <div>
                                <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                <p><?php echo shop_e((string) ($product['category'] ?? '未分类')); ?></p>
                            </div>
                            <div class="admin-dashboard-preview-meta">
                                <span><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></span>
                                <small><?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card admin-dashboard-panel admin-dashboard-danger">
            <div class="admin-dashboard-panel-head">
                <div>
                    <h2 class="admin-dashboard-section-title">危险操作</h2>
                    <p class="admin-dashboard-section-note">重置演示数据会恢复默认商品、分类与排序，请在确认测试环境后再执行。</p>
                </div>
            </div>

            <form method="post" class="admin-dashboard-danger-form" data-confirm="确定恢复默认演示数据吗？当前商品、分类和排序会被覆盖。">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="reset_products">
                <button class="btn-danger" type="submit">
                    <span class="material-symbols-outlined" aria-hidden="true">warning</span>
                    <span>重置演示数据</span>
                </button>
                <p class="admin-dashboard-danger-note">此操作会覆盖当前演示商品与排序配置，请谨慎执行。</p>
            </form>
        </section>
    </div>
</div>
