<?php
declare(strict_types=1);

/**
 * Admin POST action dispatcher。
 *
 * 根据 admin_action 参数分派到对应的 handler 文件。
 * 每个 handler 函数返回 [message, messageType]，由本文件统一处理 flash + redirect。
 *
 * 依赖外部变量（由 admin/index.php require 时提供）：
 *   $adminUrl
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../includes/csrf.php';
    require_once __DIR__ . '/../../includes/order_status.php';
    require_once __DIR__ . '/../../includes/admin_log.php';
    csrf_verify();

    $reqTab = $_POST['tab'] ?? '';
    $reqProductsPage = max(1, (int) ($_POST['products_page'] ?? 1));
    $reqOrdersPage = max(1, (int) ($_POST['orders_page'] ?? 1));
    $reqUsersPage = max(1, (int) ($_POST['users_page'] ?? 1));
    $reqOrderStatus = shop_normalize_order_status(trim((string) ($_POST['order_status'] ?? '')));
    $reqProductCategory = trim((string) ($_POST['product_category'] ?? ''));
    $reqProductStatus = trim((string) ($_POST['product_status'] ?? ''));
    $action = (string) ($_POST['admin_action'] ?? '');

    // 按领域加载对应的 handler 文件。
    $result = null;

    switch ($action) {
        // ── 分类 ──
        case 'save_category':
            require_once __DIR__ . '/category_actions.php';
            $result = handle_save_category();
            break;
        case 'delete_category':
            require_once __DIR__ . '/category_actions.php';
            $result = handle_delete_category();
            break;

        // ── 商品 ──
        case 'save_product':
            require_once __DIR__ . '/product_actions.php';
            $result = handle_save_product();
            break;
        case 'update_sort':
            require_once __DIR__ . '/product_actions.php';
            $result = handle_update_sort();
            break;
        case 'delete_product':
            require_once __DIR__ . '/product_actions.php';
            $result = handle_delete_product();
            break;
        case 'batch_product_action':
            require_once __DIR__ . '/product_actions.php';
            $result = handle_batch_product_action();
            break;
        case 'reset_products':
            $result = ['示例数据恢复功能已停用。', 'info'];
            break;

        // ── 用户 ──
        case 'save_user':
            require_once __DIR__ . '/user_actions.php';
            $result = handle_save_user($adminUrl);
            break;
        case 'delete_user':
            require_once __DIR__ . '/user_actions.php';
            $result = handle_delete_user();
            break;
        case 'toggle_user_status':
            require_once __DIR__ . '/user_actions.php';
            $result = handle_toggle_user_status();
            break;

        // ── 订单 ──
        case 'delete_order':
            require_once __DIR__ . '/order_actions.php';
            $result = handle_delete_order();
            break;
        case 'update_order':
            require_once __DIR__ . '/order_actions.php';
            $result = handle_update_order();
            break;
        case 'update_order_status':
            require_once __DIR__ . '/order_actions.php';
            $result = handle_update_order_status();
            break;

        // ── 设置 ──
        case 'save_payment':
            require_once __DIR__ . '/setting_actions.php';
            $result = handle_save_payment();
            break;
        case 'change_password':
            require_once __DIR__ . '/setting_actions.php';
            $result = handle_change_password();
            // handle_change_password 成功时会直接 redirect，不会到这里。
            break;
        case 'save_role':
            require_once __DIR__ . '/setting_actions.php';
            $result = handle_save_role();
            break;
        case 'save_consult':
            require_once __DIR__ . '/setting_actions.php';
            $result = handle_save_consult();
            break;

        // ── 优惠券 ──
        case 'save_coupon':
            require_once __DIR__ . '/coupon_actions.php';
            $result = handle_save_coupon();
            break;
        case 'delete_coupon':
            require_once __DIR__ . '/coupon_actions.php';
            $result = handle_delete_coupon();
            break;
        case 'toggle_coupon':
            require_once __DIR__ . '/coupon_actions.php';
            $result = handle_toggle_coupon();
            break;

        // ── 页面 ──
        case 'save_page':
            require_once __DIR__ . '/page_actions.php';
            $result = handle_save_page();
            break;

        // ── 更新中心 ──
        case 'check_update':
            require_once __DIR__ . '/update_actions.php';
            $result = handle_check_update();
            break;
        case 'create_backup':
            require_once __DIR__ . '/update_actions.php';
            $result = handle_create_backup();
            break;
        case 'apply_update':
            require_once __DIR__ . '/update_actions.php';
            $result = handle_apply_update();
            break;
        case 'rollback_update':
            require_once __DIR__ . '/update_actions.php';
            $result = handle_rollback_update();
            break;
        case 'delete_backup':
            require_once __DIR__ . '/update_actions.php';
            $result = handle_delete_backup();
            break;

        default:
            $result = ['未知操作。', 'error'];
            break;
    }

    [$message, $messageType] = $result;
    shop_admin_flash($message, $messageType);

    $redirectUrl = $adminUrl . ($reqTab !== '' ? '&tab=' . urlencode($reqTab) : '');
    if ($reqTab === 'products') {
        $redirectUrl .= '&products_page=' . $reqProductsPage;
        if ($reqProductCategory !== '') {
            $redirectUrl .= '&product_category=' . urlencode($reqProductCategory);
        }
        if ($reqProductStatus !== '') {
            $redirectUrl .= '&product_status=' . urlencode($reqProductStatus);
        }
    } elseif ($reqTab === 'orders') {
        $redirectUrl .= '&orders_page=' . $reqOrdersPage;
        if ($reqOrderStatus !== '') {
            $redirectUrl .= '&order_status=' . urlencode($reqOrderStatus);
        }
    } elseif ($reqTab === 'users') {
        $redirectUrl .= '&users_page=' . $reqUsersPage;
    } elseif ($reqTab === 'coupons') {
        $reqCouponsPage = max(1, (int) ($_POST['coupons_page'] ?? 1));
        $redirectUrl .= '&coupons_page=' . $reqCouponsPage;
    }
    header('Location: ' . $redirectUrl);
    exit;
}
