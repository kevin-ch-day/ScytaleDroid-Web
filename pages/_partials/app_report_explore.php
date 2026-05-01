  <section class="section" id="explore">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Explore Details</h2>
          <p class="panel-subtitle">Use the dedicated app pages for the full evidence behind this summary.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="explore-grid">
          <article class="explore-card">
            <div class="app-primary">Findings</div>
            <p class="table-subline"><?= e(fmt_hml($selectedHigh, $selectedMed, $selectedLow, $selectedInfo)) ?> across the selected session.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_findings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Findings</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Components</div>
            <p class="table-subline"><?= e((string)$componentSummary['weak_provider_guards']) ?> weak-guard provider exposures out of <?= e((string)$componentSummary['exported_providers']) ?> exported providers.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_components.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Components</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Permissions</div>
            <p class="table-subline"><?= e((string)$permissionSummary['dangerous']) ?> dangerous and <?= e((string)$permissionSummary['signature_privileged']) ?> signature/privileged permissions.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_permissions.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Permissions</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Strings</div>
            <p class="table-subline"><?= e((string)$selectedHighEntropy) ?> high-entropy indicators and <?= e((string)($stringsSummary['endpoints'] ?? 0)) ?> endpoints.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_strings.php') . '?pkg=' . urlencode($packageName) . '&session=' . urlencode((string)$activeSession)) ?>">Open Strings</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Dynamic Runtime</div>
            <p class="table-subline"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?> runs available. Match level: package-level.</p>
            <a class="btn-ghost" href="<?= e(url('pages/app_dynamic.php') . '?pkg=' . urlencode($packageName)) ?>">Open Dynamic</a>
          </article>
          <article class="explore-card">
            <div class="app-primary">Run Health</div>
            <p class="table-subline"><?= e($sessionHealth['status']) ?> · <?= e($sessionUsabilitySummary) ?> for <?= e((string)$activeSession) ?>.</p>
            <a class="btn-ghost" href="<?= e(url('pages/run_health.php')) ?>">Open Run Health</a>
          </article>
        </div>
      </div>
    </div>
  </section>
