<?php
// pages/_partials/table_apps.php
// Expected variables (extracted by index.php):
// $rows, $total, $page, $size, $baseUrl, $persist, $severityTotals, $analyzedCount, $catalogOnlyCount, $groupSearchResults, $analyzedRows, $catalogOnlyRows
?>

<section class="panel" data-panel="results">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">App Directory</h2>
      <p class="panel-subtitle">
        Fleet view of the latest web-facing static summaries. <?= $total !== null ? e((int)$total) . ' tracked app' . ((int)$total === 1 ? '' : 's') : e('Unknown total') ?>.
      </p>
    </div>
    <div class="panel-actions">
      <button type="button" class="btn-ghost" data-action="toggle-density">Toggle Density</button>
    </div>
  </div>
  <div class="panel-body">
    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">Tracked Apps</span>
        <span class="metric-value"><?= e((string)$total) ?></span>
        <p class="muted">Analyzed <?= e((string)($analyzedCount ?? 0)) ?><?php if (!empty($catalogOnlyCount)): ?> • Catalog-only <?= e((string)$catalogOnlyCount) ?><?php endif; ?></p>
      </div>
      <div class="metric-card">
        <span class="metric-label">Latest Session (page)</span>
        <span class="metric-value metric-value-session"><?= e($latestSessionStamp ?: '—') ?></span>
      </div>
      <div class="metric-card">
        <span class="metric-label">Source Mix (page)</span>
        <span class="metric-value info"><?= e((string)count($rows)) ?></span>
        <div class="source-mix-list">
          <?php if (!empty($sourceStateCounts)): ?>
            <?php foreach ($sourceStateCounts as $state => $count): ?>
              <span class="source-mix-item">
                <?= source_state_chip($state) ?>
                <span class="muted"><?= e((string)$count) ?></span>
              </span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="muted">No visible rows.</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="metric-card">
        <span class="metric-label">High Findings (page)</span>
        <span class="metric-value bad" data-metric="high"><?= e((string)$severityTotals['high']) ?></span>
        <p class="muted">Medium <?= e((string)$severityTotals['med']) ?> • Low <?= e((string)$severityTotals['low']) ?></p>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover table-sticky" data-table="apps">
        <thead>
          <tr>
            <th>App</th>
            <th class="col-center">Grade</th>
            <th class="col-num">Score</th>
            <th class="col-center">H/M/L</th>
            <th>Data State</th>
            <th>Last Scanned</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="6" class="text-center muted p-4">
                <em>No apps found. Try clearing filters.</em>
              </td>
            </tr>
          <?php else: ?>
            <?php
            $renderRow = static function (array $r): void {
              $pkg = $r['package_name'] ?? '';
              $viewUrl = $pkg ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) : null;
              $hml = app_directory_hmli_text($r);
              $state = (string)($r['source_state'] ?? null);
              $sessionStamp = trim((string)($r['session_stamp'] ?? ''));
              $profile = trim((string)($r['profile_label'] ?? ''));
              $category = trim((string)($r['category'] ?? ''));
            ?>
              <tr>
                <td class="cell-clip">
                  <div class="app-primary">
                    <?php if ($viewUrl): ?>
                      <a href="<?= e($viewUrl) ?>"><?= e($r['app_label'] ?? $pkg) ?></a>
                    <?php else: ?>
                      <?= e($r['app_label'] ?? $pkg) ?>
                    <?php endif; ?>
                  </div>
                  <div class="table-subline"><?= e($profile !== '' ? $profile : ($category !== '' ? $category : 'Unclassified')) ?></div>
                  <div class="package-inline">
                    <?php if ($viewUrl): ?>
                      <a href="<?= e($viewUrl) ?>" class="muted js-package package-link" data-package="<?= e($pkg) ?>"><?= e($pkg) ?></a>
                    <?php else: ?>
                      <span class="muted js-package package-link" data-package="<?= e($pkg) ?>"><?= e($pkg) ?></span>
                    <?php endif; ?>
                    <?php if ($pkg): ?>
                      <button type="button" class="copy-btn" data-copy="<?= e($pkg) ?>" aria-label="Copy package name">Copy</button>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="col-center"><?= app_directory_grade_badge($r['grade'] ?? null, $state) ?></td>
                <td class="col-num"><?= e(app_directory_score_text($r['score_capped'] ?? null, $state)) ?></td>
                <td class="col-center" data-hml="<?= e($hml) ?>"><?= e($hml) ?></td>
                <td>
                  <div class="meta-stack">
                    <span class="table-subline"><?= source_state_chip($state) ?></span>
                    <span class="session-stamp"><?= e($sessionStamp !== '' ? $sessionStamp : '—') ?></span>
                  </div>
                </td>
                <td class="nowrap"><?= e(fmt_date($r['last_scanned'] ?? null) ?: '—') ?></td>
              </tr>
            <?php };
            ?>
            <?php if (!empty($groupSearchResults)): ?>
              <tr class="table-group-row">
                <td colspan="6">Primary analyzed apps</td>
              </tr>
              <?php foreach ($analyzedRows as $r): $renderRow($r); endforeach; ?>
              <tr class="table-group-row">
                <td colspan="6">Related catalog packages</td>
              </tr>
              <?php foreach ($catalogOnlyRows as $r): $renderRow($r); endforeach; ?>
            <?php else: ?>
              <?php foreach ($rows as $r): $renderRow($r); endforeach; ?>
            <?php endif; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel-footer">
    <?php pager_render($baseUrl, (int)$total, (int)$page, (int)$size, $persist); ?>
  </div>
</section>
