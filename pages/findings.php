<?php
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$severityAllowed = ['critical', 'high', 'medium', 'low', 'info'];
$severity = guard_choice($_GET['severity'] ?? null, $severityAllowed);
$q = guard_search($_GET['q'] ?? null);

$categoryOptions = [];
$masvsOptions = [];
$category = null;
$masvsArea = null;
$rows = [];
$total = 0;
$errorMsg = null;
[$size, $offset, $page] = pager_from_query($_GET);

try {
    $categoryOptions = findings_categories();
    $masvsOptions = findings_masvs_areas();
    $category = guard_choice($_GET['category'] ?? null, $categoryOptions);
    $masvsArea = guard_choice($_GET['masvs_area'] ?? null, $masvsOptions);

    $pg = findings_explorer_paged($severity, $category, $masvsArea, $q, $page, $size);
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] findings explorer failed: ' . $e);
}

$baseUrl = PAGES_URL . '/findings.php';
$persist = [
    'severity' => $severity,
    'category' => $category,
    'masvs_area' => $masvsArea,
    'q' => $q,
    'size' => $size,
];
$hasActiveFilters = $severity !== null || $category !== null || $masvsArea !== null || $q !== null;

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
        <p class="panel-subtitle">Search the latest persisted static findings across the current fleet.</p>
      </div>
      <div class="panel-actions">
        <button type="button" class="panel-toggle" data-action="toggle-panel" aria-expanded="true">Collapse</button>
      </div>
    </div>
    <div class="panel-body">
      <form class="form-row findings-form" method="get" action="<?= e($baseUrl) ?>">
        <input type="search" name="q" placeholder="Search app, package, or finding title" value="<?= e($q ?? '') ?>" autocomplete="off">

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

        <select name="size" aria-label="Page size">
          <?php foreach ((defined('PAGE_SIZES') ? PAGE_SIZES : [25, 50, 100]) as $opt): ?>
            <option value="<?= (int)$opt ?>" <?= (int)$opt === (int)$size ? 'selected' : '' ?>><?= (int)$opt ?>/page</option>
          <?php endforeach; ?>
        </select>

        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn-ghost<?= $hasActiveFilters ? '' : ' disabled' ?>" href="<?= e($baseUrl) ?>">Clear</a>
      </form>
    </div>
  </section>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Latest Findings</h2>
        <p class="panel-subtitle"><?= e((string)$total) ?> finding row(s) matched the current filters.</p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (!$rows): ?>
        <p class="muted">No findings matched the current filters.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sticky">
            <thead>
              <tr>
                <th>Finding</th>
                <th>App</th>
                <th class="col-center">Severity</th>
                <th>Category</th>
                <th>MASVS</th>
                <th>Session</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
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
