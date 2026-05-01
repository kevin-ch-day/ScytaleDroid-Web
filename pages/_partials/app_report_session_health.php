  <section class="section detail-grid" id="session-health">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Data Completeness</h2>
          <p class="panel-subtitle">Quick trust view for the selected session. Full diagnostics live on Run Health.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e($runHealthUrl) ?>">Open Run Health</a>
        </div>
      </div>
      <div class="panel-body">
        <div class="coverage-grid">
          <div class="coverage-item">
            <?= chip($sessionHealth['findings_total'] > 0 ? 'Findings present' : 'Findings missing', $sessionHealth['findings_total'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['findings_total']) ?> persisted rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['permission_rows'] > 0 ? 'Permissions present' : 'Permissions missing', $sessionHealth['permission_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['permission_rows']) ?> matrix rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['string_rows'] > 0 ? 'Strings present' : 'Strings missing', $sessionHealth['string_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['string_rows']) ?> summary rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($componentSummary['providers'] > 0 ? 'Components present' : 'Components missing', $componentSummary['providers'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$componentSummary['providers']) ?> provider rows</p>
          </div>
          <div class="coverage-item">
            <?= chip($sessionHealth['audit_rows'] > 0 ? 'Evidence present' : 'Evidence incomplete', $sessionHealth['audit_rows'] > 0 ? 'info' : 'medium') ?>
            <p class="table-subline"><?= e((string)$sessionHealth['audit_rows']) ?> audit rows</p>
          </div>
          <div class="coverage-item">
            <span title="<?= e($sessionUsabilityHint) ?>"><?= session_usability_chip($sessionUsability) ?></span>
            <p class="table-subline"><?= e($sessionUsabilitySummary) ?></p>
          </div>
        </div>
        <?php if ($showFindingReconcile): ?>
          <div class="card compact-card top-gap">
            <div class="app-primary">Finding persistence reconcile</div>
            <p class="table-subline">Runtime vs capped counts mirrored on <code>static_analysis_runs</code> after finalization.</p>
            <?php if ($findingPersistenceRuntime !== null && $findingPersistenceRuntime !== ''): ?>
              <p class="muted">Runtime detector total recorded for this run: <strong><?= e((string)$findingPersistenceRuntime) ?></strong>.</p>
            <?php endif; ?>
            <?php if ($findingPersistenceCapped !== null && $findingPersistenceCapped > 0): ?>
              <p class="muted">Not persisted beyond cap: <strong><?= e((string)$findingPersistenceCapped) ?></strong> finding rows.</p>
            <?php endif; ?>
            <?php if ($findingPersistenceCappedText !== ''): ?>
              <p class="pre-wrap muted"><?= e($findingPersistenceCappedText) ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (!$activeSessionUsable && !empty($preferredSession)): ?>
          <div class="alert alert-warning top-gap">
            Selected session <?= e((string)$activeSession) ?> is not finalized for app-report use.
            Findings, permissions, or strings are incomplete.
            <a href="<?= e(url('pages/app_report.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$preferredSession)) ?>">Switch to latest completed session</a>
            or review the fleet status in <a href="<?= e($runHealthUrl) ?>">Run Health</a>.
          </div>
        <?php elseif ($sessionHealth['findings_total'] === 0 || $sessionHealth['permission_rows'] === 0 || $sessionHealth['string_rows'] === 0): ?>
          <div class="alert alert-warning top-gap">
            This summary is missing one or more expected data surfaces for the selected session.
            Use <a href="<?= e($runHealthUrl) ?>">Run Health</a> to confirm whether the session is partial, missing rows, or still reconciling.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Build Metadata</h2>
          <p class="panel-subtitle">Version, SDK, and session metadata for this summary source.</p>
        </div>
      </div>
      <div class="panel-body">
        <dl class="detail-kv">
          <div><dt>Version Name</dt><dd><?= e((string)($details['version_name'] ?? '—')) ?></dd></div>
          <div><dt>Version Code</dt><dd><?= e((string)($details['version_code'] ?? '—')) ?></dd></div>
          <div><dt>Target SDK</dt><dd><?= e((string)($details['target_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Min SDK</dt><dd><?= e((string)($details['min_sdk'] ?? '—')) ?></dd></div>
          <div><dt>Session Type</dt><dd><?= e($sessionType) ?></dd></div>
          <div><dt>Selected Static Session</dt><dd><?= e((string)$activeSession) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>
