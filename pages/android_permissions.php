<?php
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$errorMsg = null;
$overview = [];
$topDangerous = [];
$sourceBreakdown = [];
$protectionBreakdown = [];
$sensitiveCombos = [];

try {
    $overview = permission_intel_overview();
    $topDangerous = permission_intel_top_dangerous(15);
    $sourceBreakdown = permission_intel_source_breakdown(10);
    $protectionBreakdown = permission_intel_protection_breakdown(10);
    $sensitiveCombos = permission_intel_sensitive_combos(10);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] permission intelligence failed: ' . $e);
}

$PAGE_TITLE = 'Permission Intelligence';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <?php if ($errorMsg): ?>
    <div class="alert alert-danger"><?= e($errorMsg) ?></div>
  <?php else: ?>
    <div class="panel">
      <div class="panel-header">
        <div>
          <h1 class="panel-title">Permission Intelligence</h1>
          <p class="panel-subtitle">Cross-app view of requested permissions, protection levels, source families, and sensitive combinations from the persisted static permission matrix.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Permission Rows</span><span class="metric-value"><?= e((string)($overview['permission_rows'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Apps With Rows</span><span class="metric-value info"><?= e((string)($overview['apps_with_permissions'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Distinct Permissions</span><span class="metric-value"><?= e((string)($overview['distinct_permissions'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Dangerous Rows</span><span class="metric-value bad"><?= e((string)($overview['dangerous_rows'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Signature Rows</span><span class="metric-value warn"><?= e((string)($overview['signature_rows'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Custom Rows</span><span class="metric-value"><?= e((string)($overview['custom_rows'] ?? 0)) ?></span></div>
        </div>
      </div>
    </div>

    <div class="detail-grid section-tight">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Top Dangerous Permissions</h2>
            <p class="panel-subtitle">Most prevalent dangerous permissions across distinct apps.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Permission</th>
                  <th>Source</th>
                  <th>Protection</th>
                  <th class="col-num">Apps</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($topDangerous as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['permission_name'] ?? '')) ?></td>
                    <td><?= e((string)($row['source'] ?? '—')) ?></td>
                    <td><?= e((string)($row['protection'] ?? '—')) ?></td>
                    <td class="col-num"><?= e((string)($row['app_count'] ?? 0)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Sensitive Permission Combinations</h2>
            <p class="panel-subtitle">Simple cross-app combinations that are useful for privacy-oriented review.</p>
          </div>
        </div>
        <div class="panel-body">
          <?php if (!$sensitiveCombos): ?>
            <p class="muted">No tracked sensitive permission combinations were found in the current dataset.</p>
          <?php else: ?>
            <div class="detail-stack compact-stack">
              <?php foreach ($sensitiveCombos as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div class="app-primary"><?= e((string)($row['combo_label'] ?? 'Unknown combo')) ?></div>
                    <?= chip((string)($row['app_count'] ?? 0) . ' apps', 'medium') ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="detail-grid section-tight">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Source Breakdown</h2>
            <p class="panel-subtitle">Where permission names are coming from in the current matrix.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Source</th>
                  <th class="col-num">Rows</th>
                  <th class="col-num">Distinct</th>
                  <th class="col-num">Apps</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($sourceBreakdown as $row): ?>
                  <tr>
                    <td><?= e((string)($row['source'] ?? '—')) ?></td>
                    <td class="col-num"><?= e((string)($row['permission_rows'] ?? 0)) ?></td>
                    <td class="col-num"><?= e((string)($row['distinct_permissions'] ?? 0)) ?></td>
                    <td class="col-num"><?= e((string)($row['app_count'] ?? 0)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <h2 class="panel-title">Protection Breakdown</h2>
            <p class="panel-subtitle">Most common permission protection levels in the current matrix.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Protection</th>
                  <th class="col-num">Rows</th>
                  <th class="col-num">Distinct</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($protectionBreakdown as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['protection'] ?? '—')) ?></td>
                    <td class="col-num"><?= e((string)($row['permission_rows'] ?? 0)) ?></td>
                    <td class="col-num"><?= e((string)($row['distinct_permissions'] ?? 0)) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
