<?php
// Expected:
// $title (string)
// $message (string)
// Optional:
// $packageName (?string)
?>

<section class="section">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title"><?= e($title ?? 'App Detail') ?></h1>
        <p class="panel-subtitle"><?= e($message ?? 'Select an application to continue.') ?></p>
      </div>
    </div>
    <div class="panel-body">
      <form method="get" action="<?= e(url('pages/app_report.php')) ?>" class="lookup-form">
        <div class="lookup-grid">
          <input
            type="search"
            name="pkg"
            value="<?= e((string)($packageName ?? '')) ?>"
            placeholder="Enter package name, e.g. com.google.android.gms"
            aria-label="Package name"
          >
          <button type="submit" class="btn btn-primary">Open App</button>
          <a class="btn-ghost" href="<?= e(url('pages/index.php')) ?>">Apps Directory</a>
        </div>
      </form>
      <?php if (!empty($packageName)): ?>
        <p class="muted lookup-note">No app overview was found for <code><?= e((string)$packageName) ?></code>. Check the package name or return to the directory.</p>
      <?php else: ?>
        <p class="muted lookup-note">Use a package from the directory, or paste one directly here to jump into overview, findings, permissions, strings, and dynamic runs.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
