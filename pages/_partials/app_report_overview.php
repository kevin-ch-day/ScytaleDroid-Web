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
          <div class="metric-card"><span class="metric-label">Static Grade</span><span class="metric-value"><?= e((string)$scoreMeta['grade_text']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Normalized Score</span><span class="metric-value"><?= e((string)$scoreMeta['normalized_score_text']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Risk Band</span><span class="metric-value"><?= e((string)($scoreMeta['risk_band'] ?? '—')) ?></span></div>
          <div class="metric-card"><span class="metric-label">Findings H/M/L/I</span><span class="metric-value"><?= e(fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dangerous Permissions</span><span class="metric-value bad"><?= e((string)$selectedDangerous) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">High-Entropy Strings</span><span class="metric-value"><?= e((string)$selectedHighEntropy) ?></span></div>
          <div class="metric-card">
            <span class="metric-label">Dynamic Evidence</span>
            <span class="metric-value info"><?= e((string)dynamic_evidence_quality_meta($dynamicSummary)['label']) ?></span>
            <p class="muted"><?= e((string)dynamic_evidence_quality_meta($dynamicSummary)['summary']) ?></p>
          </div>
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
          <?= dynamic_evidence_quality_chip($dynamicSummary) ?>
          <?php if (((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0): ?>
            <?= chip('Dynamic runs ' . (string)($dynamicSummary['dynamic_runs'] ?? 0), 'muted') ?>
            <?= chip('Quota-valid ' . (string)($dynamicSummary['quota_valid_runs'] ?? 0), ((int)($dynamicSummary['quota_valid_runs'] ?? 0)) > 0 ? 'info' : 'medium') ?>
            <?= chip('Supplemental ' . (string)($dynamicSummary['supplemental_valid_runs'] ?? 0), ((int)($dynamicSummary['supplemental_valid_runs'] ?? 0)) > 0 ? 'medium' : 'muted') ?>
            <?= chip('Static linked ' . (string)($dynamicSummary['static_linked_runs'] ?? 0), ((int)($dynamicSummary['static_linked_runs'] ?? 0)) > 0 ? 'info' : 'high') ?>
            <?= chip('Features ready ' . (string)($dynamicSummary['features_available_runs'] ?? 0), ((int)($dynamicSummary['features_available_runs'] ?? 0)) > 0 ? 'info' : 'high') ?>
          <?php endif; ?>
        </div>
        <div class="card compact-card top-gap">
          <div class="app-primary">Why this score</div>
          <p class="table-subline">This page shows the normalized analyst-facing static score. Raw/internal scoring stays on diagnostics surfaces only.</p>
          <?php if (empty($scoreDrivers)): ?>
            <p class="muted">No clear score drivers were derived for this session. Review <a href="<?= e($runHealthUrl) ?>">Run Health</a> if the selected session looks incomplete.</p>
          <?php else: ?>
            <ul class="detail-list">
              <?php foreach ($scoreDrivers as $driver): ?>
                <li><?= e($driver) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
