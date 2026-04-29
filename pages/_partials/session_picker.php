<?php
// pages/_partials/session_picker.php
// Expected:
// $packageName (string), $sessions (array), $activeSession (?string), $sessionPage (string)
// $activeSessionRow (?array), $activeSessionUsable (bool), $preferredSession (?string), $preferredSessionRow (?array), $newerIncompleteSessionRow (?array)
?>

<?php if (!empty($sessions)): ?>
  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Data Source</h2>
          <p class="panel-subtitle">This report uses one static session across Overview, Findings, Permissions, Components, and Strings.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (!empty($activeSessionRow)): ?>
          <div class="session-summary">
            <div class="session-summary-main">
              <div class="app-primary"><?= e((string)($activeSessionRow['session_stamp'] ?? '')) ?></div>
              <div class="table-subline">
                <?= e((string)($activeSessionRow['run_status'] ?? 'UNKNOWN')) ?>
                · H/M/L <?= e(fmt_hml((int)($activeSessionRow['high'] ?? 0), (int)($activeSessionRow['med'] ?? 0), (int)($activeSessionRow['low'] ?? 0), (int)($activeSessionRow['info'] ?? 0))) ?>
              </div>
            </div>
            <div class="chip-row">
              <?= status_chip((string)($activeSessionRow['run_status'] ?? 'UNKNOWN')) ?>
              <?= session_usability_chip((string)($activeSessionRow['session_usability'] ?? 'unknown')) ?>
              <?php if (!empty($activeSessionRow['grade']) && $activeSessionUsable): ?>
                <?= chip('Audit ' . (string)$activeSessionRow['grade'], 'low') ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if (!$activeSessionUsable && !empty($preferredSessionRow)): ?>
          <?php
          $preferredStamp = (string)($preferredSessionRow['session_stamp'] ?? '');
          $switchHref = url('pages/' . ltrim($sessionPage, '/')) . '?pkg=' . urlencode($packageName) . '&session=' . urlencode($preferredStamp);
          ?>
          <div class="alert alert-warning">
            Selected session <?= e((string)($activeSessionRow['session_stamp'] ?? '')) ?> is not finalized yet.
            Static findings, permission rows, and string summaries are incomplete.
            Latest usable completed session: <strong><?= e($preferredStamp) ?></strong>.
            <a href="<?= e($switchHref) ?>">Switch to latest completed</a>
          </div>
        <?php elseif (!empty($newerIncompleteSessionRow) && !empty($preferredSessionRow)): ?>
          <div class="alert alert-warning">
            A newer incomplete session exists: <strong><?= e((string)($newerIncompleteSessionRow['session_stamp'] ?? '')) ?></strong>.
            This report is using the latest completed usable session instead.
          </div>
        <?php endif; ?>

        <details class="session-browser">
          <summary>Change session</summary>
          <div class="session-browser-groups">
            <?php
            $recommended = [];
            $incomplete = [];
            $historical = [];
            $failed = [];
            foreach ($sessions as $row) {
                $state = strtolower((string)($row['session_usability'] ?? ''));
                if ((int)($row['is_usable_complete'] ?? 0) === 1) {
                    $historical[] = $row;
                } elseif ($state === 'in_progress_no_rows') {
                    $incomplete[] = $row;
                } elseif ($state === 'failed') {
                    $failed[] = $row;
                } else {
                    $historical[] = $row;
                }
            }
            if (!empty($preferredSessionRow)) {
                $recommended[] = $preferredSessionRow;
                $historical = array_values(array_filter(
                    $historical,
                    static fn(array $row): bool => (string)($row['session_stamp'] ?? '') !== (string)($preferredSessionRow['session_stamp'] ?? '')
                ));
            }

            $groups = [
                'Recommended' => $recommended,
                'Incomplete' => $incomplete,
                'Historical' => $historical,
                'Failed' => $failed,
            ];
            ?>
            <?php foreach ($groups as $label => $groupRows): ?>
              <?php if (empty($groupRows)) continue; ?>
              <div class="session-group">
                <div class="sidebar-section-title"><?= e($label) ?></div>
                <div class="session-list">
                  <?php foreach ($groupRows as $row): ?>
                    <?php
                    $stamp = (string)($row['session_stamp'] ?? '');
                    $href = url('pages/' . ltrim($sessionPage, '/')) . '?pkg=' . urlencode($packageName) . '&session=' . urlencode($stamp);
                    $active = $stamp !== '' && $stamp === $activeSession;
                    ?>
                    <a class="session-link<?= $active ? ' is-active' : '' ?>" href="<?= e($href) ?>">
                      <span class="session-link-main"><?= e($stamp) ?></span>
                      <span class="session-link-meta">
                        <?= e((string)($row['run_status'] ?? 'UNKNOWN')) ?>
                        · <?= e(fmt_hml((int)($row['high'] ?? 0), (int)($row['med'] ?? 0), (int)($row['low'] ?? 0), (int)($row['info'] ?? 0))) ?>
                      </span>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </details>
      </div>
    </div>
  </section>
<?php endif; ?>
