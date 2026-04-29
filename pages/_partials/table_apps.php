<?php
// pages/_partials/table_apps.php
// Expected variables (extracted by index.php):
// $rows, $total, $page, $size, $baseUrl, $persist, $severityTotals
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
            <th>Run Context</th>
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
            <?php foreach ($rows as $r): ?>
              <?php
              $pkg = $r['package_name'] ?? '';
              $viewUrl = $pkg ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) : null;
              $hml = fmt_hml($r['high'] ?? 0, $r['med'] ?? 0, $r['low'] ?? 0);
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
                  <div class="table-subline"><?= e($r['category'] ?? 'Uncategorized') ?></div>
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
                <td class="col-center"><?= grade_badge($r['grade'] ?? null) ?></td>
                <td class="col-num"><?= e(isset($r['score_capped']) ? (string)$r['score_capped'] : '') ?></td>
                <td class="col-center" data-hml="<?= e($hml) ?>"><?= e($hml) ?></td>
                <td>
                  <div class="meta-stack">
                    <span class="session-stamp"><?= e($r['session_stamp'] ?? '') ?></span>
                    <span class="table-subline"><?= source_state_chip($r['source_state'] ?? null) ?></span>
                  </div>
                </td>
                <td class="nowrap"><?= e(fmt_date($r['last_scanned'] ?? null)) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="panel-footer">
    <?php pager_render($baseUrl, (int)$total, (int)$page, (int)$size, $persist); ?>
  </div>
</section>
