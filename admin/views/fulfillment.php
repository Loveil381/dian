<?php declare(strict_types=1); ?>

<section class="admin-section">
    <div class="section-head">
        <div>
            <h2 class="section-title"><?php echo $editingFulfillment ? '编辑发货方式' : '新增发货方式'; ?></h2>
            <p class="section-note">定义商品的发货方式类型（如国内现货、预售等），然后在商品编辑页中指派给具体商品。</p>
        </div>
    </div>

    <form method="post" class="admin-form-card">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="tab" value="fulfillment">
        <input type="hidden" name="admin_action" value="save_fulfillment_type">
        <input type="hidden" name="id" value="<?php echo (int) ($selectedFulfillment['id'] ?? 0); ?>">

        <div class="form-grid">
            <label class="field">
                <span class="label">类型名称</span>
                <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedFulfillment['name'] ?? '')); ?>" placeholder="例如：国内现货">
            </label>

            <label class="field">
                <span class="label">标识符 (slug)</span>
                <input type="text" name="slug" required value="<?php echo shop_e((string) ($selectedFulfillment['slug'] ?? '')); ?>" placeholder="例如：in_stock" pattern="[a-z0-9_]+" title="仅限小写字母、数字和下划线">
                <span class="help">系统内部标识，创建后建议不要修改。</span>
            </label>

            <label class="field field-full">
                <span class="label">描述</span>
                <input type="text" name="description" value="<?php echo shop_e((string) ($selectedFulfillment['description'] ?? '')); ?>" placeholder="客户可见的简要说明">
            </label>

            <label class="field">
                <span class="label">图标</span>
                <input type="text" name="icon" value="<?php echo shop_e((string) ($selectedFulfillment['icon'] ?? 'local_shipping')); ?>" placeholder="Material icon 名">
                <span class="help">Material Symbols 图标名，如 inventory_2、schedule。</span>
            </label>

            <label class="field">
                <span class="label">徽标颜色</span>
                <input type="color" name="badge_color" value="<?php echo shop_e((string) ($selectedFulfillment['badge_color'] ?? '#6a37d4')); ?>">
            </label>

            <label class="field">
                <span class="label">排序</span>
                <input type="number" name="sort" min="0" value="<?php echo (int) ($selectedFulfillment['sort'] ?? 0); ?>">
                <span class="help">数字越小越靠前。</span>
            </label>

            <label class="field">
                <span class="label">状态</span>
                <select name="status">
                    <option value="active" <?php echo ($selectedFulfillment['status'] ?? '') === 'active' ? 'selected' : ''; ?>>启用</option>
                    <option value="inactive" <?php echo ($selectedFulfillment['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>停用</option>
                </select>
            </label>

            <div class="field">
                <span class="label">允许零库存购买</span>
                <label class="admin-checkbox-label">
                    <input type="hidden" name="allow_zero_stock" value="0">
                    <input type="checkbox" name="allow_zero_stock" value="1" <?php echo ((int) ($selectedFulfillment['allow_zero_stock'] ?? 0)) === 1 ? 'checked' : ''; ?>>
                    <span>开启后，指派了此发货方式的商品在库存为 0 时仍可购买（预售模式）。</span>
                </label>
            </div>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit"><?php echo $editingFulfillment ? '保存' : '新增'; ?></button>
            <?php if ($editingFulfillment): ?>
                <a class="btn btn-secondary" href="index.php?page=admin&tab=fulfillment">取消编辑</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="section-head" style="margin-top: var(--space-xl);">
        <div>
            <h3 class="section-title">已有发货方式</h3>
            <p class="section-note">共 <?php echo count($fulfillmentTypes); ?> 种发货方式。</p>
        </div>
    </div>

    <?php if (empty($fulfillmentTypes)): ?>
        <div class="admin-empty-state">
            <p>暂无发货方式，请先创建。</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>名称</th>
                        <th>标识符</th>
                        <th>图标</th>
                        <th>零库存</th>
                        <th>排序</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfillmentTypes as $ft): ?>
                        <tr>
                            <td><?php echo (int) $ft['id']; ?></td>
                            <td>
                                <span class="badge" style="background: <?php echo shop_e((string) $ft['badge_color']); ?>; color: #fff;">
                                    <?php echo shop_e((string) $ft['name']); ?>
                                </span>
                            </td>
                            <td><code><?php echo shop_e((string) $ft['slug']); ?></code></td>
                            <td><span class="material-symbols-outlined" style="font-size:1.25rem;"><?php echo shop_e((string) $ft['icon']); ?></span></td>
                            <td><?php echo ((int) $ft['allow_zero_stock']) === 1 ? '✓ 允许' : '—'; ?></td>
                            <td><?php echo (int) $ft['sort']; ?></td>
                            <td>
                                <span class="badge <?php echo $ft['status'] === 'active' ? 'badge-success' : 'badge-muted'; ?>">
                                    <?php echo $ft['status'] === 'active' ? '启用' : '停用'; ?>
                                </span>
                            </td>
                            <td class="admin-table-actions">
                                <a class="btn btn-ghost btn-sm" href="index.php?page=admin&tab=fulfillment&edit_fulfillment=<?php echo (int) $ft['id']; ?>">编辑</a>
                                <form method="post" class="inline-form" onsubmit="return confirm('确定删除「<?php echo shop_e((string) $ft['name']); ?>」？');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tab" value="fulfillment">
                                    <input type="hidden" name="admin_action" value="delete_fulfillment_type">
                                    <input type="hidden" name="id" value="<?php echo (int) $ft['id']; ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
