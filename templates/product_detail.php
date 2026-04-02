<?php
declare(strict_types=1);

require_once __DIR__ . '/../data/products.php';

$id = (int) ($_GET['id'] ?? 0);
$allProducts = shop_get_products();
$product = shop_find_product($allProducts, $id);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    echo '商品未找到';
    exit;
}

$pageTitle = '魔女小店 - ' . shop_e($product['name']);
$currentPage = 'product_detail';
$showFooter = true;

$images = $product['images'] ?? [];
if (empty($images) && !empty($product['cover_image'])) {
    $images[] = $product['cover_image'];
}

$displayImage = !empty($product['cover_image']) ? $product['cover_image'] : ($images[0] ?? '');

// Get payment configuration from settings
$pdo = get_db_connection();
$prefix = get_db_prefix();
$wechatQr = '';
$alipayQr = '';
$requireAddress = false;

$userPhone = '';
$userAddress = '';
$userName = $_SESSION['user_name'] ?? '';
$isLoggedIn = isset($_SESSION['user_id']);

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT `key`, `value` FROM `{$prefix}settings` WHERE `key` IN ('wechat_qr', 'alipay_qr', 'require_address')");
        while ($row = $stmt->fetch()) {
            if ($row['key'] === 'wechat_qr') $wechatQr = $row['value'];
            if ($row['key'] === 'alipay_qr') $alipayQr = $row['value'];
            if ($row['key'] === 'require_address') $requireAddress = ($row['value'] === '1');
        }
        
        if ($isLoggedIn) {
            $stmtUser = $pdo->prepare("SELECT phone, address FROM `{$prefix}users` WHERE id = ?");
            $stmtUser->execute([$_SESSION['user_id']]);
            $userData = $stmtUser->fetch();
            if ($userData) {
                $userPhone = $userData['phone'] ?? '';
                $userAddress = $userData['address'] ?? '';
            }
        }
    } catch (PDOException $e) {}
}

$hasPayment = ($wechatQr !== '' || $alipayQr !== '');
$skus = [];
if (!empty($product['sku'])) {
    $skus = json_decode($product['sku'], true) ?: [];
}
if (empty($skus)) {
    $skus = [['name' => '默认规格', 'stock' => (int)$product['stock'], 'price' => (float)$product['price']]];
}

include __DIR__ . '/header.php';
?>

<main class="page-shell">
    <div style="max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <?php if ($displayImage): ?>
                <img src="<?php echo shop_e($displayImage); ?>" alt="<?php echo shop_e($product['name']); ?>" style="width: 100%; border-radius: 8px; object-fit: cover;">
            <?php else: ?>
                <div style="width: 100%; height: 300px; background: #e5e7eb; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af;">暂无图片</div>
            <?php endif; ?>
            
            <?php if (count($images) > 1): ?>
            <div style="display: flex; gap: 10px; margin-top: 10px; overflow-x: auto;">
                <?php foreach ($images as $img): ?>
                    <img src="<?php echo shop_e($img); ?>" alt="Thumbnail" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid <?php echo $img === $displayImage ? '#2563eb' : 'transparent'; ?>;" onclick="document.querySelector('img[alt=\'<?php echo shop_e($product['name']); ?>\']').src = this.src; this.parentElement.querySelectorAll('img').forEach(el => el.style.borderColor = 'transparent'); this.style.borderColor = '#2563eb';">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="flex: 1; min-width: 300px;">
            <h1 style="font-size: 24px; margin-top: 0;"><?php echo shop_e($product['name']); ?></h1>
            <p style="color: #6b7280; font-size: 14px; margin-bottom: 20px;">分类: <?php echo shop_e($product['category']); ?> | 销量: <?php echo shop_format_sales((int)$product['sales']); ?></p>
            
            <div style="font-size: 28px; color: #dc2626; font-weight: bold; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <span id="mainPriceDisplay"><?php echo shop_format_price((float)$product['price']); ?></span>
                <span id="soldOutBadge" style="display: <?php echo $skus[0]['stock'] <= 0 ? 'inline-block' : 'none'; ?>; padding: 4px 8px; background: #fee2e2; color: #dc2626; font-size: 14px; border-radius: 4px; font-weight: normal;">已售罄</span>
            </div>
            
            <div style="margin-bottom: 20px;">
                <strong style="display: block; margin-bottom: 10px;">选择规格:</strong>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;" id="skuOptions">
                    <?php foreach ($skus as $index => $sku): ?>
                        <div class="sku-btn <?php echo $index === 0 ? 'active' : ''; ?>" 
                             style="padding: 8px 16px; border: 2px solid <?php echo $index === 0 ? '#2563eb' : '#e5e7eb'; ?>; border-radius: 6px; cursor: pointer; color: <?php echo $index === 0 ? '#2563eb' : '#4b5563'; ?>; <?php echo (int)$sku['stock'] <= 0 ? 'opacity: 0.5; pointer-events: none;' : ''; ?>"
                             onclick="selectSku(<?php echo $index; ?>, '<?php echo shop_e($sku['name']); ?>', <?php echo (float)$sku['price']; ?>, <?php echo (int)$sku['stock']; ?>)">
                            <?php echo shop_e($sku['name']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <strong>库存:</strong> <span id="stockDisplay"><?php echo shop_format_sales((int)$skus[0]['stock']); ?></span> 件
            </div>
            
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 30px; font-size: 14px; line-height: 1.6; color: #4b5563;">
                <?php echo nl2br(shop_e($product['description'])); ?>
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button id="buyBtn" onclick="showPaymentPopup()" style="flex: 1; padding: 15px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer;">
                    立即购买：<?php echo shop_format_price((float)$skus[0]['price']); ?>
                </button>
                <form method="post" action="index.php?page=cart" style="flex: 1; display: flex;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="name" value="<?php echo shop_e($product['name']); ?>">
                    <input type="hidden" name="cover_image" value="<?php echo shop_e($displayImage); ?>">
                    <input type="hidden" name="sku_name" id="cartSkuName" value="<?php echo shop_e($skus[0]['name']); ?>">
                    <input type="hidden" name="sku_price" id="cartSkuPrice" value="<?php echo (float)$skus[0]['price']; ?>">
                    <button id="cartBtnSubmit" type="submit" style="width: 100%; padding: 15px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer;">
                        加入购物车
                    </button>
                </form>
            </div>
            
            <script>
            let currentPrice = <?php echo (float)$skus[0]['price']; ?>;
            let currentSkuName = '<?php echo shop_e($skus[0]['name']); ?>';
            
            function selectSku(index, name, price, stock) {
                document.querySelectorAll('.sku-btn').forEach((btn, i) => {
                    if (i === index) {
                        btn.style.borderColor = '#2563eb';
                        btn.style.color = '#2563eb';
                    } else {
                        btn.style.borderColor = '#e5e7eb';
                        btn.style.color = '#4b5563';
                    }
                });
                
                currentPrice = price;
                currentSkuName = name;
                if (document.getElementById('mainPriceDisplay')) {
                    document.getElementById('mainPriceDisplay').innerText = '￥' + price.toLocaleString('en-US', {minimumFractionDigits: 2});
                } else {
                    document.querySelector('div[style*="color: #dc2626"]').innerText = '￥' + price.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
                
                if (document.getElementById('soldOutBadge')) {
                    document.getElementById('soldOutBadge').style.display = stock <= 0 ? 'inline-block' : 'none';
                }
                
                document.getElementById('stockDisplay').innerText = stock.toLocaleString('en-US');
                
                const buyBtn = document.getElementById('buyBtn');
                const cartBtn = document.getElementById('cartBtnSubmit');
                
                if (stock <= 0) {
                    if (buyBtn) {
                        buyBtn.disabled = true;
                        buyBtn.style.background = '#9ca3af';
                        buyBtn.innerText = '已售罄';
                    }
                    if (cartBtn) {
                        cartBtn.disabled = true;
                        cartBtn.style.background = '#9ca3af';
                        cartBtn.innerText = '已售罄';
                    }
                } else {
                    if (buyBtn) {
                        buyBtn.disabled = false;
                        buyBtn.style.background = '#2563eb';
                        buyBtn.innerText = '立即购买：￥' + price.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                    if (cartBtn) {
                        cartBtn.disabled = false;
                        cartBtn.style.background = '#f59e0b';
                        cartBtn.innerText = '加入购物车';
                    }
                }
                
                if (document.getElementById('selectedSkuInput')) {
                    document.getElementById('selectedSkuInput').value = name;
                }
                if (document.getElementById('selectedPriceInput')) {
                    document.getElementById('selectedPriceInput').value = price;
                }
                if (document.getElementById('popupPriceDisplay')) {
                    document.getElementById('popupPriceDisplay').innerText = '¥' + price.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
                if (document.getElementById('cartSkuName')) {
                    document.getElementById('cartSkuName').value = name;
                }
                if (document.getElementById('cartSkuPrice')) {
                    document.getElementById('cartSkuPrice').value = price;
                }
            }

            window.addEventListener('DOMContentLoaded', () => {
                const initialStock = <?php echo (int)$skus[0]['stock']; ?>;
                const buyBtn = document.getElementById('buyBtn');
                const cartBtn = document.getElementById('cartBtnSubmit');
                
                if (initialStock <= 0) {
                    if (buyBtn) {
                        buyBtn.disabled = true;
                        buyBtn.style.background = '#9ca3af';
                        buyBtn.innerText = '已售罄';
                    }
                    if (cartBtn) {
                        cartBtn.disabled = true;
                        cartBtn.style.background = '#9ca3af';
                        cartBtn.innerText = '已售罄';
                    }
                }
            });
            </script>
        </div>
    </div>
</main>

<div id="alertPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 3000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 320px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #4b5563; margin-bottom: 15px;">提示</h3>
        <p id="alertMsg" style="color: #4b5563; margin-bottom: 25px; line-height: 1.5;"></p>
        <div style="display: flex; gap: 10px;">
            <button onclick="hideAlert()" style="flex: 1; padding: 10px; background: #e5e7eb; color: #4b5563; border: none; border-radius: 6px; cursor: pointer;">取消</button>
            <a href="index.php?page=profile" style="flex: 1; padding: 10px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; display: inline-block;">去填写</a>
        </div>
    </div>
</div>

<div id="paymentPopup" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px; position: relative; text-align: center;">
        <button onclick="hidePaymentPopup()" style="position: absolute; top: 10px; right: 10px; border: none; background: transparent; font-size: 20px; cursor: pointer;">&times;</button>
        <h2 style="margin-top: 0;">选择支付方式</h2>
        
        <?php if (!$hasPayment): ?>
            <p style="color: #6b7280; padding: 20px;">支付系统维护中</p>
        <?php else: ?>
            <div style="display: flex; gap: 20px; justify-content: center; margin: 20px 0;">
                <?php if ($wechatQr): ?>
                <button onclick="showQR('wechat')" style="padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer;">微信支付</button>
                <?php endif; ?>
                <?php if ($alipayQr): ?>
                <button onclick="showQR('alipay')" style="padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer;">支付宝</button>
                <?php endif; ?>
            </div>
            
            <div id="qrContainer" style="display: none; margin: 20px 0;">
                <p id="popupPriceDisplay" style="font-size: 28px; font-weight: bold; color: #dc2626; margin-bottom: 15px;"></p>
                
                <div id="wechatQR" style="display: none; width: 200px; height: 200px; background: #e5e7eb; margin: 0 auto; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($wechatQr); ?>" alt="微信收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div id="alipayQR" style="display: none; width: 200px; height: 200px; background: #e5e7eb; margin: 0 auto; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                    <img src="<?php echo shop_e($alipayQr); ?>" alt="支付宝收款码" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </div>
            
            <form method="post" action="index.php?page=create_order" id="paidForm" style="display: none;">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="sku_name" id="selectedSkuInput" value="<?php echo shop_e($skus[0]['name']); ?>">
                <input type="hidden" name="sku_price" id="selectedPriceInput" value="<?php echo (float)$skus[0]['price']; ?>">
                <input type="hidden" name="pay_method" id="payMethodInput" value="">
                <button type="submit" onclick="hidePaymentPopup()" style="width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 20px;">我已支付</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function showAlert(msg) {
    document.getElementById('alertMsg').innerText = msg;
    document.getElementById('alertPopup').style.display = 'flex';
}

function hideAlert() {
    document.getElementById('alertPopup').style.display = 'none';
}

function showPaymentPopup() {
    <?php if (!$hasPayment): ?>
    showAlert('支付系统维护中');
    return;
    <?php endif; ?>
    
    <?php if ($requireAddress): ?>
    const hasName = <?php echo json_encode(!empty($userName)); ?>;
    const hasPhone = <?php echo json_encode(!empty($userPhone)); ?>;
    const hasAddress = <?php echo json_encode(!empty($userAddress)); ?>;
    
    if (!hasName || !hasPhone || !hasAddress) {
        showAlert('请在个人中心完整填写默认收货人、手机号和收货地址才能购买。');
        return;
    }
    <?php endif; ?>
    
    document.getElementById('paymentPopup').style.display = 'flex';
    document.getElementById('qrContainer').style.display = 'none';
    document.getElementById('paidForm').style.display = 'none';
    document.getElementById('popupPriceDisplay').innerText = '¥' + currentPrice.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function hidePaymentPopup() {
    document.getElementById('paymentPopup').style.display = 'none';
}

function showQR(method) {
    document.getElementById('qrContainer').style.display = 'block';
    document.getElementById('paidForm').style.display = 'block';
    document.getElementById('payMethodInput').value = method;
    
    if (method === 'wechat') {
        document.getElementById('wechatQR').style.display = 'flex';
        document.getElementById('alipayQR').style.display = 'none';
    } else {
        document.getElementById('wechatQR').style.display = 'none';
        document.getElementById('alipayQR').style.display = 'flex';
    }
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
