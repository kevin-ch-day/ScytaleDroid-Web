<?php
// pages/app_findings.php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$errorMsg = $context['error'];

$summary = null;
$findings = [];
if ($packageName && $activeSession && !$errorMsg) {
    try {
        $summary = app_findings_summary($packageName, $activeSession);
        $findings = app_findings_list($packageName, $activeSession, 150);
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app findings failed: ' . $e);
    }
}

$PAGE_TITLE = $packageName ? ('Findings: ' . $packageName) : 'App Findings';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null): ?>
  <section class="section"><div class="panel"><div class="panel-body"><p class="muted">Choose an app to inspect static findings.</p></div></div></section>
<?php else: ?>
  <?php
  $activeTab = 'findings';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_findings.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Finding Summary</h2>
          <p class="panel-subtitle">Severity totals for the selected session.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if ($summary === null): ?>
          <p class="muted">No findings summary is available for this package/session.</p>
        <?php else: ?>
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">High</span><span class="metric-value bad"><?= e((string)($summary['high'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Medium</span><span class="metric-value warn"><?= e((string)($summary['med'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Low</span><span class="metric-value info"><?= e((string)($summary['low'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Info</span><span class="metric-value"><?= e((string)($summary['info'] ?? '0')) ?></span></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Findings</h2>
          <p class="panel-subtitle">Top persisted findings ordered by severity.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($findings)): ?>
          <p class="muted">No finding rows were found for this session.</p>
        <?php else: ?>
          <div class="detail-stack">
            <?php foreach ($findings as $row): ?>
              <?php
              $severity = strtolower((string)($row['severity'] ?? 'info'));
              $tone = in_array($severity, ['critical', 'high', 'medium', 'low', 'info'], true) ? $severity : 'muted';
              ?>
              <article class="card">
                <div class="card-header">
                  <div class="chip-row">
                    <?= chip(strtoupper((string)($row['severity'] ?? 'INFO')), $tone) ?>
                    <span><?= e((string)($row['title'] ?? 'Untitled finding')) ?></span>
                  </div>
                </div>
                <?php if (!empty($row['evidence'])): ?>
                  <p class="pre-wrap muted"><?= e((string)$row['evidence']) ?></p>
                <?php endif; ?>
                <?php if (!empty($row['fix'])): ?>
                  <p><strong>Fix:</strong> <?= e((string)$row['fix']) ?></p>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
