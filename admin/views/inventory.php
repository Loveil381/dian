<?php declare(strict_types=1); ?>
<section class="admin-inventory-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">库存管理</h2>
            <p class="section-note">重点查看低库存商品，并保留原有商品库存编辑与删除逻辑。</p>
        </div>
        <div class="section-actions">
            <span class="badge">库存总量 <?php echo shop_format_sales((int) ($inventoryStats['stock_total'] ?? 0)); ?> 件</span>
            <?php if ($editingInventory): ?>
                <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=inventory">返回新增</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-inventory-kpi" aria-label="库存概览">
        <div class="admin-inventory-kpi-card admin-inventory-kpi-card--primary">
            <strong class="admin-inventory-kpi-value"><?php echo shop_format_sales((int) ($inventoryStats['total'] ?? 0)); ?></strong>
            <span class="admin-inventory-kpi-label">商品总数</span>
        </div>
        <div class="admin-inventory-kpi-card admin-inventory-kpi-card--primary">
            <strong class="admin-inventory-kpi-value"><?php echo shop_format_sales((int) ($inventoryStats['stock_total'] ?? 0)); ?></strong>
            <span class="admin-inventory-kpi-label">库存总量（件）</span>
        </div>
        <div class="admin-inventory-kpi-card admin-inventory-kpi-card--warning">
            <strong class="admin-inventory-kpi-value"><?php echo shop_format_sales((int) ($inventoryStats['low'] ?? 0)); ?></strong>
            <span class="admin-inventory-kpi-label">低库存（≤50）</span>
        </div>
        <div class="admin-inventory-kpi-card admin-inventory-kpi-card--danger">
            <strong class="admin-inventory-kpi-value"><?php echo shop_format_sales((int) ($inventoryStats['zero'] ?? 0)); ?></strong>
            <span class="admin-inventory-kpi-label">零库存缺货</span>
        </div>
    </div>

    <div class="admin-inventory-hero">
        <div class="admin-inventory-summary">
            <div class="section-head">
                <div>
                    <h3 class="section-title">低库存提醒</h3>
                    <p class="section-note">当前显示库存小于等于 50 的商品，方便优先补货。</p>
                </div>
                <span class="badge"><?php echo count($lowStockProducts); ?> 项</span>
            </div>

            <?php if (empty($lowStockProducts)): ?>
                <ul class="simple-list">
                    <li>当前没有低库存商品。</li>
                </ul>
            <?php else: ?>
                <div class="admin-inventory-lowstock">
                    <?php foreach ($lowStockProducts as $product): ?>
                        <article class="admin-inventory-lowstock-item">
                            <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                            <small>
                                库存 <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件 /
                                销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?> 件 /
                                <?php echo shop_e(shop_sort_label($product, 'home_sort', '首页排序')); ?> /
                                <?php echo shop_e(shop_sort_label($product, 'page_sort', '列表排序')); ?>
                            </small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="admin-inventory-summary admin-inventory-form">
            <div class="section-head">
                <div>
                    <h3 class="section-title"><?php echo $editingInventory ? '编辑库存商品' : '新增库存商品'; ?></h3>
                    <p class="section-note">保留现有商品保存逻辑，只调整表单排版和信息分组。</p>
                </div>
                <span class="badge"><?php echo $editingInventory ? '编辑中' : '新建'; ?></span>
            </div>

            <form method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                <input type="hidden" name="admin_action" value="save_product">
                <input type="hidden" name="id" value="<?php echo (int) ($selectedInventoryForm['id'] ?? 0); ?>">

                <div class="form-grid">
                    <label class="field field-full">
                        <span class="label">商品名称</span>
                        <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedInventoryForm['name'] ?? '')); ?>" placeholder="请输入商品名称">
                    </label>

                    <label class="field">
                        <span class="label">商品分类</span>
                        <select name="category" required>
                            <?php foreach ($categoryChoices as $category): ?>
                                <option value="<?php echo shop_e($category); ?>" <?php echo (string) ($selectedInventoryForm['category'] ?? '') === $category ? 'selected' : ''; ?>>
                                    <?php echo shop_e($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field">
                        <span class="label">库存数量</span>
                        <input type="number" min="0" name="stock" value="<?php echo (int) ($selectedInventoryForm['stock'] ?? 0); ?>">
                    </label>

                    <label class="field">
                        <span class="label">价格</span>
                        <input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedInventoryForm['price'] ?? '0')); ?>">
                    </label>

                    <label class="field">
                        <span class="label">商品状态</span>
                        <select name="status">
                            <option value="on_sale" <?php echo (string) ($selectedInventoryForm['status'] ?? 'on_sale') === 'on_sale' ? 'selected' : ''; ?>>上架</option>
                            <option value="off_sale" <?php echo (string) ($selectedInventoryForm['status'] ?? '') === 'off_sale' ? 'selected' : ''; ?>>下架</option>
                        </select>
                    </label>

                    <input type="hidden" name="sales" value="<?php echo (int) ($selectedInventoryForm['sales'] ?? 0); ?>">
                    <input type="hidden" name="published_at" value="<?php echo shop_e($selectedInventoryPublishedAtInput); ?>">
                    <input type="hidden" name="tag" value="<?php echo shop_e((string) ($selectedInventoryForm['tag'] ?? '')); ?>">
                    <input type="hidden" name="home_sort" value="<?php echo (int) ($selectedInventoryForm['home_sort'] ?? 0); ?>">
                    <input type="hidden" name="page_sort" value="<?php echo (int) ($selectedInventoryForm['page_sort'] ?? 0); ?>">
                    <?php
                    // 保留原有SKU数据，解析JSON并作为数组提交
                    $existingSkus = [];
                    if (!empty($selectedInventoryForm['sku'])) {
                        $decodedSkus = json_decode((string) $selectedInventoryForm['sku'], true);
                        if (is_array($decodedSkus)) {
                            $existingSkus = $decodedSkus;
                        }
                    }
                    foreach ($existingSkus as $index => $sku):
                    ?>
                        <input type="hidden" name="sku[<?php echo $index; ?>][name]" value="<?php echo shop_e((string) ($sku['name'] ?? '')); ?>">
                        <input type="hidden" name="sku[<?php echo $index; ?>][stock]" value="<?php echo (int) ($sku['stock'] ?? 0); ?>">
                        <input type="hidden" name="sku[<?php echo $index; ?>][price]" value="<?php echo (float) ($sku['price'] ?? 0); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="cover_image" value="<?php echo shop_e((string) ($selectedInventoryForm['cover_image'] ?? '')); ?>">
                    <input type="hidden" name="description" value="<?php echo shop_e((string) ($selectedInventoryForm['description'] ?? '')); ?>">
                </div>

                <div class="actions">
                    <button class="btn btn-primary" type="submit"><?php echo $editingInventory ? '保存库存' : '创建库存商品'; ?></button>
                    <?php if ($editingInventory): ?>
                        <a class="btn btn-secondary" href="index.php?page=admin&tab=inventory">取消编辑</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-inventory-table panel section">
        <div class="section-head">
            <div>
                <h3 class="section-title">库存列表</h3>
                <p class="section-note">按库存由低到高展示，便于优先处理缺货商品。</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 30%;">商品</th>
                        <th style="width: 15%;">分类</th>
                        <th style="width: 15%;">库存</th>
                        <th style="width: 15%;">销量</th>
                        <th style="width: 25%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventoryRows)): ?>
                        <tr>
                            <td colspan="5" class="meta" style="padding: 20px 10px;">当前没有可管理的库存商品。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventoryRows as $product): ?>
                            <?php $stockValue = (int) ($product['stock'] ?? 0); ?>
                            <tr>
                                <td>
                                    <div class="name">#<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?></div>
                                    <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                </td>
                                <td class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></td>
                                <td>
                                    <div class="name <?php echo $stockValue <= ($lowStockThreshold ?? 50) ? 'admin-stock-low' : ''; ?>">
                                        <?php echo shop_format_sales($stockValue); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="name"><?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=inventory&edit_inventory=<?php echo (int) ($product['id'] ?? 0); ?>">编辑</a>
                                        <form method="post" data-confirm="确定删除这个库存商品吗？">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="admin_action" value="delete_product">
                                            <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">删除商品</button>
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
