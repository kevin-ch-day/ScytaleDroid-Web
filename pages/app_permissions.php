<?php
// pages/app_permissions.php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$errorMsg = $context['error'];

$rows = [];
$counts = ['dangerous' => 0, 'signature' => 0, 'privileged' => 0, 'custom' => 0];
if ($packageName && $activeSession && !$errorMsg) {
    try {
        $rows = app_permissions($packageName, $activeSession, 300);
        foreach ($rows as $row) {
            $counts['dangerous'] += (int)($row['is_runtime_dangerous'] ?? 0);
            $counts['signature'] += (int)($row['is_signature'] ?? 0);
            $counts['privileged'] += (int)($row['is_privileged'] ?? 0);
            $counts['custom'] += (int)($row['is_custom'] ?? 0);
        }
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app permissions failed: ' . $e);
    }
}

$PAGE_TITLE = $packageName ? ('Permissions: ' . $packageName) : 'App Permissions';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null): ?>
  <section class="section"><div class="panel"><div class="panel-body"><p class="muted">Choose an app to review its persisted permission matrix.</p></div></div></section>
<?php else: ?>
  <?php
  $activeTab = 'permissions';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_permissions.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Permission Summary</h2>
          <p class="panel-subtitle">Classification counts for the selected static session.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Dangerous</span><span class="metric-value bad"><?= e((string)$counts['dangerous']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Signature</span><span class="metric-value warn"><?= e((string)$counts['signature']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Privileged</span><span class="metric-value info"><?= e((string)$counts['privileged']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Custom</span><span class="metric-value"><?= e((string)$counts['custom']) ?></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Permission Matrix</h2>
          <p class="panel-subtitle">Persisted permission rows for the selected run.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($rows)): ?>
          <p class="muted">No permission-matrix rows were found for this package/session.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Permission</th>
                  <th>Protection</th>
                  <th>Source</th>
                  <th>Severity</th>
                  <th>Flags</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $row): ?>
                  <?php
                  $flags = [];
                  if (!empty($row['is_runtime_dangerous'])) $flags[] = 'dangerous';
                  if (!empty($row['is_signature'])) $flags[] = 'signature';
                  if (!empty($row['is_privileged'])) $flags[] = 'privileged';
                  if (!empty($row['is_special_access'])) $flags[] = 'special';
                  if (!empty($row['is_custom'])) $flags[] = 'custom';
                  ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['permission_name'] ?? '')) ?></td>
                    <td><?= e((string)($row['protection'] ?? '—')) ?></td>
                    <td><?= e((string)($row['source'] ?? '—')) ?></td>
                    <td><?= e((string)($row['severity'] ?? '0')) ?></td>
                    <td><?= e($flags ? implode(', ', $flags) : '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
