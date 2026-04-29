<?php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

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
$details = decode_assoc_json(is_array($app) ? ($app['details_json'] ?? null) : null);

$findingSummary = null;
$topFindings = [];
$permissionRows = [];
$stringsSummary = null;
$stringSamples = [];
$fileProviders = [];
$providerAcl = [];
$dynamicSummary = [];
$dynamicRuns = [];

if ($packageName && $activeSession && !$errorMsg && is_array($app)) {
    try {
        $findingSummary = app_findings_summary($packageName, $activeSession);
        $topFindings = app_findings_list($packageName, $activeSession, 8);
        $permissionRows = app_permissions($packageName, $activeSession, 12);
        $stringsSummary = app_strings_summary($packageName, $activeSession);
        $stringSamples = app_string_samples($packageName, $activeSession, 6);
        $fileProviders = app_fileproviders($packageName, $activeSession, 12);
        $providerAcl = app_provider_acl($packageName, $activeSession, 12);
        $dynamicSummary = app_dynamic_summary($packageName);
        $dynamicRuns = app_dynamic_runs($packageName, 5);
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app report failed: ' . $e);
    }
}

$selectedGrade = $activeSessionUsable
    ? ($activeSessionRow['grade'] ?? ($app['grade'] ?? null))
    : ($app['grade'] ?? null);
$selectedScore = $activeSessionUsable
    ? ($activeSessionRow['score_capped'] ?? ($app['score_capped'] ?? null))
    : ($app['score_capped'] ?? null);
$selectedHigh = (int)($findingSummary['high'] ?? ($activeSessionRow['high'] ?? ($app['high'] ?? 0)));
$selectedMed = (int)($findingSummary['med'] ?? ($activeSessionRow['med'] ?? ($app['med'] ?? 0)));
$selectedLow = (int)($findingSummary['low'] ?? ($activeSessionRow['low'] ?? ($app['low'] ?? 0)));
$selectedInfo = (int)($findingSummary['info'] ?? ($activeSessionRow['info'] ?? ($app['info'] ?? 0)));
$selectedDangerous = (int)($activeSessionRow['dangerous_count'] ?? ($app['dangerous_count'] ?? 0));
$selectedHighEntropy = (int)($stringsSummary['high_entropy'] ?? ($activeSessionRow['high_entropy'] ?? ($app['high_entropy'] ?? 0)));
$sessionUsability = strtolower((string)($activeSessionRow['session_usability'] ?? 'unknown'));
$sessionHealth = [
    'status' => (string)($activeSessionRow['run_status'] ?? 'UNKNOWN'),
    'findings_total' => (int)($activeSessionRow['findings_total'] ?? 0),
    'permission_rows' => (int)($activeSessionRow['permission_rows'] ?? 0),
    'string_rows' => (int)($activeSessionRow['string_rows'] ?? 0),
    'audit_rows' => (int)($activeSessionRow['audit_rows'] ?? 0),
    'link_rows' => (int)($activeSessionRow['link_rows'] ?? 0),
];

$componentSummary = [
    'providers' => count($fileProviders),
    'exported_providers' => 0,
    'weak_provider_guards' => 0,
    'acl_rows' => count($providerAcl),
];
foreach ($fileProviders as $row) {
    $exported = (int)($row['exported'] ?? 0) === 1;
    if ($exported) {
        $componentSummary['exported_providers']++;
    }
    $guard = strtolower((string)($row['effective_guard'] ?? ''));
    if ($exported && ($guard === '' || in_array($guard, ['none', 'weak'], true))) {
        $componentSummary['weak_provider_guards']++;
    }
}

$sections = [
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'session-health', 'label' => 'Session Health'],
    ['id' => 'static-risk', 'label' => 'Static Risk'],
    ['id' => 'permissions', 'label' => 'Permissions'],
    ['id' => 'components', 'label' => 'Exported Components'],
    ['id' => 'strings', 'label' => 'Secrets & Strings'],
    ['id' => 'dynamic', 'label' => 'Dynamic Runtime'],
    ['id' => 'sessions', 'label' => 'Sessions'],
];

$PAGE_TITLE = $packageName ? ('App Report: ' . $packageName) : 'App Report';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null || !is_array($app)): ?>
  <?php
  $title = 'App Report';
  $message = $packageName === null
    ? 'Select an app to open a full report with static, permission, string, and dynamic sections.'
    : 'This package is not available in the current app directory.';
  require __DIR__ . '/_partials/app_lookup_empty.php';
  ?>
<?php else: ?>
  <?php
  $activeTab = 'report';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_report.php';
  require __DIR__ . '/_partials/session_picker.php';
  require __DIR__ . '/_partials/report_section_nav.php';
  ?>

  <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
    <div class="alert alert-warning">
      Selected session <?= e((string)$activeSession) ?> is not finalized for report use.
      Showing incomplete session context only. Latest usable completed session: <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>"><?= e((string)$preferredSession) ?></a>.
    </div>
  <?php endif; ?>

  <section class="section" id="overview">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Overview</h2>
          <p class="panel-subtitle">Current posture for the selected app session.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Static Grade</span><span class="metric-value"><?= e((string)($selectedGrade ?? '-')) ?></span></div>
          <div class="metric-card"><span class="metric-label">Composite Score</span><span class="metric-value"><?= e((string)($selectedScore ?? '—')) ?></span></div>
          <div class="metric-card"><span class="metric-label">Findings H/M/L</span><span class="metric-value"><?= e(fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dangerous Permissions</span><span class="metric-value bad"><?= e((string)$selectedDangerous) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">High-Entropy Strings</span><span class="metric-value"><?= e((string)$selectedHighEntropy) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value info"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Data Source Session</span><span class="metric-value metric-value-session"><?= e((string)$activeSession) ?></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section detail-grid" id="session-health">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Session Health</h2>
          <p class="panel-subtitle">Report usability is based on whether findings, permissions, strings, audit rows, and session links are finalized for the selected session.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/run_health.php')) ?>">Open Run Health</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Status</span><span class="metric-value"><?= e($sessionHealth['status']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Findings Rows</span><span class="metric-value"><?= e((string)$sessionHealth['findings_total']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Permission Rows</span><span class="metric-value"><?= e((string)$sessionHealth['permission_rows']) ?></span></div>
          <div class="metric-card"><span class="metric-label">String Rows</span><span class="metric-value"><?= e((string)$sessionHealth['string_rows']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Audit Rows</span><span class="metric-value"><?= e((string)$sessionHealth['audit_rows']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Session Links</span><span class="metric-value"><?= e((string)$sessionHealth['link_rows']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Usability</span><span class="metric-value"><?= strip_tags(session_usability_chip($sessionUsability)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dynamic Data</span><span class="metric-value"><?= e(((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0 ? 'available' : 'none') ?></span></div>
        </div>
        <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
          <div class="alert alert-warning top-gap">
            Selected session <?= e((string)$activeSession) ?> is not finalized for app-report use.
            Findings, permissions, or strings are incomplete.
            <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>">Switch to latest completed session</a>
            or review the fleet status in <a href="<?= e(url('pages/run_health.php')) ?>">Run Health</a>.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Build Metadata</h2>
          <p class="panel-subtitle">Version and SDK context from the selected app summary payload.</p>
        </div>
      </div>
      <div class="panel-body">
        <dl class="detail-kv">
          <div><dt>Version Name</dt><dd><?= e((string)($details['version_name'] ?? '—')) ?></dd></div>
          <div><dt>Version Code</dt><dd><?= e((string)($details['version_code'] ?? '—')) ?></dd></div>
          <div><dt>Target SDK</dt><dd><?= e((string)($details['target_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Min SDK</dt><dd><?= e((string)($details['min_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Latest Static Session</dt><dd><?= e((string)($app['latest_static_session'] ?? '—')) ?></dd></div>
          <div><dt>Latest Audit Session</dt><dd><?= e((string)($app['latest_audit_session'] ?? '—')) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section class="section detail-grid" id="static-risk">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Static Risk</h2>
          <p class="panel-subtitle">Top findings and the latest persisted summary for this session.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_findings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Findings</a>
        </div>
      </div>
      <div class="panel-body">
        <?php if ($findingSummary === null): ?>
          <p class="muted">No findings summary is available for this session.</p>
        <?php else: ?>
          <div class="chip-row">
            <?= chip('High ' . (string)($findingSummary['high'] ?? 0), 'high') ?>
            <?= chip('Medium ' . (string)($findingSummary['med'] ?? 0), 'medium') ?>
            <?= chip('Low ' . (string)($findingSummary['low'] ?? 0), 'low') ?>
            <?= chip('Info ' . (string)($findingSummary['info'] ?? 0), 'info') ?>
          </div>
        <?php endif; ?>
        <div class="detail-stack compact-stack top-gap">
          <?php foreach ($topFindings as $row): ?>
            <?php $tone = strtolower((string)($row['severity'] ?? 'info')); ?>
            <article class="card compact-card">
              <div class="compact-row">
                <div>
                  <div class="app-primary"><?= e((string)($row['title'] ?? 'Untitled finding')) ?></div>
                  <?php if (!empty($row['evidence'])): ?>
                    <div class="table-subline"><?= e(finding_evidence_excerpt((string)$row['evidence'], 180)) ?></div>
                  <?php endif; ?>
                </div>
                <?= chip(strtoupper((string)($row['severity'] ?? 'INFO')), $tone) ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </section>

  <section class="section" id="permissions">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Permissions</h2>
          <p class="panel-subtitle">First-pass permission intelligence for the selected static session.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_permissions.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Permissions</a>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($permissionRows)): ?>
          <p class="muted">
            No permission matrix rows were found for this session.
            <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
              Selected session is not finalized yet.
              <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>">Switch to latest completed</a>.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Permission</th>
                  <th>Protection</th>
                  <th>Source</th>
                  <th>Weight</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($permissionRows as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['permission_name'] ?? '')) ?></td>
                    <td><?= e((string)($row['protection'] ?? '—')) ?></td>
                    <td><?= e((string)($row['source'] ?? '—')) ?></td>
                    <td><?= permission_weight_chip($row['severity'] ?? 0) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section" id="components">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Exported Components</h2>
          <p class="panel-subtitle">Provider exposure is surfaced directly because it is a top recurring static risk.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/components.php') . '?q=' . urlencode($packageName)) ?>">Open Components</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Providers</span><span class="metric-value"><?= e((string)$componentSummary['providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Weak Guards</span><span class="metric-value bad"><?= e((string)$componentSummary['weak_provider_guards']) ?></span></div>
          <div class="metric-card"><span class="metric-label">ACL Rows</span><span class="metric-value"><?= e((string)$componentSummary['acl_rows']) ?></span></div>
        </div>

        <?php if (!empty($fileProviders)): ?>
          <div class="table-responsive top-gap">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Authority</th>
                  <th class="col-center">Exported</th>
                  <th>Guard</th>
                  <th>Risk</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fileProviders as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['provider_name'] ?? $row['component_name'] ?? '')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['authority'] ?? '')) ?></td>
                    <td class="col-center"><?= e(((int)($row['exported'] ?? 0)) === 1 ? 'yes' : 'no') ?></td>
                    <td><?= e((string)($row['effective_guard'] ?? '—')) ?></td>
                    <td><?= e((string)($row['risk'] ?? '—')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section detail-grid" id="strings">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Secrets & Strings</h2>
          <p class="panel-subtitle">Persisted string buckets and representative samples for this session.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_strings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Strings</a>
        </div>
      </div>
      <div class="panel-body">
        <?php if ($stringsSummary === null): ?>
          <p class="muted">
            No string summary is available for this session.
            <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
              Selected session is not finalized yet.
              <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>">Switch to latest completed</a>.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">High Entropy</span><span class="metric-value warn"><?= e((string)($stringsSummary['high_entropy'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Endpoints</span><span class="metric-value"><?= e((string)($stringsSummary['endpoints'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">HTTP Cleartext</span><span class="metric-value bad"><?= e((string)($stringsSummary['http_cleartext'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Cloud Refs</span><span class="metric-value"><?= e((string)($stringsSummary['cloud_refs'] ?? '0')) ?></span></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Selected Samples</h2>
          <p class="panel-subtitle">Representative masked values persisted for the same session.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($stringSamples)): ?>
          <p class="muted">No string samples were found for this session.</p>
        <?php else: ?>
          <div class="detail-stack compact-stack">
            <?php foreach ($stringSamples as $row): ?>
              <article class="card compact-card">
                <div class="compact-row">
                  <div>
                    <div class="app-primary"><?= e((string)($row['bucket'] ?? 'unknown')) ?></div>
                    <div class="table-subline"><?= e((string)($row['value_masked'] ?? '')) ?></div>
                  </div>
                  <?php if (!empty($row['risk_tag'])): ?>
                    <?= chip((string)$row['risk_tag'], 'medium') ?>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section" id="dynamic">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Dynamic Runtime</h2>
          <p class="panel-subtitle">Runtime sessions and evidence availability for this package.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_dynamic.php') . '?pkg=' . urlencode($packageName)) ?>">Open Dynamic</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Valid PCAPs</span><span class="metric-value"><?= e((string)($dynamicSummary['valid_pcaps'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Countable Runs</span><span class="metric-value"><?= e((string)($dynamicSummary['countable_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Runtime Tiers</span><span class="metric-value"><?= e((string)($dynamicSummary['tier_count'] ?? 0)) ?></span></div>
        </div>
        <?php if (!empty($dynamicRuns)): ?>
          <div class="table-responsive top-gap">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Run</th>
                  <th>Status</th>
                  <th>Profile</th>
                  <th>Started</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dynamicRuns as $row): ?>
                  <?php $runId = (string)($row['dynamic_run_id'] ?? ''); ?>
                  <tr>
                    <td><a href="<?= e(url('pages/dynamic_run.php') . '?run=' . urlencode($runId)) ?>"><?= e($runId) ?></a></td>
                    <td><?= status_chip((string)($row['status'] ?? 'UNKNOWN')) ?></td>
                    <td><?= e((string)($row['run_profile'] ?? 'unknown')) ?></td>
                    <td><?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section" id="sessions">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Session History</h2>
          <p class="panel-subtitle">Recent persisted static-analysis sessions for this package.</p>
        </div>
      </div>
      <div class="panel-body">
        <p class="muted">Use the Data Source panel above to switch between completed, in-progress, historical, and failed sessions without changing the rest of the report layout.</p>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
