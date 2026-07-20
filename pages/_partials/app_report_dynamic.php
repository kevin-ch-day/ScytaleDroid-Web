  <?php
  $dynamicServiceSummary = is_array($dynamicServiceSummary ?? null) ? $dynamicServiceSummary : [];
  $dynamicSignalSummary = is_array($dynamicSignalSummary ?? null) ? $dynamicSignalSummary : [];
  $dynamicDomainContext = is_array($dynamicDomainContext ?? null) ? $dynamicDomainContext : [];
  $dynamicDomainHitCount = 0;
  $dynamicRootDomains = [];
  foreach ($dynamicDomainContext as $row) {
      $dynamicDomainHitCount += (int)($row['total_indicator_hits'] ?? 0);
      $root = trim((string)($row['root_domain'] ?? ''));
      if ($root !== '') {
          $dynamicRootDomains[strtolower($root)] = true;
      }
  }
  ?>
  <section class="section" id="dynamic">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Dynamic Runtime</h2>
          <p class="panel-subtitle">Runtime availability, observed network destinations, and service context for this package.</p>
        </div>
        <div class="panel-actions">
          <a class="btn-ghost" href="<?= e(url('pages/app_dynamic.php') . '?pkg=' . urlencode($packageName)) ?>">Open Dynamic</a>
        </div>
      </div>
      <div class="panel-body">
        <?php if (!empty($dynamicPayloadError ?? null)): ?>
          <div class="alert alert-warning"><?= e((string)$dynamicPayloadError) ?></div>
        <?php else: ?>
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Dynamic Runs</span><span class="metric-value"><?= e((string)($dynamicSummary['dynamic_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Valid PCAPs</span><span class="metric-value"><?= e((string)($dynamicSummary['valid_pcaps'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Quota-valid / Supplemental</span><span class="metric-value"><?= e((string)($dynamicSummary['quota_valid_runs'] ?? 0)) ?> / <?= e((string)($dynamicSummary['supplemental_valid_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Static Linked / Features Ready</span><span class="metric-value"><?= e((string)($dynamicSummary['static_linked_runs'] ?? 0)) ?> / <?= e((string)($dynamicSummary['features_available_runs'] ?? 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Top Domains / Roots</span><span class="metric-value"><?= e((string)count($dynamicDomainContext)) ?> / <?= e((string)count($dynamicRootDomains)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Mapped Services</span><span class="metric-value"><?= e((string)count($dynamicServiceSummary)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Mapped Signals</span><span class="metric-value"><?= e((string)count($dynamicSignalSummary)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Top Domain Hits</span><span class="metric-value"><?= e(number_format((float)$dynamicDomainHitCount, 0)) ?></span></div>
        </div>
        <?php if (((int)($dynamicSummary['dynamic_runs'] ?? 0)) > 0): ?>
          <p class="inline-hint top-gap">
            Dynamic data is available for this package. This summary is package-level only and may not represent the exact same app version or APK artifact as the selected static session.
          </p>
          <?php if (!empty($dynamicSignalSummary)): ?>
            <h3 class="top-gap">Top Network Signals</h3>
            <div class="detail-stack compact-stack">
              <?php foreach ($dynamicSignalSummary as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><?= e((string)($row['display_name'] ?? $row['signal_key'] ?? '')) ?></div>
                      <div class="table-subline">
                        <?= e(runtime_format_csv($row['focus_area'] ?? '')) ?> ·
                        <?= e(runtime_format_csv($row['severity_hint'] ?? '')) ?> ·
                        domains <?= e((string)($row['distinct_domains'] ?? 0)) ?> ·
                        runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>
                      </div>
                      <div class="table-subline"><?= e(runtime_format_csv($row['service_names_csv'] ?? '')) ?></div>
                    </div>
                    <span class="metric-value"><?= e(number_format((float)($row['total_indicator_hits'] ?? 0), 0)) ?></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($dynamicServiceSummary)): ?>
            <h3 class="top-gap">Top Network Services</h3>
            <div class="detail-stack compact-stack">
              <?php foreach ($dynamicServiceSummary as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><?= e((string)($row['display_name'] ?? $row['service_key'] ?? '')) ?></div>
                      <div class="table-subline">
                        <?= e((string)($row['owner_name'] ?? '-')) ?> ·
                        <?= e(runtime_format_csv($row['service_category'] ?? '')) ?> ·
                        domains <?= e((string)($row['distinct_domains'] ?? 0)) ?> ·
                        runs <?= e((string)($row['observed_run_count'] ?? 0)) ?>
                      </div>
                      <div class="table-subline"><?= e(runtime_format_csv($row['signal_names_csv'] ?? $row['signal_keys_csv'] ?? '')) ?></div>
                    </div>
                    <span class="metric-value"><?= e(number_format((float)($row['total_indicator_hits'] ?? 0), 0)) ?></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($dynamicDomainContext)): ?>
            <h3 class="top-gap">Top Domains</h3>
            <div class="detail-stack compact-stack">
              <?php foreach ($dynamicDomainContext as $row): ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><?= e((string)($row['observed_domain'] ?? '')) ?></div>
                      <div class="table-subline">
                        <?= e((string)($row['root_domain'] ?? '-')) ?> ·
                        <?= e(runtime_format_csv($row['owner_classes_csv'] ?? '')) ?> ·
                        <?= e(runtime_format_csv($row['role_classes_csv'] ?? '')) ?>
                      </div>
                      <div class="table-subline">
                        <?= e(runtime_format_csv($row['service_names_csv'] ?? $row['service_keys_csv'] ?? '')) ?> ·
                        <?= e(runtime_format_csv($row['signal_names_csv'] ?? $row['signal_keys_csv'] ?? '')) ?>
                      </div>
                    </div>
                    <span class="metric-value"><?= e(number_format((float)($row['total_indicator_hits'] ?? 0), 0)) ?></span>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($dynamicRuns)): ?>
            <div class="detail-stack compact-stack top-gap">
              <?php foreach ($dynamicRuns as $row): ?>
                <?php $runId = (string)($row['dynamic_run_id'] ?? ''); ?>
                <article class="card compact-card">
                  <div class="compact-row">
                    <div>
                      <div class="app-primary"><a href="<?= e(url('pages/dynamic_run.php') . '?run=' . urlencode($runId)) ?>"><?= e($runId) ?></a></div>
                      <div class="table-subline"><?= e((string)($row['run_profile'] ?? 'unknown')) ?> · <?= e(fmt_date((string)($row['started_at_utc'] ?? ''))) ?></div>
                    </div>
                    <?= status_chip((string)($row['status'] ?? 'UNKNOWN')) ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <p class="muted top-gap">No dynamic runtime rows are available for this package yet.</p>
        <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
