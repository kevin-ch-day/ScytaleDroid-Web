<?php
// pages/dynamic_run.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$runId = guard_dynamic_run_id($_GET['run'] ?? null);
$run = null;
$indicators = [];
$domainContext = [];
$issues = [];
$cohorts = [];
$models = [];
$regimes = [];
$errorMsg = null;

if ($runId !== null) {
    try {
        $run = dynamic_run_detail($runId);
        if ($run !== null) {
            $domainContext = dynamic_run_domain_context($runId, 120);
            $indicators = dynamic_run_indicators($runId, 120);
            $issues = dynamic_run_issues($runId, 80);
            $cohorts = dynamic_run_cohorts($runId, 40);
            $models = dynamic_run_model_metrics($runId, 80);
            $regimes = dynamic_run_risk_regimes($runId, 40);
        }
    } catch (Throwable $e) {
        $errorMsg = page_error_message('dynamic run detail', $e);
    }
}

/** Page-local aliases preserve template readability; rendering behavior lives in lib/render.php. */
function fmt_run_number($value, int $decimals = 1): string
{
    return runtime_format_number($value, $decimals);
}

function fmt_run_pct($value): string
{
    return runtime_format_percent($value);
}

function fmt_run_bool($value): string
{
    return runtime_format_bool($value);
}

function fmt_run_csv($value): string
{
    return runtime_format_csv($value);
}

function run_baseline_class(array $run): string
{
    return runtime_baseline_class($run, true);
}

function run_baseline_reasons(array $run): string
{
    return runtime_qfg_reasons($run);
}

function run_csv_has($value, string $needle): bool
{
    return runtime_csv_has($value, $needle);
}

$domainCount = count($domainContext);
$firstPartyDomainCount = 0;
$thirdPartyDomainCount = 0;
$domainHitCount = 0;
foreach ($domainContext as $row) {
    $domainHitCount += (int)($row['total_indicator_hits'] ?? 0);
    if (((int)($row['is_first_party'] ?? 0)) === 1 || runtime_csv_has($row['owner_classes_csv'] ?? '', 'first_party')) {
        $firstPartyDomainCount++;
    }
    if (runtime_csv_has($row['owner_classes_csv'] ?? '', 'third_party')) {
        $thirdPartyDomainCount++;
    }
}

$packageName = is_array($run) ? (string)($run['package_name'] ?? '') : '';
$featureState = is_array($run) ? (string)($run['feature_state'] ?? 'unknown_features') : 'unknown_features';
$staticLinkState = is_array($run) ? (string)($run['static_link_state'] ?? 'unknown_static_link') : 'unknown_static_link';
$PAGE_TITLE = $runId ? ('Dynamic Run: ' . $runId) : 'Dynamic Run';
require_once __DIR__ . '/../lib/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="alert alert-danger"><?= e($errorMsg) ?></div>
<?php elseif ($runId === null): ?>
  <section class="section"><div class="panel"><div class="panel-body"><p class="muted">Choose a runtime run from the Runtime Deviation index.</p></div></div></section>
<?php elseif ($run === null): ?>
  <section class="section"><div class="alert alert-warning">No dynamic run was found for the requested id.</div></section>
<?php else: ?>
  <section class="section detail-hero">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h1 class="panel-title"><?= e((string)($run['app_label'] ?? $packageName)) ?></h1>
          <p class="panel-subtitle">
            <strong><?= e($packageName) ?></strong>
            <span class="muted">· <?= e($runId) ?></span>
          </p>
        </div>
        <div class="panel-actions chip-row">
          <?= status_chip((string)($run['status'] ?? 'UNKNOWN')) ?>
          <?= chip((string)($run['tier'] ?? 'unknown'), 'muted') ?>
          <?= runtime_quota_state_chip((string)($run['quota_state'] ?? '')) ?>
          <?= runtime_technical_validity_chip((string)($run['technical_validity_state'] ?? '')) ?>
          <?= runtime_feature_state_chip($featureState) ?>
          <?= runtime_static_link_state_chip($staticLinkState) ?>
          <?php if ($packageName !== ''): ?>
            <a class="btn" href="<?= e(url('pages/app_dynamic.php') . '?pkg=' . urlencode($packageName)) ?>">Package Dynamic</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <?php if ($featureState !== 'features_available' || $staticLinkState !== 'static_linked'): ?>
    <section class="section">
      <div class="alert alert-warning">
        This run has incomplete derived DB linkage.
        <?= e(runtime_feature_state_hint($featureState)) ?>
        <?= e(runtime_static_link_state_hint($staticLinkState)) ?>
        Evidence remains available, but cross-analysis interpretation should treat these fields as incomplete.
      </div>
    </section>
  <?php endif; ?>

  <section class="section detail-grid">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Run Context</h2>
          <p class="panel-subtitle">Capture identity, timing, and evidence links.</p>
        </div>
      </div>
      <div class="panel-body">
        <dl class="detail-kv">
          <div><dt>Scenario</dt><dd><?= e((string)($run['scenario_id'] ?? '-')) ?></dd></div>
          <div><dt>Started</dt><dd><?= e(fmt_date((string)($run['started_at_utc'] ?? ''))) ?></dd></div>
          <div><dt>Ended</dt><dd><?= e(fmt_date((string)($run['ended_at_utc'] ?? ''))) ?></dd></div>
          <div><dt>Duration</dt><dd><?= e(fmt_run_number($run['duration_seconds'] ?? $run['sampling_duration_seconds'] ?? null, 1)) ?>s</dd></div>
          <div><dt>Run Profile</dt><dd><?= e((string)($run['run_profile'] ?? 'unknown')) ?></dd></div>
          <div><dt>Interaction</dt><dd><?= e((string)($run['interaction_level'] ?? 'unknown')) ?></dd></div>
          <div><dt>Evidence Path</dt><dd class="cell-clip"><?= e((string)($run['evidence_path'] ?? '-')) ?></dd></div>
          <div><dt>PCAP</dt><dd><?= e(fmt_run_bool($run['pcap_valid'] ?? null)) ?> · <?= e(fmt_run_number($run['pcap_bytes'] ?? null, 0)) ?> bytes</dd></div>
          <div><dt>Technical Validity</dt><dd><?= runtime_technical_validity_chip((string)($run['technical_validity_state'] ?? '')) ?></dd></div>
          <div><dt>Quota State</dt><dd><?= runtime_quota_state_chip((string)($run['quota_state'] ?? '')) ?></dd></div>
          <div><dt>Baseline Class</dt><dd><?= e(run_baseline_class($run)) ?></dd></div>
          <?php if (((int)($run['baseline_not_idle'] ?? 0)) === 1): ?>
            <div><dt>QFG Reasons</dt><dd><?= e(run_baseline_reasons($run)) ?></dd></div>
          <?php endif; ?>
          <div><dt>Cohort Eligibility</dt><dd><?= e((string)($run['cohort_eligibility_state'] ?? 'COHORT_NOT_EVALUATED')) ?></dd></div>
          <div><dt>Feature State</dt><dd><?= runtime_feature_state_chip($featureState) ?></dd></div>
          <div><dt>Static Link</dt><dd><?= runtime_static_link_state_chip($staticLinkState) ?></dd></div>
        </dl>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Network Features</h2>
          <p class="panel-subtitle">Persisted feature summary for the run.</p>
        </div>
      </div>
      <div class="panel-body">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Packets</span><span class="metric-value"><?= e(fmt_run_number($run['packet_count'] ?? null, 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Bytes/sec</span><span class="metric-value"><?= e(fmt_run_number($run['bytes_per_sec'] ?? null, 1)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Packets/sec</span><span class="metric-value"><?= e(fmt_run_number($run['packets_per_sec'] ?? null, 1)) ?></span></div>
          <div class="metric-card"><span class="metric-label">TLS / QUIC</span><span class="metric-value"><?= e(fmt_run_pct($run['tls_ratio'] ?? null)) ?> / <?= e(fmt_run_pct($run['quic_ratio'] ?? null)) ?></span></div>
          <div class="metric-card"><span class="metric-label">DNS / SNI</span><span class="metric-value"><?= e(fmt_run_number($run['unique_dns_qname_count'] ?? null, 0)) ?> / <?= e(fmt_run_number($run['unique_sni_count'] ?? null, 0)) ?></span></div>
          <div class="metric-card"><span class="metric-label">Low Signal</span><span class="metric-value"><?= e(fmt_run_bool($run['low_signal'] ?? null)) ?></span></div>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Domain Destinations</h2>
          <p class="panel-subtitle">Observed DNS/SNI destinations mapped to services and interpretation signals.</p>
        </div>
      </div>
      <div class="panel-body detail-stack">
        <div class="metrics-grid">
          <div class="metric-card"><span class="metric-label">Observed Domains</span><span class="metric-value"><?= e((string)$domainCount) ?></span></div>
          <div class="metric-card"><span class="metric-label">First-party / Third-party</span><span class="metric-value"><?= e((string)$firstPartyDomainCount) ?> / <?= e((string)$thirdPartyDomainCount) ?></span></div>
          <div class="metric-card"><span class="metric-label">Indicator Hits</span><span class="metric-value"><?= e(fmt_run_number($domainHitCount, 0)) ?></span></div>
        </div>

        <?php if (empty($domainContext)): ?>
          <p class="muted">No domain observation rows were found for this dynamic run.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>Domain</th>
                  <th>Class</th>
                  <th>Service</th>
                  <th>Signals</th>
                  <th>Evidence</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($domainContext as $row): ?>
                  <tr>
                    <td class="cell-clip">
                      <strong><?= e((string)($row['observed_domain'] ?? '')) ?></strong><br>
                      <span class="muted"><?= e((string)($row['root_domain'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_run_csv($row['owner_classes_csv'] ?? '')) ?> · <?= e(fmt_run_csv($row['role_classes_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_run_csv($row['confidence_csv'] ?? '')) ?> · <?= e(fmt_run_csv($row['classification_basis_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_run_csv($row['service_names_csv'] ?? $row['service_keys_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_run_csv($row['service_categories_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      <?= e(fmt_run_csv($row['signal_names_csv'] ?? $row['signal_keys_csv'] ?? '')) ?><br>
                      <span class="muted"><?= e(fmt_run_csv($row['signal_focus_areas_csv'] ?? '')) ?> · <?= e(fmt_run_csv($row['signal_severity_hints_csv'] ?? '')) ?></span>
                    </td>
                    <td>
                      rows <?= e((string)($row['observation_rows'] ?? 0)) ?>,
                      hits <?= e(fmt_run_number($row['total_indicator_hits'] ?? 0, 0)) ?><br>
                      <span class="muted"><?= e(fmt_run_csv($row['indicator_types_csv'] ?? '')) ?> · <?= e(fmt_run_csv($row['indicator_sources_csv'] ?? '')) ?></span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h2 class="panel-title">Runtime Regimes</h2>
          <p class="panel-subtitle">Cross-analysis static exposure plus runtime deviation summaries for cohorts containing this run.</p>
        </div>
      </div>
      <div class="panel-body">
        <?php if (empty($regimes)): ?>
          <p class="muted">No risk-regime rows were found for this dynamic run.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead><tr><th>Cohort</th><th>Static</th><th>Dynamic</th><th>Final Regime</th><th>Created</th></tr></thead>
              <tbody>
                <?php foreach ($regimes as $row): ?>
                  <tr>
                    <td class="cell-clip"><?= e((string)($row['cohort_id'] ?? '')) ?></td>
                    <td><?= e((string)($row['static_grade'] ?? '-')) ?> · <?= e(fmt_run_number($row['static_score'] ?? null, 3)) ?></td>
                    <td><?= e((string)($row['dynamic_grade_if'] ?? '-')) ?> · <?= e(fmt_run_number($row['dynamic_score_if'] ?? null, 3)) ?></td>
                    <td><?= e((string)($row['final_regime_if'] ?? '-')) ?></td>
                    <td><?= e(fmt_date((string)($row['created_at_utc'] ?? ''))) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="section detail-grid">
    <div class="panel">
      <div class="panel-header"><div><h2 class="panel-title">Indicators</h2><p class="panel-subtitle">Top DNS/SNI/network indicators persisted for the run.</p></div></div>
      <div class="panel-body">
        <?php if (empty($indicators)): ?>
          <p class="muted">No network indicators were found.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead><tr><th>Type</th><th>Value</th><th>Count</th><th>Source</th></tr></thead>
              <tbody>
                <?php foreach ($indicators as $row): ?>
                  <tr>
                    <td><?= e((string)($row['indicator_type'] ?? '')) ?></td>
                    <td class="cell-clip"><?= e((string)($row['indicator_value'] ?? '')) ?></td>
                    <td><?= e((string)($row['indicator_count'] ?? 0)) ?></td>
                    <td><?= e((string)($row['indicator_source'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><div><h2 class="panel-title">Issues And Cohorts</h2><p class="panel-subtitle">Validity notes, cohort inclusion, and model metrics.</p></div></div>
      <div class="panel-body detail-stack">
        <h3>Issues</h3>
        <?php if (empty($issues)): ?>
          <p class="muted">No issues were recorded for this run.</p>
        <?php else: ?>
          <?php foreach ($issues as $row): ?>
            <article class="card">
              <strong><?= e((string)($row['issue_code'] ?? 'issue')) ?></strong>
              <p class="muted pre-wrap"><?= e((string)($row['details_json'] ?? '')) ?></p>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>

        <h3>Cohorts</h3>
        <?php if (empty($cohorts)): ?>
          <p class="muted">No cohort membership rows were found.</p>
        <?php else: ?>
          <div class="chip-row">
            <?php foreach ($cohorts as $row): ?>
              <?= chip((string)($row['cohort_id'] ?? 'cohort') . ' · ' . (string)($row['run_role'] ?? 'run'), !empty($row['included']) ? 'info' : 'muted') ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <h3>Model Metrics</h3>
        <?php if (empty($models)): ?>
          <p class="muted">No model metrics were found for cohorts containing this run.</p>
        <?php else: ?>
          <div class="chip-row">
            <?php foreach ($models as $row): ?>
              <?= chip((string)($row['phase'] ?? '-') . ' ' . (string)($row['model_key'] ?? '-') . ': ' . fmt_run_pct($row['flagged_pct'] ?? null), 'medium') ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../lib/footer.php'; ?>
