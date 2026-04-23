<?php
// pages/_partials/app_header.php
// Expected:
// $app (array<string,mixed>|null), $packageName (string), $activeSession (?string)
$appLabel = $app['app_label'] ?? $packageName;
$category = $app['category'] ?? 'Uncategorized';
$profile = $app['profile_label'] ?? 'Unclassified';
$grade = $app['grade'] ?? null;
$score = $app['score_capped'] ?? null;
$latestAudit = $app['last_scanned'] ?? null;
?>

<section class="section detail-hero">
  <div class="panel">
    <div class="panel-header">
      <div>
        <h1 class="panel-title"><?= e($appLabel) ?></h1>
        <p class="panel-subtitle">
          <strong><?= e($packageName) ?></strong>
          <span class="muted">· <?= e($category) ?></span>
          <span class="muted">· <?= e($profile) ?></span>
        </p>
      </div>
      <div class="panel-actions chip-row">
        <?= grade_badge(is_string($grade) ? $grade : null) ?>
        <?php if ($activeSession): ?>
          <?= chip('Session ' . $activeSession, 'info') ?>
        <?php endif; ?>
        <?php if ($score !== null && $score !== ''): ?>
          <?= chip('Score ' . $score, 'medium') ?>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($latestAudit): ?>
      <div class="panel-body">
        <p class="muted">Latest permission audit: <?= e(fmt_date((string)$latestAudit)) ?></p>
      </div>
    <?php endif; ?>
  </div>
</section>
