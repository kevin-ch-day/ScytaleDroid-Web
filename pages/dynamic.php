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
$topServices = [];
$topSignals = [];
$topDomains = [];
$rows = [];
$total = 0;
$errorMsg = null;

try {
    $overview = runtime_deviation_overview();
    $topServices = runtime_top_services(10);
    $topSignals = runtime_top_signals(10);
    $topDomains = runtime_top_domains(10);
    $pg = runtime_deviation_runs_paged($status, $tier, $q, $page, $size);
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = page_error_message('runtime deviation', $e);
}

$baseUrl = PAGES_URL . '/dynamic.php';
$persist = ['q' => $q, 'status' => $status, 'tier' => $tier, 'size' => $size];
$filtered = array_filter(['q' => $q, 'status' => $status, 'tier' => $tier], fn($v) => $v !== null && $v !== '');

/** Page-local aliases preserve template readability; rendering behavior lives in lib/render.php. */
function fmt_rate($value, int $decimals = 2): string
{
    return runtime_format_number($value, $decimals);
}

function fmt_bool_label($value): string
{
    return runtime_format_bool($value);
}

function fmt_dynamic_page_csv($value): string
{
    return runtime_format_csv($value);
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
        <p class="panel-subtitle">Governed runtime evidence, including Strict Idle, Quiescent FG, raw interactive runs, network features, service context, and risk regimes.</p>
      </div>
    </div>
    <div class="panel-body">
      <div class="metrics-grid">
        <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value"><?= e((string)($overview['dynamic_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Packages</span><span class="metric-value"><?= e((string)($overview['dynamic_packages'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Success / Degraded / Failed</span><span class="metric-value"><?= e((string)($overview['successful_runs'] ?? 0)) ?> / <?= e((string)($overview['degraded_runs'] ?? 0)) ?> / <?= e((string)($overview['failed_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Quota-valid / Supplemental</span><span class="metric-value"><?= e((string)($overview['quota_valid_runs'] ?? 0)) ?> / <?= e((string)($overview['supplemental_valid_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Strict Idle / QFG / Interactive</span><span class="metric-value"><?= e((string)($overview['strict_idle_runs'] ?? 0)) ?> / <?= e((string)($overview['quiescent_fg_runs'] ?? 0)) ?> / <?= e((string)($overview['interactive_raw_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Invalid / Unevaluated historical</span><span class="metric-value"><?= e((string)($overview['invalid_or_skipped_runs'] ?? 0)) ?> / <?= e((string)($overview['unevaluated_historical_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Static Linked / Missing Link</span><span class="metric-value"><?= e((string)($overview['static_linked_runs'] ?? 0)) ?> / <?= e((string)($overview['missing_static_link_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Features Ready / Missing</span><span class="metric-value"><?= e((string)($overview['features_available_runs'] ?? 0)) ?> / <?= e((string)($overview['missing_feature_runs'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Indicators / Issues</span><span class="metric-value"><?= e((string)($overview['indicator_rows'] ?? 0)) ?> / <?= e((string)($overview['issue_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Domain Rows</span><span class="metric-value"><?= e((string)($overview['domain_observation_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Observed / Root Domains</span><span class="metric-value"><?= e((string)($overview['observed_domains'] ?? 0)) ?> / <?= e((string)($overview['root_domains'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Service / Signal Catalog</span><span class="metric-value"><?= e((string)($overview['service_catalog_rows'] ?? 0)) ?> / <?= e((string)($overview['signal_catalog_rows'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Cohorts / Regimes</span><span class="metric-value"><?= e((string)($overview['cohorts'] ?? 0)) ?> / <?= e((string)($overview['risk_regime_rows'] ?? 0)) ?></span></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Fleet Signal Context</h2>
        <p class="panel-subtitle">Interpretation signals mapped from observed services and domains.</p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (empty($topSignals) && !$errorMsg): ?>
        <p class="muted">No mapped signal rows were found in dynamic domain observations.</p>
      <?php elseif (!empty($topSignals)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Signal</th>
                <th>Focus</th>
                <th>Services</th>
                <th>Coverage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topSignals as $row): ?>
                <tr>
                  <td>
                    <strong><?= e((string)($row['display_name'] ?? $row['signal_key'] ?? '')) ?></strong><br>
                    <span class="muted"><?= e((string)($row['signal_key'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e(fmt_dynamic_page_csv($row['focus_area'] ?? '')) ?> · <?= e(fmt_dynamic_page_csv($row['severity_hint'] ?? '')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['signal_family'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e(fmt_dynamic_page_csv($row['service_names_csv'] ?? '')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['service_categories_csv'] ?? '')) ?></span>
                  </td>
                  <td>
                    packages <?= e((string)($row['package_count'] ?? 0)) ?>,
                    runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>,
                    domains <?= e((string)($row['distinct_domains'] ?? 0)) ?><br>
                    <span class="muted">hits <?= e(fmt_rate($row['total_indicator_hits'] ?? 0, 0)) ?> · services <?= e((string)($row['matched_service_count'] ?? 0)) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Top Observed Domains</h2>
        <p class="panel-subtitle">Highest-volume observed domains across persisted dynamic captures.</p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (empty($topDomains) && !$errorMsg): ?>
        <p class="muted">No domain observation rows were found.</p>
      <?php elseif (!empty($topDomains)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Domain</th>
                <th>Class</th>
                <th>Mapped Context</th>
                <th>Coverage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topDomains as $row): ?>
                <tr>
                  <td class="cell-clip">
                    <strong><?= e((string)($row['observed_domain'] ?? '')) ?></strong><br>
                    <span class="muted"><?= e((string)($row['root_domain'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e(fmt_dynamic_page_csv($row['owner_classes_csv'] ?? '')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['role_classes_csv'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e(fmt_dynamic_page_csv($row['service_names_csv'] ?? '')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['signal_names_csv'] ?? '')) ?></span>
                  </td>
                  <td>
                    packages <?= e((string)($row['package_count'] ?? 0)) ?>,
                    runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>,
                    hits <?= e(fmt_rate($row['total_indicator_hits'] ?? 0, 0)) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['indicator_types_csv'] ?? '')) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Fleet Service Context</h2>
        <p class="panel-subtitle">Most frequent mapped services across dynamic DNS/SNI domain observations.</p>
      </div>
    </div>
    <div class="panel-body">
      <?php if (empty($topServices) && !$errorMsg): ?>
        <p class="muted">No mapped service rows were found in dynamic domain observations.</p>
      <?php elseif (!empty($topServices)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Service</th>
                <th>Owner / Category</th>
                <th>Signals</th>
                <th>Coverage</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($topServices as $row): ?>
                <tr>
                  <td>
                    <strong><?= e((string)($row['display_name'] ?? $row['service_key'] ?? '')) ?></strong><br>
                    <span class="muted"><?= e((string)($row['service_key'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e((string)($row['owner_name'] ?? '-')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['owner_class'] ?? '')) ?> · <?= e(fmt_dynamic_page_csv($row['service_category'] ?? '')) ?></span><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['primary_use_case'] ?? '')) ?></span>
                  </td>
                  <td>
                    <?= e(fmt_dynamic_page_csv($row['signal_names_csv'] ?? '')) ?><br>
                    <span class="muted"><?= e(fmt_dynamic_page_csv($row['signal_focus_areas_csv'] ?? '')) ?> · <?= e(fmt_dynamic_page_csv($row['signal_severity_hints_csv'] ?? '')) ?></span>
                  </td>
                  <td>
                    packages <?= e((string)($row['package_count'] ?? 0)) ?>,
                    runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>,
                    domains <?= e((string)($row['distinct_domains'] ?? 0)) ?><br>
                    <span class="muted">hits <?= e(fmt_rate($row['total_indicator_hits'] ?? 0, 0)) ?> · roots <?= e((string)($row['distinct_root_domains'] ?? 0)) ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Run Index</h2>
        <p class="panel-subtitle">Filter by package, app label, run id, run outcome, or tier.</p>
      </div>
    </div>
    <div class="panel-body">
      <form class="form-row" method="get" action="<?= e($baseUrl) ?>">
        <label>
          <span class="metric-label">Search</span>
          <input type="search" name="q" value="<?= e((string)$q) ?>" placeholder="package, app label, or run id">
        </label>
        <label>
          <span class="metric-label">Run outcome</span>
          <select name="status">
            <option value="">Any outcome</option>
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
                <th>Run outcome</th>
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
                    <span class="muted">domains: <?= e((string)($row['distinct_observed_domains'] ?? 0)) ?> observed / <?= e((string)($row['distinct_root_domains'] ?? 0)) ?> root</span><br>
                    <span class="muted">services: <?= e((string)($row['matched_service_count'] ?? 0)) ?>, signals: <?= e((string)($row['matched_signal_count'] ?? 0)) ?></span><br>
                    <?= runtime_feature_state_chip($featureState) ?>
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
                    <span class="muted">quota: <?= e(runtime_quota_state_label((string)($row['quota_state'] ?? ''))) ?> · technical: <?= e(runtime_technical_validity_label((string)($row['technical_validity_state'] ?? ''))) ?></span><br>
                    <?= runtime_static_link_state_chip($staticLinkState) ?>
                    <?= runtime_feature_state_chip($featureState) ?>
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
