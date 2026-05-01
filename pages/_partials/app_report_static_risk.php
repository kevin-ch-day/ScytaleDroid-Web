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
          <p class="muted">No findings summary is available for this session. Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether the selected session is incomplete or missing finalized rows.</p>
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

