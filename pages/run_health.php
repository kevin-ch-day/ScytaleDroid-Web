<?php
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$errorMsg = null;
$quality = [];
$sessions = [];

try {
    $quality = static_session_quality();
    $sessions = static_session_health(20);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] run health failed: ' . $e);
}

$PAGE_TITLE = 'Run Health';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php else: ?>
    <div class="panel">
      <div class="panel-header">
        <div>
          <h1 class="panel-title">Run Health</h1>
          <p class="panel-subtitle">Static session data quality across findings, permissions, strings, audit rows, and session-link finalization.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Static Sessions</span><span class="metric-value"><?= e((string)($quality['session_count'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Static Runs</span><span class="metric-value"><?= e((string)($quality['total_static_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Completed</span><span class="metric-value info"><?= e((string)($quality['completed_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">In Progress</span><span class="metric-value warn"><?= e((string)($quality['in_progress_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Failed / Aborted</span><span class="metric-value bad"><?= e((string)($quality['failed_runs'] ?? 0)) ?></span></div>
        </div>
      </div>
    </div>

    <section class="section">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Recent Static Sessions</h2>
            <p class="panel-subtitle">Usability is inferred from whether finalized findings, permission rows, string summaries, audit rows, and session links are present.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover table-sticky">
              <thead>
                <tr>
                  <th>Session</th>
                  <th>Type</th>
                  <th class="col-center">Status</th>
                  <th class="col-num">Apps</th>
                  <th class="col-num">Findings</th>
                  <th class="col-num">Permissions</th>
                  <th class="col-num">Strings</th>
                  <th class="col-num">Audit</th>
                  <th class="col-num">Links</th>
                  <th>Usability</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sessions as $row): ?>
                  <?php
                  $appRuns = (int)($row['app_runs'] ?? 0);
                  $findingsReady = (int)($row['findings_ready'] ?? 0);
                  $permissionsReady = (int)($row['permissions_ready'] ?? 0);
                  $stringsReady = (int)($row['strings_ready'] ?? 0);
                  $auditReady = (int)($row['audit_ready'] ?? 0);
                  $linkReady = (int)($row['link_ready'] ?? 0);
                  $state = (string)($row['session_usability'] ?? 'unknown');
                  $stateHint = session_usability_hint($state);
                  $stateSummary = session_usability_summary_text($state);
                  $typeHint = session_type_hint((string)($row['session_stamp'] ?? ''), null);
                  ?>
                  <tr>
                    <td><span class="session-stamp"><?= e((string)($row['session_stamp'] ?? '')) ?></span></td>
                    <td><span title="<?= e($typeHint) ?>"><?= session_type_chip((string)($row['session_stamp'] ?? ''), null) ?></span></td>
                    <td class="col-center"><?= status_chip((string)($row['status'] ?? 'UNKNOWN')) ?></td>
                    <td class="col-num"><?= e((string)$appRuns) ?></td>
                    <td class="col-num"><?= e((string)$findingsReady) ?></td>
                    <td class="col-num"><?= e((string)$permissionsReady) ?></td>
                    <td class="col-num"><?= e((string)$stringsReady) ?></td>
                    <td class="col-num"><?= e((string)$auditReady) ?></td>
                    <td class="col-num"><?= e((string)$linkReady) ?></td>
                    <td>
                      <div class="meta-stack">
                        <span title="<?= e($stateHint) ?>"><?= session_usability_chip($state) ?></span>
                        <span class="table-subline muted"><?= e($stateSummary) ?></span>
                      </div>
                    </td>
                    <td class="nowrap"><?= e(fmt_date((string)($row['created_at'] ?? ''))) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
