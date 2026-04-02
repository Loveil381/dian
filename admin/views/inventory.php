<section class="grid">
    <div class="panel section" id="admin-stock">
        <div class="section-head">
            <div>
                <h2 class="section-title">缺货看板</h2>
                <p class="section-note">库存低于 50 的商品会自动汇总，便于及时补货。</p>
            </div>
            <span class="badge"><?php echo count($lowStockProducts); ?> 个预警</span>
        </div>

        <?php if (empty($lowStockProducts)): ?>
            <ul class="simple-list"><li>一切良好，暂无商品库存告急。</li></ul>
        <?php else: ?>
            <ul class="simple-list">
                <?php foreach ($lowStockProducts as $product): ?>
                    <li>
                        <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                        <small>现余：<?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?> 件 · 已卖：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?> · <?php echo shop_e(shop_sort_label($product, 'home_sort', '首页')); ?> / <?php echo shop_e(shop_sort_label($product, 'page_sort', '大盘')); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<section class="grid" style="margin-top: 16px;">
    <div class="panel section" id="admin-inventory">
        <div class="section-head">
            <div>
                <h2 class="section-title">库存管理</h2>
                <p class="section-note">单独调仓与改价通道。</p>
            </div>
            <div class="section-actions">
                <span class="badge">总库存: <?php echo shop_format_sales($inventoryStats['stock_total'] ?? 0); ?> 件</span>
                <?php if ($editingInventory): ?>
                    <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=inventory">新增库存商品</a>
                <?php endif; ?>
            </div>
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
                            <option value="<?php echo shop_e($category); ?>" <?php echo (string)($selectedInventoryForm['category'] ?? '') === $category ? 'selected' : ''; ?>><?php echo shop_e($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                
                <label class="field">
                    <span class="label">库存数量 (即时更改)</span>
                    <input type="number" min="0" name="stock" value="<?php echo (int) ($selectedInventoryForm['stock'] ?? 0); ?>">
                </label>
                
                <label class="field">
                    <span class="label">价格</span>
                    <input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedInventoryForm['price'] ?? '0')); ?>">
                </label>
                
                <label class="field">
                    <span class="label">状态</span>
                    <select name="status">
                        <option value="on_sale" <?php echo (string)($selectedInventoryForm['status'] ?? 'on_sale') === 'on_sale' ? 'selected' : ''; ?>>正常销售</option>
                        <option value="off_sale" <?php echo (string)($selectedInventoryForm['status'] ?? '') === 'off_sale' ? 'selected' : ''; ?>>隐藏下架</option>
                    </select>
                </label>
                
                <input type="hidden" name="sales" value="<?php echo (int) ($selectedInventoryForm['sales'] ?? 0); ?>">
                <input type="hidden" name="published_at" value="<?php echo shop_e($selectedInventoryPublishedAtInput); ?>">
                <input type="hidden" name="tag" value="<?php echo shop_e((string) ($selectedInventoryForm['tag'] ?? '')); ?>">
                <input type="hidden" name="home_sort" value="<?php echo (int) ($selectedInventoryForm['home_sort'] ?? 0); ?>">
                <input type="hidden" name="page_sort" value="<?php echo (int) ($selectedInventoryForm['page_sort'] ?? 0); ?>">
                <input type="hidden" name="sku" value="<?php echo shop_e((string) ($selectedInventoryForm['sku'] ?? '')); ?>">
                <input type="hidden" name="cover_image" value="<?php echo shop_e((string) ($selectedInventoryForm['cover_image'] ?? '')); ?>">
                <input type="hidden" name="description" value="<?php echo shop_e((string) ($selectedInventoryForm['description'] ?? '')); ?>">
            </div>
            
            <div class="actions">
                <button class="btn btn-primary" type="submit"><?php echo $editingInventory ? '保存库存' : '入库商品'; ?></button>
                <?php if ($editingInventory): ?>
                    <a class="btn btn-secondary" href="index.php?page=admin&tab=inventory">取消</a>
                <?php endif; ?>
            </div>
        </form>
        
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 30%;">商品号与名称</th>
                        <th style="width: 15%;">分类</th>
                        <th style="width: 15%;">库存基数</th>
                        <th style="width: 15%;">累销</th>
                        <th style="width: 25%;">快排操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventoryRows)): ?>
                        <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无可盘点的商品存货。</td></tr>
                    <?php else: ?>
                        <?php foreach ($inventoryRows as $product): ?>
                            <tr>
                                <td>
                                    <div class="name">#<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?></div>
                                    <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                </td>
                                <td class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></td>
                                <td>
                                    <div class="name" style="color: <?php echo (int)($product['stock'] ?? 0) <= 50 ? '#dc2626' : 'inherit'; ?>">
                                        <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?>
                                    </div>
                                </td>
                                <td><div class="name"><?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div></td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=inventory&edit_inventory=<?php echo (int) ($product['id'] ?? 0); ?>">操作</a>
                                        <form method="post" data-confirm="确认永久下架并清理该商品库存记录？">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="admin_action" value="delete_product">
                                            <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">清理出库</button>
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
