<?php
// pages/app_strings.php
require_once __DIR__ . '/../lib/app_detail.php';
require_once __DIR__ . '/../lib/render.php';

$context = load_app_detail_context($_GET['pkg'] ?? null, $_GET['session'] ?? null);
$packageName = $context['package_name'];
$app = $context['app'];
$sessions = $context['sessions'];
$activeSession = $context['active_session'];
$errorMsg = $context['error'];

$summary = null;
$samples = [];
if ($packageName && $activeSession && !$errorMsg) {
    try {
        $summary = app_strings_summary($packageName, $activeSession);
        $samples = app_string_samples($packageName, $activeSession, 80);
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] app strings failed: ' . $e);
    }
}

$PAGE_TITLE = $packageName ? ('Strings: ' . $packageName) : 'App Strings';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($packageName === null): ?>
  <section class="section"><div class="neon-panel"><div class="panel-body"><p class="muted">Choose an app to explore persisted strings intelligence.</p></div></div></section>
<?php else: ?>
  <?php
  $activeTab = 'strings';
  require __DIR__ . '/_partials/app_header.php';
  require __DIR__ . '/_partials/tabs_nav.php';
  $sessionPage = 'app_strings.php';
  require __DIR__ . '/_partials/session_picker.php';
  ?>

  <section class="section">
    <div class="neon-panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">String Summary</h2>
          <p class="panel-subtitle">Persisted bucket counts for the selected session.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if ($summary === null): ?>
          <p class="muted">No strings summary is available for this package/session.</p>
        <?php else: ?>
          <div class="metrics-grid">
            <div class="metric-card"><span class="metric-label">High Entropy</span><span class="metric-value warn"><?= e((string)($summary['high_entropy'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Endpoints</span><span class="metric-value"><?= e((string)($summary['endpoints'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Cloud Refs</span><span class="metric-value"><?= e((string)($summary['cloud_refs'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">HTTP Cleartext</span><span class="metric-value bad"><?= e((string)($summary['http_cleartext'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">Flags</span><span class="metric-value"><?= e((string)($summary['flags'] ?? '0')) ?></span></div>
            <div class="metric-card"><span class="metric-label">URIs</span><span class="metric-value"><?= e((string)($summary['uris'] ?? '0')) ?></span></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="neon-panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Selected Samples</h2>
          <p class="panel-subtitle">Representative masked strings persisted for this run.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($samples)): ?>
          <p class="muted">No string samples were found for this package/session.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Bucket</th>
                  <th>Value</th>
                  <th>Source</th>
                  <th>Metadata</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($samples as $row): ?>
                  <?php
                  $meta = array_filter([
                      $row['provider'] ?? null,
                      $row['risk_tag'] ?? null,
                      $row['confidence'] ?? null,
                      $row['scheme'] ?? null,
                  ], static fn($v) => $v !== null && $v !== '');
                  ?>
                  <tr>
                    <td><?= e((string)($row['bucket'] ?? 'unknown')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['value_masked'] ?? '')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['src'] ?? '')) ?></td>
                    <td><?= e($meta ? implode(' · ', $meta) : '—') ?></td>
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
