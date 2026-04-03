<?php declare(strict_types=1); ?>
<div class="admin-categories" id="admin-categories">
    <section class="card-hero admin-categories-hero">
        <div class="admin-categories-hero-copy">
            <span class="badge badge-primary">Category Management</span>
            <h1 class="admin-categories-title">分类管理</h1>
            <p class="admin-categories-desc">维护分类名称、图标、描述与排序，保留原有新增、编辑、删除逻辑。</p>
        </div>
        <div class="admin-categories-hero-meta">
            <span class="badge"><?php echo shop_e((string) count($categoryManagementRows)); ?> 个分类</span>
            <?php if ($editingCategory): ?>
                <span class="badge badge-warning">编辑中</span>
            <?php endif; ?>
        </div>
    </section>

    <div class="admin-categories-grid">
        <section class="card admin-categories-form-panel">
            <div class="admin-categories-panel-head">
                <div>
                    <h2 class="admin-categories-section-title"><?php echo $editingCategory ? '编辑分类' : '新增分类'; ?></h2>
                    <p class="admin-categories-section-note">表单字段与提交逻辑保持不变，只整理信息结构与操作入口。</p>
                </div>
                <?php if ($editingCategory): ?>
                    <a class="btn-ghost" href="index.php?page=admin&tab=categories">返回新增</a>
                <?php endif; ?>
            </div>

            <form method="post" class="admin-categories-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                <input type="hidden" name="admin_action" value="save_category">
                <input type="hidden" name="id" value="<?php echo (int) ($selectedCategoryForm['id'] ?? 0); ?>">

                <label class="admin-categories-field">
                    <span class="font-label admin-categories-label">分类名称</span>
                    <input class="input" type="text" name="name" required value="<?php echo shop_e((string) ($selectedCategoryForm['name'] ?? '')); ?>" placeholder="请输入分类名称">
                </label>

                <div class="admin-categories-form-row">
                    <label class="admin-categories-field">
                        <span class="font-label admin-categories-label">分类图标</span>
                        <input class="input" type="text" name="emoji" maxlength="4" value="<?php echo shop_e((string) ($selectedCategoryForm['emoji'] ?? '')); ?>" placeholder="例如：🛍️">
                    </label>

                    <label class="admin-categories-field">
                        <span class="font-label admin-categories-label">强调色</span>
                        <input class="input" type="text" name="accent" value="<?php echo shop_e((string) ($selectedCategoryForm['accent'] ?? '#cbd5e1')); ?>" placeholder="#6a37d4">
                    </label>
                </div>

                <label class="admin-categories-field">
                    <span class="font-label admin-categories-label">排序</span>
                    <input class="input" type="number" min="0" name="sort" value="<?php echo (int) ($selectedCategoryForm['sort'] ?? 0); ?>">
                </label>

                <label class="admin-categories-field">
                    <span class="font-label admin-categories-label">分类描述</span>
                    <textarea class="input" name="description" placeholder="请输入分类描述"><?php echo shop_e((string) ($selectedCategoryForm['description'] ?? '')); ?></textarea>
                </label>

                <div class="admin-categories-form-actions">
                    <button class="btn-primary" type="submit">
                        <span class="material-symbols-outlined" aria-hidden="true">auto_fix_high</span>
                        <span><?php echo $editingCategory ? '保存分类' : '创建分类'; ?></span>
                    </button>
                    <p class="admin-categories-help">保存后会继续使用原有分类同步逻辑。</p>
                </div>
            </form>
        </section>

        <section class="card admin-categories-list-panel">
            <div class="admin-categories-panel-head">
                <div>
                    <h2 class="admin-categories-section-title">分类列表</h2>
                    <p class="admin-categories-section-note">可查看排序、商品数量、主力商品，并继续执行编辑或删除。</p>
                </div>
                <span class="badge badge-primary"><?php echo shop_e((string) count($categoryManagementRows)); ?> 项</span>
            </div>

            <?php if (empty($categoryManagementRows)): ?>
                <div class="admin-categories-empty">
                    <span class="material-symbols-outlined" aria-hidden="true">category</span>
                    <p>当前还没有分类，请先创建一个分类。</p>
                </div>
            <?php else: ?>
                <div class="admin-categories-list">
                    <?php foreach ($categoryManagementRows as $category): ?>
                        <?php $categoryDescription = trim((string) ($category['description'] ?? '')); ?>
                        <article class="admin-category-item">
                            <div class="admin-category-item-head">
                                <div class="admin-category-item-title-wrap">
                                    <div class="admin-category-item-icon"><?php echo shop_e((string) ($category['emoji'] ?? '🛍️')); ?></div>
                                    <div>
                                        <h3 class="admin-category-item-title"><?php echo shop_e((string) ($category['name'] ?? '')); ?></h3>
                                        <p class="admin-category-item-desc"><?php echo shop_e($categoryDescription !== '' ? $categoryDescription : '暂未填写分类描述。'); ?></p>
                                    </div>
                                </div>
                                <div class="admin-category-item-badges">
                                    <span class="badge">排序 <?php echo (int) ($category['sort'] ?? 0); ?></span>
                                    <span class="badge badge-primary"><?php echo shop_format_sales((int) ($category['count'] ?? 0)); ?></span>
                                </div>
                            </div>

                            <div class="admin-category-item-meta">
                                <div class="admin-category-item-meta-block">
                                    <span class="admin-category-item-meta-label">强调色</span>
                                    <strong><?php echo shop_e((string) ($category['accent'] ?? '#cbd5e1')); ?></strong>
                                </div>
                                <div class="admin-category-item-meta-block">
                                    <span class="admin-category-item-meta-label">最高销量商品</span>
                                    <strong><?php echo shop_e((string) ($category['top_name'] ?? '暂无商品')); ?></strong>
                                    <small>销量 <?php echo shop_format_sales((int) ($category['top_sales'] ?? 0)); ?></small>
                                </div>
                            </div>

                            <div class="admin-category-item-actions">
                                <a class="btn-secondary btn-sm" href="index.php?page=admin&tab=categories&edit_category=<?php echo (int) ($category['id'] ?? 0); ?>">
                                    <span class="material-symbols-outlined" aria-hidden="true">edit</span>
                                    <span>编辑</span>
                                </a>
                                <form method="post" data-confirm="确定删除这个分类吗？">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tab" value="<?php echo shop_e($currentTab); ?>">
                                    <input type="hidden" name="admin_action" value="delete_category">
                                    <input type="hidden" name="id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
                                    <button class="btn-danger btn-sm" type="submit">
                                        <span class="material-symbols-outlined" aria-hidden="true">delete</span>
                                        <span>删除</span>
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
