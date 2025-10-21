<?php
// pages/app_findings.php
require_once __DIR__ . '/../lib/header.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';

$pkg     = guard_str($_GET['pkg'] ?? null);
$session = guard_str($_GET['session'] ?? null);
?>

<h1>App Findings</h1>

<div class="section">
    <?php if ($pkg === null || $session === null): ?>
        <p class="muted">Choose an application and session to inspect static findings.</p>
    <?php else: ?>
        <p>Results for <strong><?= e($pkg) ?></strong> @ <strong><?= e($session) ?></strong> will appear here.</p>
        <p class="muted">Filters and pagination controls will be added in a future milestone.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
