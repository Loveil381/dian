<section class="admin-payment-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">支付设置</h2>
            <p class="section-note">配置下单收货要求与收款二维码，保持支付入口清晰可维护。</p>
        </div>
        <div class="section-actions">
            <span class="badge">Payment</span>
        </div>
    </div>

    <form method="post" class="admin-payment-card">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
        <input type="hidden" name="admin_action" value="save_payment">

        <label class="field field-full check-row admin-payment-check">
            <input type="checkbox" name="require_address" value="1" <?php echo $requireAddress === '1' ? 'checked' : ''; ?>>
            <span class="label">强制要求买家下单前填写收货人、手机号和收货地址</span>
        </label>

        <div class="admin-payment-grid">
            <section class="admin-payment-channel">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">微信收款</h3>
                        <p class="section-note">支持上传截图或直接填写二维码地址。</p>
                    </div>
                </div>

                <div class="admin-payment-input-row">
                    <input type="text" id="wechat_qr" data-qr-input="wechat" name="wechat_qr" value="<?php echo shop_e($wechatQr); ?>" placeholder="微信收款码地址 / 链接">
                    <button type="button" class="btn btn-secondary" data-trigger-click="wechat_upload">上传截图</button>
                    <input type="file" id="wechat_upload" data-payment-upload="wechat" accept="image/*" class="admin-hidden-file">
                </div>

                <div class="admin-payment-preview">
                    <?php if ($wechatQr): ?>
                        <img class="admin-payment-preview-image" src="<?php echo shop_e($wechatQr); ?>" alt="微信收款码">
                    <?php else: ?>
                        <span class="admin-payment-placeholder">暂无微信二维码</span>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-payment-channel">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">支付宝收款</h3>
                        <p class="section-note">同样支持地址输入与截图上传。</p>
                    </div>
                </div>

                <div class="admin-payment-input-row">
                    <input type="text" id="alipay_qr" data-qr-input="alipay" name="alipay_qr" value="<?php echo shop_e($alipayQr); ?>" placeholder="支付宝收款码地址 / 链接">
                    <button type="button" class="btn btn-secondary" data-trigger-click="alipay_upload">上传截图</button>
                    <input type="file" id="alipay_upload" data-payment-upload="alipay" accept="image/*" class="admin-hidden-file">
                </div>

                <div class="admin-payment-preview">
                    <?php if ($alipayQr): ?>
                        <img class="admin-payment-preview-image" src="<?php echo shop_e($alipayQr); ?>" alt="支付宝收款码">
                    <?php else: ?>
                        <span class="admin-payment-placeholder">暂无支付宝二维码</span>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">保存支付设置</button>
        </div>
    </form>
</section>
