<?php
declare(strict_types=1);

/**
 * Categories tab 数据加载。
 * 依赖父作用域：$products, $editingCategory
 * 设置变量：$selectedCategoryForm, $categoryManagementRows
 */

$categories = shop_get_categories();

if ($editingCategory !== null) {
    $selectedCategoryForm = shop_normalize_category($editingCategory, (int) ($editingCategory['id'] ?? 0));
} else {
    $selectedCategoryForm['sort'] = count($categories) + 1;
}

foreach ($categories as $category) {
    $categoryName = (string) ($category['name'] ?? '未分类');
    $items = array_values(array_filter($products, static fn (array $p): bool => (string) ($p['category'] ?? '') === $categoryName));
    $top = $items !== [] ? shop_sort_products_by_field($items, 'home_sort')[0] : null;
    $categoryManagementRows[] = [
        'id' => (int) ($category['id'] ?? 0),
        'name' => $categoryName,
        'description' => (string) ($category['description'] ?? ''),
        'accent' => (string) ($category['accent'] ?? '#cbd5e1'),
        'emoji' => (string) ($category['emoji'] ?? '🛍️'),
        'sort' => (int) ($category['sort'] ?? 0),
        'count' => count($items),
        'top_name' => $top !== null ? (string) ($top['name'] ?? '') : '暂无商品',
        'top_sales' => $top !== null ? (int) ($top['sales'] ?? 0) : 0,
    ];
}
usort($categoryManagementRows, static fn (array $a, array $b): int => ((int) ($a['sort'] ?? 0)) <=> ((int) ($b['sort'] ?? 0)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
