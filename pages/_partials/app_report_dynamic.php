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
