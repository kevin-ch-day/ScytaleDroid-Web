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
            <?php else: ?>
              Review <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether string rows are missing or this session is partial.
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
          <p class="muted">No string samples were found for this session. Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether selected rows are missing or this session is partial.</p>
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
