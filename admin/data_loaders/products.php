<?php
declare(strict_types=1);

/**
 * Products tab 数据加载。
 * 依赖父作用域：$products, $pdo, $prefix, $perPage, $editingProduct, $categoryOptions, $adminUrl
 * 设置变量：$selectedProduct, $publishedAtInput, $selectedCategory, $categoryChoices,
 *           $productRows, $productPagination, $productPaginationUrl, $productCategoryFilter,
 *           $productStatusFilter, $homePreview, $pagePreview
 */

if ($editingProduct !== null) {
    $selectedProduct = shop_normalize_product($editingProduct, (int) ($editingProduct['id'] ?? 0));
}
$selectedCategory = (string) ($selectedProduct['category'] ?? '未分类');
$publishedAtInput = shop_to_input_datetime((string) ($selectedProduct['published_at'] ?? ''));
if ($publishedAtInput === '') {
    $publishedAtInput = date('Y-m-d\TH:i');
}

foreach ($products as $product) {
    $name = (string) ($product['category'] ?? '未分类');
    if (!in_array($name, $categoryChoices, true)) {
        $categoryChoices[] = $name;
    }
}
if ($selectedCategory === '') {
    $selectedCategory = $categoryChoices[0] ?? '未分类';
}

$homePreview = array_slice(shop_sort_products_for_home($products), 0, 6);
$pagePreview = array_slice(shop_sort_products_for_page($products), 0, 6);

$productsPage = max(1, (int) ($_GET['products_page'] ?? 1));
$productCategoryFilter = trim((string) ($_GET['product_category'] ?? ''));
$productStatusFilter = trim((string) ($_GET['product_status'] ?? ''));
if ($productCategoryFilter !== '' && !in_array($productCategoryFilter, $categoryChoices, true)) {
    $productCategoryFilter = '';
}
if (!in_array($productStatusFilter, ['', 'on_sale', 'off_sale'], true)) {
    $productStatusFilter = '';
}

$productPaginationBase = $adminUrl . '&tab=products';
if ($productCategoryFilter !== '') {
    $productPaginationBase .= '&product_category=' . urlencode($productCategoryFilter);
}
if ($productStatusFilter !== '') {
    $productPaginationBase .= '&product_status=' . urlencode($productStatusFilter);
}
$productPaginationUrl = $productPaginationBase . '&products_page=';

if ($pdo) {
    try {
        $productWhere = [];
        $productParams = [];
        if ($productCategoryFilter !== '') {
            $productWhere[] = 'category = ?';
            $productParams[] = $productCategoryFilter;
        }
        if ($productStatusFilter !== '') {
            $productWhere[] = 'status = ?';
            $productParams[] = $productStatusFilter;
        }
        $productWhereSql = $productWhere === [] ? '' : ' WHERE ' . implode(' AND ', $productWhere);
        $productCountStmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}products`" . $productWhereSql);
        $productCountStmt->execute($productParams);
        $productTotal = (int) $productCountStmt->fetchColumn();
        $productPagination = shop_paginate($productTotal, $perPage, $productsPage);
        $productStmt = $pdo->prepare("SELECT * FROM `{$prefix}products`" . $productWhereSql . " ORDER BY id ASC LIMIT ? OFFSET ?");
        $bindIndex = 1;
        foreach ($productParams as $param) {
            $productStmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
        }
        $productStmt->bindValue($bindIndex, (int) $productPagination['limit'], PDO::PARAM_INT);
        $productStmt->bindValue($bindIndex + 1, (int) $productPagination['offset'], PDO::PARAM_INT);
        $productStmt->execute();
        $productRows = array_map(
            static fn (array $row): array => shop_normalize_product($row, (int) ($row['id'] ?? 0)),
            $productStmt->fetchAll()
        );
    } catch (PDOException $e) {
        shop_log_exception('商品分页查询失败', $e);
        $filtered = array_values(array_filter($products, static function (array $p) use ($productCategoryFilter, $productStatusFilter): bool {
            if ($productCategoryFilter !== '' && (string) ($p['category'] ?? '') !== $productCategoryFilter) return false;
            if ($productStatusFilter !== '' && (string) ($p['status'] ?? '') !== $productStatusFilter) return false;
            return true;
        }));
        $productPagination = shop_paginate(count($filtered), $perPage, $productsPage);
        $productRows = array_slice($filtered, (int) $productPagination['offset'], (int) $productPagination['limit']);
    }
} else {
    $filtered = array_values(array_filter($products, static function (array $p) use ($productCategoryFilter, $productStatusFilter): bool {
        if ($productCategoryFilter !== '' && (string) ($p['category'] ?? '') !== $productCategoryFilter) return false;
        if ($productStatusFilter !== '' && (string) ($p['status'] ?? '') !== $productStatusFilter) return false;
        return true;
    }));
    $productPagination = shop_paginate(count($filtered), $perPage, $productsPage);
    $productRows = array_slice($filtered, (int) $productPagination['offset'], (int) $productPagination['limit']);
}
