<?php declare(strict_types=1); ?>
<section class="admin-settings-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">系统设置</h2>
            <p class="section-note">这里保留当前权限入口和管理员账号修改能力，方便后续继续扩展。</p>
        </div>
        <div class="section-actions">
            <span class="badge">System</span>
        </div>
    </div>

    <div class="admin-settings-grid">
        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_role">

            <div class="section-head">
                <div>
                    <h3 class="section-title">权限预设</h3>
                    <p class="section-note">当前保持原有占位逻辑，页面层先展示后续计划中的权限管理入口。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span class="label">游客访问范围</span>
                    <select name="guest_access">
                        <option value="all">允许浏览全部前台</option>
                        <option value="login_only">仅登录后可访问部分页面</option>
                    </select>
                </label>

                <label class="field">
                    <span class="label">新用户审核方式</span>
                    <select name="user_verify">
                        <option value="auto">自动通过</option>
                        <option value="manual">管理员审核后通过</option>
                    </select>
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">保存预设</button>
                <span class="help">当前提交后仍会走现有“开发中”提示逻辑。</span>
            </div>
        </form>

        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_consult">

            <div class="section-head">
                <div>
                    <h3 class="section-title">在线咨询</h3>
                    <p class="section-note">配置首页聊天气泡弹窗中展示的联系方式。关闭后首页将隐藏咨询按钮和药师区块。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field field-full" style="display: flex; align-items: center; gap: var(--space-sm); flex-direction: row;">
                    <input type="checkbox" name="consult_enabled" value="1" <?php echo $consultEnabled === '1' ? 'checked' : ''; ?>>
                    <span class="label" style="margin: 0;">启用在线咨询按钮</span>
                </label>

                <label class="field field-full">
                    <span class="label">弹窗标题</span>
                    <input type="text" name="consult_title" value="<?php echo shop_e($consultTitle); ?>" placeholder="默认：在线咨询">
                </label>

                <label class="field field-full">
                    <span class="label">欢迎语</span>
                    <input type="text" name="consult_greeting" value="<?php echo shop_e($consultGreeting); ?>" placeholder="默认：您好！有什么可以帮您的吗？">
                </label>

                <div class="field field-full">
                    <span class="label">微信二维码图片</span>
                    <div class="admin-payment-input-row">
                        <input type="text" id="consult_wechat_qr" data-qr-input="consult_wechat" name="consult_wechat_qr" value="<?php echo shop_e($consultWechatQr); ?>" placeholder="图片地址或上传">
                        <button type="button" class="btn btn-secondary" data-trigger-click="consult_wechat_upload">上传二维码</button>
                        <input type="file" id="consult_wechat_upload" data-payment-upload="consult_wechat" accept="image/*" class="admin-hidden-file">
                    </div>
                    <div class="admin-payment-preview" id="consult_wechat_preview">
                        <?php if ($consultWechatQr !== ''): ?>
                            <img class="admin-payment-preview-image" src="<?php echo shop_e($consultWechatQr); ?>" alt="微信二维码">
                            <div class="admin-payment-preview-overlay">
                                <span class="material-symbols-outlined">visibility</span>
                            </div>
                        <?php else: ?>
                            <span class="admin-payment-placeholder">暂未设置微信二维码</span>
                        <?php endif; ?>
                    </div>
                </div>

                <label class="field field-full">
                    <span class="label">微信号</span>
                    <input type="text" name="consult_wechat_id" value="<?php echo shop_e($consultWechatId); ?>" placeholder="例如：mofang_shop">
                </label>

                <label class="field field-full">
                    <span class="label">联系电话</span>
                    <input type="text" name="consult_phone" value="<?php echo shop_e($consultPhone); ?>" placeholder="例如：400-123-4567">
                </label>

                <label class="field field-full">
                    <span class="label">咨询提示语</span>
                    <input type="text" name="consult_notice" value="<?php echo shop_e($consultNotice); ?>" placeholder="例如：工作日 9:00-18:00 回复">
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">保存咨询设置</button>
            </div>
        </form>

        <!-- 订单通知设置 -->
        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_notification">

            <h3 class="admin-settings-card-title">
                <span class="material-symbols-outlined" aria-hidden="true">notifications_active</span>
                订单通知
            </h3>

            <div class="admin-settings-desc">订单状态变更时自动发送邮件通知。需要先配置 SMTP 环境变量才能正常发送。</div>

            <div class="field" style="margin-bottom:var(--space-md)">
                <span class="label">管理员接收邮箱</span>
                <input class="input" type="email" name="notify_admin_email"
                       value="<?php echo shop_e($notifyAdminEmail); ?>"
                       placeholder="admin@example.com" style="max-width:24rem">
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:var(--space-xl)">
                <div class="field" style="min-width:10rem">
                    <span class="label">管理员通知</span>
                    <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer;margin-bottom:var(--space-xs)">
                        <input type="checkbox" name="notify_admin_created" value="1" <?php echo $notifyAdminCreated === '1' ? 'checked' : ''; ?>>
                        新订单提醒
                    </label>
                    <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer">
                        <input type="checkbox" name="notify_admin_paid" value="1" <?php echo $notifyAdminPaid === '1' ? 'checked' : ''; ?>>
                        收款提醒
                    </label>
                </div>

                <div class="field" style="min-width:10rem">
                    <span class="label">客户通知</span>
                    <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer;margin-bottom:var(--space-xs)">
                        <input type="checkbox" name="notify_customer_created" value="1" <?php echo $notifyCustomerCreated === '1' ? 'checked' : ''; ?>>
                        下单确认
                    </label>
                    <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer;margin-bottom:var(--space-xs)">
                        <input type="checkbox" name="notify_customer_shipped" value="1" <?php echo $notifyCustomerShipped === '1' ? 'checked' : ''; ?>>
                        发货通知
                    </label>
                    <label style="display:flex;align-items:center;gap:var(--space-sm);cursor:pointer">
                        <input type="checkbox" name="notify_customer_completed" value="1" <?php echo $notifyCustomerCompleted === '1' ? 'checked' : ''; ?>>
                        完成通知
                    </label>
                </div>
            </div>

            <div class="actions">
                <button class="btn btn-primary btn-sm" type="submit">保存通知设置</button>
            </div>
        </form>

        <form method="post" class="admin-settings-card">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="change_password">

            <div class="section-head">
                <div>
                    <h3 class="section-title">管理员账号</h3>
                    <p class="section-note">支持修改管理员用户名与密码，提交成功后会要求重新登录。</p>
                </div>
            </div>

            <div class="form-grid">
                <label class="field field-full">
                    <span class="label">新管理员用户名</span>
                    <input type="text" name="new_username" placeholder="留空则仅修改密码" autocomplete="off">
                </label>

                <label class="field field-full">
                    <span class="label">新密码</span>
                    <input type="password" name="new_password" required placeholder="至少 8 位，建议使用更强密码" autocomplete="new-password">
                </label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit" data-confirm-click="确定要更新管理员账号信息吗？提交后需要重新登录。">更新账号信息</button>
            </div>
        </form>
    </div>
</section>
