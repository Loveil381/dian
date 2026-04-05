function shopFormatSitePrice(price) {
    return '¥' + Number(price).toLocaleString('zh-CN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function shopUpdateProductActionState(stock, price) {
    const buyBtn = document.getElementById('buyBtn');
    const cartBtn = document.getElementById('cartBtnSubmit');

    if (stock <= 0) {
        if (buyBtn) {
            buyBtn.disabled = true;
            buyBtn.classList.add('btn--disabled');
            buyBtn.classList.remove('btn--active');
            buyBtn.textContent = '暂时缺货';
        }

        if (cartBtn) {
            cartBtn.disabled = true;
            cartBtn.classList.add('btn--disabled');
            cartBtn.classList.remove('btn--active');
            cartBtn.textContent = '暂时缺货';
        }

        return;
    }

    if (buyBtn) {
        buyBtn.disabled = false;
        buyBtn.classList.remove('btn--disabled');
        buyBtn.classList.add('btn--active');
        buyBtn.textContent = '立即购买 ' + shopFormatSitePrice(price);
    }

    if (cartBtn) {
        cartBtn.disabled = false;
        cartBtn.classList.remove('btn--disabled');
        cartBtn.classList.remove('btn--active');
        cartBtn.textContent = '加入购物车';
    }
}

function selectSku(index, name, price, stock) {
    document.querySelectorAll('.sku-btn').forEach((btn, btnIndex) => {
        btn.classList.toggle('sku-btn--selected', btnIndex === index);
    });

    currentPrice = price;
    currentSkuName = name;

    const mainPriceDisplay = document.getElementById('mainPriceDisplay');
    if (mainPriceDisplay) {
        mainPriceDisplay.textContent = shopFormatSitePrice(price);
    }

    const soldOutBadge = document.getElementById('soldOutBadge');
    if (soldOutBadge) {
        soldOutBadge.style.display = stock <= 0 ? 'inline-flex' : 'none';
    }

    const stockDisplay = document.getElementById('stockDisplay');
    if (stockDisplay) {
        stockDisplay.textContent = stock.toLocaleString('zh-CN');
    }

    shopUpdateProductActionState(stock, price);

    const selectedSkuInput = document.getElementById('selectedSkuInput');
    if (selectedSkuInput) {
        selectedSkuInput.value = name;
    }

    const selectedPriceInput = document.getElementById('selectedPriceInput');
    if (selectedPriceInput) {
        selectedPriceInput.value = price;
    }

    const popupPriceDisplay = document.getElementById('popupPriceDisplay');
    if (popupPriceDisplay) {
        popupPriceDisplay.textContent = shopFormatSitePrice(price);
    }

    const cartSkuName = document.getElementById('cartSkuName');
    if (cartSkuName) {
        cartSkuName.value = name;
    }

    const cartSkuPrice = document.getElementById('cartSkuPrice');
    if (cartSkuPrice) {
        cartSkuPrice.value = price;
    }
}

function showAlert(message) {
    const alertMsg = document.getElementById('alertMsg');
    const alertPopup = document.getElementById('alertPopup');

    if (!alertMsg || !alertPopup) {
        window.alert(message);
        return;
    }

    alertMsg.textContent = message;
    alertPopup.style.display = 'flex';
}

function hideAlert() {
    const alertPopup = document.getElementById('alertPopup');
    if (alertPopup) {
        alertPopup.style.display = 'none';
    }
}

function showPaymentPopup() {
    if (typeof hasPayment !== 'undefined' && !hasPayment) {
        showAlert('当前未配置支付方式，请联系管理员。');
        return;
    }

    if (typeof requireAddress !== 'undefined' && requireAddress && typeof hasUserInfo !== 'undefined' && !hasUserInfo) {
        showAlert('请先完善收货信息后再继续支付。');
        return;
    }

    const paymentPopup = document.getElementById('paymentPopup');
    const qrContainer = document.getElementById('qrContainer');
    const paidForm = document.getElementById('paidForm');
    const popupPriceDisplay = document.getElementById('popupPriceDisplay');
    const wechatQR = document.getElementById('wechatQR');
    const alipayQR = document.getElementById('alipayQR');

    if (!paymentPopup) {
        return;
    }

    paymentPopup.style.display = 'flex';

    if (qrContainer) {
        qrContainer.style.display = 'none';
    }

    if (paidForm) {
        paidForm.style.display = 'none';
    }

    if (wechatQR) {
        wechatQR.style.display = 'none';
    }

    if (alipayQR) {
        alipayQR.style.display = 'none';
    }

    if (popupPriceDisplay && typeof currentPrice !== 'undefined') {
        popupPriceDisplay.textContent = shopFormatSitePrice(currentPrice);
    }
}

function hidePaymentPopup() {
    const paymentPopup = document.getElementById('paymentPopup');
    if (paymentPopup) {
        paymentPopup.style.display = 'none';
    }
}

function selectPayment(method, element) {
    document.querySelectorAll('.pay-method-btn').forEach((button) => {
        if (button instanceof HTMLElement) {
            button.classList.toggle('pay-method-btn--selected', button === element);
        }
    });

    if (typeof initialPayMethod !== 'undefined') {
        initialPayMethod = method;
    }

    showQR(method);
}

function showQR(method) {
    const qrContainer = document.getElementById('qrContainer');
    const paidForm = document.getElementById('paidForm');
    const payMethodInput = document.getElementById('payMethodInput');
    const wechatQR = document.getElementById('wechatQR');
    const alipayQR = document.getElementById('alipayQR');
    const popupPriceDisplay = document.getElementById('popupPriceDisplay');

    if (qrContainer) {
        qrContainer.style.display = 'block';
    }

    if (paidForm) {
        paidForm.style.display = 'block';
    }

    if (payMethodInput) {
        payMethodInput.value = method;
    }

    if (popupPriceDisplay && typeof currentPrice !== 'undefined') {
        popupPriceDisplay.textContent = shopFormatSitePrice(currentPrice);
    }

    if (wechatQR) {
        wechatQR.style.display = method === 'wechat' ? 'flex' : 'none';
    }

    if (alipayQR) {
        alipayQR.style.display = method === 'alipay' ? 'flex' : 'none';
    }
}

function submitOrder() {
    const payMethodInput = document.getElementById('payMethodInput');
    const form = document.getElementById('checkoutForm');

    if (!payMethodInput || !form) {
        return;
    }

    if (!payMethodInput.value) {
        window.alert('请先选择支付方式。');
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (window.confirm('确认已完成支付并提交订单吗？')) {
        form.submit();
    }
}

function shopHideSearchResults(searchResults) {
    if (!searchResults) {
        return;
    }

    searchResults.hidden = true;
    searchResults.innerHTML = '';
}

function shopBindSearchAjax(searchForm, searchInput) {
    const searchResults = document.getElementById('searchAjaxResults');
    if (!searchForm || !searchInput || !searchResults) {
        return;
    }

    let debounceTimer = null;
    let searchAbortController = null;

    const renderMessage = (message) => {
        searchResults.hidden = false;
        searchResults.innerHTML = `<div class="empty-state"><strong>${message}</strong></div>`;
    };

    const fetchResults = (keyword) => {
        if (searchAbortController) {
            searchAbortController.abort();
        }

        renderMessage('正在搜索...');
        searchAbortController = new AbortController();

        fetch(`index.php?page=products&keyword=${encodeURIComponent(keyword)}&ajax=1`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: searchAbortController.signal
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('search_request_failed');
                }

                return response.text();
            })
            .then((html) => {
                const trimmedHtml = html.trim();
                searchResults.hidden = false;
                searchResults.innerHTML = trimmedHtml !== '' ? trimmedHtml : '<div class="empty-state"><strong>未找到相关商品</strong></div>';
            })
            .catch((error) => {
                if (error.name === 'AbortError') {
                    return;
                }

                renderMessage('未找到相关商品');
            });
    };

    searchInput.addEventListener('keyup', () => {
        const keyword = searchInput.value.trim();

        if (debounceTimer) {
            window.clearTimeout(debounceTimer);
        }

        if (keyword.length < 2) {
            if (searchAbortController) {
                searchAbortController.abort();
            }

            shopHideSearchResults(searchResults);
            return;
        }

        debounceTimer = window.setTimeout(() => {
            fetchResults(keyword);
        }, 300);
    });

    searchInput.addEventListener('blur', () => {
        window.setTimeout(() => {
            if (document.activeElement !== searchInput) {
                shopHideSearchResults(searchResults);
            }
        }, 150);
    });
}

function shopBindFooterEvents() {
    const menuBtn = document.getElementById('menuBtn');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');

    const closeMobileNav = () => {
        document.body.classList.remove('mobile-nav-open');
        if (menuBtn) {
            menuBtn.setAttribute('aria-expanded', 'false');
        }
    };

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            if (window.innerWidth > 768) {
                return;
            }

            const isOpen = document.body.classList.toggle('mobile-nav-open');
            menuBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', (event) => {
            const keyword = searchInput ? searchInput.value.trim() : '';
            if (keyword !== '') {
                return;
            }

            event.preventDefault();
            window.alert('请输入搜索关键词。');
        });
    }

    shopBindSearchAjax(searchForm, searchInput);

    document.querySelectorAll('.page-nav .page-link').forEach((link) => {
        link.addEventListener('click', closeMobileNav);
    });

    window.addEventListener('resize', () => {
        document.body.dataset.viewport = String(window.innerWidth);
        if (window.innerWidth > 768) {
            closeMobileNav();
        }
    });

    document.body.dataset.viewport = String(window.innerWidth);
}

function shopBindProductGallery() {
    const thumbList = document.getElementById('productThumbList');
    const mainImage = document.getElementById('productMainImage');

    if (!thumbList || !mainImage) {
        return;
    }

    thumbList.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLImageElement) || target.dataset.productThumb !== '1') {
            return;
        }

        mainImage.src = target.src;

        thumbList.querySelectorAll('[data-product-thumb="1"]').forEach((thumb) => {
            if (thumb instanceof HTMLElement) {
                thumb.classList.remove('product-detail-thumb--active');
            }
        });

        target.classList.add('product-detail-thumb--active');
    });
}

function shopUpdateQtyDisplay(qty) {
    var display = document.getElementById('qtyDisplay');
    var cartQtyInput = document.getElementById('cartQtyInput');
    var buyQtyInput = document.getElementById('buyQtyInput');
    var decBtn = document.getElementById('qtyDec');

    if (display) {
        display.textContent = String(qty);
    }
    if (cartQtyInput) {
        cartQtyInput.value = String(qty);
    }
    if (buyQtyInput) {
        buyQtyInput.value = String(qty);
    }
    if (decBtn) {
        if (qty <= 1) {
            decBtn.classList.add('qty-stepper-btn--disabled');
        } else {
            decBtn.classList.remove('qty-stepper-btn--disabled');
        }
    }
}

function shopGetCurrentStock() {
    var stockDisplay = document.getElementById('stockDisplay');
    if (!stockDisplay) {
        return 999;
    }
    return parseInt(stockDisplay.textContent.replace(/,/g, ''), 10) || 0;
}

function shopBindQtyStepper() {
    var decBtn = document.getElementById('qtyDec');
    var incBtn = document.getElementById('qtyInc');

    if (!decBtn || !incBtn) {
        return;
    }

    // 初始状态：数量为 1 时禁用减少按钮
    decBtn.classList.add('qty-stepper-btn--disabled');

    decBtn.addEventListener('click', function() {
        var display = document.getElementById('qtyDisplay');
        if (!display) {
            return;
        }
        var current = parseInt(display.textContent, 10) || 1;
        if (current > 1) {
            shopUpdateQtyDisplay(current - 1);
        }
    });

    incBtn.addEventListener('click', function() {
        var display = document.getElementById('qtyDisplay');
        if (!display) {
            return;
        }
        var current = parseInt(display.textContent, 10) || 1;
        var maxStock = shopGetCurrentStock();
        if (current < maxStock) {
            shopUpdateQtyDisplay(current + 1);
        }
    });
}

function shopBindProductDetailActions() {
    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) {
            return;
        }

        const skuButton = target.closest('[data-sku-index]');
        if (skuButton instanceof HTMLElement) {
            selectSku(
                Number(skuButton.dataset.skuIndex || '0'),
                skuButton.dataset.skuName || '',
                Number(skuButton.dataset.skuPrice || '0'),
                Number(skuButton.dataset.skuStock || '0')
            );
            // 切换规格后重置数量为 1
            shopUpdateQtyDisplay(1);
            return;
        }

        const actionButton = target.closest('[data-action]');
        if (!(actionButton instanceof HTMLElement)) {
            return;
        }

        const action = actionButton.dataset.action || '';
        if (action === 'show-payment-popup') {
            showPaymentPopup();
        } else if (action === 'hide-payment-popup') {
            hidePaymentPopup();
        } else if (action === 'hide-alert') {
            hideAlert();
        } else if (action === 'show-qr') {
            showQR(actionButton.dataset.payMethod || '');
        } else if (action === 'select-payment') {
            const method = actionButton.dataset.payMethod || '';
            selectPayment(method, actionButton);
        } else if (action === 'submit-order') {
            submitOrder();
        } else if (action === 'toggle-consult') {
            var consultCard = document.getElementById('consultCard');
            var widget = consultCard ? consultCard.closest('.consult-widget') : null;
            if (consultCard && widget) {
                var isOpen = !consultCard.classList.contains('consult-card--hidden');
                consultCard.classList.toggle('consult-card--hidden', isOpen);
                widget.classList.toggle('consult-widget--open', !isOpen);
            }
        } else if (action === 'copy-consult-wechat') {
            var wechatIdEl = document.getElementById('consultWechatId');
            if (wechatIdEl && navigator.clipboard) {
                navigator.clipboard.writeText(wechatIdEl.textContent.trim()).then(function() {
                    var icon = actionButton.querySelector('.material-symbols-outlined');
                    if (icon) {
                        icon.textContent = 'check';
                        setTimeout(function() { icon.textContent = 'content_copy'; }, 1500);
                    }
                });
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    shopBindFooterEvents();
    shopBindProductGallery();
    shopBindProductDetailActions();
    shopBindQtyStepper();

    const buyBtn = document.getElementById('buyBtn');
    const cartBtn = document.getElementById('cartBtnSubmit');
    if ((buyBtn || cartBtn) && typeof initialStock !== 'undefined' && typeof currentPrice !== 'undefined') {
        shopUpdateProductActionState(initialStock, currentPrice);
    }

    if (typeof initialPayMethod !== 'undefined' && initialPayMethod) {
        const initialPayButton = document.querySelector(`.pay-method-btn[data-pay-method="${initialPayMethod}"]`);
        if (initialPayButton instanceof HTMLElement) {
            selectPayment(initialPayMethod, initialPayButton);
        } else {
            showQR(initialPayMethod);
        }
    }

    // ── 通用确认弹窗（data-confirm-click） ──
    document.querySelectorAll('[data-confirm-click]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            var message = el.getAttribute('data-confirm-click') || '确定继续当前操作吗？';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ── 返回按钮（data-back）：有浏览历史时 history.back()，否则走 href 兜底 ──
    document.querySelectorAll('[data-back]').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (window.history.length > 1) {
                e.preventDefault();
                history.back();
            }
        });
    });
});
