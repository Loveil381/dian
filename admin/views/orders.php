<section class="grid">
    <div class="panel section" id="admin-orders">
        <?php $order_status_options = shop_order_status_options(); ?>
        <div class="section-head">
            <div>
                <h2 class="section-title">订单管理</h2>
                <p class="section-note">订单商品已改为 JSON 结构化存储，后台展示按商品数组解析。</p>
            </div>
            <div class="section-actions">
                <span class="badge"><?php echo (int) ($orderPagination['total'] ?? count($orderRows)); ?> 笔订单</span>
            </div>
        </div>

        <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 16px;">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="tab" value="orders">
            <label class="field" style="min-width: 220px;">
                <span class="label">订单状态筛选</span>
                <select name="order_status">
                    <option value="">全部</option>
                    <?php foreach ($order_status_options as $status_key => $status_option): ?>
                        <option value="<?php echo shop_e($status_key); ?>" <?php echo $orderStatusFilter === $status_key ? 'selected' : ''; ?>><?php echo shop_e((string) $status_option['label']); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-secondary btn-sm" type="submit">应用筛选</button>
            <?php if ($orderStatusFilter !== ''): ?>
                <a class="btn btn-soft btn-sm" href="index.php?page=admin&tab=orders">清空筛选</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 22%;">订单号 / 时间</th>
                        <th style="width: 28%;">商品明细</th>
                        <th style="width: 15%;">用户 / 物流</th>
                        <th style="width: 15%;">金额 / 状态</th>
                        <th style="width: 20%;">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orderRows)): ?>
                        <tr>
                            <td colspan="5" class="meta" style="padding: 20px 10px;">暂无订单数据。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orderRows as $order): ?>
                            <tr>
                                <td>
                                    <div class="name" style="font-family: monospace; font-size: 13px;">#<?php echo shop_e((string) ($order['order_no'] ?? '')); ?></div>
                                    <div class="meta"><?php echo shop_short_datetime((string) ($order['created_at'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <div class="title" style="line-height: 1.6;">
                                        <?php foreach (($order['items_data'] ?? []) as $item): ?>
                                            <div>
                                                <?php echo shop_e((string) ($item['name'] ?? '商品')); ?>
                                                <?php if ((string) ($item['sku_name'] ?? '') !== ''): ?>
                                                    <span class="meta">（<?php echo shop_e((string) $item['sku_name']); ?>）</span>
                                                <?php endif; ?>
                                                <span class="meta">× <?php echo (int) ($item['quantity'] ?? 1); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($order['user_id'])): ?>
                                        <div class="name">UID: <?php echo (int) ($order['user_id'] ?? 0); ?></div>
                                    <?php else: ?>
                                        <div class="name">游客订单</div>
                                    <?php endif; ?>
                                    <div class="meta"><?php echo shop_e((string) ($order['express_company'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <?php $status_meta = shop_order_status_meta((string) ($order['status'] ?? 'pending')); ?>
                                    <div class="name"><?php echo shop_format_price((float) ($order['total'] ?? 0)); ?></div>
                                    <span class="status-pill" style="margin-top: 4px; display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: <?php echo shop_e((string) $status_meta['badge_background']); ?>; color: <?php echo shop_e((string) $status_meta['badge_color']); ?>; border: 1px solid <?php echo shop_e((string) $status_meta['badge_background']); ?>;">
                                        <?php echo shop_e((string) $status_meta['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form class="sort-form" method="post" style="display: flex; gap: 8px; align-items:flex-start; flex-wrap: wrap;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="orders_page" value="<?php echo (int) ($orderPagination['current_page'] ?? 1); ?>">
                                        <input type="hidden" name="order_status" value="<?php echo shop_e($orderStatusFilter); ?>">
                                        <input type="hidden" name="admin_action" value="update_order_status">
                                        <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                        <select name="status" style="width: 100px; padding: 4px; font-size: 12px;">
                                            <?php $current_status = shop_normalize_order_status((string) ($order['status'] ?? 'pending')); ?>
                                            <?php foreach ($order_status_options as $status_key => $status_option): ?>
                                                <option value="<?php echo shop_e($status_key); ?>" <?php echo $current_status === $status_key ? 'selected' : ''; ?>><?php echo shop_e((string) $status_option['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-soft btn-sm" type="submit" style="padding: 4px 8px; font-size: 12px; border-radius: 4px;">更新状态</button>
                                    </form>
                                    <form method="post" data-confirm="确认永久删除这笔订单吗？" style="margin-top: 8px;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="orders_page" value="<?php echo (int) ($orderPagination['current_page'] ?? 1); ?>">
                                        <input type="hidden" name="order_status" value="<?php echo shop_e($orderStatusFilter); ?>">
                                        <input type="hidden" name="admin_action" value="delete_order">
                                        <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                        <button class="btn btn-danger btn-sm" type="submit" style="padding: 4px 8px; font-size: 12px; border-radius: 4px;">删除订单</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php echo shop_render_pagination($orderPagination, $orderPaginationUrl); ?>
    </div>
</section>
