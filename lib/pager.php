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

    echo '<div class="pager">';
    echo '<a class="prev' . ($page === 1 ? ' disabled' : '') . '" href="' . htmlspecialchars($mk($prev)) . '">Prev</a>';
    // simple window: current ±2
    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) echo '<span class="muted">…</span>';
    for ($i = $start; $i <= $end; $i++) {
        $cls = 'page' . ($i === $page ? ' active' : '');
        echo '<a class="' . $cls . '" href="' . htmlspecialchars($mk($i)) . '">' . $i . '</a>';
    }
    if ($end < $pages) echo '<span class="muted">…</span>';
    echo '<a class="next' . ($page === $pages ? ' disabled' : '') . '" href="' . htmlspecialchars($mk($next)) . '">Next</a>';
    echo '</div>';
}
