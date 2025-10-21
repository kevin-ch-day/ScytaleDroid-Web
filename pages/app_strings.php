<?php
// pages/app_strings.php
require_once __DIR__ . '/../lib/header.php';
require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';

$pkg     = guard_str($_GET['pkg'] ?? null);
$session = guard_str($_GET['session'] ?? null);
?>

<h1>App Strings Intelligence</h1>

<div class="section">
    <?php if ($pkg === null || $session === null): ?>
        <p class="muted">Provide an app package and session to explore extracted strings.</p>
    <?php else: ?>
        <p>Insights for <strong><?= e($pkg) ?></strong> @ <strong><?= e($session) ?></strong> will appear here.</p>
        <p class="muted">Bucket filters and search will be implemented soon.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
