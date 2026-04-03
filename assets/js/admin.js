let skuIndex = 0;

document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menuBtn');
    const overlay = document.getElementById('overlay');
    const skuContainer = document.getElementById('sku-container');

    const closeSidebar = () => {
        document.body.classList.remove('sidebar-open');
    };

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    document.querySelectorAll('.nav-link').forEach((link) => {
        link.addEventListener('click', closeSidebar);
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

    if (skuContainer) {
        const nextIndex = Number.parseInt(skuContainer.dataset.nextIndex || String(skuContainer.children.length), 10);
        skuIndex = Number.isNaN(nextIndex) ? skuContainer.children.length : nextIndex;
    }

    bindAdminConfirmForms();
    bindAdminConfirmButtons();
    bindAdminTriggerButtons();
    bindAdminFileInputs();
    bindAdminSkuEvents();
    bindAdminPaymentInputs();
    bindAdminProductSelection();

    if (document.getElementById('imagesTextarea')) {
        setTimeout(syncGallery, 300);
    }
});

function bindAdminConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || '确定继续当前操作吗？';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function bindAdminConfirmButtons() {
    document.querySelectorAll('button[data-confirm-click]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.getAttribute('data-confirm-click') || '确定继续当前操作吗？';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
}

function bindAdminTriggerButtons() {
    document.querySelectorAll('[data-trigger-click]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetId = button.getAttribute('data-trigger-click');
            if (!targetId) {
                return;
            }

            const target = document.getElementById(targetId);
            if (target instanceof HTMLElement) {
                target.click();
            }
        });
    });

    document.querySelectorAll('[data-sync-gallery]').forEach((button) => {
        button.addEventListener('click', syncGallery);
    });

    document.querySelectorAll('[data-add-sku]').forEach((button) => {
        button.addEventListener('click', addSkuItem);
    });
}

function bindAdminFileInputs() {
    document.querySelectorAll('input[data-image-upload]').forEach((input) => {
        input.addEventListener('change', handleImageUpload);
    });

    document.querySelectorAll('input[data-payment-upload]').forEach((input) => {
        input.addEventListener('change', (event) => {
            const type = input.getAttribute('data-payment-upload');
            if (type) {
                uploadPaymentQr(event, type);
            }
        });
    });
}

function bindAdminSkuEvents() {
    const container = document.getElementById('sku-container');
    if (!container) {
        return;
    }

    container.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const removeButton = target.closest('[data-sku-remove]');
        if (!(removeButton instanceof HTMLElement)) {
            return;
        }

        const skuItem = removeButton.closest('.sku-item, .admin-products-sku-item');
        if (skuItem instanceof HTMLElement) {
            skuItem.remove();
        }
    });
}

function bindAdminPaymentInputs() {
    document.querySelectorAll('[data-qr-input]').forEach((input) => {
        input.addEventListener('input', () => {
            const type = input.getAttribute('data-qr-input');
            if (type) {
                updateQrPreview(type);
            }
        });
    });
}

function bindAdminProductSelection() {
    const selectAllCheckbox = document.getElementById('selectAllProducts');
    const productCheckboxes = Array.from(document.querySelectorAll('[data-product-checkbox]'));

    if (!(selectAllCheckbox instanceof HTMLInputElement) || productCheckboxes.length === 0) {
        return;
    }

    selectAllCheckbox.addEventListener('change', () => {
        productCheckboxes.forEach((checkbox) => {
            if (checkbox instanceof HTMLInputElement) {
                checkbox.checked = selectAllCheckbox.checked;
            }
        });
    });

    productCheckboxes.forEach((checkbox) => {
        if (!(checkbox instanceof HTMLInputElement)) {
            return;
        }

        checkbox.addEventListener('change', () => {
            const checkedCount = productCheckboxes.filter((item) => item instanceof HTMLInputElement && item.checked).length;
            selectAllCheckbox.checked = checkedCount === productCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < productCheckboxes.length;
        });
    });
}

function addSkuItem() {
    const container = document.getElementById('sku-container');
    if (!container) {
        return;
    }

    const html = `
        <div class="sku-item admin-products-sku-item">
            <input type="text" name="sku[${skuIndex}][name]" placeholder="SKU 名称">
            <input type="number" name="sku[${skuIndex}][stock]" placeholder="库存" min="0" value="0">
            <input type="number" name="sku[${skuIndex}][price]" placeholder="价格" step="0.01" min="0" value="0">
            <button type="button" class="btn btn-danger btn-sm" data-sku-remove>删除</button>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    skuIndex += 1;
    container.dataset.nextIndex = String(skuIndex);
}

function getCsrfToken() {
    const field = document.querySelector('input[name="_csrf_token"]');
    return field instanceof HTMLInputElement ? field.value : '';
}

function getAdminUploadUrl() {
    // 依赖根路由上下文：admin 页面经 /index.php?page=admin 加载，相对路径从根解析
    return 'admin/upload.php';
}

async function postAdminUpload(formData) {
    const csrfToken = getCsrfToken();
    if (!csrfToken) {
        throw new Error('缺少 CSRF 令牌，请刷新页面后重试。');
    }

    const response = await fetch(getAdminUploadUrl(), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    });

    const rawText = await response.text();
    let data = {};

    if (rawText.trim() !== '') {
        try {
            data = JSON.parse(rawText);
        } catch (error) {
            throw new Error(response.ok ? '上传返回格式错误，请联系管理员。' : rawText.trim());
        }
    }

    if (!response.ok || data.error) {
        throw new Error(data.error || '上传失败，请联系管理员。');
    }

    return data;
}

function handleImageUpload(event) {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.files || !input.files.length) {
        return;
    }

    const textarea = document.getElementById('imagesTextarea');
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return;
    }

    Array.from(input.files).forEach(async (file) => {
        const formData = new FormData();
        formData.append('file', file);

        try {
            const data = await postAdminUpload(formData);
            if (data.url) {
                const currentText = textarea.value.trim();
                textarea.value = currentText ? currentText + '\n' + data.url : data.url;
                syncGallery();
            }
        } catch (error) {
            window.alert(error instanceof Error ? error.message : '上传出错，请联系管理员。');
        }
    });

    input.value = '';
}

function syncGallery() {
    const textarea = document.getElementById('imagesTextarea');
    const coverInput = document.getElementById('coverImageInput');
    const gallery = document.getElementById('galleryPreview');
    if (!(textarea instanceof HTMLTextAreaElement) || !(coverInput instanceof HTMLInputElement) || !(gallery instanceof HTMLElement)) {
        return;
    }

    const currentCover = coverInput.value.trim();
    gallery.replaceChildren();

    textarea.value
        .split('\n')
        .map((line) => line.trim())
        .filter((line) => line !== '')
        .forEach((url) => {
            const isCover = url === currentCover;
            const imgBox = document.createElement('div');
            imgBox.style.cssText = `width: 80px; height: 80px; position: relative; border-radius: 6px; overflow: hidden; cursor: pointer; border: 3px solid ${isCover ? '#2563eb' : 'transparent'}`;
            imgBox.addEventListener('click', () => {
                coverInput.value = url;
                syncGallery();
            });

            const img = document.createElement('img');
            img.src = url;
            img.alt = '商品图片';
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';

            const badge = document.createElement('div');
            badge.style.cssText = `position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(37,99,235,0.9); color: white; font-size: 10px; text-align: center; display: ${isCover ? 'block' : 'none'}`;
            badge.innerText = '封面';

            imgBox.appendChild(img);
            imgBox.appendChild(badge);
            gallery.appendChild(imgBox);
        });
}

function updateQrPreview(type) {
    const input = document.getElementById(type + '_qr');
    const preview = document.getElementById(type + '_preview');
    if (!(input instanceof HTMLInputElement) || !(preview instanceof HTMLElement)) {
        return;
    }

    const url = input.value.trim();
    preview.replaceChildren();

    if (url) {
        const image = document.createElement('img');
        image.src = url;
        image.alt = '支付二维码';
        image.style.width = '100%';
        image.style.height = '100%';
        image.style.objectFit = 'contain';
        preview.appendChild(image);
        return;
    }

    const emptyText = document.createElement('span');
    emptyText.style.color = '#94a3b8';
    emptyText.style.fontSize = '12px';
    emptyText.innerText = '暂未设置二维码';
    preview.appendChild(emptyText);
}

function uploadPaymentQr(event, type) {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.files || !input.files[0]) {
        return;
    }

    const formData = new FormData();
    formData.append('file', input.files[0]);

    postAdminUpload(formData)
        .then((data) => {
            if (data.url) {
                const targetInput = document.getElementById(type + '_qr');
                if (targetInput instanceof HTMLInputElement) {
                    targetInput.value = data.url;
                }
                updateQrPreview(type);
            }
        })
        .catch((error) => {
            window.alert(error instanceof Error ? error.message : '上传二维码失败，请联系管理员。');
        });

    input.value = '';
}
