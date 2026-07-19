<?php
// pages/apps.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$q        = guard_search($_GET['q'] ?? null);
$requestedCategory = guard_category($_GET['category'] ?? null);
$category = null;
$categoryOptions = [];
$includeCatalogOnly = guard_bool($_GET['include_catalog'] ?? null, $q === null);
[$size, $offset, $page] = pager_from_query($_GET);
$hasActiveFilters = false;

$rows = [];
$total = 0;
$errorMsg = null;
$rawProbe = [];

try {
    $categoryOptions = apps_directory_categories();
    $category = guard_choice($requestedCategory, $categoryOptions);
    $pg    = apps_directory_paged($category, $q, $includeCatalogOnly, $page, $size);
    $rows  = $pg['rows']  ?? [];
    $total = (int)($pg['total'] ?? 0);

    if ($total === 0 && !$hasActiveFilters) {
        $rawProbe = apps_directory_probe(10);
    }
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] apps failed: ' . $e);
}

$hasActiveFilters = $q !== null || $category !== null || !$includeCatalogOnly;

$baseUrl  = PAGES_URL . '/apps.php';
$persist  = ['q' => $q, 'category' => $category, 'size' => $size, 'include_catalog' => $includeCatalogOnly ? '1' : null];

$severityTotals = ['high' => 0, 'med' => 0, 'low' => 0, 'info' => 0];
$sourceStateCounts = [];
$latestSessionStamp = null;
$latestScannedAt = null;
$catalogOnlyCount = 0;
$analyzedCount = 0;
$dynamicAppCount = 0;
$dynamicRunCount = 0;
$dynamicQuotaValidCount = 0;
$dynamicDomainCount = 0;
$dynamicRootDomainCount = 0;
foreach ($rows as $r) {
    $severityTotals['high'] += (int)($r['high'] ?? 0);
    $severityTotals['med']  += (int)($r['med']  ?? 0);
    $severityTotals['low']  += (int)($r['low']  ?? 0);
    $severityTotals['info'] += (int)($r['info'] ?? 0);
    $state = (string)($r['source_state'] ?? 'unknown');
    $sourceStateCounts[$state] = (int)($sourceStateCounts[$state] ?? 0) + 1;
    if ($state === 'catalog_only') {
        $catalogOnlyCount++;
    } else {
        $analyzedCount++;
    }
    $rowDynamicRuns = (int)($r['dynamic_runs'] ?? 0);
    if ($rowDynamicRuns > 0) {
        $dynamicAppCount++;
        $dynamicRunCount += $rowDynamicRuns;
        $dynamicQuotaValidCount += (int)($r['dynamic_quota_valid_runs'] ?? 0);
        $dynamicDomainCount += (int)($r['dynamic_observed_domains'] ?? 0);
        $dynamicRootDomainCount += (int)($r['dynamic_root_domains'] ?? 0);
    }
    $stamp = trim((string)($r['session_stamp'] ?? ''));
    if ($stamp !== '' && ($latestSessionStamp === null || strcmp($stamp, $latestSessionStamp) > 0)) {
        $latestSessionStamp = $stamp;
    }
    $scanned = trim((string)($r['last_scanned'] ?? ''));
    $scannedTs = $scanned !== '' ? strtotime($scanned) : false;
    if ($scannedTs !== false && ($latestScannedAt === null || $scannedTs > $latestScannedAt)) {
        $latestScannedAt = $scannedTs;
    }
}

$directoryStateSummary = $catalogOnlyCount > 0
    ? 'Showing analyzed apps first with optional inventory-only related packages.'
    : 'Showing packages with finalized analysis-facing data.';

$analyzedRows = array_values(array_filter($rows, static fn(array $r): bool => (string)($r['source_state'] ?? '') !== 'catalog_only'));
$catalogOnlyRows = array_values(array_filter($rows, static fn(array $r): bool => (string)($r['source_state'] ?? '') === 'catalog_only'));
$groupSearchResults = $q !== null && $includeCatalogOnly && !empty($catalogOnlyRows) && !empty($analyzedRows);

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
