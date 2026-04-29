<?php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$activeSessionUsable = $context['active_session_usable'];
$activeSessionRow = $context['active_session_row'];
$preferredSession = $context['preferred_session'];
$preferredSessionRow = $context['preferred_session_row'];
$newerIncompleteSessionRow = $context['newer_incomplete_session_row'];
$errorMsg = $context['error'];

$fileProviders = [];
$providerAcl = [];
$componentSummary = [
    'providers' => 0,
    'exported_providers' => 0,
    'weak_provider_guards' => 0,
    'acl_rows' => 0,
];

if ($packageName && $activeSession && !$errorMsg) {
    try {
        $fileProviders = app_fileproviders($packageName, $activeSession, 150);
        $providerAcl = app_provider_acl($packageName, $activeSession, 200);
        $componentSummary['providers'] = count($fileProviders);
        $componentSummary['acl_rows'] = count($providerAcl);
        foreach ($fileProviders as $row) {
            $exported = (int)($row['exported'] ?? 0) === 1;
            $guard = strtolower((string)($row['effective_guard'] ?? ''));
            if ($exported) {
                $componentSummary['exported_providers']++;
            }
            if ($exported && ($guard === '' || in_array($guard, ['none', 'weak'], true))) {
                $componentSummary['weak_provider_guards']++;
            }
        }
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app components failed: ' . $e);
    }
}

$PAGE_TITLE = $packageName ? ('Components: ' . $packageName) : 'App Components';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null || !is_array($app)): ?>
  <?php
  $title = 'App Components';
  $message = $packageName === null
    ? 'Choose an app to inspect exported providers and provider guard detail.'
    : 'This package is not available in the current app directory.';
  require __DIR__ . '/_partials/app_lookup_empty.php';
  ?>
<?php else: ?>
  <?php
  $activeTab = 'components';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_components.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Component Summary</h2>
          <p class="panel-subtitle">Provider exposure counts for the selected static session.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Providers</span><span class="metric-value"><?= e((string)$componentSummary['providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Exported Providers</span><span class="metric-value warn"><?= e((string)$componentSummary['exported_providers']) ?></span></div>
          <div class="metric-card"><span class="metric-label">Weak Guards</span><span class="metric-value bad"><?= e((string)$componentSummary['weak_provider_guards']) ?></span></div>
          <div class="metric-card"><span class="metric-label">ACL Rows</span><span class="metric-value"><?= e((string)$componentSummary['acl_rows']) ?></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Providers</h2>
          <p class="panel-subtitle">Exported state, authorities, and effective guard posture for persisted providers.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($fileProviders)): ?>
          <p class="muted">
            No provider rows were found for this package/session.
            <?php if (!$activeSessionUsable && $preferredSession): ?>
              Selected session is not finalized yet. Latest usable completed session:
              <a href="<?= e(url('pages/app_components.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode($preferredSession)) ?>"><?= e($preferredSession) ?></a>.
            <?php endif; ?>
          </p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Authority</th>
                  <th class="col-center">Exported</th>
                  <th>Guard</th>
                  <th>Risk</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($fileProviders as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['provider_name'] ?? $row['component_name'] ?? '')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['authority'] ?? '')) ?></td>
                    <td class="col-center"><?= e(((int)($row['exported'] ?? 0)) === 1 ? 'yes' : 'no') ?></td>
                    <td><?= e((string)($row['effective_guard'] ?? '—')) ?></td>
                    <td><?= e((string)($row['risk'] ?? '—')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Provider ACL Detail</h2>
          <p class="panel-subtitle">Read/write/base guard detail for persisted provider paths.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($providerAcl)): ?>
          <p class="muted">No provider ACL rows were found for this package/session.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Provider</th>
                  <th>Path</th>
                  <th class="col-center">Exported</th>
                  <th>Read Guard</th>
                  <th>Write Guard</th>
                  <th>Base Guard</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($providerAcl as $row): ?>
                  <?php
                  $baseGuard = (string)($row['base_perm'] ?? '—');
                  if ($baseGuard === '') {
                      $baseGuard = '—';
                  }
                  ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['provider_name'] ?? '')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['path'] ?? '—')) ?></td>
                    <td class="col-center"><?= e(((int)($row['exported'] ?? 0)) === 1 ? 'yes' : 'no') ?></td>
                    <td class="cell-clip"><?= e((string)($row['read_guard'] ?? $row['read_perm'] ?? '—')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['write_guard'] ?? $row['write_perm'] ?? '—')) ?></td>
                    <td class="cell-clip"><?= e($baseGuard) ?></td>
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
