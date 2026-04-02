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

    if (document.getElementById('imagesTextarea')) {
        setTimeout(syncGallery, 500);
    }
});

// 商品编辑相关函数。
function addSkuItem() {
    const container = document.getElementById('sku-container');
    if (!container) {
        return;
    }

    const html = `
        <div class="sku-item" style="display: flex; gap: 10px; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <input type="text" name="sku[${skuIndex}][name]" placeholder="规格名" style="flex: 2;">
            <input type="number" name="sku[${skuIndex}][stock]" placeholder="库存" style="flex: 1;" min="0" value="0">
            <input type="number" name="sku[${skuIndex}][price]" placeholder="价格" style="flex: 1;" step="0.01" min="0" value="0">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">删除</button>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    skuIndex += 1;
    container.dataset.nextIndex = String(skuIndex);
}

function handleImageUpload(event) {
    const files = event.target.files;
    if (!files.length) {
        return;
    }

    const textarea = document.getElementById('imagesTextarea');
    if (!textarea) {
        return;
    }

    Array.from(files).forEach((file) => {
        const formData = new FormData();
        formData.append('file', file);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.url) {
                    const currentText = textarea.value.trim();
                    textarea.value = currentText ? currentText + '\n' + data.url : data.url;
                    syncGallery();
                } else if (data.error) {
                    alert(data.error);
                }
            })
            .catch(() => {
                alert('上传出错，请联系管理员。');
            });
    });
}

function syncGallery() {
    const textarea = document.getElementById('imagesTextarea');
    const coverInput = document.getElementById('coverImageInput');
    const gallery = document.getElementById('galleryPreview');
    if (!textarea || !coverInput || !gallery) {
        return;
    }

    const currentCover = coverInput.value.trim();
    gallery.innerHTML = '';

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
            img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';

            const badge = document.createElement('div');
            badge.style.cssText = `position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(37,99,235,0.9); color: white; font-size: 10px; text-align: center; display: ${isCover ? 'block' : 'none'}`;
            badge.innerText = '封面';

            imgBox.appendChild(img);
            imgBox.appendChild(badge);
            gallery.appendChild(imgBox);
        });
}

// 支付配置相关函数。
function updateQrPreview(type) {
    const input = document.getElementById(type + '_qr');
    const preview = document.getElementById(type + '_preview');
    if (!input || !preview) {
        return;
    }

    const url = input.value.trim();
    if (url) {
        preview.innerHTML = `<img src="${url}" style="width: 100%; height: 100%; object-fit: contain;" alt="收款码">`;
        return;
    }

    preview.innerHTML = `<span style="color: #94a3b8; font-size: 12px;">暂未配置收款码</span>`;
}

function uploadPaymentQr(event, type) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.url) {
                const input = document.getElementById(type + '_qr');
                if (input) {
                    input.value = data.url;
                }
                updateQrPreview(type);
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(() => {
            alert('上传收款码过程出错，请联系后台。');
        });
}
