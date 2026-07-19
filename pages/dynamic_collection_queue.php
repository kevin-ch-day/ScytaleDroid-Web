<?php
// pages/dynamic_collection_queue.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../lib/pager.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$q = guard_search($_GET['q'] ?? null);
$statusOptions = ['needs_static', 'baseline', 'interactive', 'legacy', 'review', 'complete'];
$cohortOptions = ['research_dataset_beta', 'research_dataset_alpha'];
$collectionStatus = guard_choice($_GET['collection_status'] ?? null, $statusOptions);
$cohortKey = guard_choice($_GET['cohort_key'] ?? null, $cohortOptions);
[$size, $offset, $page] = pager_from_query($_GET);

$overview = [];
$rows = [];
$total = 0;
$errorMsg = null;

try {
    $overview = dynamic_collection_queue_overview();
    $recommendedCaptures = dynamic_collection_queue_recommended_captures(5);
    $pg = dynamic_collection_queue_paged($collectionStatus, $cohortKey, $q, $page, $size);
    $rows = $pg['rows'] ?? [];
    $total = (int)($pg['total'] ?? 0);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] dynamic collection queue failed: ' . $e);
}

$recommendedCaptures = $recommendedCaptures ?? [];

$baseUrl = PAGES_URL . '/dynamic_collection_queue.php';
$persist = ['q' => $q, 'collection_status' => $collectionStatus, 'cohort_key' => $cohortKey, 'size' => $size];
$filtered = array_filter(['q' => $q, 'collection_status' => $collectionStatus, 'cohort_key' => $cohortKey], fn($v) => $v !== null && $v !== '');

function dcq_supplemental_label(int $extra, int $low): string
{
    $parts = [];
    if ($extra > 0) {
        $parts[] = '+' . $extra . ($extra === 1 ? ' extra' : ' extras');
    }
    if ($low > 0) {
        $parts[] = '+' . $low . ' low';
    }
    return $parts === [] ? '—' : implode(', ', $parts);
}

function dcq_status_label(string $status): string
{
    return match ($status) {
        'needs_static' => 'Needs static',
        'baseline' => 'Baseline gap',
        'interactive' => 'Interactive gap',
        'legacy' => 'Legacy only',
        'review' => 'Review',
        'complete' => 'Complete',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

$recommendedPackage = null;
foreach ($rows as $candidate) {
    $state = (string)($candidate['collection_status'] ?? '');
    if (in_array($state, ['needs_static', 'baseline', 'interactive', 'legacy', 'review'], true)) {
        $recommendedPackage = (string)($candidate['package_name'] ?? '');
        break;
    }
}

$PAGE_TITLE = 'Dynamic Collection Queue';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <div class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">Dynamic Collection Queue</h1>
        <p class="panel-subtitle">
          Active research cohort apps from persisted dynamic runs (default CLI cohort: Research Dataset Beta).
          Quota math uses quota-counted runs on the current harvested build when a latest APK SHA is known; otherwise all builds.
          Supplemental and low-signal retained runs appear in Base+ / Int+. CLI queue may still differ on live device drift and local tracker scoping.
        </p>
      </div>
    </div>
    <div class="panel-body">
      <div class="metrics-grid">
        <div class="metric-card"><span class="metric-label">Cohort Apps</span><span class="metric-value"><?= e((string)($overview['cohort_apps'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Complete / Baseline / Interactive</span><span class="metric-value"><?= e((string)($overview['complete_apps'] ?? 0)) ?> / <?= e((string)($overview['baseline_gap_apps'] ?? 0)) ?> / <?= e((string)($overview['interactive_gap_apps'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Needs Static / Legacy apps / Review</span><span class="metric-value"><?= e((string)($overview['needs_static_apps'] ?? 0)) ?> / <?= e((string)($overview['legacy_apps'] ?? 0)) ?> / <?= e((string)($overview['review_apps'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Need Baseline / Interactive Slots</span><span class="metric-value"><?= e((string)($overview['total_need_baseline'] ?? 0)) ?> / <?= e((string)($overview['total_need_interactive'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Quota-counted (B / I)</span><span class="metric-value"><?= e((string)($overview['total_baseline_quota_counted'] ?? 0)) ?> / <?= e((string)($overview['total_interactive_quota_counted'] ?? 0)) ?></span></div>
        <div class="metric-card"><span class="metric-label">Supplemental / Invalid (current build)</span><span class="metric-value"><?= e((string)($overview['total_supplemental_runs'] ?? 0)) ?> / <?= e((string)($overview['total_invalid_runs'] ?? 0)) ?></span></div>
      </div>
    </div>
  </div>
</section>

<?php if ($recommendedCaptures !== []): ?>
<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Recommended Captures</h2>
        <p class="panel-subtitle">Quota-gap apps ordered by interactive need first (DB view; does not filter live device drift — use CLI for drift-aware capture plan).</p>
      </div>
    </div>
    <div class="panel-body">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>App</th>
            <th>Status</th>
            <th>Gap</th>
            <th>Baseline</th>
            <th>Interactive</th>
            <th>QA</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recommendedCaptures as $index => $cap): ?>
            <tr>
              <td><?= e((string)($index + 1)) ?></td>
              <td><?= e((string)($cap['app_label'] ?? $cap['package_name'] ?? '—')) ?></td>
              <td><?= e(dcq_status_label((string)($cap['collection_status'] ?? ''))) ?></td>
              <td><?= e((string)($cap['quota_gap_label'] ?? '—')) ?></td>
              <td><?= e((string)($cap['baseline_quota_counted'] ?? 0)) ?>/3</td>
              <td><?= e((string)($cap['interactive_quota_counted'] ?? 0)) ?>/4</td>
              <td><?= e((string)($cap['qa_label'] ?? '—')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h2 class="panel-title">Cohort Queue</h2>
        <p class="panel-subtitle">Filter by package, app label, or collection status. Recommended next app is marked with <code>&gt;</code> when visible on this page.</p>
      </div>
    </div>
    <div class="panel-body">
      <form class="form-row" method="get" action="<?= e($baseUrl) ?>">
        <label>
          <span class="metric-label">Search</span>
          <input type="search" name="q" value="<?= e((string)$q) ?>" placeholder="package or app label">
        </label>
        <label>
          <span class="metric-label">Cohort</span>
          <select name="cohort_key">
            <option value="">All active cohorts</option>
            <?php foreach ($cohortOptions as $opt): ?>
              <option value="<?= e($opt) ?>" <?= (string)$cohortKey === (string)$opt ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          <span class="metric-label">Collection status</span>
          <select name="collection_status">
            <option value="">Any status</option>
            <?php foreach ($statusOptions as $opt): ?>
              <option value="<?= e($opt) ?>" <?= (string)$collectionStatus === (string)$opt ? 'selected' : '' ?>><?= e(dcq_status_label($opt)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="btn btn-primary">Apply</button>
        <?php if ($filtered !== []): ?>
          <a class="btn btn-secondary" href="<?= e($baseUrl) ?>">Clear</a>
        <?php endif; ?>
      </form>

      <?php if (empty($rows) && !$errorMsg): ?>
        <p class="muted">No cohort queue rows matched the current filters.</p>
      <?php elseif (!empty($rows)): ?>
        <div class="table-responsive">
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>App</th>
                <th>Collection status</th>
                <th>Need</th>
                <th>Gap</th>
                <th>Next</th>
                <th>QA</th>
                <th>Cohort</th>
                <th>Scope</th>
                <th>Baseline</th>
                <th>Base+</th>
                <th>Interactive</th>
                <th>Int+</th>
                <th>Build</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $index => $row): ?>
                <?php
                $pkg = (string)($row['package_name'] ?? '');
                $isRecommended = $recommendedPackage !== null && $pkg === $recommendedPackage;
                $rowNum = (string)($offset + $index + 1);
                $versionName = trim((string)($row['latest_version_name'] ?? ''));
                $versionCode = trim((string)($row['latest_version_code'] ?? ''));
                $buildLabel = ($versionName !== '' || $versionCode !== '')
                    ? trim($versionName . ($versionCode !== '' ? " ($versionCode)" : ''))
                    : '—';
                ?>
                <tr<?= $isRecommended ? ' class="table-warning"' : '' ?>>
                  <td><?= e($isRecommended ? '>' . $rowNum : $rowNum) ?></td>
                  <td>
                    <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($pkg)) ?>"><strong><?= e((string)($row['app_label'] ?? $pkg)) ?></strong></a><br>
                    <span class="muted"><?= e($pkg) ?></span>
                  </td>
                  <td><?= status_chip(dcq_status_label((string)($row['collection_status'] ?? ''))) ?></td>
                  <td><?= e((string)($row['need_label'] ?? '—')) ?></td>
                  <td><?= e((string)(($row['quota_gap_label'] ?? '') !== '' ? $row['quota_gap_label'] : '—')) ?></td>
                  <td><?= e((string)($row['next_action_hint'] ?? '—')) ?></td>
                  <td><?= e((string)($row['qa_label'] ?? '—')) ?></td>
                  <td><span class="muted"><?= e(str_replace('research_dataset_', '', (string)($row['cohort_key'] ?? ''))) ?></span></td>
                  <td><span class="muted"><?= e(str_replace('db_', '', (string)($row['data_scope'] ?? ''))) ?></span></td>
                  <td><?= e((string)($row['baseline_quota_label'] ?? '—')) ?></td>
                  <td><?= e(dcq_supplemental_label((int)($row['baseline_extra_valid'] ?? 0), (int)($row['baseline_low_signal_retained'] ?? 0))) ?></td>
                  <td><?= e((string)($row['interactive_display_label'] ?? $row['interactive_quota_label'] ?? '—')) ?></td>
                  <td><?= e(dcq_supplemental_label((int)($row['interactive_extra_valid'] ?? 0), (int)($row['interactive_low_signal_retained'] ?? 0))) ?></td>
                  <td><?= e($buildLabel) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($recommendedPackage !== null): ?>
          <p class="muted"><code>&gt;</code> marks the recommended next app on this page (first open gap in current sort).</p>
        <?php endif; ?>
        <?= pager_render($baseUrl, $total, $page, $size, $persist) ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
