<?php declare(strict_types=1); ?>
<?php $order_status_options = shop_order_status_options(); ?>

<section class="admin-orders-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">订单管理</h2>
            <p class="section-note">查看订单商品、金额、状态和买家信息，支持填写快递公司与单号。</p>
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
                        <th style="width: 18%;">订单号 / 时间</th>
                        <th style="width: 22%;">商品信息</th>
                        <th style="width: 20%;">买家信息</th>
                        <th style="width: 12%;">金额 / 状态</th>
                        <th style="width: 28%;">物流 / 操作</th>
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
                            <?php
                            // 解析多条快递单号（换行分隔存储）
                            $raw_tracking = trim((string) ($order['tracking_numbers'] ?? ''));
                            $tracking_lines = $raw_tracking !== '' ? array_filter(array_map('trim', explode("\n", $raw_tracking))) : [];
                            ?>
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
                                    <?php $customer = trim((string) ($order['customer'] ?? '')); ?>
                                    <?php $phone    = trim((string) ($order['phone'] ?? '')); ?>
                                    <?php $address  = trim((string) ($order['address'] ?? '')); ?>
                                    <?php if ($customer !== ''): ?>
                                        <div class="name"><?php echo shop_e($customer); ?></div>
                                    <?php else: ?>
                                        <div class="name meta">游客</div>
                                    <?php endif; ?>
                                    <?php if ($phone !== ''): ?>
                                        <div class="meta"><?php echo shop_e($phone); ?></div>
                                    <?php endif; ?>
                                    <?php if ($address !== ''): ?>
                                        <div class="meta" style="word-break:break-all;"><?php echo shop_e($address); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($order['user_id'])): ?>
                                        <div class="meta">UID: <?php echo (int) ($order['user_id'] ?? 0); ?></div>
                                    <?php endif; ?>
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

                                        <!-- 快递 + 状态一体更新 -->
                                        <form class="sort-form" method="post" style="display:flex; flex-direction:column; gap:6px;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                            <input type="hidden" name="orders_page" value="<?php echo (int) ($orderPagination['current_page'] ?? 1); ?>">
                                            <input type="hidden" name="order_status" value="<?php echo shop_e($orderStatusFilter); ?>">
                                            <input type="hidden" name="admin_action" value="update_order">
                                            <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">

                                            <input
                                                class="input input-sm"
                                                type="text"
                                                name="express_company"
                                                placeholder="快递公司"
                                                value="<?php echo shop_e((string) ($order['express_company'] ?? '')); ?>"
                                                style="width:100%;"
                                            >
                                            <textarea
                                                class="input input-sm"
                                                name="tracking_numbers"
                                                placeholder="快递单号，每行一个"
                                                rows="<?php echo max(2, count($tracking_lines)); ?>"
                                                style="width:100%; resize:vertical;"
                                            ><?php echo shop_e($raw_tracking); ?></textarea>

                                            <select name="status" style="width:100%;">
                                                <?php foreach ($order_status_options as $status_key => $status_option): ?>
                                                    <option value="<?php echo shop_e($status_key); ?>" <?php echo $current_status === $status_key ? 'selected' : ''; ?>>
                                                        <?php echo shop_e((string) $status_option['label']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-soft btn-sm" type="submit">保存更新</button>
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
