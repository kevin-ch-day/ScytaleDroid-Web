<?php
// pages/_partials/table_apps.php
// Expected variables (extracted by index.php):
// $rows, $total, $page, $size, $baseUrl, $persist, $severityTotals, $analyzedCount, $catalogOnlyCount, $groupSearchResults, $analyzedRows, $catalogOnlyRows, $latestScannedAt
?>

<section class="panel" data-panel="results">
  <div class="panel-header">
    <div>
      <h2 class="panel-title">App Directory</h2>
      <p class="panel-subtitle">
        Fleet view of the latest web-facing static summaries. <?= $total !== null ? e((int)$total) . ' tracked app' . ((int)$total === 1 ? '' : 's') : e('Unknown total') ?>.
      </p>
      <p class="muted"><?= e($directoryStateSummary ?? '') ?></p>
    </div>
  </div>
  <div class="panel-body">
    <div class="metrics-grid">
      <div class="metric-card">
        <span class="metric-label">Tracked Apps</span>
        <span class="metric-value"><?= e((string)$total) ?></span>
        <p class="muted">Analyzed <?= e((string)($analyzedCount ?? 0)) ?><?php if (!empty($catalogOnlyCount)): ?> • Catalog only <?= e((string)$catalogOnlyCount) ?><?php endif; ?></p>
      </div>
      <div class="metric-card">
        <span class="metric-label">Latest Session (page)</span>
        <span class="metric-value metric-value-session"><?= e($latestSessionStamp ?: '—') ?></span>
        <p class="muted">Latest scan <?= e($latestScannedAt !== null ? fmt_date_compact(date('c', (int)$latestScannedAt)) : '—') ?></p>
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
        <span class="metric-label">Severity Totals (page)</span>
        <div class="severity-mini-grid" aria-label="Page severity totals">
          <div class="severity-mini-stat">
            <span class="severity-mini-key">H</span>
            <span class="severity-mini-value bad" data-metric="high"><?= e((string)$severityTotals['high']) ?></span>
          </div>
          <div class="severity-mini-stat">
            <span class="severity-mini-key">M</span>
            <span class="severity-mini-value warn"><?= e((string)$severityTotals['med']) ?></span>
          </div>
          <div class="severity-mini-stat">
            <span class="severity-mini-key">L</span>
            <span class="severity-mini-value info"><?= e((string)$severityTotals['low']) ?></span>
          </div>
          <div class="severity-mini-stat">
            <span class="severity-mini-key">I</span>
            <span class="severity-mini-value"><?= e((string)$severityTotals['info']) ?></span>
          </div>
        </div>
      </div>
      <div class="metric-card">
        <span class="metric-label">Dynamic Coverage (page)</span>
        <span class="metric-value"><?= e((string)($dynamicAppCount ?? 0)) ?> apps</span>
        <p class="muted">
          <?= e((string)($dynamicRunCount ?? 0)) ?> runs •
          <?= e((string)($dynamicQuotaValidCount ?? 0)) ?> quota-valid •
          <?= e((string)($dynamicDomainCount ?? 0)) ?> observed domains •
          <?= e((string)($dynamicRootDomainCount ?? 0)) ?> root domains
        </p>
      </div>
    </div>

    <p class="inline-hint app-state-hint">
      <strong>Data state matters:</strong> catalog-only rows are package records without finalized static-analysis results. They are inventory context, not clean findings.
    </p>

    <div class="table-responsive">
      <table class="table table-striped table-hover table-sticky" data-table="apps">
        <thead>
          <tr>
            <th>App</th>
            <th>Static Posture</th>
            <th>Dynamic Runs</th>
            <th>Network Context</th>
            <th>Latest Evidence</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
              <td colspan="5" class="text-center muted p-4">
                <em>No apps found. Try clearing filters.</em>
              </td>
            </tr>
          <?php else: ?>
            <?php
            $renderRow = static function (array $r): void {
              $pkg = $r['package_name'] ?? '';
              $viewUrl = $pkg ? url('pages/app_report.php') . '?pkg=' . urlencode($pkg) : null;
              $dynamicUrl = $pkg ? url('pages/app_dynamic.php') . '?pkg=' . urlencode($pkg) : null;
              $state = (string)($r['source_state'] ?? null);
              $profile = trim((string)($r['profile_label'] ?? ''));
              $category = trim((string)($r['category'] ?? ''));
              $secondaryLabel = app_secondary_label($profile, $category);
              $dynamicRuns = (int)($r['dynamic_runs'] ?? 0);
              $dynamicSuccess = (int)($r['dynamic_successful_runs'] ?? 0);
              $dynamicDegraded = (int)($r['dynamic_degraded_runs'] ?? 0);
              $dynamicFailed = (int)($r['dynamic_failed_runs'] ?? 0);
              $latestDynamicRunId = trim((string)($r['latest_dynamic_run_id'] ?? ''));
              $latestDynamicUrl = $latestDynamicRunId !== '' ? url('pages/dynamic_run.php') . '?run=' . urlencode($latestDynamicRunId) : null;
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
                  <?php if ($secondaryLabel !== ''): ?>
                    <div class="table-subline"><?= e($secondaryLabel) ?></div>
                  <?php endif; ?>
                  <div class="table-subline"><?= e((string)$pkg) ?></div>
                </td>
                <td>
                  <?= app_directory_grade_badge($r['grade'] ?? null, $state) ?>
                  <span class="muted">score <?= e(app_directory_score_text($r['score_capped'] ?? null, $state)) ?></span><br>
                  <span class="muted">
                    H/M/L/I
                    <?= e(app_directory_severity_value($r, 'high')) ?> /
                    <?= e(app_directory_severity_value($r, 'med')) ?> /
                    <?= e(app_directory_severity_value($r, 'low')) ?> /
                    <?= e(app_directory_severity_value($r, 'info')) ?>
                  </span><br>
                  <?= source_state_chip($state) ?>
                </td>
                <td>
                  <?php if ($dynamicRuns > 0): ?>
                    <strong><?= e((string)$dynamicRuns) ?> run<?= $dynamicRuns === 1 ? '' : 's' ?></strong><br>
                    <span class="muted">success / degraded / failed: <?= e((string)$dynamicSuccess) ?> / <?= e((string)$dynamicDegraded) ?> / <?= e((string)$dynamicFailed) ?></span><br>
                    <span class="muted">quota-valid <?= e((string)($r['dynamic_quota_valid_runs'] ?? 0)) ?> • supplemental <?= e((string)($r['dynamic_supplemental_valid_runs'] ?? 0)) ?></span><br>
                    <span class="muted">features ready <?= e((string)($r['dynamic_features_available_runs'] ?? 0)) ?> • static linked <?= e((string)($r['dynamic_static_linked_runs'] ?? 0)) ?></span>
                  <?php else: ?>
                    <span class="muted">No dynamic runs</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($dynamicRuns > 0): ?>
                    domains <?= e((string)($r['dynamic_observed_domains'] ?? 0)) ?> observed /
                    <?= e((string)($r['dynamic_root_domains'] ?? 0)) ?> root<br>
                    <span class="muted">service and signal detail opens in the dynamic view</span><br>
                    <?php if ($dynamicUrl): ?>
                      <a href="<?= e($dynamicUrl) ?>">Open dynamic</a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="muted">No network context</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="muted">static</span> <?= e(fmt_date_compact($r['last_scanned'] ?? null) ?: '—') ?><br>
                  <?php if ($dynamicRuns > 0): ?>
                    <span class="muted">dynamic</span> <?= e(fmt_date_compact($r['latest_dynamic_started_at'] ?? null) ?: '—') ?><br>
                    <?php if ($latestDynamicUrl): ?>
                      <a class="cell-clip" href="<?= e($latestDynamicUrl) ?>"><?= e($latestDynamicRunId) ?></a>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="muted">dynamic —</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php };
            ?>
            <?php if (!empty($groupSearchResults)): ?>
              <tr class="table-group-row">
                <td colspan="5">Primary analyzed apps</td>
              </tr>
              <?php foreach ($analyzedRows as $r): $renderRow($r); endforeach; ?>
              <tr class="table-group-row">
                <td colspan="5">Related catalog packages (inventory context only)</td>
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
