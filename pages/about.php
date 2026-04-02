<?php
// pages/about.php
$PAGE_TITLE = 'About';
require_once __DIR__ . '/../lib/header.php';
?>

<section class="section">
  <div class="neon-panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title">About ScytaleDroid-Web</h1>
        <p class="panel-subtitle">Read-only analysis console for persisted ScytaleDroid results.</p>
      </div>
    </div>
    <div class="panel-body detail-stack">
      <p>ScytaleDroid-Web is a lightweight PHP interface for browsing package-level risk summaries, static findings, permissions, and strings intelligence stored in the ScytaleDroid database.</p>
      <p class="muted">This UI is intentionally read-only. Filesystem artifacts remain authoritative in the main platform; the web app is an exploration surface over derived database state.</p>
      <div class="detail-kv">
        <div><dt>Primary Route</dt><dd><a href="<?= e(url('pages/index.php')) ?>">Apps Directory</a></dd></div>
        <div><dt>Detail Views</dt><dd>Overview, Findings, Permissions, Strings</dd></div>
        <div><dt>Backend</dt><dd>PHP + PDO MySQL/MariaDB</dd></div>
        <div><dt>Mode</dt><dd>Read-only operator console</dd></div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
