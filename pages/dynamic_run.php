<?php
// pages/dynamic_run.php

require_once __DIR__ . '/../lib/guards.php';
require_once __DIR__ . '/../lib/render.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';

$runId = guard_dynamic_run_id($_GET['run'] ?? null);
$run = null;
$indicators = [];
$issues = [];
$cohorts = [];
$models = [];
$regimes = [];
$errorMsg = null;

if ($runId !== null) {
    try {
        $run = dynamic_run_detail($runId);
        if ($run !== null) {
            $indicators = dynamic_run_indicators($runId, 120);
            $issues = dynamic_run_issues($runId, 80);
            $cohorts = dynamic_run_cohorts($runId, 40);
            $models = dynamic_run_model_metrics($runId, 80);
            $regimes = dynamic_run_risk_regimes($runId, 40);
        }
    } catch (Throwable $e) {
        $errorMsg = 'DB error: ' . $e->getMessage();
        error_log('[ScytaleDroid-Web] dynamic run detail failed: ' . $e);
    }
}

function fmt_run_number($value, int $decimals = 1): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format((float)$value, $decimals);
}

function fmt_run_pct($value): string
{
    if ($value === null || $value === '') {
        return '-';
    }
    return number_format(((float)$value) * 100, 1) . '%';
}

function fmt_run_bool($value): string
{
    if ($value === null || $value === '') {
        return 'unknown';
    }
    return ((int)$value) === 1 ? 'yes' : 'no';
}

function state_chip_tone(string $state): string
{
    if ($state === 'features_available' || $state === 'static_linked') {
        return 'info';
    }
    if ($state === 'missing_features') {
        return 'medium';
    }
    return 'high';
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
          <?= chip($featureState, state_chip_tone($featureState)) ?>
          <?= chip($staticLinkState, state_chip_tone($staticLinkState)) ?>
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
        This run has incomplete derived DB linkage:
        <?= e($featureState) ?>,
        <?= e($staticLinkState) ?>.
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
          <div><dt>Run Profile</dt><dd><?= e((string)($run['operator_run_profile'] ?? $run['run_profile'] ?? $run['profile_key'] ?? 'unknown')) ?></dd></div>
          <div><dt>Interaction</dt><dd><?= e((string)($run['operator_interaction_level'] ?? $run['interaction_level'] ?? 'unknown')) ?></dd></div>
          <div><dt>Evidence Path</dt><dd class="cell-clip"><?= e((string)($run['evidence_path'] ?? '-')) ?></dd></div>
          <div><dt>PCAP</dt><dd><?= e(fmt_run_bool($run['pcap_valid'] ?? null)) ?> · <?= e(fmt_run_number($run['pcap_bytes'] ?? null, 0)) ?> bytes</dd></div>
          <div><dt>Feature State</dt><dd><?= chip($featureState, state_chip_tone($featureState)) ?></dd></div>
          <div><dt>Static Link</dt><dd><?= chip($staticLinkState, state_chip_tone($staticLinkState)) ?></dd></div>
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
