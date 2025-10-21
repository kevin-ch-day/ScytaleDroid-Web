<?php
// pages/view_app.php
require_once __DIR__ . '/../lib/header.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';

$pkg = guard_str($_GET['pkg'] ?? null);
$session = guard_str($_GET['session'] ?? null);
?>

<h1>App Detail</h1>

<div class="section">
    <?php if ($pkg === null): ?>
        <p class="muted">Select an application from the directory to view details.</p>
    <?php else: ?>
        <p class="muted">Package: <strong><?= e($pkg) ?></strong></p>
        <?php if ($session): ?>
            <p class="muted">Session: <strong><?= e($session) ?></strong></p>
        <?php else: ?>
            <p class="muted">Session: <em>latest</em></p>
        <?php endif; ?>
        <p>Detailed findings, strings, and permissions views will appear here.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
