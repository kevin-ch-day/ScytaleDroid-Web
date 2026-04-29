<?php
// pages/apps.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$q        = guard_search($_GET['q'] ?? null);
$category = guard_category($_GET['category'] ?? null);
[$size, $offset, $page] = pager_from_query($_GET);
$hasActiveFilters = $q !== null || $category !== null;

$rows = [];
$total = 0;
$errorMsg = null;
$rawProbe = [];

try {
    $pg    = apps_directory_paged($category, $q, $page, $size);
    $rows  = $pg['rows']  ?? [];
    $total = (int)($pg['total'] ?? 0);

    if ($total === 0 && !$hasActiveFilters) {
        $rawProbe = apps_directory_probe(10);
    }
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] apps failed: ' . $e);
}

$baseUrl  = PAGES_URL . '/apps.php';
$persist  = ['q' => $q, 'category' => $category, 'size' => $size];

$severityTotals = ['high' => 0, 'med' => 0, 'low' => 0];
$sourceStateCounts = [];
$latestSessionStamp = null;
foreach ($rows as $r) {
    $severityTotals['high'] += (int)($r['high'] ?? 0);
    $severityTotals['med']  += (int)($r['med']  ?? 0);
    $severityTotals['low']  += (int)($r['low']  ?? 0);
    $state = (string)($r['source_state'] ?? 'unknown');
    $sourceStateCounts[$state] = (int)($sourceStateCounts[$state] ?? 0) + 1;
    $stamp = trim((string)($r['session_stamp'] ?? ''));
    if ($stamp !== '' && ($latestSessionStamp === null || strcmp($stamp, $latestSessionStamp) > 0)) {
        $latestSessionStamp = $stamp;
    }
}

$PAGE_TITLE = 'Apps';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
    <?php if ($errorMsg): ?>
        <div class="alert alert-danger"><?= e($errorMsg) ?></div>
    <?php elseif ($total === 0 && !empty($rawProbe)): ?>
        <div class="alert alert-warning">
            No rows matched the current directory query, but the underlying dataset is not empty.
            Clear filters and try again.
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/_partials/filters_apps.php'; ?>
    <?php require __DIR__ . '/_partials/table_apps.php'; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
