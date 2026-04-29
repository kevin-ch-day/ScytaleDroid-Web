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
$sessionUsabilitySummary = session_usability_summary_text($sessionUsability);
$sessionUsabilityHint = session_usability_hint($sessionUsability);
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

$sessionType = session_type_label((string)$activeSession, (string)($activeSessionRow['profile'] ?? ''));

$riskBand = null;
foreach ($topFindings as $row) {
    $title = (string)($row['title'] ?? '');
    if (preg_match('/^Composite risk\s+[—-]\s+(.+)$/iu', $title, $matches)) {
        $riskBand = trim((string)($matches[1] ?? ''));
        break;
    }
}

$permissionSummary = [
    'dangerous' => 0,
    'signature_privileged' => 0,
    'special_access' => 0,
    'custom_defined' => 0,
];
$permissionHighlights = [];
foreach ($permissionRows as $row) {
    if ((int)($row['is_runtime_dangerous'] ?? 0) === 1) {
        $permissionSummary['dangerous']++;
    }
    if ((int)($row['is_signature'] ?? 0) === 1 || (int)($row['is_privileged'] ?? 0) === 1) {
        $permissionSummary['signature_privileged']++;
    }
    if ((int)($row['is_special_access'] ?? 0) === 1) {
        $permissionSummary['special_access']++;
    }
    if ((int)($row['is_custom'] ?? 0) === 1) {
        $permissionSummary['custom_defined']++;
    }
    if (count($permissionHighlights) < 4) {
        $permissionHighlights[] = [
            'name' => (string)($row['permission_name'] ?? ''),
            'protection' => (string)($row['protection'] ?? '—'),
            'source' => (string)($row['source'] ?? '—'),
            'weight' => (int)($row['severity'] ?? 0),
        ];
    }
}

$providerHighlights = [];
foreach ($fileProviders as $row) {
    $exported = (int)($row['exported'] ?? 0) === 1;
    $guard = strtolower((string)($row['effective_guard'] ?? ''));
    if ($exported && ($guard === '' || in_array($guard, ['none', 'weak'], true))) {
        $providerHighlights[] = [
            'provider_name' => (string)($row['provider_name'] ?? $row['component_name'] ?? ''),
            'authority' => (string)($row['authority'] ?? ''),
            'guard' => (string)($row['effective_guard'] ?? '—'),
        ];
    }
    if (count($providerHighlights) >= 3) {
        break;
    }
}

$stringHighlights = [];
foreach ($stringSamples as $row) {
    if (count($stringHighlights) >= 3) {
        break;
    }
    $stringHighlights[] = [
        'bucket' => (string)($row['bucket'] ?? 'unknown'),
        'value' => (string)($row['value_masked'] ?? ''),
        'risk_tag' => (string)($row['risk_tag'] ?? ''),
    ];
}

$topRiskPatterns = [];
if ($riskBand) {
    $topRiskPatterns[] = [
        'title' => 'Composite risk posture',
        'summary' => 'Risk band: ' . $riskBand,
        'tone' => 'high',
    ];
}
if ($componentSummary['weak_provider_guards'] > 0) {
    $topRiskPatterns[] = [
        'title' => 'Exported providers with weak guards',
        'summary' => $componentSummary['weak_provider_guards'] . ' provider surfaces need stronger ACLs or permissions.',
        'tone' => 'high',
    ];
}
if ($selectedDangerous > 0) {
    $topRiskPatterns[] = [
        'title' => 'Sensitive permission exposure',
        'summary' => $selectedDangerous . ' dangerous permissions are requested in the selected session.',
        'tone' => 'medium',
    ];
}
if ($selectedHighEntropy > 0) {
    $topRiskPatterns[] = [
        'title' => 'High-entropy string exposure',
        'summary' => $selectedHighEntropy . ' high-entropy string indicators were persisted for review.',
        'tone' => 'medium',
    ];
}
foreach ($topFindings as $row) {
    $title = (string)($row['title'] ?? '');
    if ($title === '' || str_starts_with($title, 'Composite risk')) {
        continue;
    }
    $topRiskPatterns[] = [
        'title' => $title,
        'summary' => finding_evidence_excerpt((string)($row['evidence'] ?? ''), 140),
        'tone' => strtolower((string)($row['severity'] ?? 'info')),
    ];
    if (count($topRiskPatterns) >= 5) {
        break;
    }
}
$topRiskPatterns = array_slice($topRiskPatterns, 0, 5);

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
          <p class="panel-subtitle">Summary posture for the selected app session. Use the app tabs for full findings, permissions, strings, and dynamic detail.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Static Grade</span><span class="metric-value"><?= e((string)($selectedGrade ?? '-')) ?></span></div>
          <div class="metric-card"><span class="metric-label">Normalized Score</span><span class="metric-value"><?= e((string)($selectedScore ?? '—')) ?></span></div>
          <div class="metric-card"><span class="metric-label">Findings H/M/L/I</span><span class="metric-value"><?= e(fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dangerous Permissions</span><span class="metric-value bad"><?= e((string)$selectedDangerous) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">High-Entropy Strings</span><span class="metric-value"><?= e((string)$selectedHighEntropy) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value info"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?></span></div>
          <div class="metric-card">
            <span class="metric-label">Data Source Session</span>
            <span class="metric-value metric-value-session"><?= e((string)$activeSession) ?></span>
            <p class="muted"><?= e($sessionUsabilitySummary) ?></p>
          </div>
        </div>
        <div class="chip-row top-gap">
          <?= chip('Session type: ' . $sessionType, 'muted') ?>
          <?= chip('Findings ' . ($sessionHealth['findings_total'] > 0 ? 'present' : 'missing'), $sessionHealth['findings_total'] > 0 ? 'info' : 'medium') ?>
          <?= chip('Permissions ' . ($sessionHealth['permission_rows'] > 0 ? 'present' : 'missing'), $sessionHealth['permission_rows'] > 0 ? 'info' : 'medium') ?>
          <?= chip('Strings ' . ($sessionHealth['string_rows'] > 0 ? 'present' : 'missing'), $sessionHealth['string_rows'] > 0 ? 'info' : 'medium') ?>
          <?= chip('Components ' . ($componentSummary['providers'] > 0 ? 'present' : 'missing'), $componentSummary['providers'] > 0 ? 'info' : 'medium') ?>
          <?= chip(((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0 ? 'Dynamic available' : 'Dynamic missing', ((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0 ? 'info' : 'muted') ?>
          <?php if (((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0): ?>
            <?= chip('Dynamic match: package-level', 'medium') ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="section detail-grid" id="session-health">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Data Completeness</h2>
          <p class="panel-subtitle">Quick trust view for the selected session. Full diagnostics live on Run Health.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/run_health.php')) ?>">Open Run Health</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="coverage-grid">
          <div class="coverage-item">
            <?= chip($sessionHealth['findings_total'] > 0 ? 'Findings present' : 'Findings missing', $sessionHealth['findings_total'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['findings_total']) ?> persisted rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['permission_rows'] > 0 ? 'Permissions present' : 'Permissions missing', $sessionHealth['permission_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['permission_rows']) ?> matrix rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['string_rows'] > 0 ? 'Strings present' : 'Strings missing', $sessionHealth['string_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['string_rows']) ?> summary rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($componentSummary['providers'] > 0 ? 'Components present' : 'Components missing', $componentSummary['providers'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$componentSummary['providers']) ?> provider rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['audit_rows'] > 0 ? 'Evidence present' : 'Evidence incomplete', $sessionHealth['audit_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['audit_rows']) ?> audit rows</p>
          </div>
          <div class="coverage-item">
            <span title="<?= e($sessionUsabilityHint) ?>"><?= session_usability_chip($sessionUsability) ?></span>
            <p class="table-subline"><?= e($sessionUsabilitySummary) ?></p>
          </div>
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
          <p class="panel-subtitle">Version, SDK, and session metadata for this summary source.</p>
        </div>
      </div>
      <div class="panel-body">
        <dl class="detail-kv">
          <div><dt>Version Name</dt><dd><?= e((string)($details['version_name'] ?? '—')) ?></dd></div>
          <div><dt>Version Code</dt><dd><?= e((string)($details['version_code'] ?? '—')) ?></dd></div>
          <div><dt>Target SDK</dt><dd><?= e((string)($details['target_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Min SDK</dt><dd><?= e((string)($details['min_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Session Type</dt><dd><?= e($sessionType) ?></dd></div>
          <div><dt>Selected Static Session</dt><dd><?= e((string)$activeSession) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section class="section" id="explore">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Explore Details</h2>
          <p class="panel-subtitle">Use the dedicated app pages for the full evidence behind this summary.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="explore-grid">
          <article class="explore-card">
            <div class="app-primary">Findings</div>
            <p class="table-subline"><?= e(fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)) ?> across the selected session.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_findings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Findings</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Components</div>
            <p class="table-subline"><?= e((string)$componentSummary['weak_provider_guards']) ?> weak-guard provider exposures out of <?= e((string)$componentSummary['exported_providers']) ?> exported providers.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_components.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Components</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Permissions</div>
            <p class="table-subline"><?= e((string)$permissionSummary['dangerous']) ?> dangerous and <?= e((string)$permissionSummary['signature_privileged']) ?> signature/privileged permissions.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_permissions.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Permissions</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Strings</div>
            <p class="table-subline"><?= e((string)$selectedHighEntropy) ?> high-entropy indicators and <?= e((string)($stringsSummary['endpoints'] ?? 0)) ?> endpoints.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_strings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Strings</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Dynamic Runtime</div>
            <p class="table-subline"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?> runs available. Match level: package-level.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_dynamic.php') . '?pkg=' . urlencode($packageName)) ?>">Open Dynamic</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Run Health</div>
            <p class="table-subline"><?= e($sessionHealth['status']) ?> · <?= e($sessionUsabilitySummary) ?> for <?= e((string)$activeSession) ?>.</p>
            <a class="btn-ghost" href="<?= e(url('pages/run_health.php')) ?>">Open Run Health</a>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="section detail-grid" id="static-risk">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Static Risk</h2>
          <p class="panel-subtitle">Top risk patterns for this app. Full finding rows live on the dedicated Findings page.</p>
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
          <?php foreach ($topRiskPatterns as $pattern): ?>
            <article class="card compact-card">
              <div class="compact-row">
                <div>
                  <div class="app-primary"><?= e((string)($pattern['title'] ?? 'Untitled pattern')) ?></div>
                  <?php if (!empty($pattern['summary'])): ?>
                    <div class="table-subline"><?= e((string)$pattern['summary']) ?></div>
                  <?php endif; ?>
                </div>
                <?= chip(strtoupper((string)($pattern['tone'] ?? 'INFO')), (string)($pattern['tone'] ?? 'info')) ?>
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
          <p class="panel-subtitle">Permission posture summary for the selected static session. Open the full page for the complete matrix and risk detail.</p>
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
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">Dangerous requested</span><span class="metric-value bad"><?= e((string)$permissionSummary['dangerous']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Signature / Privileged</span><span class="metric-value"><?= e((string)$permissionSummary['signature_privileged']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Special access</span><span class="metric-value warn"><?= e((string)$permissionSummary['special_access']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Custom / app-defined</span><span class="metric-value"><?= e((string)$permissionSummary['custom_defined']) ?></span></div>
          </div>
          <?php if (!empty($permissionHighlights)): ?>
            <div class="detail-stack compact-stack top-gap">
              <?php foreach ($permissionHighlights as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><?= e($row['name']) ?></div>
                      <div class="table-subline"><?= e($row['protection']) ?> · <?= e($row['source']) ?></div>
                    </div>
                    <?= permission_weight_chip($row['weight']) ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section" id="components">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Exported Components</h2>
          <p class="panel-subtitle">Component exposure summary for this app. Open the fleet Components page for full provider and guard detail.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_components.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Components</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Providers</span><span class="metric-value"><?= e((string)$componentSummary['providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Weak Guards</span><span class="metric-value bad"><?= e((string)$componentSummary['weak_provider_guards']) ?></span></div>
          <div class="metric-card"><span class="metric-label">ACL Rows</span><span class="metric-value"><?= e((string)$componentSummary['acl_rows']) ?></span></div>
        </div>

        <?php if (!empty($providerHighlights)): ?>
          <div class="detail-stack compact-stack top-gap">
            <?php foreach ($providerHighlights as $row): ?>
              <article class="card compact-card">
                <div class="compact-row">
                  <div>
                    <div class="app-primary"><?= e($row['provider_name']) ?></div>
                    <div class="table-subline"><?= e($row['authority']) ?></div>
                  </div>
                  <?= chip('Guard ' . $row['guard'], 'high') ?>
                </div>
              </article>
            <?php endforeach; ?>
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
          <p class="panel-subtitle">String signal summary for this session. Full sample review belongs on the Strings page.</p>
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
          <h2 class="panel-title">Representative Samples</h2>
          <p class="panel-subtitle">A few masked examples are shown here. Use the Strings page for full review.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($stringHighlights)): ?>
          <p class="muted">No string samples were found for this session.</p>
        <?php else: ?>
          <div class="detail-stack compact-stack">
            <?php foreach ($stringHighlights as $row): ?>
              <article class="card compact-card">
                <div class="compact-row">
                  <div>
                    <div class="app-primary"><?= e((string)($row['bucket'] ?? 'unknown')) ?></div>
                    <div class="table-subline"><?= e((string)($row['value'] ?? '')) ?></div>
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
          <p class="panel-subtitle">Dynamic availability is summarized here only. Use the Dynamic page for run-by-run detail.</p>
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
        <?php if (((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0): ?>
          <p class="inline-hint top-gap">
            Dynamic data is available for this package. This summary is package-level only and may not represent the exact same app version or APK artifact as the selected static session.
          </p>
          <?php if (!empty($dynamicRuns)): ?>
            <div class="detail-stack compact-stack top-gap">
              <?php foreach ($dynamicRuns as $row): ?>
                <?php $runId = (string)($row['dynamic_run_id'] ?? ''); ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><a href="<?= e(url('pages/dynamic_run.php') . '?run=' . urlencode($runId)) ?>"><?= e($runId) ?></a></div>
                      <div class="table-subline"><?= e((string)($row['run_profile'] ?? 'unknown')) ?> · <?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></div>
                    </div>
                    <?= status_chip((string)($row['status'] ?? 'UNKNOWN')) ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted top-gap">No dynamic runtime rows are available for this package yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
