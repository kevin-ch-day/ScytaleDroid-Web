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
if ($packageName && !$errorMsg) {
    try {
        $summary = app_dynamic_summary($packageName);
        $runs = app_dynamic_runs($packageName, 80);
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app dynamic failed: ' . $e);
    }
}

function fmt_dynamic_number($value, int $decimals = 1): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format((float)$value, $decimals);
}

function fmt_dynamic_bool($value): string
{
    if ($value === null || $value === '') {
        return 'unknown';
    }
    return ((int)$value) === 1 ? 'yes' : 'no';
}

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
          <div class="metric-card"><span class="metric-label">Valid PCAPs</span><span class="metric-value"><?= e((string)($summary['valid_pcaps'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Countable Runs</span><span class="metric-value"><?= e((string)($summary['countable_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Runtime Tiers</span><span class="metric-value"><?= e((string)($summary['tier_count'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Latest Run</span><span class="metric-value"><?= e(fmt_date((string)($summary['latest_started_at'] ?? ''))) ?></span></div>
        </div>
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
                    <td><?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></td>
                    <td>
                      packets <?= e(fmt_dynamic_number($row['packet_count'] ?? null, 0)) ?>,
                      bytes/s <?= e(fmt_dynamic_number($row['bytes_per_sec'] ?? null, 1)) ?><br>
                      <span class="muted">low signal: <?= e(fmt_dynamic_bool($row['low_signal'] ?? null)) ?>, issues: <?= e((string)($row['issue_count'] ?? 0)) ?></span>
                    </td>
                    <td>
                      <span class="muted">pcap valid: <?= e(fmt_dynamic_bool($row['pcap_valid'] ?? null)) ?></span><br>
                      <span class="muted">countable: <?= e(fmt_dynamic_bool($row['countable'] ?? null)) ?></span>
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
