<section class="grid">
    <div class="panel section" id="admin-categories">
        <div class="section-head">
            <div>
                <h2 class="section-title">分类管理</h2>
                <p class="section-note">支持添加、编辑和删除分类，分类会同步影响商品归类展示。</p>
            </div>
            <div class="section-actions">
                <span class="badge"><?php echo count($categoryManagementRows); ?> 个分类</span>
                <?php if ($editingCategory): ?>
                    <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=categories">添加分类</a>
                <?php endif; ?>
            </div>
        </div>

        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_category">
            <input type="hidden" name="id" value="<?php echo (int) ($selectedCategoryForm['id'] ?? 0); ?>">

            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">分类名称</span>
                    <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedCategoryForm['name'] ?? '')); ?>" placeholder="请输入分类名称">
                </label>

                <label class="field">
                    <span class="label">分类图标</span>
                    <input type="text" name="emoji" maxlength="4" value="<?php echo shop_e((string) ($selectedCategoryForm['emoji'] ?? '🛍️')); ?>" placeholder="例如：🍰">
                </label>

                <label class="field">
                    <span class="label">主题颜色</span>
                    <input type="text" name="accent" value="<?php echo shop_e((string) ($selectedCategoryForm['accent'] ?? '#cbd5e1')); ?>" placeholder="#f59e0b">
                </label>

                <label class="field">
                    <span class="label">排序</span>
                    <input type="number" min="0" name="sort" value="<?php echo (int) ($selectedCategoryForm['sort'] ?? 0); ?>">
                </label>

                <label class="field field-full">
                    <span class="label">分类说明</span>
                    <textarea name="description" placeholder="请输入分类说明"><?php echo shop_e((string) ($selectedCategoryForm['description'] ?? '')); ?></textarea>
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit"><?php echo $editingCategory ? '保存分类' : '添加分类'; ?></button>
                <?php if ($editingCategory): ?>
                    <a class="btn btn-secondary" href="index.php?page=admin&tab=categories">取消编辑</a>
                <?php endif; ?>
                <span class="help">保存的分类会即时刷新到客户端。</span>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 24%;">分类</th>
                        <th style="width: 34%;">说明</th>
                        <th style="width: 10%;">排序</th>
                        <th style="width: 12%;">商品数</th>
                        <th style="width: 20%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categoryManagementRows)): ?>
                        <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无分类，请新增。</td></tr>
                    <?php else: ?>
                        <?php foreach ($categoryManagementRows as $category): ?>
                            <tr>
                                <td>
                                    <div class="name" style="display:flex; align-items:center; gap:8px;">
                                        <span style="display:inline-flex; width:12px; height:12px; border-radius:50%; background:<?php echo shop_e((string) ($category['accent'] ?? '#cbd5e1')); ?>;"></span>
                                        <?php echo shop_e((string) ($category['emoji'] ?? '🛍️') . ' ' . (string) ($category['name'] ?? '')); ?>
                                    </div>
                                    <div class="meta">Top：<?php echo shop_e((string) ($category['top_name'] ?? '暂无商品')); ?> · 销量 <?php echo shop_format_sales((int) ($category['top_sales'] ?? 0)); ?></div>
                                </td>
                                <td class="meta"><?php echo shop_e((string) ($category['description'] ?? '')); ?></td>
                                <td><div class="name"><?php echo (int) ($category['sort'] ?? 0); ?></div></td>
                                <td><div class="name"><?php echo shop_format_sales((int) ($category['count'] ?? 0)); ?></div></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=categories&edit_category=<?php echo (int) ($category['id'] ?? 0); ?>">编辑</a>
                                        <form method="post" onsubmit="return confirm('确定删除这个分类吗？');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="admin_action" value="delete_category">
                                            <input type="hidden" name="id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
