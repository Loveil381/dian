<?php declare(strict_types=1); ?>
<?php $order_status_options = shop_order_status_options(); ?>

<section class="admin-orders-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">订单管理</h2>
            <p class="section-note">查看订单商品、金额、状态和用户信息，并保留原有状态更新与删除逻辑。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo (int) ($orderPagination['total'] ?? count($orderRows)); ?> 笔订单</span>
        </div>
    </div>

    <div class="admin-orders-card">
        <form method="get" class="admin-orders-filter-bar">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="tab" value="orders">
            <label class="field admin-filter-wide">
                <span class="label">订单状态</span>
                <select name="order_status">
                    <option value="">全部订单</option>
                    <?php foreach ($order_status_options as $status_key => $status_option): ?>
                        <option value="<?php echo shop_e($status_key); ?>" <?php echo $orderStatusFilter === $status_key ? 'selected' : ''; ?>>
                            <?php echo shop_e((string) $status_option['label']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-secondary btn-sm" type="submit">筛选订单</button>
            <?php if ($orderStatusFilter !== ''): ?>
                <a class="btn btn-soft btn-sm" href="index.php?page=admin&tab=orders">清除筛选</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="admin-orders-table">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 22%;">订单号 / 时间</th>
                        <th style="width: 28%;">商品信息</th>
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
                            <?php $status_meta = shop_order_status_meta((string) ($order['status'] ?? 'pending')); ?>
                            <?php $current_status = shop_normalize_order_status((string) ($order['status'] ?? 'pending')); ?>
                            <tr>
                                <td>
                                    <div class="name">#<?php echo shop_e((string) ($order['order_no'] ?? '')); ?></div>
                                    <div class="meta"><?php echo shop_short_datetime((string) ($order['created_at'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <div class="admin-orders-meta">
                                        <?php foreach (($order['items_data'] ?? []) as $item): ?>
                                            <div>
                                                <?php echo shop_e((string) ($item['name'] ?? '商品')); ?>
                                                <?php if ((string) ($item['sku_name'] ?? '') !== ''): ?>
                                                    <span class="meta"> / <?php echo shop_e((string) $item['sku_name']); ?></span>
                                                <?php endif; ?>
                                                <span class="meta"> × <?php echo (int) ($item['quantity'] ?? 1); ?></span>
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
                                    <div class="name"><?php echo shop_format_price((float) ($order['total'] ?? 0)); ?></div>
                                    <span
                                        class="admin-orders-status"
                                        style="margin-top: 4px; background: <?php echo shop_e((string) $status_meta['badge_background']); ?>; color: <?php echo shop_e((string) $status_meta['badge_color']); ?>; border: 1px solid <?php echo shop_e((string) $status_meta['badge_background']); ?>;"
                                    >
                                        <?php echo shop_e((string) $status_meta['label']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="admin-orders-actions">
                                        <form class="sort-form" method="post">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="orders_page" value="<?php echo (int) ($orderPagination['current_page'] ?? 1); ?>">
                                            <input type="hidden" name="order_status" value="<?php echo shop_e($orderStatusFilter); ?>">
                                            <input type="hidden" name="admin_action" value="update_order_status">
                                            <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                            <select name="status">
                                                <?php foreach ($order_status_options as $status_key => $status_option): ?>
                                                    <option value="<?php echo shop_e($status_key); ?>" <?php echo $current_status === $status_key ? 'selected' : ''; ?>>
                                                        <?php echo shop_e((string) $status_option['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-soft btn-sm" type="submit">更新状态</button>
                                        </form>

                                        <form method="post" data-confirm="确定删除这笔订单吗？">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="orders_page" value="<?php echo (int) ($orderPagination['current_page'] ?? 1); ?>">
                                            <input type="hidden" name="order_status" value="<?php echo shop_e($orderStatusFilter); ?>">
                                            <input type="hidden" name="admin_action" value="delete_order">
                                            <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                            <button class="btn btn-danger btn-sm" type="submit">删除订单</button>
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

    <?php echo shop_render_pagination($orderPagination, $orderPaginationUrl); ?>
</section>
