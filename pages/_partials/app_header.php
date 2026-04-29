<?php
// pages/_partials/app_header.php
// Expected:
// $app (array<string,mixed>|null), $packageName (string), $activeSession (?string)
// Optional:
// $activeSessionRow (?array<string,mixed>)
$activeSessionUsable = $activeSessionUsable ?? false;
$preferredSessionRow = $preferredSessionRow ?? null;
$activeSessionRow = $activeSessionRow ?? null;
$appLabel = $app['app_label'] ?? $packageName;
$category = $app['category'] ?? 'Uncategorized';
$profile = $app['profile_label'] ?? 'Unclassified';
$grade = $activeSessionUsable ? ($activeSessionRow['grade'] ?? ($app['grade'] ?? null)) : ($app['grade'] ?? null);
$score = $activeSessionUsable ? ($activeSessionRow['score_capped'] ?? ($app['score_capped'] ?? null)) : ($app['score_capped'] ?? null);
$auditStamp = $activeSessionUsable ? ($activeSessionRow['audit_created_at'] ?? ($app['last_scanned'] ?? null)) : ($app['last_scanned'] ?? null);
$runStatus = $activeSessionRow['run_status'] ?? null;
$sessionCreated = $activeSessionRow['created_at'] ?? null;
$auditLabel = $activeSessionUsable ? 'Selected session audit' : 'Latest completed audit';
$activeSessionState = (string)($activeSessionRow['session_usability'] ?? 'unknown');
$activeSessionStateSummary = session_usability_summary_text($activeSessionState);
$activeSessionStateHint = session_usability_hint($activeSessionState);
$activeSessionTypeHint = session_type_hint((string)$activeSession, (string)($activeSessionRow['profile'] ?? ''));
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
          <?= chip('Data source ' . $activeSession, 'info') ?>
          <span title="<?= e($activeSessionTypeHint) ?>"><?= session_type_chip((string)$activeSession, (string)($activeSessionRow['profile'] ?? '')) ?></span>
        <?php endif; ?>
        <?php if ($runStatus): ?>
          <?= status_chip((string)$runStatus) ?>
        <?php endif; ?>
        <?php if ($activeSessionRow): ?>
          <span title="<?= e($activeSessionStateHint) ?>"><?= session_usability_chip($activeSessionState) ?></span>
        <?php endif; ?>
        <?php if ($score !== null && $score !== ''): ?>
          <?= chip('Score ' . $score, 'medium') ?>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($auditStamp || $sessionCreated): ?>
      <div class="panel-body">
        <p class="muted">
          <?php if ($activeSessionRow): ?>
            <span class="muted"><?= e($activeSessionStateSummary) ?></span>
            <span class="muted"> · </span>
          <?php endif; ?>
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
