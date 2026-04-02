<?php
// pages/_partials/session_picker.php
// Expected:
// $packageName (string), $sessions (array), $activeSession (?string), $sessionPage (string)
?>

<?php if (!empty($sessions)): ?>
  <section class="section">
    <div class="neon-panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Sessions</h2>
          <p class="panel-subtitle">Switch between recorded static-analysis sessions for this package.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="session-list">
          <?php foreach ($sessions as $row): ?>
            <?php
            $stamp = (string)($row['session_stamp'] ?? '');
            $href = url('pages/' . ltrim($sessionPage, '/')) . '?pkg=' . urlencode($packageName) . '&session=' . urlencode($stamp);
            $active = $stamp !== '' && $stamp === $activeSession;
            ?>
            <a class="session-link<?= $active ? ' is-active' : '' ?>" href="<?= e($href) ?>">
              <span class="session-link-main"><?= e($stamp) ?></span>
              <span class="session-link-meta">
                <?= e((string)($row['run_status'] ?? 'UNKNOWN')) ?>
                · H/M/L <?= e(fmt_hml((int)($row['high'] ?? 0), (int)($row['med'] ?? 0), (int)($row['low'] ?? 0), (int)($row['info'] ?? 0))) ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>
