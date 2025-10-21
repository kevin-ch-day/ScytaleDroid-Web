<?php
// pages/index.php
require_once __DIR__ . '/../lib/header.php';

// helpers & db
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

// inputs
$q        = guard_search($_GET['q'] ?? null);
$category = guard_category($_GET['category'] ?? null);
[$size, $offset, $page] = pager_from_query($_GET);

// data (paginated)
$pg    = apps_directory_paged($category, $q, $page, $size);
$rows  = $pg['rows'];
$total = $pg['total'];

// helper for query string persistence
$baseUrl  = BASE_URL . '/pages/index.php';
$persist  = ['q' => $q, 'category' => $category, 'size' => $size];
$filtered = array_filter($persist, fn($v) => $v !== null && $v !== '');

// aggregate severity counts for quick-glance metrics
$severityTotals = ['high' => 0, 'med' => 0, 'low' => 0];
foreach ($rows as $r) {
    $severityTotals['high'] += (int)($r['high'] ?? 0);
    $severityTotals['med']  += (int)($r['med'] ?? 0);
    $severityTotals['low']  += (int)($r['low'] ?? 0);
}

$filtersData = [
    'baseUrl'  => $baseUrl,
    'q'        => $q,
    'category' => $category,
    'size'     => $size,
    'hasActiveFilters' => !empty($filtered),
];

$tableData = [
    'rows'     => $rows,
    'total'    => $total,
    'page'     => $page,
    'size'     => $size,
    'baseUrl'  => $baseUrl,
    'persist'  => $filtered,
    'severityTotals' => $severityTotals,
];
?>

<main class="layout-grid section">
  <?php
  extract($filtersData, EXTR_SKIP);
  require __DIR__ . '/_partials/filters_apps.php';

  extract($tableData, EXTR_SKIP);
  require __DIR__ . '/_partials/table_apps.php';
  ?>
</main>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>