<section class="grid">
    <div class="panel section" id="admin-payment">
        <div class="section-head">
            <div>
                <h2 class="section-title">支付管理</h2>
                <p class="section-note">在此配置微信支付和支付宝支付的收款码。</p>
            </div>
            <span class="badge">Payment</span>
        </div>

        <form method="post">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">
            <input type="hidden" name="admin_action" value="save_payment">
            
            <div class="form-grid">
                <label class="field field-full check-row">
                    <input type="checkbox" name="require_address" value="1" <?php echo $requireAddress === '1' ? 'checked' : ''; ?>>
                    <span class="label" style="margin: 0; color: inherit; font-size: 14px;">强制要求买家下单前填写收货人、手机号和收货地址</span>
                </label>

                <label class="field field-full">
                    <span class="label">微信支付 (可填图片URL，也可点击上传)</span>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="wechat_qr" name="wechat_qr" value="<?php echo shop_e($wechatQr); ?>" placeholder="微信收款码地址 / 链接" style="flex: 1;" oninput="updateQrPreview('wechat')">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('wechat_upload').click()">上传截图</button>
                        <input type="file" id="wechat_upload" accept="image/*" style="display: none;" onchange="uploadPaymentQr(event, 'wechat')">
                    </div>
                    <div id="wechat_preview" style="margin-top: 10px; width: 150px; height: 150px; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($wechatQr): ?>
                            <img src="<?php echo shop_e($wechatQr); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 12px;">暂未配置收款码</span>
                        <?php endif; ?>
                    </div>
                </label>
                
                <label class="field field-full">
                    <span class="label">支付宝支付 (可填图片URL，也可点击上传)</span>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="alipay_qr" name="alipay_qr" value="<?php echo shop_e($alipayQr); ?>" placeholder="支付宝收款码地址 / 链接" style="flex: 1;" oninput="updateQrPreview('alipay')">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('alipay_upload').click()">上传截图</button>
                        <input type="file" id="alipay_upload" accept="image/*" style="display: none;" onchange="uploadPaymentQr(event, 'alipay')">
                    </div>
                    <div id="alipay_preview" style="margin-top: 10px; width: 150px; height: 150px; border: 1px dashed #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($alipayQr): ?>
                            <img src="<?php echo shop_e($alipayQr); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 12px;">暂未配置收款码</span>
                        <?php endif; ?>
                    </div>
                </label>
            </div>

            <script>
            function updateQrPreview(type) {
                const input = document.getElementById(type + '_qr');
                const preview = document.getElementById(type + '_preview');
                const url = input.value.trim();
                
                if (url) {
                    preview.innerHTML = `<img src="${url}" style="width: 100%; height: 100%; object-fit: contain;" alt="收款码">`;
                } else {
                    preview.innerHTML = `<span style="color: #94a3b8; font-size: 12px;">暂未配置收款码</span>`;
                }
            }

            function uploadPaymentQr(event, type) {
                const file = event.target.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('file', file);
                
                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.url) {
                        document.getElementById(type + '_qr').value = data.url;
                        updateQrPreview(type);
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(err => alert('上传收款码过程出错，请联系后台。'));
            }
            </script>
            
            <div class="actions">
                <button class="btn btn-primary" type="submit">保存支付状态</button>
            </div>
        </form>
    </div>
</section>
