<?php declare(strict_types=1);

function shop_paginate(int $total, int $perPage = 20, int $currentPage = 1): array
{
    $safe_total = max(0, $total);
    $safe_per_page = max(1, $perPage);
    $total_pages = max(1, (int) ceil($safe_total / $safe_per_page));
    $safe_current_page = min(max(1, $currentPage), $total_pages);
    $offset = ($safe_current_page - 1) * $safe_per_page;

    return [
        'offset' => $offset,
        'limit' => $safe_per_page,
        'total_pages' => $total_pages,
        'current_page' => $safe_current_page,
        'has_prev' => $safe_current_page > 1,
        'has_next' => $safe_current_page < $total_pages,
        'total' => $safe_total,
    ];
}

function shop_render_pagination(array $pagination, string $baseUrl): string
{
    $total_pages = max(1, (int) ($pagination['total_pages'] ?? 1));
    $current_page = max(1, (int) ($pagination['current_page'] ?? 1));

    if ($total_pages <= 1) {
        return '';
    }

    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    $html = '<nav class="shop-pagination" aria-label="分页导航">';

    if (!empty($pagination['has_prev'])) {
        $html .= '<a href="' . shop_e($baseUrl . ($current_page - 1)) . '" class="shop-pagination-link">上一页</a>';
    }

    for ($page = $start_page; $page <= $end_page; $page++) {
        $is_current = $page === $current_page;
        $cls = 'shop-pagination-link' . ($is_current ? ' shop-pagination-link--active' : '');
        $html .= '<a href="' . shop_e($baseUrl . $page) . '" class="' . $cls . '">' . $page . '</a>';
    }

    if (!empty($pagination['has_next'])) {
        $html .= '<a href="' . shop_e($baseUrl . ($current_page + 1)) . '" class="shop-pagination-link">下一页</a>';
    }

    $html .= '</nav>';

    return $html;
}
