<?php
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$groupAllowed = ['title', 'detector', 'masvs_area', 'app'];
$severityAllowed = ['critical', 'high', 'medium', 'low', 'info'];
$appScopeAllowed = ['all', 'user_apps', 'system_oem_apps', 'google_apps'];

$groupBy = guard_choice($_GET['group_by'] ?? null, $groupAllowed) ?? 'title';
$groupValue = trim((string)($_GET['value'] ?? ''));
$severity = guard_choice($_GET['severity'] ?? null, $severityAllowed);
$appScope = guard_choice($_GET['app_scope'] ?? null, $appScopeAllowed) ?? 'all';
$includeSynthetic = isset($_GET['include_synthetic']) && $_GET['include_synthetic'] === '1';

$categoryOptions = findings_categories();
$masvsOptions = findings_masvs_areas();
$detectorOptions = findings_detectors();
$sessionOptions = findings_sessions();

$category = guard_choice($_GET['category'] ?? null, $categoryOptions);
$masvsArea = guard_choice($_GET['masvs_area'] ?? null, $masvsOptions);
$detector = guard_choice($_GET['detector'] ?? null, $detectorOptions);
$sessionStamp = guard_choice($_GET['session'] ?? null, $sessionOptions);
$scopeFilter = $appScope === 'all' ? null : $appScope;

$summary = [];
$apps = [];
$examples = [];
$errorMsg = null;

if ($groupValue === '') {
    $errorMsg = 'Missing group value.';
} else {
    try {
        $summary = findings_group_detail_summary($groupBy, $groupValue, $severity, $category, $masvsArea, $detector, $sessionStamp, $scopeFilter, $includeSynthetic);
        $apps = findings_group_detail_apps($groupBy, $groupValue, $severity, $category, $masvsArea, $detector, $sessionStamp, $scopeFilter, $includeSynthetic);
        $examples = findings_group_detail_examples($groupBy, $groupValue, $severity, $category, $masvsArea, $detector, $sessionStamp, $scopeFilter, $includeSynthetic);
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] findings group detail failed: ' . $e);
    }
}

$backUrl = url('pages/findings.php') . '?' . http_build_query(array_filter([
    'group_by' => $groupBy,
    'severity' => $severity,
    'category' => $category,
    'masvs_area' => $masvsArea,
    'detector' => $detector,
    'session' => $sessionStamp,
    'app_scope' => $appScope !== 'all' ? $appScope : null,
    'include_synthetic' => $includeSynthetic ? '1' : null,
], static fn($v) => $v !== null && $v !== ''));

$groupLabel = match ($groupBy) {
    'detector' => 'Detector',
    'masvs_area' => 'MASVS Area',
    'app' => 'App Group',
    default => 'Finding Pattern',
};

$actionLink = match ($groupBy) {
    'detector' => $groupValue === 'provider_acl' || $groupValue === 'ipc_components' ? url('pages/components.php') : null,
    'masvs_area' => $groupValue === 'PLATFORM' ? url('pages/components.php') : ($groupValue === 'PRIVACY' ? url('pages/permissions.php') : null),
    default => (stripos($groupValue, 'provider') !== false || stripos($groupValue, 'exported') !== false) ? url('pages/components.php') : ((stripos($groupValue, 'permission') !== false) ? url('pages/permissions.php') : null),
};
$actionText = match (true) {
    $actionLink === null => 'Open Findings Explorer with narrower filters',
    str_contains((string)$actionLink, 'components.php') => 'Open Components for structural exposure review',
    str_contains((string)$actionLink, 'permissions.php') => 'Open Permissions for permission review',
    default => 'Open related detail page',
};

$PAGE_TITLE = 'Finding Group Detail';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <section class="panel detail-hero">
    <div class="panel-header">
      <div>
        <h1 class="panel-title"><?= e($groupLabel) ?>: <?= e($groupValue !== '' ? $groupValue : 'Unknown') ?></h1>
        <p class="panel-subtitle">
          <span><?= e((string)($summary['category'] ?? 'Uncategorized')) ?></span>
          <span>•</span>
          <span><?= e((string)($summary['masvs_area'] ?? 'Unmapped')) ?></span>
          <span>•</span>
          <span><?= e((string)($summary['detector'] ?? $groupBy)) ?></span>
        </p>
      </div>
      <div class="panel-actions">
        <a class="btn-ghost" href="<?= e($backUrl) ?>">Back to Explorer</a>
        <?php if ($actionLink): ?>
          <a class="btn btn-primary" href="<?= e($actionLink) ?>"><?= e($actionText) ?></a>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Pattern Summary</h2>
        <p class="panel-subtitle">Severity distribution, affected scope, and analyst context for this finding pattern.</p>
      </div>
    </div>
    <div class="panel-body">
      <div class="metrics-grid">
        <div class="metric-card">
          <span class="metric-label">Finding Rows</span>
          <span class="metric-value"><?= e((string)($summary['finding_rows'] ?? 0)) ?></span>
        </div>
        <div class="metric-card">
          <span class="metric-label">Affected Apps</span>
          <span class="metric-value"><?= e((string)($summary['affected_apps'] ?? 0)) ?></span>
        </div>
        <div class="metric-card">
          <span class="metric-label">Severity H/M/L/I</span>
          <span class="metric-value"><?= e(fmt_hml((int)($summary['high_rows'] ?? 0), (int)($summary['medium_rows'] ?? 0), (int)($summary['low_rows'] ?? 0), (int)($summary['info_rows'] ?? 0))) ?></span>
          <p class="muted">Critical rows: <?= e((string)($summary['critical_rows'] ?? 0)) ?></p>
        </div>
        <div class="metric-card">
          <span class="metric-label">App Mix</span>
          <span class="metric-value"><?= e((string)((int)($summary['affected_apps'] ?? 0) - (int)($summary['system_rows'] ?? 0))) ?>/<?= e((string)($summary['system_rows'] ?? 0)) ?></span>
          <p class="muted">Non-system vs system/OEM row mix • Google rows <?= e((string)($summary['google_rows'] ?? 0)) ?></p>
        </div>
      </div>

      <div class="callout-block">
        <strong>Why this matters:</strong>
        <?php if (stripos($groupValue, 'exported') !== false || stripos((string)($summary['detector'] ?? ''), 'provider_acl') !== false || stripos((string)($summary['detector'] ?? ''), 'ipc_components') !== false): ?>
          This pattern points to exposed Android IPC or component surfaces that may be reachable by other apps without strong guardrails.
        <?php elseif (stripos($groupValue, 'permission') !== false): ?>
          This pattern suggests repeated permission governance or capability exposure that can change the privacy posture across multiple apps.
        <?php else: ?>
          This pattern repeats across multiple apps and is useful for triage, cohort analysis, and deeper component or permission review.
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Affected Apps</h2>
        <p class="panel-subtitle">Apps and packages most affected by this pattern.</p>
      </div>
    </div>
    <div class="panel-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead>
            <tr>
              <th>App</th>
              <th>Profile</th>
              <th>Session</th>
              <th class="col-num">Finding rows</th>
              <th>H/M/L/I</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($apps as $row): ?>
              <?php $appUrl = url('pages/app_report.php') . '?pkg=' . urlencode((string)$row['package_name']) . '&session=' . urlencode((string)$row['session_stamp']); ?>
              <tr>
                <td class="cell-clip">
                  <a href="<?= e($appUrl) ?>"><?= e((string)($row['app_label'] ?? $row['package_name'])) ?></a>
                  <div class="table-subline"><?= e((string)($row['package_name'] ?? '')) ?></div>
                </td>
                <td><?= e((string)($row['profile_label'] ?? 'Unclassified')) ?></td>
                <td><span class="session-stamp"><?= e((string)($row['session_stamp'] ?? '')) ?></span></td>
                <td class="col-num"><?= e((string)($row['finding_rows'] ?? 0)) ?></td>
                <td><?= e(fmt_hml((int)($row['high_rows'] ?? 0), (int)($row['medium_rows'] ?? 0), (int)($row['low_rows'] ?? 0), (int)($row['info_rows'] ?? 0))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Example Evidence</h2>
        <p class="panel-subtitle">Representative finding rows for manual review.</p>
      </div>
    </div>
    <div class="panel-body">
      <div class="stack-list">
        <?php foreach ($examples as $row): ?>
          <?php $appUrl = url('pages/app_report.php') . '?pkg=' . urlencode((string)$row['package_name']) . '&session=' . urlencode((string)$row['session_stamp']); ?>
          <article class="finding-card">
            <div class="finding-card-header">
              <div>
                <h3 class="finding-card-title"><?= e((string)($row['title'] ?? 'Untitled finding')) ?></h3>
                <p class="finding-card-subtitle">
                  <a href="<?= e($appUrl) ?>"><?= e((string)($row['app_label'] ?? $row['package_name'])) ?></a>
                  · <?= e((string)($row['package_name'] ?? '')) ?>
                  · <?= e((string)($row['detector'] ?? 'unknown')) ?>
                </p>
              </div>
              <?= chip(strtoupper((string)($row['severity'] ?? 'info')), (string)($row['severity'] ?? 'info')) ?>
            </div>
            <div class="finding-card-body">
              <p class="muted"><?= e((string)($row['category'] ?? 'Uncategorized')) ?> · <?= e((string)($row['masvs_area'] ?? 'Unmapped')) ?></p>
              <pre class="code-block"><?= e(finding_evidence_display_text($row['evidence'] ?? null)) ?></pre>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
