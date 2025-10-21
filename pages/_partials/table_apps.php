<?php
// pages/_partials/table_apps.php
// Expected variables (extracted by index.php):
// $rows, $total, $page, $size, $baseUrl, $persist, $severityTotals
?>

<section class="neon-panel" data-panel="results">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">App Directory</h2>
      <p class="panel-subtitle">
        Latest snapshot per package. <?= $total !== null ? e((int)$total) . ' tracked app' . ((int)$total === 1 ? '' : 's') : e('Unknown total') ?>.
      </p>
    </div>
    <div class="panel-actions">
      <button type="button" class="btn-ghost" data-action="toggle-density">Toggle Density</button>
    </div>
  </div>
  <div class="panel-body">
    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">High Findings (page)</span>
        <span class="metric-value bad" data-metric="high"><?= e((string)$severityTotals['high']) ?></span>
      </div>
      <div class="metric-card">
        <span class="metric-label">Medium Findings (page)</span>
        <span class="metric-value warn" data-metric="med"><?= e((string)$severityTotals['med']) ?></span>
      </div>
      <div class="metric-card">
        <span class="metric-label">Low Findings (page)</span>
        <span class="metric-value info" data-metric="low"><?= e((string)$severityTotals['low']) ?></span>
      </div>
      <div class="metric-card">
        <span class="metric-label">Page</span>
        <span class="metric-value"><?= e((string)$page) ?></span>
        <p class="muted">Showing <?= e((string)$size) ?> results per page.</p>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-hover table-sticky" data-table="apps">
        <thead>
          <tr>
            <th>App</th>
            <th>Package</th>
            <th>Category</th>
            <th class="col-center">Grade</th>
            <th class="col-num">Score</th>
            <th class="col-center">H/M/L</th>
            <th>Last Scanned</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="7" class="text-center muted p-4">
                <em>No apps found. Try clearing filters.</em>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
              $pkg = $r['package_name'] ?? '';
              $viewUrl = $pkg ? BASE_URL . '/pages/view_app.php?pkg=' . urlencode($pkg) : null;
              $hml = fmt_hml($r['high'] ?? 0, $r['med'] ?? 0, $r['low'] ?? 0);
              ?>
              <tr>
                <td class="cell-clip">
                  <?php if ($viewUrl): ?>
                    <a href="<?= e($viewUrl) ?>"><?= e($r['app_label'] ?? $pkg) ?></a>
                  <?php else: ?>
                    <?= e($r['app_label'] ?? $pkg) ?>
                  <?php endif; ?>
                </td>
                <td class="cell-clip">
                  <div class="flex items-center gap-2">
                    <?php if ($viewUrl): ?>
                      <a href="<?= e($viewUrl) ?>" class="muted js-package" data-package="<?= e($pkg) ?>"><?= e($pkg) ?></a>
                    <?php else: ?>
                      <span class="muted js-package" data-package="<?= e($pkg) ?>"><?= e($pkg) ?></span>
                    <?php endif; ?>
                    <?php if ($pkg): ?>
                      <button type="button" class="copy-btn" data-copy="<?= e($pkg) ?>" aria-label="Copy package name">Copy</button>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?= e($r['category'] ?? 'Uncategorized') ?></td>
                <td class="col-center"><?= grade_badge($r['grade'] ?? null) ?></td>
                <td class="col-num"><?= e(isset($r['score_capped']) ? (string)$r['score_capped'] : '') ?></td>
                <td class="col-center" data-hml="<?= e($hml) ?>"><?= e($hml) ?></td>
                <td><?= e(fmt_date($r['last_scanned'] ?? null)) ?></td>
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
