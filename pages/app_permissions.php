<?php
// pages/app_permissions.php
require_once __DIR__ . '/../lib/header.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';

$pkg     = guard_str($_GET['pkg'] ?? null);
$session = guard_str($_GET['session'] ?? null);
?>

<h1>App Permissions</h1>

<div class="section">
    <?php if ($pkg === null || $session === null): ?>
        <p class="muted">Select an app and session to review permissions snapshots.</p>
    <?php else: ?>
        <p>Permission matrices for <strong><?= e($pkg) ?></strong> @ <strong><?= e($session) ?></strong> will be displayed here.</p>
        <p class="muted">Risk group summaries and detailed tables are planned for the next milestone.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
