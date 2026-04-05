<?php declare(strict_types=1); ?>
<div class="admin-pages" id="admin-pages">
    <section class="card-hero admin-pages-hero">
        <div class="admin-pages-hero-copy">
            <span class="badge badge-primary">Page Management</span>
            <h1 class="admin-pages-title">页面管理</h1>
            <p class="admin-pages-desc">维护隐私政策、用户协议、退换货政策等页面内容。</p>
        </div>
        <div class="admin-pages-hero-meta">
            <span class="badge"><?php echo shop_e((string) count($pageRows)); ?> 个页面</span>
            <?php if ($editingPage): ?>
                <span class="badge badge-warning">编辑中</span>
            <?php endif; ?>
        </div>
    </section>

    <div class="admin-pages-grid">
        <?php if ($editingPage): ?>
        <section class="card admin-pages-form-panel">
            <div class="admin-pages-panel-head">
                <div>
                    <h2 class="admin-pages-section-title">编辑页面</h2>
                    <p class="admin-pages-section-note">修改页面标题和内容，支持 HTML 格式。</p>
                </div>
                <a class="btn-ghost" href="index.php?page=admin&tab=pages">返回列表</a>
            </div>

            <form method="post" class="admin-pages-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="save_page">
                <input type="hidden" name="id" value="<?php echo (int) ($editingPage['id'] ?? 0); ?>">

                <div class="admin-pages-field">
                    <span class="font-label admin-pages-label">页面标识</span>
                    <span class="badge"><?php echo shop_e((string) ($editingPage['slug'] ?? '')); ?></span>
                </div>

                <label class="admin-pages-field">
                    <span class="font-label admin-pages-label">页面标题</span>
                    <input class="input" type="text" name="title" required value="<?php echo shop_e((string) ($editingPage['title'] ?? '')); ?>" placeholder="请输入页面标题">
                </label>

                <label class="admin-pages-field">
                    <span class="font-label admin-pages-label">页面内容（HTML）</span>
                    <textarea class="input" name="content" rows="20" placeholder="请输入页面内容"><?php echo shop_e((string) ($editingPage['content'] ?? '')); ?></textarea>
                </label>

                <div class="admin-pages-form-actions">
                    <button class="btn-primary" type="submit">
                        <span class="material-symbols-outlined" aria-hidden="true">save</span>
                        <span>保存页面</span>
                    </button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <section class="card admin-pages-list-panel">
            <div class="admin-pages-panel-head">
                <div>
                    <h2 class="admin-pages-section-title">页面列表</h2>
                    <p class="admin-pages-section-note">点击编辑按钮修改页面内容。</p>
                </div>
                <span class="badge badge-primary"><?php echo shop_e((string) count($pageRows)); ?> 项</span>
            </div>

            <?php if (empty($pageRows)): ?>
                <div class="admin-pages-empty">
                    <span class="material-symbols-outlined" aria-hidden="true">description</span>
                    <p>当前还没有页面数据，请运行数据库迁移。</p>
                </div>
            <?php else: ?>
                <div class="admin-pages-list">
                    <?php foreach ($pageRows as $row): ?>
                        <article class="admin-page-item">
                            <div class="admin-page-item-head">
                                <div>
                                    <h3 class="admin-page-item-title"><?php echo shop_e((string) ($row['title'] ?? '')); ?></h3>
                                    <p class="admin-page-item-slug"><?php echo shop_e((string) ($row['slug'] ?? '')); ?></p>
                                </div>
                                <div class="admin-page-item-badges">
                                    <span class="badge"><?php echo shop_e((string) ($row['updated_at'] ?? '')); ?></span>
                                </div>
                            </div>
                            <div class="admin-page-item-actions">
                                <a class="btn-secondary btn-sm" href="index.php?page=admin&tab=pages&edit_page=<?php echo (int) ($row['id'] ?? 0); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                    <span>编辑</span>
                                </a>
                                <a class="btn-ghost btn-sm" href="index.php?page=page&slug=<?php echo shop_e((string) ($row['slug'] ?? '')); ?>" target="_blank">
                                    <span class="material-symbols-outlined" aria-hidden="true">open_in_new</span>
                                    <span>预览</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
