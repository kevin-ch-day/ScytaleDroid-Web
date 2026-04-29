<?php
// pages/about.php
$PAGE_TITLE = 'About';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">About ScytaleDroid-Web</h1>
        <p class="panel-subtitle">Read-only analysis console for persisted ScytaleDroid results.</p>
      </div>
    </div>
    <div class="panel-body detail-stack">
      <p>ScytaleDroid-Web is a lightweight PHP interface for browsing static exposure results, runtime-deviation runs, package-level risk summaries, permissions, and strings intelligence stored in the ScytaleDroid database.</p>
      <p class="muted">This UI is intentionally read-only. The database is the primary application data source; filesystem JSON/CSV artifacts should remain export, archive, or fast-local sidecars.</p>
      <div class="detail-kv">
        <div><dt>Home Route</dt><dd><a href="<?= e(url('pages/index.php')) ?>">Home / Fleet Dashboard</a></dd></div>
        <div><dt>Runtime Route</dt><dd><a href="<?= e(url('pages/dynamic.php')) ?>">Runtime Deviation</a></dd></div>
        <div><dt>Report Views</dt><dd>Overview, Findings, Permissions, Strings, Dynamic Runs</dd></div>
        <div><dt>Backend</dt><dd>PHP + PDO MySQL/MariaDB</dd></div>
        <div><dt>Mode</dt><dd>Read-only operator console</dd></div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
