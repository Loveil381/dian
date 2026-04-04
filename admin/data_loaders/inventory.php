<?php
declare(strict_types=1);

/**
 * Inventory tab 数据加载。
 * 依赖父作用域：$products, $categoryOptions, $categoryChoices, $editingInventory
 * 设置变量：$inventoryRows, $inventoryStats, $lowStockProducts,
 *           $selectedInventoryForm, $selectedInventoryPublishedAtInput, $categoryChoices（扩展）
 */

foreach ($products as $product) {
    $name = (string) ($product['category'] ?? '未分类');
    if (!in_array($name, $categoryChoices, true)) {
        $categoryChoices[] = $name;
    }
}

if ($editingInventory !== null) {
    $selectedInventoryForm = shop_normalize_product($editingInventory, (int) ($editingInventory['id'] ?? 0));
}
$selectedInventoryPublishedAtInput = shop_to_input_datetime((string) ($selectedInventoryForm['published_at'] ?? ''));
if ($selectedInventoryPublishedAtInput === '') {
    $selectedInventoryPublishedAtInput = date('Y-m-d\TH:i');
}

$inventoryRows = $products;
usort($inventoryRows, static fn (array $a, array $b): int => ((int) ($a['stock'] ?? 0)) <=> ((int) ($b['stock'] ?? 0)) ?: (((int) ($b['sales'] ?? 0)) <=> ((int) ($a['sales'] ?? 0))));

$lowStockProducts = array_values(array_filter($products, static fn (array $p): bool => (int) ($p['stock'] ?? 0) <= 50));
usort($lowStockProducts, static fn (array $a, array $b): int => ((int) ($a['stock'] ?? 0)) <=> ((int) ($b['stock'] ?? 0)) ?: (((int) ($b['sales'] ?? 0)) <=> ((int) ($a['sales'] ?? 0))));

$inventoryStats = [
    'total' => count($inventoryRows),
    'low' => count($lowStockProducts),
    'zero' => 0,
    'stock_total' => 0,
];
foreach ($inventoryRows as $p) {
    $stock = (int) ($p['stock'] ?? 0);
    $inventoryStats['stock_total'] += $stock;
    if ($stock === 0) {
        $inventoryStats['zero']++;
    }
}
