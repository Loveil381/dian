function shopFormatSitePrice(price) {
    return '¥' + Number(price).toLocaleString('en-US', {
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
            buyBtn.style.background = '#94a3b8';
            buyBtn.innerText = '已售罄';
        }

        if (cartBtn) {
            cartBtn.disabled = true;
            cartBtn.style.background = '#94a3b8';
            cartBtn.innerText = '已售罄';
        }

        return;
    }

    if (buyBtn) {
        buyBtn.disabled = false;
        buyBtn.style.background = '#2563eb';
        buyBtn.innerText = '立即购买 ' + shopFormatSitePrice(price);
    }

    if (cartBtn) {
        cartBtn.disabled = false;
        cartBtn.style.background = '#f59e0b';
        cartBtn.innerText = '加入购物车';
    }
}

function selectSku(index, name, price, stock) {
    document.querySelectorAll('.sku-btn').forEach((btn, btnIndex) => {
        if (btnIndex === index) {
            btn.style.borderColor = '#2563eb';
            btn.style.color = '#2563eb';
        } else {
            btn.style.borderColor = '#e5e7eb';
            btn.style.color = '#334155';
        }
    });

    currentPrice = price;
    currentSkuName = name;

    const mainPriceDisplay = document.getElementById('mainPriceDisplay');
    if (mainPriceDisplay) {
        mainPriceDisplay.innerText = shopFormatSitePrice(price);
    }

    const soldOutBadge = document.getElementById('soldOutBadge');
    if (soldOutBadge) {
        soldOutBadge.style.display = stock <= 0 ? 'inline-flex' : 'none';
    }

    const stockDisplay = document.getElementById('stockDisplay');
    if (stockDisplay) {
        stockDisplay.innerText = stock.toLocaleString('en-US');
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
        popupPriceDisplay.innerText = shopFormatSitePrice(price);
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
        alert(message);
        return;
    }

    alertMsg.innerText = message;
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
        showAlert('当前未配置支付方式，请稍后再试。');
        return;
    }

    if (typeof requireAddress !== 'undefined' && requireAddress && typeof hasUserInfo !== 'undefined' && !hasUserInfo) {
        showAlert('请先在个人中心完善收货信息，再继续购买。');
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
        popupPriceDisplay.innerText = shopFormatSitePrice(currentPrice);
    }
}

function hidePaymentPopup() {
    const paymentPopup = document.getElementById('paymentPopup');
    if (paymentPopup) {
        paymentPopup.style.display = 'none';
    }
}

function showQR(method) {
    const qrContainer = document.getElementById('qrContainer');
    const paidForm = document.getElementById('paidForm');
    const payMethodInput = document.getElementById('payMethodInput');
    const wechatQR = document.getElementById('wechatQR');
    const alipayQR = document.getElementById('alipayQR');

    if (qrContainer) {
        qrContainer.style.display = 'block';
    }

    if (paidForm) {
        paidForm.style.display = 'block';
    }

    if (payMethodInput) {
        payMethodInput.value = method;
    }

    if (wechatQR) {
        wechatQR.style.display = method === 'wechat' ? (document.getElementById('paymentPopup') ? 'flex' : 'block') : 'none';
    }

    if (alipayQR) {
        alipayQR.style.display = method === 'alipay' ? (document.getElementById('paymentPopup') ? 'flex' : 'block') : 'none';
    }
}

function selectPayment(method, element) {
    document.querySelectorAll('.pay-method-btn').forEach((btn) => {
        btn.style.borderColor = '#e5e7eb';
        btn.style.backgroundColor = '#ffffff';
    });

    element.style.borderColor = method === 'wechat' ? '#10b981' : '#0ea5e9';
    element.style.backgroundColor = method === 'wechat' ? '#ecfdf5' : '#f0f9ff';

    showQR(method);
}

function submitOrder() {
    const payMethodInput = document.getElementById('payMethodInput');
    const form = document.getElementById('checkoutForm');

    if (!payMethodInput || !form) {
        return;
    }

    if (!payMethodInput.value) {
        alert('请选择支付方式并完成扫码。');
        return;
    }

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    if (confirm('确认已经完成支付，并提交订单吗？')) {
        form.submit();
    }
}

function shopBindFooterEvents() {
    const menuBtn = document.getElementById('menuBtn');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const cartBtn = document.getElementById('cartBtn');

    if (menuBtn) {
        menuBtn.addEventListener('click', () => {
            console.log('菜单按钮已点击，后续可接入侧边栏。');
            alert('菜单功能暂未开放');
        });
    }

    if (searchForm) {
        searchForm.addEventListener('submit', (event) => {
            const keyword = searchInput ? searchInput.value.trim() : '';
            if (keyword !== '') {
                console.log(`搜索关键词：${keyword}`);
                return;
            }

            event.preventDefault();
            console.log('搜索关键词为空');
            alert('请输入搜索关键词');
        });
    }

    if (cartBtn) {
        cartBtn.addEventListener('click', () => {
            console.log('购物车入口已点击');
        });
    }

    window.addEventListener('resize', () => {
        console.log(`当前窗口宽度：${window.innerWidth}px`);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    shopBindFooterEvents();

    const buyBtn = document.getElementById('buyBtn');
    const cartBtn = document.getElementById('cartBtnSubmit');
    if ((buyBtn || cartBtn) && typeof initialStock !== 'undefined' && typeof currentPrice !== 'undefined') {
        shopUpdateProductActionState(initialStock, currentPrice);
    }

    if (typeof initialPayMethod !== 'undefined' && initialPayMethod) {
        const selectedButton = document.querySelector(`.pay-method-btn[data-pay-method="${initialPayMethod}"]`);
        if (selectedButton) {
            selectPayment(initialPayMethod, selectedButton);
        }
    }

    console.log('页面公共脚本已加载');
});
