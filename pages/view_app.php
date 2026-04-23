<?php
// pages/view_app.php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$activeSessionRow = $context['active_session_row'];
$errorMsg = $context['error'];
$details = decode_assoc_json(is_array($app) ? ($app['details_json'] ?? null) : null);

$PAGE_TITLE = $packageName ? ('App Detail: ' . $packageName) : 'App Detail';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null): ?>
  <section class="section">
    <div class="panel">
      <div class="panel-body">
        <h1>App Detail</h1>
        <p class="muted">Select an application from the directory to inspect overview, findings, permissions, and strings.</p>
      </div>
    </div>
  </section>
<?php else: ?>
  <?php
  $activeTab = 'overview';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'view_app.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <section class="section detail-grid">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Latest Overview</h2>
          <p class="panel-subtitle">Most recent audit and static summary known for this package.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card">
            <span class="metric-label">Grade</span>
            <span class="metric-value"><?= e((string)($app['grade'] ?? '-')) ?></span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Score</span>
            <span class="metric-value"><?= e((string)($app['score_capped'] ?? '—')) ?></span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Findings H/M/L</span>
            <span class="metric-value"><?= e(fmt_hml((int)($app['high'] ?? 0), (int)($app['med'] ?? 0), (int)($app['low'] ?? 0), (int)($app['info'] ?? 0))) ?></span>
          </div>
          <div class="metric-card">
            <span class="metric-label">High-Entropy Strings</span>
            <span class="metric-value"><?= e((string)($app['high_entropy'] ?? '0')) ?></span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Dangerous Perms</span>
            <span class="metric-value"><?= e((string)($app['dangerous_count'] ?? '0')) ?></span>
          </div>
          <div class="metric-card">
            <span class="metric-label">Signature/Vendor</span>
            <span class="metric-value"><?= e((string)($app['signature_count'] ?? '0')) ?> / <?= e((string)($app['vendor_count'] ?? '0')) ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Build Metadata</h2>
          <p class="panel-subtitle">Decoded from the latest static summary payload when available.</p>
        </div>
      </div>
      <div class="panel-body">
        <dl class="detail-kv">
          <div><dt>Latest Static Session</dt><dd><?= e((string)($app['latest_static_session'] ?? '—')) ?></dd></div>
          <div><dt>Latest Audit Session</dt><dd><?= e((string)($app['latest_audit_session'] ?? '—')) ?></dd></div>
          <div><dt>Version Name</dt><dd><?= e((string)($details['version_name'] ?? '—')) ?></dd></div>
          <div><dt>Version Code</dt><dd><?= e((string)($details['version_code'] ?? '—')) ?></dd></div>
          <div><dt>Target SDK</dt><dd><?= e((string)($details['target_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Min SDK</dt><dd><?= e((string)($details['min_sdk'] ?? '—')) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <?php if ($activeSessionRow): ?>
    <section class="section">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Active Session Snapshot</h2>
            <p class="panel-subtitle">Current session selected for deeper review.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="chip-row">
            <?= status_chip((string)($activeSessionRow['run_status'] ?? 'UNKNOWN')) ?>
            <?= chip('Findings ' . fmt_hml((int)($activeSessionRow['high'] ?? 0), (int)($activeSessionRow['med'] ?? 0), (int)($activeSessionRow['low'] ?? 0), (int)($activeSessionRow['info'] ?? 0)), 'info') ?>
            <?= chip('Entropy ' . (string)($activeSessionRow['high_entropy'] ?? '0'), 'medium') ?>
            <?php if (!empty($activeSessionRow['grade'])): ?>
              <?= chip('Audit ' . (string)$activeSessionRow['grade'], 'low') ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($activeSessionRow['non_canonical_reasons'])): ?>
            <p class="muted pre-wrap"><?= e((string)$activeSessionRow['non_canonical_reasons']) ?></p>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
