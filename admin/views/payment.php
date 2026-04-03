<?php declare(strict_types=1); ?>
<section class="admin-payment-shell">
    <div class="section-head">
        <div>
            <h2 class="section-title">支付设置</h2>
            <p class="section-note">管理微信与支付宝二维码，控制是否必须填写完整收货地址。</p>
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
            <span class="label">要求用户下单时填写完整收货地址</span>
        </label>

        <div class="admin-payment-grid">
            <section class="admin-payment-channel">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">微信支付</h3>
                        <p class="section-note">支持手动输入二维码地址，也可以直接上传图片。</p>
                    </div>
                </div>

                <div class="admin-payment-input-row">
                    <input type="text" id="wechat_qr" data-qr-input="wechat" name="wechat_qr" value="<?php echo shop_e($wechatQr); ?>" placeholder="微信二维码地址或相对路径">
                    <button type="button" class="btn btn-secondary" data-trigger-click="wechat_upload">上传二维码</button>
                    <input type="file" id="wechat_upload" data-payment-upload="wechat" accept="image/*" class="admin-hidden-file">
                </div>

                <div class="admin-payment-preview" id="wechat_preview">
                    <?php if ($wechatQr): ?>
                        <img class="admin-payment-preview-image" src="<?php echo shop_e($wechatQr); ?>" alt="微信支付二维码">
                    <?php else: ?>
                        <span class="admin-payment-placeholder">暂未设置微信二维码</span>
                    <?php endif; ?>
                </div>
            </section>

            <section class="admin-payment-channel">
                <div class="section-head">
                    <div>
                        <h3 class="section-title">支付宝支付</h3>
                        <p class="section-note">支持手动输入二维码地址，也可以直接上传图片。</p>
                    </div>
                </div>

                <div class="admin-payment-input-row">
                    <input type="text" id="alipay_qr" data-qr-input="alipay" name="alipay_qr" value="<?php echo shop_e($alipayQr); ?>" placeholder="支付宝二维码地址或相对路径">
                    <button type="button" class="btn btn-secondary" data-trigger-click="alipay_upload">上传二维码</button>
                    <input type="file" id="alipay_upload" data-payment-upload="alipay" accept="image/*" class="admin-hidden-file">
                </div>

                <div class="admin-payment-preview" id="alipay_preview">
                    <?php if ($alipayQr): ?>
                        <img class="admin-payment-preview-image" src="<?php echo shop_e($alipayQr); ?>" alt="支付宝二维码">
                    <?php else: ?>
                        <span class="admin-payment-placeholder">暂未设置支付宝二维码</span>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <div class="actions">
            <button class="btn btn-primary" type="submit">保存支付设置</button>
        </div>
    </form>
</section>
