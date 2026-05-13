<?php
// pages/app_findings.php
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
$runHealthUrl = url('pages/run_health.php');

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
<?php elseif ($packageName === null || !is_array($app)): ?>
  <?php
  $title = 'App Findings';
  $message = $packageName === null
    ? 'Choose an app to inspect static findings.'
    : 'This package is not available in the current app directory.';
  require __DIR__ . '/_partials/app_lookup_empty.php';
  ?>
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
          <?php if (!$activeSessionUsable && $preferredSession): ?>
            <p class="muted">No findings summary is available because the selected session is not finalized. Latest usable completed session: <a href="<?= e(url('pages/app_findings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode($preferredSession)) ?>"><?= e($preferredSession) ?></a>. Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> if you need to confirm why this session is incomplete.</p>
          <?php else: ?>
            <p class="muted">No findings summary is available for this package/session. Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether rows are missing or the selected session is partial.</p>
          <?php endif; ?>
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
          <p class="muted">No finding rows were found for this session. Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether the selected session is incomplete or missing finalized findings rows.</p>
        <?php else: ?>
          <div class="detail-stack">
            <?php foreach ($findings as $row): ?>
              <?php
              $severity = strtolower((string)($row['severity'] ?? 'info'));
              $tone = in_array($severity, ['critical', 'high', 'medium', 'low', 'info'], true) ? $severity : 'muted';
              $severityRaw = strtolower((string)($row['severity_raw'] ?? ''));
              $showRawSeverity = $severityRaw !== '' && $severityRaw !== $severity;
              $evidenceExcerpt = finding_evidence_excerpt($row['evidence'] ?? null, 320);
              ?>
              <article class="card">
                <div class="card-header">
                  <div class="chip-row">
                    <?= chip(strtoupper((string)($row['severity'] ?? 'INFO')), $tone) ?>
                    <?php if ($showRawSeverity): ?>
                      <?= chip('Raw: ' . strtoupper((string)($row['severity_raw'] ?? '')), 'muted') ?>
                    <?php endif; ?>
                    <span><?= e((string)($row['title'] ?? 'Untitled finding')) ?></span>
                  </div>
                </div>
                <?php if ($evidenceExcerpt !== ''): ?>
                  <p class="pre-wrap muted"><?= e($evidenceExcerpt) ?></p>
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
