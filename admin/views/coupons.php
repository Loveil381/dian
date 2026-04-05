<?php declare(strict_types=1); ?>
<?php
/**
 * 优惠券管理视图
 * 包含创建/编辑表单和优惠券列表（筛选+分页）。
 */

$editId = (int) ($selectedCoupon['id'] ?? 0);
$isEditing = $editId > 0;
?>

<!-- 创建 / 编辑表单 -->
<section>
    <div class="section-head">
        <div>
            <h2 class="section-title">
                <span class="material-symbols-outlined" aria-hidden="true">confirmation_number</span>
                <?php echo $isEditing ? '编辑优惠券' : '优惠券管理'; ?>
            </h2>
            <p class="section-note">创建和管理优惠券，吸引更多客户下单。</p>
        </div>
        <div class="section-actions">
            <span class="badge"><?php echo (int) ($couponPagination['total'] ?? 0); ?> 张优惠券</span>
        </div>
    </div>

    <div class="card" style="padding:var(--space-lg)">
        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="admin_action" value="save_coupon">
            <input type="hidden" name="tab" value="coupons">
            <input type="hidden" name="coupon_id" value="<?php echo $editId; ?>">

            <div class="form-grid">
                <!-- 左列 -->
                <div class="field">
                    <span class="label">券码</span>
                    <div style="display:flex;gap:var(--space-sm)">
                        <input class="input" type="text" name="code" id="couponCodeInput"
                               value="<?php echo shop_e((string) ($selectedCoupon['code'] ?? '')); ?>"
                               required placeholder="如 WELCOME50" style="flex:1;font-family:monospace;font-weight:700;letter-spacing:0.05em">
                        <button type="button" class="btn btn-soft btn-sm"
                                onclick="document.getElementById('couponCodeInput').value='COUP'+Math.random().toString(36).substring(2,8).toUpperCase()">
                            <span class="material-symbols-outlined" style="font-size:1.125rem">autorenew</span>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <span class="label">开始时间</span>
                    <input class="input" type="datetime-local" name="starts_at"
                           value="<?php echo shop_e(shop_to_input_datetime((string) ($selectedCoupon['starts_at'] ?? ''))); ?>">
                </div>

                <div class="field">
                    <span class="label">优惠类型</span>
                    <select class="input" name="type">
                        <option value="fixed" <?php echo ($selectedCoupon['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>满减（固定金额）</option>
                        <option value="percent" <?php echo ($selectedCoupon['type'] ?? '') === 'percent' ? 'selected' : ''; ?>>折扣（百分比）</option>
                    </select>
                </div>

                <div class="field">
                    <span class="label">结束时间</span>
                    <input class="input" type="datetime-local" name="expires_at"
                           value="<?php echo shop_e(shop_to_input_datetime((string) ($selectedCoupon['expires_at'] ?? ''))); ?>">
                </div>

                <div class="field">
                    <span class="label">优惠值</span>
                    <input class="input" type="number" name="value" step="0.01" min="0"
                           value="<?php echo shop_e((string) ($selectedCoupon['value'] ?? '0')); ?>"
                           placeholder="金额或百分比数值">
                </div>

                <div class="field">
                    <span class="label">最低消费（元）</span>
                    <input class="input" type="number" name="min_order_amount" step="0.01" min="0"
                           value="<?php echo shop_e((string) ($selectedCoupon['min_order_amount'] ?? '0')); ?>"
                           placeholder="0 = 无门槛">
                </div>

                <div class="field">
                    <span class="label">使用上限</span>
                    <input class="input" type="number" name="usage_limit" min="0"
                           value="<?php echo (int) ($selectedCoupon['usage_limit'] ?? 0); ?>"
                           placeholder="0 = 不限">
                </div>

                <div class="field" style="display:flex;align-items:flex-end;gap:var(--space-sm)">
                    <button type="submit" class="btn btn-primary" style="flex:1">
                        <span class="material-symbols-outlined" style="font-size:1.125rem">save</span>
                        <?php echo $isEditing ? '更新优惠券' : '保存优惠券'; ?>
                    </button>
                    <?php if ($isEditing): ?>
                        <a href="<?php echo shop_e($adminUrl . '&tab=coupons'); ?>" class="btn btn-soft">取消</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- 筛选 + 列表 -->
<section style="margin-top:var(--space-xl)">
    <div class="admin-coupon-filter">
        <form method="get" style="display:flex;gap:var(--space-sm);align-items:flex-end;flex-wrap:wrap">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="tab" value="coupons">
            <label class="field" style="margin:0">
                <span class="label">状态</span>
                <select class="input" name="coupon_status">
                    <option value="">全部状态</option>
                    <option value="active" <?php echo $couponStatusFilter === 'active' ? 'selected' : ''; ?>>生效中</option>
                    <option value="disabled" <?php echo $couponStatusFilter === 'disabled' ? 'selected' : ''; ?>>已禁用</option>
                </select>
            </label>
            <button class="btn btn-primary btn-sm">筛选</button>
            <?php if ($couponStatusFilter !== ''): ?>
                <a href="<?php echo shop_e($adminUrl . '&tab=coupons'); ?>" style="font-size:var(--text-caption);color:var(--color-primary)">清除筛选</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>券码</th>
                        <th>类型</th>
                        <th>面值</th>
                        <th>门槛</th>
                        <th>已用 / 上限</th>
                        <th>有效期</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($couponRows)): ?>
                    <tr>
                        <td colspan="8" style="text-align:center;padding:var(--space-xl);color:var(--color-outline)">
                            <span class="material-symbols-outlined" style="font-size:2rem;display:block;margin-bottom:var(--space-sm)">confirmation_number</span>
                            暂无优惠券，点击上方表单创建
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($couponRows as $coupon): ?>
                    <tr>
                        <!-- 券码 -->
                        <td>
                            <span class="admin-coupon-code"><?php echo shop_e((string) ($coupon['code'] ?? '')); ?></span>
                        </td>

                        <!-- 类型 -->
                        <td>
                            <?php
                            $type = (string) ($coupon['type'] ?? 'fixed');
                            $typeLabels = ['fixed' => '满减', 'percent' => '折扣'];
                            ?>
                            <span class="admin-coupon-type admin-coupon-type--<?php echo shop_e($type); ?>">
                                <?php echo shop_e($typeLabels[$type] ?? $type); ?>
                            </span>
                        </td>

                        <!-- 面值 -->
                        <td class="name">
                            <?php
                            $value = (float) ($coupon['value'] ?? 0);
                            echo $type === 'percent'
                                ? shop_e(rtrim(rtrim(number_format($value, 1), '0'), '.') . ' 折')
                                : shop_format_price($value);
                            ?>
                        </td>

                        <!-- 门槛 -->
                        <td class="meta">
                            <?php echo $coupon['min_order_amount'] > 0 ? '满 ' . shop_format_price((float) $coupon['min_order_amount']) : '无门槛'; ?>
                        </td>

                        <!-- 已用/上限 -->
                        <td>
                            <div class="admin-coupon-usage">
                                <span class="meta">
                                    <?php echo (int) ($coupon['used_count'] ?? 0); ?> / <?php echo ((int) ($coupon['usage_limit'] ?? 0)) > 0 ? (int) $coupon['usage_limit'] : '不限'; ?>
                                </span>
                                <?php if (((int) ($coupon['usage_limit'] ?? 0)) > 0): ?>
                                    <?php $pct = min(100, round((int) $coupon['used_count'] / max(1, (int) $coupon['usage_limit']) * 100)); ?>
                                    <div class="admin-coupon-progress">
                                        <div class="admin-coupon-progress-fill" style="width:<?php echo $pct; ?>%"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- 有效期 -->
                        <td class="meta">
                            <?php
                            $start = !empty($coupon['starts_at']) ? shop_short_date((string) $coupon['starts_at']) : '—';
                            $end = !empty($coupon['expires_at']) ? shop_short_date((string) $coupon['expires_at']) : '—';
                            echo shop_e($start . ' ~ ' . $end);
                            ?>
                        </td>

                        <!-- 状态 -->
                        <td>
                            <?php if (($coupon['status'] ?? '') === 'active'): ?>
                                <span class="status-pill success">生效中</span>
                            <?php else: ?>
                                <span class="status-pill muted">已禁用</span>
                            <?php endif; ?>
                        </td>

                        <!-- 操作 -->
                        <td>
                            <div class="row-actions">
                                <a href="<?php echo shop_e($adminUrl . '&tab=coupons&edit_coupon=' . (int) ($coupon['id'] ?? 0)); ?>"
                                   class="btn btn-sm btn-soft">编辑</a>

                                <form method="post" style="display:inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="admin_action" value="toggle_coupon">
                                    <input type="hidden" name="tab" value="coupons">
                                    <input type="hidden" name="coupon_id" value="<?php echo (int) ($coupon['id'] ?? 0); ?>">
                                    <button class="btn btn-sm btn-soft">
                                        <?php echo ($coupon['status'] ?? '') === 'active' ? '禁用' : '启用'; ?>
                                    </button>
                                </form>

                                <form method="post" style="display:inline"
                                      data-confirm="确定要删除优惠券「<?php echo shop_e((string) ($coupon['code'] ?? '')); ?>」吗？此操作不可撤销。">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="admin_action" value="delete_coupon">
                                    <input type="hidden" name="tab" value="coupons">
                                    <input type="hidden" name="coupon_id" value="<?php echo (int) ($coupon['id'] ?? 0); ?>">
                                    <button class="btn btn-sm btn-danger">删除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php echo shop_render_pagination($couponPagination, $couponPaginationUrl); ?>
    </div>
</section>
