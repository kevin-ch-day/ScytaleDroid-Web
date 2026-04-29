<?php
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$exportedAllowed = ['1', '0'];
$guardAllowed = ['none', 'weak', 'normal', 'dangerous', 'signature', 'privileged', 'custom', 'unknown'];

$q = guard_search($_GET['q'] ?? null);
$exported = guard_choice($_GET['exported'] ?? null, $exportedAllowed);
$guard = guard_choice($_GET['guard'] ?? null, $guardAllowed);
[$size, $offset, $page] = pager_from_query($_GET);

$overview = [];
$rows = [];
$total = 0;
$errorMsg = null;

try {
    $overview = component_exposure_overview();
    $pg = component_exposure_paged($exported, $guard, $q, $page, $size);
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] components failed: ' . $e);
}

$baseUrl = PAGES_URL . '/components.php';
$persist = [
    'q' => $q,
    'exported' => $exported,
    'guard' => $guard,
    'size' => $size,
];
$hasActiveFilters = $q !== null || $exported !== null || $guard !== null;

$PAGE_TITLE = 'Component Exposure';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">Component Exposure</h1>
        <p class="panel-subtitle">Fleet-level view of exported providers and guard weaknesses from the latest usable static surfaces.</p>
      </div>
      <div class="panel-actions">
        <button type="button" class="panel-toggle" data-action="toggle-panel" aria-expanded="true">Collapse</button>
      </div>
    </div>
    <div class="panel-body">
      <div class="metrics-grid">
        <div class="metric-card"><span class="metric-label">Provider Rows</span><span class="metric-value"><?= e((string)($overview['provider_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Exported Rows</span><span class="metric-value warn"><?= e((string)($overview['exported_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Weak Guards</span><span class="metric-value bad"><?= e((string)($overview['weak_guard_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Affected Apps</span><span class="metric-value info"><?= e((string)($overview['affected_apps'] ?? 0)) ?></span></div>
      </div>

      <form class="form-row findings-form top-gap" method="get" action="<?= e($baseUrl) ?>">
        <input type="search" name="q" placeholder="Search app, package, provider, or authority" value="<?= e($q ?? '') ?>" autocomplete="off">

        <select name="exported" aria-label="Exported">
          <option value="">All exposure</option>
          <option value="1" <?= $exported === '1' ? 'selected' : '' ?>>Exported only</option>
          <option value="0" <?= $exported === '0' ? 'selected' : '' ?>>Not exported</option>
        </select>

        <select name="guard" aria-label="Guard strength">
          <option value="">All guards</option>
          <?php foreach ($guardAllowed as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $guard === $opt ? 'selected' : '' ?>><?= e(ucfirst($opt)) ?></option>
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
        <h2 class="panel-title">Latest Component Rows</h2>
        <p class="panel-subtitle"><?= e((string)$total) ?> row(s) matched the current filters.</p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (!$rows): ?>
        <p class="muted">No component rows matched the current filters.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sticky">
            <thead>
              <tr>
                <th>App</th>
                <th>Provider</th>
                <th>Authority</th>
                <th class="col-center">Exported</th>
                <th>Guard</th>
                <th>Risk</th>
                <th>Session</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                $pkg = (string)($row['package_name'] ?? '');
                $session = (string)($row['session_stamp'] ?? '');
                $viewUrl = $pkg !== ''
                  ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) . '&session=' . urlencode($session)
                  : null;
                $exportedLabel = ((int)($row['exported'] ?? 0)) === 1 ? 'yes' : 'no';
                $guardTone = in_array(strtolower((string)($row['effective_guard'] ?? '')), ['', 'none', 'weak'], true) ? 'high' : 'low';
                $riskTone = strtolower((string)($row['risk'] ?? ''));
                $riskTone = in_array($riskTone, ['high', 'medium', 'low', 'info'], true) ? $riskTone : 'muted';
                ?>
                <tr>
                  <td class="cell-clip">
                    <?php if ($viewUrl): ?>
                      <a href="<?= e($viewUrl) ?>"><?= e((string)($row['app_label'] ?? $pkg)) ?></a>
                    <?php else: ?>
                      <?= e((string)($row['app_label'] ?? $pkg)) ?>
                    <?php endif; ?>
                    <div class="table-subline"><?= e($pkg) ?></div>
                  </td>
                  <td class="cell-clip"><?= e((string)($row['provider_name'] ?? $row['component_name'] ?? '')) ?></td>
                  <td class="cell-clip"><?= e((string)($row['authority'] ?? '')) ?></td>
                  <td class="col-center"><?= chip($exportedLabel, $exportedLabel === 'yes' ? 'medium' : 'muted') ?></td>
                  <td><?= chip((string)($row['effective_guard'] ?? 'unknown'), $guardTone) ?></td>
                  <td><?= chip((string)($row['risk'] ?? 'unknown'), $riskTone) ?></td>
                  <td><span class="session-stamp"><?= e($session) ?></span></td>
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
