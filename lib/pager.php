<?php
// lib/pager.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/db_lib/db_utils.php'; // for page_resolve()

/** Wrapper that returns [limit, offset, page] from $_GET using global defaults */
function pager_from_query(array $q): array
{
    return page_resolve($q);
}

/** Render a minimal pager */
function pager_render(string $baseUrl, int $total, int $page, int $size, array $extraParams = []): void
{
    $pages = max(1, (int)ceil($total / max(1, $size)));
    if ($pages <= 1) return;

    $mk = function (int $p) use ($baseUrl, $size, $extraParams) {
        $params = array_merge($extraParams, ['page' => $p, 'size' => $size]);
        return $baseUrl . '?' . http_build_query($params);
    };

    $prev = max(1, $page - 1);
    $next = min($pages, $page + 1);

    echo '<nav class="pager" aria-label="Pagination">';
    echo '<span class="pager-summary">Page ' . $page . ' of ' . $pages . '</span>';
    echo '<div class="pager-links">';
    if ($page === 1) {
        echo '<span class="pager-link pager-prev is-disabled" aria-disabled="true">Prev</span>';
    } else {
        echo '<a class="pager-link pager-prev" href="' . htmlspecialchars($mk($prev)) . '">Prev</a>';
    }
    // simple window: current ±2
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) echo '<span class="pager-ellipsis" aria-hidden="true">…</span>';
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $page) {
            echo '<span class="pager-link page active" aria-current="page">' . $i . '</span>';
            continue;
        }
        echo '<a class="pager-link page" href="' . htmlspecialchars($mk($i)) . '">' . $i . '</a>';
    }
    if ($end < $pages) echo '<span class="pager-ellipsis" aria-hidden="true">…</span>';
    if ($page === $pages) {
        echo '<span class="pager-link pager-next is-disabled" aria-disabled="true">Next</span>';
    } else {
        echo '<a class="pager-link pager-next" href="' . htmlspecialchars($mk($next)) . '">Next</a>';
    }
    echo '</div>';
    echo '</nav>';
}
