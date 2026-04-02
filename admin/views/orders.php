<section class="grid">
    <div class="panel section" id="admin-orders">
        <div class="section-head">
            <div>
                <h2 class="section-title">订单管理</h2>
                <p class="section-note">订单商品已改为 JSON 结构化存储，后台展示按商品数组解析。</p>
            </div>
            <div class="section-actions">
                <span class="badge"><?php echo count($orderRows); ?> 笔订单</span>
            </div>
        </div>

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
                                    <div class="name"><?php echo shop_format_price((float) ($order['total_amount'] ?? 0)); ?></div>
                                    <span class="status-pill" style="margin-top: 4px; display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                                        <?php echo shop_e(shop_admin_order_status_label((string) ($order['status'] ?? 'pending'))); ?>
                                    </span>
                                </td>
                                <td>
                                    <form class="sort-form" method="post" style="display: flex; gap: 8px; align-items:flex-start; flex-wrap: wrap;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
                                        <input type="hidden" name="admin_action" value="update_order_status">
                                        <input type="hidden" name="id" value="<?php echo (int) ($order['id'] ?? 0); ?>">
                                        <select name="status" style="width: 100px; padding: 4px; font-size: 12px;">
                                            <option value="pending" <?php echo in_array((string) ($order['status'] ?? ''), ['pending', '待支付'], true) ? 'selected' : ''; ?>>待支付</option>
                                            <option value="paid" <?php echo in_array((string) ($order['status'] ?? ''), ['paid', '已支付，待发货'], true) ? 'selected' : ''; ?>>已支付</option>
                                            <option value="shipped" <?php echo in_array((string) ($order['status'] ?? ''), ['shipped', '已发货'], true) ? 'selected' : ''; ?>>已发货</option>
                                            <option value="completed" <?php echo in_array((string) ($order['status'] ?? ''), ['completed', '已完成'], true) ? 'selected' : ''; ?>>已完成</option>
                                            <option value="cancelled" <?php echo in_array((string) ($order['status'] ?? ''), ['cancelled', '已取消'], true) ? 'selected' : ''; ?>>已取消</option>
                                        </select>
                                        <button class="btn btn-soft btn-sm" type="submit" style="padding: 4px 8px; font-size: 12px; border-radius: 4px;">更新状态</button>
                                    </form>
                                    <form method="post" onsubmit="return confirm('确认永久删除这笔订单吗？');" style="margin-top: 8px;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
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
    </div>
</section>
