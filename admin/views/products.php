<?php declare(strict_types=1); ?>
<?php
$statusValue = (string) ($selectedProduct['status'] ?? 'on_sale');
$skus = [];
if (!empty($selectedProduct['sku'])) {
    $decodedSkus = json_decode((string) $selectedProduct['sku'], true);
    if (is_array($decodedSkus)) {
        $skus = $decodedSkus;
    }
}
if (empty($skus)) {
    $skus = [['name' => '', 'stock' => 0, 'price' => 0]];
}
$skuCount = count($skus);

// 解析当前商品的发货方式选项
$currentFulfillmentOptions = [];
$fulfillmentOptionsRaw = shop_decode_fulfillment_options((string) ($selectedProduct['fulfillment_options'] ?? ''));
foreach ($fulfillmentOptionsRaw as $fo) {
    $currentFulfillmentOptions[(int) $fo['type_id']] = $fo;
}
?>

<section class="admin-products-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title"><?php echo $editingProduct ? '编辑商品' : '新增商品'; ?></h2>
            <p class="section-note">保留商品保存、SKU、图片上传、排序和批量操作逻辑，只整理后台排版结构。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo $editingProduct ? '编辑中' : '新建'; ?></span>
        </div>
    </div>

    <div class="admin-products-editor">
        <div class="section-head">
            <div>
                <h3 class="section-title">商品编辑区</h3>
                <p class="section-note">所有字段和操作保持原有逻辑，仅重排布局与视觉层级。</p>
            </div>
        </div>

        <form method="post" enctype="multipart/form-data" class="admin-products-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_product">
            <input type="hidden" name="id" value="<?php echo (int) ($selectedProduct['id'] ?? 0); ?>">

            <div class="admin-products-editor-grid">
                <div class="form-grid">
                    <label class="field field-full">
                        <span class="label">商品名称</span>
                        <input type="text" name="name" required value="<?php echo shop_e((string) ($selectedProduct['name'] ?? '')); ?>" placeholder="请输入商品名称">
                    </label>

                    <label class="field">
                        <span class="label">商品分类</span>
                        <select name="category" required>
                            <?php foreach ($categoryChoices as $category): ?>
                                <option value="<?php echo shop_e($category); ?>" <?php echo $selectedCategory === $category ? 'selected' : ''; ?>>
                                    <?php echo shop_e($category); ?>
                                </option>
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

                    <label class="field">
                        <span class="label">销量</span>
                        <input type="number" min="0" name="sales" value="<?php echo (int) ($selectedProduct['sales'] ?? 0); ?>">
                    </label>

                    <label class="field">
                        <span class="label">价格</span>
                        <input type="number" min="0" step="0.01" name="price" value="<?php echo shop_e((string) ($selectedProduct['price'] ?? '0')); ?>">
                    </label>

                    <label class="field">
                        <span class="label">库存</span>
                        <input type="number" min="0" name="stock" value="<?php echo (int) ($selectedProduct['stock'] ?? 0); ?>">
                    </label>

                    <label class="field">
                        <span class="label">标签</span>
                        <input type="text" name="tag" value="<?php echo shop_e((string) ($selectedProduct['tag'] ?? '')); ?>" placeholder="例如：热卖 / 新品">
                    </label>

                    <label class="field">
                        <span class="label">首页排序</span>
                        <input type="number" min="0" name="home_sort" value="<?php echo (int) ($selectedProduct['home_sort'] ?? 0); ?>">
                        <span class="help">0 表示不参与首页优先排序。</span>
                    </label>

                    <label class="field">
                        <span class="label">列表排序</span>
                        <input type="number" min="0" name="page_sort" value="<?php echo (int) ($selectedProduct['page_sort'] ?? 0); ?>">
                        <span class="help">0 表示不参与列表优先排序。</span>
                    </label>

                    <label class="field field-full">
                        <span class="label">发布时间</span>
                        <input type="datetime-local" name="published_at" value="<?php echo shop_e($publishedAtInput); ?>">
                    </label>
                </div>

                <div class="admin-products-media">
                    <div class="field field-full">
                        <span class="label">SKU 信息</span>
                        <div id="sku-container" class="admin-products-sku-list" data-next-index="<?php echo $skuCount; ?>">
                            <?php foreach ($skus as $index => $sku): ?>
                                <div class="sku-item admin-products-sku-item">
                                    <input type="text" name="sku[<?php echo $index; ?>][name]" value="<?php echo shop_e((string) ($sku['name'] ?? '')); ?>" placeholder="SKU 名称">
                                    <input type="number" name="sku[<?php echo $index; ?>][stock]" value="<?php echo (int) ($sku['stock'] ?? 0); ?>" placeholder="库存" min="0">
                                    <input type="number" name="sku[<?php echo $index; ?>][price]" value="<?php echo (float) ($sku['price'] ?? 0); ?>" placeholder="价格" step="0.01" min="0">
                                    <button type="button" class="btn btn-danger btn-sm" data-sku-remove>删除</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="admin-products-sku-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-add-sku>+ 新增 SKU</button>
                        </div>
                    </div>

                    <?php if (!empty($allFulfillmentTypes)): ?>
                    <div class="field field-full">
                        <span class="label">发货方式</span>
                        <span class="help">勾选后，客户在购买时可选择对应发货方式。价格调整为在 SKU 基础价上的增减。</span>
                        <div class="admin-fulfillment-options">
                            <?php foreach ($allFulfillmentTypes as $ft):
                                $ftId = (int) $ft['id'];
                                $isChecked = isset($currentFulfillmentOptions[$ftId]);
                                $priceAdj = $isChecked ? (float) $currentFulfillmentOptions[$ftId]['price_adjust'] : 0;
                                $note = $isChecked ? (string) $currentFulfillmentOptions[$ftId]['note'] : '';
                            ?>
                            <div class="admin-fulfillment-item">
                                <label class="admin-checkbox-label">
                                    <input type="checkbox" name="fulfillment[<?php echo $ftId; ?>][enabled]" value="1" <?php echo $isChecked ? 'checked' : ''; ?>>
                                    <span class="badge" style="background: <?php echo shop_e((string) $ft['badge_color']); ?>; color: #fff;">
                                        <span class="material-symbols-outlined" style="font-size:0.875rem; vertical-align: middle;"><?php echo shop_e((string) $ft['icon']); ?></span>
                                        <?php echo shop_e((string) $ft['name']); ?>
                                    </span>
                                    <?php if ((int) $ft['allow_zero_stock'] === 1): ?>
                                        <span class="badge badge-muted" style="font-size:0.75rem;">允许零库存</span>
                                    <?php endif; ?>
                                </label>
                                <div class="admin-fulfillment-fields">
                                    <label class="field">
                                        <span class="label">价格调整</span>
                                        <input type="number" name="fulfillment[<?php echo $ftId; ?>][price_adjust]" value="<?php echo $priceAdj; ?>" step="0.01" placeholder="0 = 不调整">
                                    </label>
                                    <label class="field">
                                        <span class="label">备注</span>
                                        <input type="text" name="fulfillment[<?php echo $ftId; ?>][note]" value="<?php echo shop_e($note); ?>" placeholder="<?php echo shop_e((string) $ft['description']); ?>">
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <label class="field field-full">
                        <span class="label">商品图片</span>
                        <textarea id="imagesTextarea" name="images" placeholder="https://example.com/img1.jpg&#10;https://example.com/img2.jpg"><?php echo shop_e(implode("\n", $selectedProduct['images'] ?? [])); ?></textarea>
                        <input type="file" id="imageUpload" class="admin-hidden-file" data-image-upload multiple accept="image/*">
                        <div class="admin-products-media-actions">
                            <button type="button" class="btn btn-secondary btn-sm" data-trigger-click="imageUpload">上传图片</button>
                            <button type="button" class="btn btn-secondary btn-sm" data-sync-gallery>刷新图片预览</button>
                        </div>
                        <div id="galleryPreview" class="admin-products-gallery"></div>
                    </label>

                    <label class="field field-full">
                        <span class="label">封面图片</span>
                        <input type="text" id="coverImageInput" name="cover_image" value="<?php echo shop_e((string) ($selectedProduct['cover_image'] ?? '')); ?>" placeholder="请输入封面图片地址">
                    </label>

                    <label class="field field-full">
                        <span class="label">商品描述</span>
                        <textarea name="description" placeholder="请输入商品描述"><?php echo shop_e((string) ($selectedProduct['description'] ?? '')); ?></textarea>
                    </label>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit"><?php echo $editingProduct ? '保存商品' : '创建商品'; ?></button>
                <?php if ($editingProduct): ?>
                    <a class="btn btn-secondary" href="index.php?page=admin&tab=products">取消编辑</a>
                <?php endif; ?>
                <span class="help">图片、SKU、封面地址与排序字段会继续沿用现有保存逻辑。</span>
            </div>
        </form>
    </div>

    <div class="admin-products-preview">
        <div class="section-head">
            <div>
                <h3 class="section-title">排序预览</h3>
                <p class="section-note">用于快速对照首页排序和商品页排序的当前效果。</p>
            </div>
        </div>

        <div class="preview-grid">
            <div class="preview-card">
                <div class="preview-title">首页排序预览</div>
                <ol class="preview-list">
                    <?php if (empty($homePreview)): ?>
                        <li class="preview-item">
                            <div>
                                <strong>暂无商品</strong>
                                <span>请先设置首页排序商品。</span>
                            </div>
                        </li>
                    <?php else: ?>
                        <?php foreach ($homePreview as $product): ?>
                            <li class="preview-item">
                                <div>
                                    <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                    <span><?php echo shop_e(shop_sort_label($product, 'home_sort', '首页排序')); ?> / 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
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
                        <li class="preview-item">
                            <div>
                                <strong>暂无商品</strong>
                                <span>请先设置列表排序商品。</span>
                            </div>
                        </li>
                    <?php else: ?>
                        <?php foreach ($pagePreview as $product): ?>
                            <li class="preview-item">
                                <div>
                                    <strong><?php echo shop_e((string) ($product['name'] ?? '')); ?></strong>
                                    <span><?php echo shop_e(shop_sort_label($product, 'page_sort', '列表排序')); ?> / 销量 <?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></span>
                                </div>
                                <div class="preview-meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ol>
            </div>
        </div>
    </div>

    <section class="admin-products-table panel section">
        <div class="section-head">
            <div>
                <h3 class="section-title">商品列表</h3>
                <p class="section-note">保留分类筛选、状态筛选、排序修改、批量操作与分页逻辑。</p>
            </div>
            <span class="badge"><?php echo (int) ($productPagination['total'] ?? count($productRows)); ?> 个商品</span>
        </div>

        <form method="get" class="admin-products-filter-bar">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="tab" value="products">
            <label class="field admin-filter-wide">
                <span class="label">分类筛选</span>
                <select name="product_category">
                    <option value="">全部分类</option>
                    <?php foreach ($categoryChoices as $category): ?>
                        <option value="<?php echo shop_e($category); ?>" <?php echo $productCategoryFilter === $category ? 'selected' : ''; ?>>
                            <?php echo shop_e($category); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field admin-filter-medium">
                <span class="label">状态筛选</span>
                <select name="product_status">
                    <option value="">全部状态</option>
                    <option value="on_sale" <?php echo $productStatusFilter === 'on_sale' ? 'selected' : ''; ?>>上架</option>
                    <option value="off_sale" <?php echo $productStatusFilter === 'off_sale' ? 'selected' : ''; ?>>下架</option>
                </select>
            </label>
            <button class="btn btn-secondary btn-sm" type="submit">筛选商品</button>
            <?php if ($productCategoryFilter !== '' || $productStatusFilter !== ''): ?>
                <a class="btn btn-soft btn-sm" href="index.php?page=admin&tab=products">清除筛选</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 4%;"><input type="checkbox" id="selectAllProducts" data-select-all-products></th>
                        <th style="width: 22%;">商品</th>
                        <th style="width: 12%;">分类 / 状态</th>
                        <th style="width: 10%;">销量 / 库存</th>
                        <th style="width: 24%;">排序</th>
                        <th style="width: 20%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productRows)): ?>
                        <tr>
                            <td colspan="6" class="meta" style="padding: 20px 10px;">暂无商品，请先新增商品。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($productRows as $product): ?>
                            <?php $statusClass = shop_admin_status_class((string) ($product['status'] ?? 'on_sale')); ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="<?php echo (int) ($product['id'] ?? 0); ?>" form="productBatchForm" data-product-checkbox>
                                </td>
                                <td>
                                    <div class="name">
                                        <a class="admin-product-link" href="index.php?page=product_detail&id=<?php echo (int) ($product['id'] ?? 0); ?>" target="_blank">
                                            #<?php echo (int) ($product['id'] ?? 0); ?> <?php echo shop_e((string) ($product['name'] ?? '')); ?>
                                        </a>
                                    </div>
                                    <div class="meta">SKU：<?php echo shop_e(trim((string) ($product['sku'] ?? '')) !== '' ? (string) ($product['sku'] ?? '') : '暂无'); ?></div>
                                    <div class="meta">发布时间：<?php echo shop_short_datetime((string) ($product['published_at'] ?? date('Y-m-d H:i:s'))); ?></div>
                                </td>
                                <td>
                                    <div class="meta"><?php echo shop_e((string) ($product['category'] ?? '')); ?></div>
                                    <span class="status-pill <?php echo shop_e($statusClass); ?>"><?php echo shop_e(shop_admin_status_label((string) ($product['status'] ?? 'on_sale'))); ?></span>
                                    <div class="meta"><?php echo shop_e((string) ($product['tag'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <div class="meta">销量：<?php echo shop_format_sales((int) ($product['sales'] ?? 0)); ?></div>
                                    <div class="meta">库存：<?php echo shop_format_sales((int) ($product['stock'] ?? 0)); ?></div>
                                    <div class="meta"><?php echo shop_format_price((float) ($product['price'] ?? 0)); ?></div>
                                </td>
                                <td>
                                    <form class="sort-form" method="post">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="products_page" value="<?php echo (int) ($productPagination['current_page'] ?? 1); ?>">
                                        <input type="hidden" name="product_category" value="<?php echo shop_e($productCategoryFilter); ?>">
                                        <input type="hidden" name="product_status" value="<?php echo shop_e($productStatusFilter); ?>">
                                        <input type="hidden" name="admin_action" value="update_sort">
                                        <input type="hidden" name="id" value="<?php echo (int) ($product['id'] ?? 0); ?>">
                                        <div class="sort-fields">
                                            <label class="field">
                                                <span class="label">首页排序</span>
                                                <input type="number" min="0" name="home_sort" value="<?php echo (int) ($product['home_sort'] ?? 0); ?>">
                                            </label>
                                            <label class="field">
                                                <span class="label">列表排序</span>
                                                <input type="number" min="0" name="page_sort" value="<?php echo (int) ($product['page_sort'] ?? 0); ?>">
                                            </label>
                                        </div>
                                        <div class="help">0 表示不参与优先排序。</div>
                                        <button class="btn btn-soft btn-sm" type="submit">保存排序</button>
                                    </form>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a class="btn btn-secondary btn-sm" href="index.php?page=admin&tab=products&edit=<?php echo (int) ($product['id'] ?? 0); ?>">编辑</a>
                                        <form method="post" data-confirm="确定删除这个商品吗？">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="products_page" value="<?php echo (int) ($productPagination['current_page'] ?? 1); ?>">
                                            <input type="hidden" name="product_category" value="<?php echo shop_e($productCategoryFilter); ?>">
                                            <input type="hidden" name="product_status" value="<?php echo shop_e($productStatusFilter); ?>">
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

        <form method="post" id="productBatchForm" class="admin-products-batch">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="products_page" value="<?php echo (int) ($productPagination['current_page'] ?? 1); ?>">
            <input type="hidden" name="product_category" value="<?php echo shop_e($productCategoryFilter); ?>">
            <input type="hidden" name="product_status" value="<?php echo shop_e($productStatusFilter); ?>">
            <input type="hidden" name="admin_action" value="batch_product_action">
            <span class="help">先勾选商品，再执行批量上下架或删除。</span>
            <button class="btn btn-secondary btn-sm" type="submit" name="batch_action" value="on_sale">批量上架</button>
            <button class="btn btn-secondary btn-sm" type="submit" name="batch_action" value="off_sale">批量下架</button>
            <button class="btn btn-danger btn-sm" type="submit" name="batch_action" value="delete" data-confirm-click="确定删除选中的商品吗？">批量删除</button>
        </form>

        <?php echo shop_render_pagination($productPagination, $productPaginationUrl); ?>
    </section>
</section>
