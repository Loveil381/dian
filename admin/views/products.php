<section class="grid">
    <div class="panel section" id="admin-editor">
        <div class="section-head">
            <div>
                <h2 class="section-title"><?php echo $editingProduct ? '编辑商品' : '新增商品'; ?></h2>
                <p class="section-note">这里可以同时设置首页排序和商品页排序，也可以修改基础信息。</p>
            </div>
            <span class="badge"><?php echo $editingProduct ? '编辑模式' : '新增模式'; ?></span>
        </div>

        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_product">
            <input type="hidden" name="id" value="<?php echo (int) ($selectedProduct['id'] ?? 0); ?>">

            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">商品名称</span>
                    <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedProduct['name'] ?? '')); ?>" placeholder="请输入商品名称">
                </label>

                <label class="field">
                    <span class="label">商品分类</span>
                    <select name="category" required>
                        <?php foreach ($categoryChoices as $category): ?>
                            <option value="<?php echo shop_e($category); ?>" <?php echo $selectedCategory === $category ? 'selected' : ''; ?>><?php echo shop_e($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="field">
                    <span class="label">商品状态</span>
                    <select name="status">
                        <option value="on_sale" <?php echo $statusValue === 'on_sale' ? 'selected' : ''; ?>>上架</option>
                        <option value="off_sale" <?php echo $statusValue === 'off_sale' ? 'selected' : ''; ?>>下架</option>
                    </select>
                </label>

                <label class="field"><span class="label">销量</span><input type="number" min="0" name="sales" value="<?php echo (int) ($selectedProduct['sales'] ?? 0); ?>"></label>
                <label class="field"><span class="label">价格</span><input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedProduct['price'] ?? '0')); ?>"></label>
                <label class="field"><span class="label">库存</span><input type="number" min="0" name="stock" value="<?php echo (int) ($selectedProduct['stock'] ?? 0); ?>"></label>
                <label class="field"><span class="label">标签</span><input type="text" name="tag" value="<?php echo shop_e((string) ($selectedProduct['tag'] ?? '')); ?>" placeholder="例如：爆款 / 新品"></label>
                <label class="field"><span class="label">首页排序</span><input type="number" min="0" name="home_sort" value="<?php echo (int) ($selectedProduct['home_sort'] ?? 0); ?>"><span class="help">0 = 按销量，数字越小越靠前</span></label>
                <label class="field"><span class="label">商品页排序</span><input type="number" min="0" name="page_sort" value="<?php echo (int) ($selectedProduct['page_sort'] ?? 0); ?>"><span class="help">0 = 按销量，数字越小越靠前</span></label>
                <label class="field"><span class="label">发布时间</span><input type="datetime-local" name="published_at" value="<?php echo shop_e($publishedAtInput); ?>"></label>
                
                <div class="field field-full">
                    <span class="label">商品规格 (SKU)</span>
                    <div id="sku-container" data-next-index="<?php echo count($skus); ?>" style="display: flex; flex-direction: column; gap: 10px;">
                        <?php 
                        $skus = [];
                        if (!empty($selectedProduct['sku'])) {
                            $skus = json_decode($selectedProduct['sku'], true) ?: [];
                        }
                        if (empty($skus)) {
                            $skus = [['name' => '', 'stock' => 0, 'price' => 0]];
                        }
                        foreach ($skus as $index => $sku): 
                        ?>
                        <div class="sku-item" style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <input type="text" name="sku[<?php echo $index; ?>][name]" value="<?php echo shop_e($sku['name'] ?? ''); ?>" placeholder="规格名 (如: 红色)" style="flex: 2;">
                            <input type="number" name="sku[<?php echo $index; ?>][stock]" value="<?php echo (int)($sku['stock'] ?? 0); ?>" placeholder="库存" style="flex: 1;" min="0">
                            <input type="number" name="sku[<?php echo $index; ?>][price]" value="<?php echo (float)($sku['price'] ?? 0); ?>" placeholder="价格" style="flex: 1;" step="0.01" min="0">
                            <button type="button" class="btn btn-danger btn-sm" data-sku-remove>删除</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" data-add-sku style="align-self: flex-start; margin-top: 10px;">+ 添加规格</button>
                </div>
                
                <label class="field field-full">
                    <span class="label">商品多图 (一行一张图片链接，也可直接上传)</span>
                    <textarea id="imagesTextarea" name="images" placeholder="https://example.com/img1.jpg&#10;https://example.com/img2.jpg"><?php echo shop_e(implode("\n", $selectedProduct['images'] ?? [])); ?></textarea>
                    <input type="file" id="imageUpload" data-image-upload multiple accept="image/*" style="display: none;">
                    <div style="display: flex; gap: 10px; margin-top: 5px;">
                        <button type="button" class="btn btn-secondary btn-sm" data-trigger-click="imageUpload">上传文件</button>
                        <button type="button" class="btn btn-secondary btn-sm" data-sync-gallery>刷新图库预览</button>
                    </div>
                    
                    <div id="galleryPreview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                        <!-- 动态插入图片预览 -->
                    </div>
                </label>
                
                <label class="field field-full">
                    <span class="label">指定封面图片 (在上方图库中点击图片即可设为封面，或手动输入)</span>
                    <input type="text" id="coverImageInput" name="cover_image" value="<?php echo shop_e((string) ($selectedProduct['cover_image'] ?? '')); ?>" placeholder="图片地址">
                </label>
                


                <label class="field field-full"><span class="label">商品描述</span><textarea name="description" placeholder="请输入商品描述"><?php echo shop_e((string) ($selectedProduct['description'] ?? '')); ?></textarea></label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit"><?php echo $editingProduct ? '保存修改' : '新增商品'; ?></button>
                <?php if ($editingProduct): ?>
                    <a class="btn btn-secondary" href="index.php?page=admin&tab=products">取消编辑</a>
                <?php endif; ?>
                <span class="help">保存后会同步写入数据库，并在前端自动展示。</span>
            </div>
        </form>
    </div>

    <div class="panel section" style="margin-top: 16px;">
        <div class="section-head">
            <div>
                <h2 class="section-title">排序预览</h2>
                <p class="section-note">下面可直观看到首页排序和商品页排序的生效前六项排位。</p>
            </div>
        </div>

        <div class="preview-grid">
            <div class="preview-card">
                <div class="preview-title">首页排序预览</div>
                <ol class="preview-list">
                    <?php if (empty($homePreview)): ?>
                        <li class="preview-item"><div><strong>暂无商品</strong><span>请先新增商品。</span></div></li>
                    <?php else: ?>
                        <?php foreach ($homePreview as $product): ?>
                            <li class="preview-item">
                                <div>
                                    <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                    <span><?php echo shop_e(shop_sort_label($product, 'home_sort', '首页排')); ?> · 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                </div>
                                <div class="preview-meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </div>

            <div class="preview-card">
                <div class="preview-title">商品页排序预览</div>
                <ol class="preview-list">
                    <?php if (empty($pagePreview)): ?>
                        <li class="preview-item"><div><strong>暂无商品</strong><span>请先新增商品。</span></div></li>
                    <?php else: ?>
                        <?php foreach ($pagePreview as $product): ?>
                            <li class="preview-item">
                                <div>
                                    <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                    <span><?php echo shop_e(shop_sort_label($product, 'page_sort', '商品排')); ?> · 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                </div>
                                <div class="preview-meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="section panel" id="admin-products" style="margin-top: 16px;">
    <div class="section-head">
        <div>
            <h2 class="section-title">商品列表</h2>
            <p class="section-note">可直接修改首页、商品页的显示序列。</p>
        </div>
        <span class="badge"><?php echo (int) ($productPagination['total'] ?? count($productRows)); ?> 件商品</span>
    </div>

    <p class="mobile-note">移动端下表格可横向滚动查看，可以直接在排序输入框保存。</p>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 22%;">商品</th>
                    <th style="width: 12%;">分类 / 状态</th>
                    <th style="width: 10%;">销量 / 库存</th>
                    <th style="width: 24%;">首页 / 商品页排序</th>
                    <th style="width: 20%;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($productRows)): ?>
                    <tr><td colspan="5" class="meta" style="padding: 20px 10px;">暂无商品，请先在上方新增商品。</td></tr>
                <?php else: ?>
                    <?php foreach ($productRows as $product): ?>
                        <?php $statusClass = shop_admin_status_class((string) ($product['status'] ?? 'on_sale')); ?>
                        <tr>
                            <td>
                                <div class="name"><a href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>" target="_blank" style="color: var(--primary); text-decoration: none;">#<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?></a></div>
                                <div class="meta">SKU：<?php echo shop_e(trim((string) ($product['sku'] ?? '')) !== '' ? (string) ($product['sku'] ?? '') : '未设置'); ?></div>
                                <div class="meta">上架：<?php echo shop_short_datetime((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></div>
                            </td>
                            <td>
                                <div class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></div>
                                <span class="status-pill <?php echo shop_e($statusClass); ?>"><?php echo shop_e(shop_admin_status_label((string) ($product['status'] ?? 'on_sale'))); ?></span>
                                <div class="meta"><?php echo shop_e((string) ($product['tag'] ?? '')); ?></div>
                            </td>
                            <td>
                                <div class="meta">销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>
                                <div class="meta">库存 <?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?></div>
                                <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                            </td>
                            <td>
                                <form class="sort-form" method="post">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                    <input type="hidden" name="admin_action" value="update_sort">
                                    <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                    <div class="sort-fields">
                                        <label class="field"><span class="label">首页排序</span><input type="number" min="0" name="home_sort" value="<?php echo (int) ($product['home_sort'] ?? 0); ?>"></label>
                                        <label class="field"><span class="label">商品页排序</span><input type="number" min="0" name="page_sort" value="<?php echo (int) ($product['page_sort'] ?? 0); ?>"></label>
                                    </div>
                                    <div class="help">0=销量优先，>0固定排序越小越靠前</div>
                                    <button class="btn btn-soft btn-sm" type="submit">保存排位</button>
                                </form>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=products&edit=<?php echo (int) ($product['id'] ?? 0); ?>">编辑</a>
                                    <form method="post" data-confirm="确定删除该商品？">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="admin_action" value="delete_product">
                                        <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
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
    <?php echo shop_render_pagination($productPagination, $productPaginationUrl); ?>
</section>
