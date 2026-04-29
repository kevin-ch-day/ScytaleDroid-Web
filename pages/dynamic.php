<?php
// pages/dynamic.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$q = guard_search($_GET['q'] ?? null);
$statusOptions = defined('DYNAMIC_STATUS_OPTIONS') ? DYNAMIC_STATUS_OPTIONS : ['success', 'degraded', 'failed'];
$tierOptions = defined('DYNAMIC_TIER_OPTIONS') ? DYNAMIC_TIER_OPTIONS : ['dataset', 'exploration', 'unknown'];
$status = guard_choice($_GET['status'] ?? null, $statusOptions);
$tier = guard_choice($_GET['tier'] ?? null, $tierOptions);
[$size, $offset, $page] = pager_from_query($_GET);

$overview = [];
$rows = [];
$total = 0;
$errorMsg = null;

try {
    $overview = runtime_deviation_overview();
    $pg = runtime_deviation_runs_paged($status, $tier, $q, $page, $size);
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] runtime deviation failed: ' . $e);
}

$baseUrl = PAGES_URL . '/dynamic.php';
$persist = ['q' => $q, 'status' => $status, 'tier' => $tier, 'size' => $size];
$filtered = array_filter(['q' => $q, 'status' => $status, 'tier' => $tier], fn($v) => $v !== null && $v !== '');

function fmt_rate($value, int $decimals = 2): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format((float)$value, $decimals);
}

function fmt_bool_label($value): string
{
    if ($value === null || $value === '') {
        return 'unknown';
    }
    return ((int)$value) === 1 ? 'yes' : 'no';
}

$PAGE_TITLE = 'Runtime Deviation';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">Runtime Deviation</h1>
        <p class="panel-subtitle">Baseline-relative dynamic behavior from persisted runs, network features, cohorts, and risk regimes.</p>
      </div>
    </div>
    <div class="panel-body">
      <div class="metrics-grid">
        <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value"><?= e((string)($overview['dynamic_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Packages</span><span class="metric-value"><?= e((string)($overview['dynamic_packages'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Success / Degraded / Failed</span><span class="metric-value"><?= e((string)($overview['successful_runs'] ?? 0)) ?> / <?= e((string)($overview['degraded_runs'] ?? 0)) ?> / <?= e((string)($overview['failed_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Feature Rows</span><span class="metric-value"><?= e((string)($overview['feature_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Indicators / Issues</span><span class="metric-value"><?= e((string)($overview['indicator_rows'] ?? 0)) ?> / <?= e((string)($overview['issue_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Cohorts / Regimes</span><span class="metric-value"><?= e((string)($overview['cohorts'] ?? 0)) ?> / <?= e((string)($overview['risk_regime_rows'] ?? 0)) ?></span></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Run Index</h2>
        <p class="panel-subtitle">Filter by package, app label, run id, status, or tier.</p>
      </div>
    </div>
    <div class="panel-body">
      <form class="form-row" method="get" action="<?= e($baseUrl) ?>">
        <label>
          <span class="metric-label">Search</span>
          <input type="search" name="q" value="<?= e((string)$q) ?>" placeholder="package, app label, or run id">
        </label>
        <label>
          <span class="metric-label">Status</span>
          <select name="status">
            <option value="">Any status</option>
            <?php foreach ($statusOptions as $opt): ?>
              <option value="<?= e((string)$opt) ?>" <?= (string)$status === (string)$opt ? 'selected' : '' ?>><?= e(ucfirst((string)$opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span class="metric-label">Tier</span>
          <select name="tier">
            <option value="">Any tier</option>
            <?php foreach ($tierOptions as $opt): ?>
              <option value="<?= e((string)$opt) ?>" <?= (string)$tier === (string)$opt ? 'selected' : '' ?>><?= e(ucfirst((string)$opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span class="metric-label">Page Size</span>
          <select name="size">
            <?php foreach (PAGE_SIZES as $s): ?>
              <option value="<?= (int)$s ?>" <?= (int)$s === (int)$size ? 'selected' : '' ?>><?= (int)$s ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="btn btn-primary" type="submit">Apply</button>
        <a class="btn" href="<?= e($baseUrl) ?>">Reset</a>
      </form>

      <div class="table-caption">
        <span class="title"><?= e((string)$total) ?> runtime run(s)</span>
        <?php if (!empty($filtered)): ?>
          <span class="muted">Filtered view</span>
        <?php endif; ?>
      </div>

      <?php if (empty($rows) && !$errorMsg): ?>
        <p class="muted">No dynamic runs matched the current filters.</p>
      <?php elseif (!empty($rows)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Package</th>
                <th>Status</th>
                <th>Run Profile</th>
                <th>Started</th>
                <th>Network</th>
                <th>Deviation Regime</th>
                <th>Evidence</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
                <?php
                $statusText = (string)($row['status'] ?? 'UNKNOWN');
                $profile = (string)($row['run_profile'] ?? 'unknown') . ' / ' . (string)($row['interaction_level'] ?? 'unknown');
                $network = 'packets ' . fmt_rate($row['packet_count'] ?? null, 0)
                  . ', bytes/s ' . fmt_rate($row['bytes_per_sec'] ?? null, 1);
                $featureState = (string)($row['feature_state'] ?? 'unknown_features');
                $staticLinkState = (string)($row['static_link_state'] ?? 'unknown_static_link');
                $regime = (string)($row['final_regime_if'] ?? '');
                if ($regime === '') {
                    $regime = (string)($row['dynamic_grade_if'] ?? 'not modeled');
                }
                $runId = (string)($row['dynamic_run_id'] ?? '');
                $package = (string)($row['package_name'] ?? '');
                $runUrl = $runId !== '' ? url('pages/dynamic_run.php') . '?run=' . urlencode($runId) : null;
                $packageUrl = $package !== '' ? url('pages/app_dynamic.php') . '?pkg=' . urlencode($package) : null;
                ?>
                <tr>
                  <td>
                    <?php if ($packageUrl): ?>
                      <a class="cell-clip" href="<?= e($packageUrl) ?>"><strong><?= e($package) ?></strong></a><br>
                    <?php else: ?>
                      <strong class="cell-clip"><?= e($package) ?></strong><br>
                    <?php endif; ?>
                    <span class="muted"><?= e((string)($row['app_label'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= status_chip($statusText) ?><br>
                    <span class="muted"><?= e((string)($row['tier'] ?? 'unknown')) ?></span>
                  </td>
                  <td><?= e($profile) ?></td>
                  <td><?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></td>
                  <td>
                    <?= e($network) ?><br>
                    <span class="muted">low signal: <?= e(fmt_bool_label($row['low_signal'] ?? null)) ?>, issues: <?= e((string)($row['issue_count'] ?? 0)) ?></span><br>
                    <?= chip($featureState, $featureState === 'features_available' ? 'info' : 'medium') ?>
                  </td>
                  <td>
                    <?= e($regime) ?><br>
                    <span class="muted">score <?= e(fmt_rate($row['dynamic_score_if'] ?? null, 3)) ?></span>
                  </td>
                  <td>
                    <span class="muted">run</span>
                    <?php if ($runUrl): ?>
                      <a class="cell-clip" href="<?= e($runUrl) ?>"><?= e($runId) ?></a><br>
                    <?php else: ?>
                      <span class="cell-clip"><?= e($runId) ?></span><br>
                    <?php endif; ?>
                    <span class="muted">countable: <?= e(fmt_bool_label($row['countable'] ?? null)) ?></span><br>
                    <?= chip($staticLinkState, $staticLinkState === 'static_linked' ? 'info' : 'high') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php pager_render($baseUrl, $total, $page, $size, $filtered); ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
