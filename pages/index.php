<?php
// pages/index.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';
require_once __DIR__ . '/../database/db_lib/db_queries.php';

// --- inputs
$q        = guard_search($_GET['q'] ?? null);
$category = guard_category($_GET['category'] ?? null);
[$size, $offset, $page] = pager_from_query($_GET);

// --- data (paginated)
$rows = [];
$total = 0;
$errorMsg = null;
$rawProbe = [];

try {
    $pg    = apps_directory_paged($category, $q, $page, $size);
    $rows  = $pg['rows']  ?? [];
    $total = (int)($pg['total'] ?? 0);

    if ($total === 0) {
        $rawProbe = db_all(SQL_APPS_DIR_BASE . ' ' . SQL_APPS_DIR_ORDER . ' LIMIT 10');
    }
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] index failed: ' . $e);
}

// --- qs persistence
$baseUrl  = PAGES_URL . '/index.php';
$persist  = ['q' => $q, 'category' => $category, 'size' => $size];
$filtered = array_filter($persist, fn($v) => $v !== null && $v !== '');

// --- quick metrics
$severityTotals = ['high' => 0, 'med' => 0, 'low' => 0];
foreach ($rows as $r) {
    $severityTotals['high'] += (int)($r['high'] ?? 0);
    $severityTotals['med']  += (int)($r['med']  ?? 0);
    $severityTotals['low']  += (int)($r['low']  ?? 0);
}

$hasActiveFilters = !empty($filtered);

$PAGE_TITLE = 'Apps Directory';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= e($errorMsg) ?></div>
    <?php elseif ($total === 0 && !empty($rawProbe)): ?>
        <div class="alert alert-warning">
            Pagination returned 0 rows, but a direct probe found <?= (int)count($rawProbe) ?> row(s).
            Clear filters or check WHERE/COUNT composition.
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/_partials/filters_apps.php'; ?>
    <?php require __DIR__ . '/_partials/table_apps.php'; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
