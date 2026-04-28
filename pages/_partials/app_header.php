<?php
// pages/_partials/app_header.php
// Expected:
// $app (array<string,mixed>|null), $packageName (string), $activeSession (?string)
// Optional:
// $activeSessionRow (?array<string,mixed>)
$activeSessionRow = $activeSessionRow ?? null;
$appLabel = $app['app_label'] ?? $packageName;
$category = $app['category'] ?? 'Uncategorized';
$profile = $app['profile_label'] ?? 'Unclassified';
$grade = $activeSessionRow['grade'] ?? ($app['grade'] ?? null);
$score = $activeSessionRow['score_capped'] ?? ($app['score_capped'] ?? null);
$auditStamp = $activeSessionRow['audit_created_at'] ?? ($app['last_scanned'] ?? null);
$runStatus = $activeSessionRow['run_status'] ?? null;
$sessionCreated = $activeSessionRow['created_at'] ?? null;
$auditLabel = $activeSessionRow ? 'Selected session audit' : 'Latest permission audit';
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
        <?php if ($runStatus): ?>
          <?= status_chip((string)$runStatus) ?>
        <?php endif; ?>
        <?php if ($score !== null && $score !== ''): ?>
          <?= chip('Score ' . $score, 'medium') ?>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($auditStamp || $sessionCreated): ?>
      <div class="panel-body">
        <p class="muted">
          <?php if ($auditStamp): ?>
            <?= e($auditLabel) ?>: <?= e(fmt_date((string)$auditStamp)) ?>
          <?php else: ?>
            No permission-audit timestamp is available for the selected session.
          <?php endif; ?>
          <?php if ($sessionCreated): ?>
            <span class="muted"> · Static run created: <?= e(fmt_date((string)$sessionCreated)) ?></span>
          <?php endif; ?>
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>
