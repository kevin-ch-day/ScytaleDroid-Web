  <section class="section" id="permissions">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Permissions</h2>
          <p class="panel-subtitle">Permission posture summary for the selected static session. Open the full page for the complete matrix and risk detail.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_permissions.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Permissions</a>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($permissionRows)): ?>
          <p class="muted">
            No permission matrix rows were found for this session.
            <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
              Selected session is not finalized yet.
              <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>">Switch to latest completed</a>.
            <?php else: ?>
              Review <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether permission rows are missing or this session is partial.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">Dangerous requested</span><span class="metric-value bad"><?= e((string)$permissionSummary['dangerous']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Signature / Privileged</span><span class="metric-value"><?= e((string)$permissionSummary['signature_privileged']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Special access</span><span class="metric-value warn"><?= e((string)$permissionSummary['special_access']) ?></span></div>
            <div class="metric-card"><span class="metric-label">Custom / app-defined</span><span class="metric-value"><?= e((string)$permissionSummary['custom_defined']) ?></span></div>
          </div>
          <?php if (!empty($permissionHighlights)): ?>
            <div class="detail-stack compact-stack top-gap">
              <?php foreach ($permissionHighlights as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><?= e($row['name']) ?></div>
                      <div class="table-subline"><?= e($row['protection']) ?> · <?= e($row['source']) ?></div>
                    </div>
                    <?= permission_weight_chip($row['weight']) ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
