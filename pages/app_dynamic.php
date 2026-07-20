<?php
// pages/app_dynamic.php

require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$activeSessionUsable = $context['active_session_usable'];
$activeSessionRow = $context['active_session_row'];
$preferredSession = $context['preferred_session'];
$preferredSessionRow = $context['preferred_session_row'];
$newerIncompleteSessionRow = $context['newer_incomplete_session_row'];
$errorMsg = $context['error'];

$summary = [];
$runs = [];
$domainContext = [];
$serviceSummary = [];
$signalSummary = [];
if ($packageName && !$errorMsg) {
    try {
        $summary = app_dynamic_summary($packageName);
        $runs = app_dynamic_runs($packageName, 80);
        $domainContext = app_dynamic_domain_context($packageName, 120);
        $serviceSummary = app_dynamic_service_summary($packageName, 80);
        $signalSummary = app_dynamic_signal_summary($packageName, 60);
    } catch (Throwable $e) {
        $errorMsg = page_error_message('app dynamic', $e);
    }
}

/** Page-local aliases preserve template readability; rendering behavior lives in lib/render.php. */
function fmt_dynamic_number($value, int $decimals = 1): string
{
    return runtime_format_number($value, $decimals);
}

function fmt_dynamic_bool($value): string
{
    return runtime_format_bool($value);
}

function fmt_dynamic_csv($value): string
{
    return runtime_format_csv($value);
}

function dynamic_baseline_class(array $row): string
{
    return runtime_baseline_class($row);
}

function dynamic_csv_has($value, string $needle): bool
{
    return runtime_csv_has($value, $needle);
}

/**
 * @param array<int,array<string,mixed>> $rows
 */
function dynamic_distinct_count(array $rows, string $field): int
{
    $seen = [];
    foreach ($rows as $row) {
        $value = trim((string)($row[$field] ?? ''));
        if ($value !== '') {
            $seen[strtolower($value)] = true;
        }
    }
    return count($seen);
}

/**
 * @param array<int,array<string,mixed>> $rows
 */
function dynamic_sum_field(array $rows, string $field): int
{
    $sum = 0;
    foreach ($rows as $row) {
        $sum += (int)($row[$field] ?? 0);
    }
    return $sum;
}

$domainCount = count($domainContext);
$rootDomainCount = dynamic_distinct_count($domainContext, 'root_domain');
$firstPartyDomainCount = 0;
$thirdPartyDomainCount = 0;
foreach ($domainContext as $row) {
    if (((int)($row['is_first_party'] ?? 0)) === 1 || runtime_csv_has($row['owner_classes_csv'] ?? '', 'first_party')) {
        $firstPartyDomainCount++;
    }
    if (runtime_csv_has($row['owner_classes_csv'] ?? '', 'third_party')) {
        $thirdPartyDomainCount++;
    }
}
$domainHitCount = dynamic_sum_field($domainContext, 'total_indicator_hits');

$PAGE_TITLE = $packageName ? ('Dynamic: ' . $packageName) : 'App Dynamic';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null || !is_array($app)): ?>
  <?php
  $title = 'App Dynamic';
  $message = $packageName === null
    ? 'Choose an app to inspect runtime deviation runs.'
    : 'This package is not available in the current app directory.';
  require __DIR__ . '/_partials/app_lookup_empty.php';
  ?>
<?php else: ?>
  <?php
  $activeTab = 'dynamic';
  $tabSession = $activeSession;
  $activeSession = null;
  require __DIR__ . '/_partials/app_header.php';
  $activeSession = $tabSession;
  require __DIR__ . '/_partials/tabs_nav.php';
  ?>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Runtime Summary</h2>
          <p class="panel-subtitle">Dynamic behavior captured for this package across persisted sessions.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value"><?= e((string)($summary['dynamic_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Success / Degraded / Failed</span><span class="metric-value"><?= e((string)($summary['successful_runs'] ?? 0)) ?> / <?= e((string)($summary['degraded_runs'] ?? 0)) ?> / <?= e((string)($summary['failed_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Quota-valid / Supplemental</span><span class="metric-value"><?= e((string)($summary['quota_valid_runs'] ?? 0)) ?> / <?= e((string)($summary['supplemental_valid_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">QFG retained</span><span class="metric-value"><?= e((string)($summary['quiescent_fg_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Invalid / Unevaluated historical</span><span class="metric-value"><?= e((string)($summary['invalid_or_skipped_runs'] ?? 0)) ?> / <?= e((string)($summary['unevaluated_historical_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Static Linked / Missing Link</span><span class="metric-value"><?= e((string)($summary['static_linked_runs'] ?? 0)) ?> / <?= e((string)($summary['missing_static_link_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Features Ready / Missing</span><span class="metric-value"><?= e((string)($summary['features_available_runs'] ?? 0)) ?> / <?= e((string)($summary['missing_feature_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Latest Run</span><span class="metric-value"><?= e(fmt_date((string)($summary['latest_started_at'] ?? ''))) ?></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Network Destinations</h2>
          <p class="panel-subtitle">Observed domains, mapped services, and privacy/security signals from dynamic captures.</p>
        </div>
      </div>
      <div class="panel-body detail-stack">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Observed Domains</span><span class="metric-value"><?= e((string)$domainCount) ?></span></div>
          <div class="metric-card"><span class="metric-label">Root Domains</span><span class="metric-value"><?= e((string)$rootDomainCount) ?></span></div>
          <div class="metric-card"><span class="metric-label">First-party / Third-party</span><span class="metric-value"><?= e((string)$firstPartyDomainCount) ?> / <?= e((string)$thirdPartyDomainCount) ?></span></div>
          <div class="metric-card"><span class="metric-label">Mapped Services</span><span class="metric-value"><?= e((string)count($serviceSummary)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Mapped Signals</span><span class="metric-value"><?= e((string)count($signalSummary)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Indicator Hits</span><span class="metric-value"><?= e(fmt_dynamic_number($domainHitCount, 0)) ?></span></div>
        </div>

        <h3>Signal Context</h3>
        <?php if (empty($signalSummary)): ?>
          <p class="muted">No mapped signal rows were found for this package.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Signal</th>
                  <th>Focus</th>
                  <th>Services</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($signalSummary as $row): ?>
                  <tr>
                    <td>
                      <strong><?= e((string)($row['display_name'] ?? $row['signal_key'] ?? '')) ?></strong><br>
                      <span class="muted"><?= e((string)($row['signal_key'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['focus_area'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['severity_hint'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['signal_family'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['service_names_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['service_categories_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      services <?= e((string)($row['matched_service_count'] ?? 0)) ?>,
                      domains <?= e((string)($row['distinct_domains'] ?? 0)) ?>,
                      runs <?= e((string)($row['observed_run_count'] ?? 0)) ?><br>
                      <span class="muted">hits <?= e(fmt_dynamic_number($row['total_indicator_hits'] ?? 0, 0)) ?> · roots <?= e((string)($row['distinct_root_domains'] ?? 0)) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <h3>Service Context</h3>
        <?php if (empty($serviceSummary)): ?>
          <p class="muted">No mapped service rows were found for this package.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Service</th>
                  <th>Owner / Category</th>
                  <th>Signals</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($serviceSummary as $row): ?>
                  <tr>
                    <td>
                      <strong><?= e((string)($row['display_name'] ?? $row['service_key'] ?? '')) ?></strong><br>
                      <span class="muted"><?= e((string)($row['service_key'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e((string)($row['owner_name'] ?? '-')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['owner_class'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['service_category'] ?? '')) ?></span><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['primary_use_case'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['signal_names_csv'] ?? $row['signal_keys_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['signal_focus_areas_csv'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['signal_severity_hints_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      domains <?= e((string)($row['distinct_domains'] ?? 0)) ?>,
                      runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>,
                      hits <?= e(fmt_dynamic_number($row['total_indicator_hits'] ?? 0, 0)) ?><br>
                      <span class="muted">roles: <?= e(fmt_dynamic_csv($row['role_classes_csv'] ?? '')) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <h3>Domain Context</h3>
        <?php if (empty($domainContext)): ?>
          <p class="muted">No domain observation rows were found for this package.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Domain</th>
                  <th>Class</th>
                  <th>Service</th>
                  <th>Signals</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($domainContext as $row): ?>
                  <tr>
                    <td class="cell-clip">
                      <strong><?= e((string)($row['observed_domain'] ?? '')) ?></strong><br>
                      <span class="muted"><?= e((string)($row['root_domain'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['owner_classes_csv'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['role_classes_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['confidence_csv'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['classification_basis_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['service_names_csv'] ?? $row['service_keys_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['service_categories_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_dynamic_csv($row['signal_names_csv'] ?? $row['signal_keys_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['signal_focus_areas_csv'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['signal_severity_hints_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>,
                      hits <?= e(fmt_dynamic_number($row['total_indicator_hits'] ?? 0, 0)) ?><br>
                      <span class="muted"><?= e(fmt_dynamic_csv($row['indicator_types_csv'] ?? '')) ?> · <?= e(fmt_dynamic_csv($row['indicator_sources_csv'] ?? '')) ?></span>
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
          <h2 class="panel-title">Dynamic Runs</h2>
          <p class="panel-subtitle">Captured runtime sessions with network features and evidence status.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($runs)): ?>
          <p class="muted">No dynamic runs were found for this package.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Run</th>
                  <th>Status</th>
                  <th>Profile</th>
                  <th>Baseline class</th>
                  <th>Started</th>
                  <th>Network</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($runs as $row): ?>
                  <?php
                  $runId = (string)($row['dynamic_run_id'] ?? '');
                  $runUrl = $runId ? url('pages/dynamic_run.php') . '?run=' . urlencode($runId) : null;
                  $profile = (string)($row['run_profile'] ?? 'unknown') . ' / ' . (string)($row['interaction_level'] ?? 'unknown');
                  ?>
                  <tr>
                    <td class="cell-clip">
                      <?php if ($runUrl): ?>
                        <a href="<?= e($runUrl) ?>"><?= e($runId) ?></a>
                      <?php else: ?>
                        <?= e($runId) ?>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?= status_chip((string)($row['status'] ?? 'UNKNOWN')) ?><br>
                      <span class="muted"><?= e((string)($row['tier'] ?? 'unknown')) ?></span>
                    </td>
                    <td><?= e($profile) ?></td>
                    <td><?= e(dynamic_baseline_class($row)) ?></td>
                    <td><?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></td>
                    <td>
                      packets <?= e(fmt_dynamic_number($row['packet_count'] ?? null, 0)) ?>,
                      bytes/s <?= e(fmt_dynamic_number($row['bytes_per_sec'] ?? null, 1)) ?><br>
                      <span class="muted">low signal: <?= e(fmt_dynamic_bool($row['low_signal'] ?? null)) ?>, issues: <?= e((string)($row['issue_count'] ?? 0)) ?></span>
                    </td>
                    <td>
                      <span class="muted">pcap valid: <?= e(fmt_dynamic_bool($row['pcap_valid'] ?? null)) ?> · quota: <?= e(runtime_quota_state_label((string)($row['quota_state'] ?? ''))) ?></span><br>
                      <span class="muted">technical: <?= e(runtime_technical_validity_label((string)($row['technical_validity_state'] ?? ''))) ?></span><br>
                      <?= runtime_static_link_state_chip((string)($row['static_link_state'] ?? 'unknown_static_link')) ?>
                      <?= runtime_feature_state_chip((string)($row['feature_state'] ?? 'unknown_features')) ?>
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
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
