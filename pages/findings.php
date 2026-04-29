<?php
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$severityAllowed = ['critical', 'high', 'medium', 'low', 'info'];
$groupAllowed = ['none', 'title', 'detector', 'app', 'masvs_area'];
$severity = guard_choice($_GET['severity'] ?? null, $severityAllowed);
$q = guard_search($_GET['q'] ?? null);
$groupBy = guard_choice($_GET['group_by'] ?? null, $groupAllowed) ?? 'title';
$includeSynthetic = isset($_GET['include_synthetic']) && $_GET['include_synthetic'] === '1';

$categoryOptions = [];
$masvsOptions = [];
$detectorOptions = [];
$category = null;
$masvsArea = null;
$detector = null;
$rows = [];
$total = 0;
$errorMsg = null;
[$size, $offset, $page] = pager_from_query($_GET);

try {
    $categoryOptions = findings_categories();
    $masvsOptions = findings_masvs_areas();
    $detectorOptions = findings_detectors();
    $category = guard_choice($_GET['category'] ?? null, $categoryOptions);
    $masvsArea = guard_choice($_GET['masvs_area'] ?? null, $masvsOptions);
    $detector = guard_choice($_GET['detector'] ?? null, $detectorOptions);

    if ($groupBy === 'none') {
      $pg = findings_explorer_paged_v2($severity, $category, $masvsArea, $detector, $q, $includeSynthetic, $page, $size);
    } else {
      $pg = findings_explorer_grouped($groupBy, $severity, $category, $masvsArea, $detector, $q, $includeSynthetic, $page, $size);
    }
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] findings explorer failed: ' . $e);
}

$baseUrl = PAGES_URL . '/findings.php';
$persist = [
    'group_by' => $groupBy,
    'include_synthetic' => $includeSynthetic ? '1' : null,
    'severity' => $severity,
    'category' => $category,
    'masvs_area' => $masvsArea,
    'detector' => $detector,
    'q' => $q,
    'size' => $size,
];
$hasActiveFilters = $severity !== null || $category !== null || $masvsArea !== null || $detector !== null || $q !== null || $groupBy !== 'title' || $includeSynthetic;
$suppressionLabel = $includeSynthetic ? 'Showing synthetic rollups and composite findings.' : 'Synthetic rollups and composite findings are hidden by default.';

$PAGE_TITLE = 'Findings Explorer';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <section class="panel" data-panel="findings-filters">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">Findings Explorer</h1>
        <p class="panel-subtitle">Search the latest static findings across the fleet, group repeating patterns, and keep noisy rollups out unless you explicitly want them.</p>
      </div>
      <div class="panel-actions">
        <button type="button" class="panel-toggle" data-action="toggle-panel" aria-expanded="true">Collapse</button>
      </div>
    </div>
    <div class="panel-body">
      <form class="form-row findings-form" method="get" action="<?= e($baseUrl) ?>">
        <input type="search" name="q" placeholder="Search app, package, or finding title" value="<?= e($q ?? '') ?>" autocomplete="off">

        <select name="group_by" aria-label="Group by">
          <option value="title" <?= $groupBy === 'title' ? 'selected' : '' ?>>Group by title</option>
          <option value="detector" <?= $groupBy === 'detector' ? 'selected' : '' ?>>Group by detector</option>
          <option value="app" <?= $groupBy === 'app' ? 'selected' : '' ?>>Group by app</option>
          <option value="masvs_area" <?= $groupBy === 'masvs_area' ? 'selected' : '' ?>>Group by MASVS area</option>
          <option value="none" <?= $groupBy === 'none' ? 'selected' : '' ?>>Show individual rows</option>
        </select>

        <select name="severity" aria-label="Severity">
          <option value="">All severities</option>
          <?php foreach ($severityAllowed as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $severity === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="category" aria-label="Category">
          <option value="">All categories</option>
          <?php foreach ($categoryOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $category === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="masvs_area" aria-label="MASVS area">
          <option value="">All MASVS areas</option>
          <?php foreach ($masvsOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $masvsArea === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="detector" aria-label="Detector">
          <option value="">All detectors</option>
          <?php foreach ($detectorOptions as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $detector === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="size" aria-label="Page size">
          <?php foreach ((defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100]) as $opt): ?>
            <option value="<?= (int)$opt ?>" <?= (int)$opt === (int)$size ? 'selected' : '' ?>><?= (int)$opt ?>/page</option>
          <?php endforeach; ?>
        </select>

        <label class="inline-check">
          <input type="checkbox" name="include_synthetic" value="1" <?= $includeSynthetic ? 'checked' : '' ?>>
          <span>Include synthetic rollups</span>
        </label>

        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn-ghost<?= $hasActiveFilters ? '' : ' disabled' ?>" href="<?= e($baseUrl) ?>">Clear</a>
      </form>
      <p class="inline-hint"><?= e($suppressionLabel) ?></p>
    </div>
  </section>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title"><?= e($groupBy === 'none' ? 'Latest Findings' : 'Grouped Findings') ?></h2>
        <p class="panel-subtitle">
          <?= e((string)$total) ?>
          <?= e($groupBy === 'none' ? 'finding row(s)' : 'group(s)') ?>
          matched the current filters.
        </p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (!$rows): ?>
        <p class="muted">No findings matched the current filters.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sticky">
            <thead>
              <?php if ($groupBy === 'none'): ?>
                <tr>
                  <th>Finding</th>
                  <th>App</th>
                  <th class="col-center">Severity</th>
                  <th>Category</th>
                  <th>MASVS</th>
                  <th>Session</th>
                </tr>
              <?php elseif ($groupBy === 'app'): ?>
                <tr>
                  <th>App</th>
                  <th class="col-num">Rows</th>
                  <th>H/M/L</th>
                  <th class="col-center">Dominant</th>
                </tr>
              <?php else: ?>
                <tr>
                  <th><?= e($groupBy === 'detector' ? 'Detector' : ($groupBy === 'masvs_area' ? 'MASVS Area' : 'Finding title')) ?></th>
                  <th class="col-num">Rows</th>
                  <th class="col-num">Apps</th>
                  <th class="col-center">Dominant</th>
                  <?php if ($groupBy !== 'masvs_area'): ?>
                    <th>Category</th>
                  <?php endif; ?>
                  <?php if ($groupBy !== 'masvs_area'): ?>
                    <th>MASVS</th>
                  <?php endif; ?>
                </tr>
              <?php endif; ?>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php if ($groupBy === 'none'): ?>
                  <?php
                  $pkg = (string)($row['package_name'] ?? '');
                  $viewUrl = $pkg ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) . '&session=' . urlencode((string)($row['session_stamp'] ?? '')) : null;
                  $severityTone = (string)($row['severity'] ?? 'muted');
                  ?>
                  <tr>
                    <td class="cell-clip">
                      <div class="app-primary"><?= e((string)($row['title'] ?? 'Untitled finding')) ?></div>
                      <div class="table-subline"><?= e((string)($row['detector'] ?? 'unknown')) ?></div>
                    </td>
                    <td class="cell-clip">
                      <?php if ($viewUrl): ?>
                        <a href="<?= e($viewUrl) ?>"><?= e((string)($row['app_label'] ?? $pkg)) ?></a>
                      <?php else: ?>
                        <?= e((string)($row['app_label'] ?? $pkg)) ?>
                      <?php endif; ?>
                      <div class="table-subline"><?= e($pkg) ?></div>
                    </td>
                    <td class="col-center"><?= chip(strtoupper($severityTone), $severityTone) ?></td>
                    <td><?= e((string)($row['category'] ?? 'Uncategorized')) ?></td>
                    <td><?= e((string)($row['masvs_area'] ?? 'Unmapped')) ?></td>
                    <td><span class="session-stamp"><?= e((string)($row['session_stamp'] ?? '')) ?></span></td>
                  </tr>
                <?php elseif ($groupBy === 'app'): ?>
                  <?php
                  $pkg = (string)($row['package_name'] ?? '');
                  $viewUrl = $pkg ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) : null;
                  $dominant = ((int)($row['high_rows'] ?? 0) > 0) ? 'high' : (((int)($row['medium_rows'] ?? 0) > 0) ? 'medium' : (((int)($row['low_rows'] ?? 0) > 0) ? 'low' : (string)($row['dominant_severity'] ?? 'info')));
                  ?>
                  <tr>
                    <td class="cell-clip">
                      <?php if ($viewUrl): ?>
                        <a href="<?= e($viewUrl) ?>"><?= e((string)($row['group_value'] ?? $pkg)) ?></a>
                      <?php else: ?>
                        <?= e((string)($row['group_value'] ?? $pkg)) ?>
                      <?php endif; ?>
                      <div class="table-subline"><?= e($pkg) ?></div>
                    </td>
                    <td class="col-num"><?= e((string)($row['finding_rows'] ?? 0)) ?></td>
                    <td><?= e(fmt_hml((int)($row['high_rows'] ?? 0), (int)($row['medium_rows'] ?? 0), (int)($row['low_rows'] ?? 0))) ?></td>
                    <td class="col-center"><?= chip(strtoupper((string)$dominant), (string)$dominant) ?></td>
                  </tr>
                <?php else: ?>
                  <?php $dominant = (string)($row['dominant_severity'] ?? 'info'); ?>
                  <tr>
                    <td class="cell-clip">
                      <div class="app-primary"><?= e((string)($row['group_value'] ?? '')) ?></div>
                    </td>
                    <td class="col-num"><?= e((string)($row['finding_rows'] ?? 0)) ?></td>
                    <td class="col-num"><?= e((string)($row['affected_apps'] ?? 0)) ?></td>
                    <td class="col-center"><?= chip(strtoupper($dominant), $dominant) ?></td>
                    <?php if ($groupBy !== 'masvs_area'): ?>
                      <td><?= e((string)($row['category'] ?? 'Uncategorized')) ?></td>
                    <?php endif; ?>
                    <?php if ($groupBy !== 'masvs_area'): ?>
                      <td><?= e((string)($row['masvs_area'] ?? 'Unmapped')) ?></td>
                    <?php endif; ?>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <div class="panel-footer">
      <?php pager_render($baseUrl, (int)$total, (int)$page, (int)$size, $persist); ?>
    </div>
  </section>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
