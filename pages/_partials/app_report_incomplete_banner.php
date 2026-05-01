  <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
    <div class="alert alert-warning">
      Selected session <?= e((string)$activeSession) ?> is not finalized for report use.
      Showing incomplete session context only. Latest usable completed session: <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>"><?= e((string)$preferredSession) ?></a>.
    </div>
  <?php endif; ?>
