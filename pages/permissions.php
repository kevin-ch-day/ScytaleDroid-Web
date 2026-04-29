<?php
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$errorMsg = null;
$selectedSession = trim((string)($_GET['session'] ?? ''));
$overview = [];
$sourceMeta = [];
$sessionOptions = [];
$topDangerous = [];
$sourceBreakdown = [];
$customBreakdown = [];
$protectionBreakdown = [];
$sensitiveCombos = [];

try {
    $sessionOptions = permission_intel_session_options();
    $overview = permission_intel_overview_for_session($selectedSession !== '' ? $selectedSession : null);
    $sourceMeta = permission_intel_source_meta_for_session($selectedSession !== '' ? $selectedSession : null);
    $topDangerous = permission_intel_top_dangerous_for_session($selectedSession !== '' ? $selectedSession : null, 15);
    $sourceBreakdown = permission_intel_source_breakdown_for_session($selectedSession !== '' ? $selectedSession : null, 10);
    $customBreakdown = permission_intel_custom_breakdown_for_session($selectedSession !== '' ? $selectedSession : null, 10);
    $protectionBreakdown = permission_intel_protection_breakdown_for_session($selectedSession !== '' ? $selectedSession : null, 10);
    $sensitiveCombos = permission_intel_sensitive_combos_for_session($selectedSession !== '' ? $selectedSession : null, 10);
} catch (Throwable $e) {
    $errorMsg = 'DB error: ' . $e->getMessage();
    error_log('[ScytaleDroid-Web] permission intelligence failed: ' . $e);
}

$surfaceMode = $selectedSession !== '' ? 'session' : 'preferred';
$surfaceLabel = $surfaceMode === 'session'
    ? $selectedSession
    : 'Current preferred usable app sessions';
$sessionCount = (int)($overview['session_count'] ?? 0);
$latestCreatedAt = trim((string)($sourceMeta['latest_created_at'] ?? ''));
$latestSessionStamp = trim((string)($sourceMeta['latest_session_stamp'] ?? $sourceMeta['session_stamp'] ?? ''));
$sourceStatus = trim((string)($sourceMeta['run_status'] ?? 'COMPLETED'));
$sourceUsability = trim((string)($sourceMeta['session_usability'] ?? ($surfaceMode === 'session' ? 'usable_complete' : 'preferred_usable_complete')));

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
          <p class="panel-subtitle">Cross-app view of requested permissions, protection levels, source families, and sensitive combinations from the current permission surface.</p>
        </div>
      </div>
      <div class="panel-body">
        <form method="get" class="filters-grid filters-grid-tight">
          <label class="filter-field">
            <span class="filter-label">Session</span>
            <select name="session">
              <option value="">Current preferred usable app sessions</option>
              <?php foreach ($sessionOptions as $row): ?>
                <?php $sessionValue = (string)($row['session_stamp'] ?? ''); ?>
                <option value="<?= e($sessionValue) ?>" <?= $selectedSession === $sessionValue ? 'selected' : '' ?>>
                  <?= e($sessionValue) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <div class="filter-actions">
            <button type="submit" class="btn btn-primary">Apply</button>
            <a class="btn btn-secondary" href="permissions.php">Clear</a>
          </div>
        </form>

        <div class="detail-grid section-tight">
          <article class="card compact-card">
            <div class="metric-label">Data source</div>
            <div class="app-primary"><?= e($surfaceLabel) ?></div>
            <div class="muted">
              <?= $surfaceMode === 'session'
                ? 'Single explicit static session from v_web_app_permissions.'
                : 'One preferred usable completed static session per app from v_web_permission_intel_current.' ?>
            </div>
          </article>
          <article class="card compact-card">
            <div class="metric-label">Scope</div>
            <div class="app-primary"><?= e((string)($overview['apps_with_permissions'] ?? 0)) ?> apps</div>
            <div class="muted">
              <?= $surfaceMode === 'session'
                ? 'Exact session slice only.'
                : e((string)$sessionCount) . ' distinct session(s) represented; QA/smoke/debug hidden by default.' ?>
            </div>
          </article>
          <article class="card compact-card">
            <div class="metric-label">Status</div>
            <div class="app-primary">
              <?= e($surfaceMode === 'session' ? $sourceStatus : 'COMPLETED / preferred usable') ?>
            </div>
            <div class="muted">
              <?= e($sourceUsability !== '' ? $sourceUsability : 'usable_complete') ?>
              <?php if ($latestCreatedAt !== ''): ?>
                · last finalized <?= e($latestCreatedAt) ?>
              <?php endif; ?>
            </div>
          </article>
          <article class="card compact-card">
            <div class="metric-label">Custom families</div>
            <div class="app-primary">
              <?= e((string)($overview['app_defined_distinct'] ?? 0)) ?> app-defined /
              <?= e((string)($overview['vendor_custom_distinct'] ?? 0)) ?> vendor /
              <?= e((string)($overview['google_custom_distinct'] ?? 0)) ?> Google /
              <?= e((string)($overview['unknown_custom_distinct'] ?? 0)) ?> unknown
            </div>
            <div class="muted">Distinct custom permissions by derived family.</div>
          </article>
          <article class="card compact-card">
            <div class="metric-label">Selection rules</div>
            <div class="app-primary"><?= e($surfaceMode === 'session' ? ($latestSessionStamp !== '' ? $latestSessionStamp : $surfaceLabel) : 'Catalog-only excluded') ?></div>
            <div class="muted">
              <?= $surfaceMode === 'session'
                ? 'Single selected static session; hidden QA/smoke/debug sessions not offered by default.'
                : 'Catalog-only rows excluded; hidden QA/smoke/debug sessions excluded; one usable completed session per app.' ?>
            </div>
          </article>
        </div>

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
                    <td><?= e((string)($row['source_family'] ?? $row['source'] ?? '—')) ?></td>
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
            <p class="panel-subtitle">Derived source families for the current permission surface.</p>
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
            <h2 class="panel-title">Custom Permission Families</h2>
            <p class="panel-subtitle">Split custom permissions into app-defined, vendor/OEM, Google-adjacent, and unknown buckets.</p>
          </div>
        </div>
        <div class="panel-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Custom family</th>
                  <th class="col-num">Rows</th>
                  <th class="col-num">Distinct</th>
                  <th class="col-num">Apps</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($customBreakdown as $row): ?>
                  <tr>
                    <td><?= e(permission_custom_family_label((string)($row['custom_family'] ?? ''))) ?></td>
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
    </div>

    <div class="detail-grid section-tight">
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
