  <section class="section" id="components">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Exported Components</h2>
          <p class="panel-subtitle">Component exposure summary for this app. Open the fleet Components page for full provider and guard detail.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_components.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Components</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Providers</span><span class="metric-value"><?= e((string)$componentSummary['providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Weak Guards</span><span class="metric-value bad"><?= e((string)$componentSummary['weak_provider_guards']) ?></span></div>
          <div class="metric-card"><span class="metric-label">ACL Rows</span><span class="metric-value"><?= e((string)$componentSummary['acl_rows']) ?></span></div>
        </div>

        <?php if (!empty($providerHighlights)): ?>
          <div class="detail-stack compact-stack top-gap">
            <?php foreach ($providerHighlights as $row): ?>
              <article class="card compact-card">
                <div class="compact-row">
                  <div>
                    <div class="app-primary"><?= e($row['provider_name']) ?></div>
                    <div class="table-subline"><?= e($row['authority']) ?></div>
                  </div>
                  <?= chip('Guard ' . $row['guard'], 'high') ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
