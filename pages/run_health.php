<?php
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$errorMsg = null;
$quality = [];
$sessions = [];
$filterOptions = ['sessions' => [], 'types' => []];
$sessionFilter = guard_session($_GET['session'] ?? null);
$typeFilter = guard_choice($_GET['type'] ?? null, ['full', 'fast', 'single_app', 'qa', 'smoke', 'debug', 'rerun', 'failed', 'partial', 'session']);
$includeHidden = guard_bool($_GET['include_hidden'] ?? null, false);

try {
    $quality = static_session_quality();
    $sessions = static_session_health(40, $sessionFilter, $typeFilter, $includeHidden);
    $filterOptions = static_session_filter_options(60, true);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] run health failed: ' . $e);
}

$baseUrl = PAGES_URL . '/run_health.php';
$hasActiveFilters = $sessionFilter !== null || $typeFilter !== null || $includeHidden;

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
        <form class="form-row findings-form top-gap" method="get" action="<?= e($baseUrl) ?>">
          <select name="session" aria-label="Session">
            <option value="">All recent sessions</option>
            <?php foreach (($filterOptions['sessions'] ?? []) as $stamp): ?>
              <option value="<?= e((string)$stamp) ?>" <?= $sessionFilter === (string)$stamp ? 'selected' : '' ?>><?= e((string)$stamp) ?></option>
            <?php endforeach; ?>
          </select>

          <select name="type" aria-label="Run type">
            <option value="">All run types</option>
            <?php foreach (($filterOptions['types'] ?? []) as $typeKey => $typeLabel): ?>
              <option value="<?= e((string)$typeKey) ?>" <?= $typeFilter === (string)$typeKey ? 'selected' : '' ?>><?= e((string)$typeLabel) ?></option>
            <?php endforeach; ?>
          </select>

          <label class="inline-check">
            <input type="checkbox" name="include_hidden" value="1" <?= $includeHidden ? 'checked' : '' ?>>
            <span>Include QA / smoke / debug</span>
          </label>

          <button class="btn btn-primary" type="submit">Apply</button>
          <a class="btn-ghost<?= $hasActiveFilters ? '' : ' disabled' ?>" href="<?= e($baseUrl) ?>">Clear</a>
        </form>
        <p class="inline-hint top-gap">
          Run Health is the default explanation surface for missing or partial app pages. Filter by session or run type here first; package-level drilldown still needs a dedicated read-model follow-up.
        </p>
      </div>
    </div>

    <section class="section">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Recent Static Sessions</h2>
            <p class="panel-subtitle">Usability is inferred from whether finalized findings, permission rows, string summaries, audit rows, and session links are present. <?= e((string)count($sessions)) ?> row(s) matched the current filters.</p>
          </div>
        </div>
        <div class="panel-body">
          <?php if (empty($sessions)): ?>
            <div class="alert alert-warning">No static sessions matched the current filters. Try clearing the filters or include hidden QA / smoke / debug sessions.</div>
          <?php else: ?>
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
                    $typeLabel = (string)($row['session_type_label'] ?? session_type_label((string)($row['session_stamp'] ?? ''), null));
                    $typeTone = (string)(session_type_meta((string)($row['session_stamp'] ?? ''), null)['tone'] ?? 'muted');
                    ?>
                    <tr>
                      <td><span class="session-stamp"><?= e((string)($row['session_stamp'] ?? '')) ?></span></td>
                      <td><span title="<?= e($typeHint) ?>"><?= chip($typeLabel, $typeTone) ?></span></td>
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
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
