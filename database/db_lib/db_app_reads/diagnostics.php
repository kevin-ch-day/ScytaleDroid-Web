<?php
// database/db_lib/db_app_reads/diagnostics.php — lightweight DB diagnostic counters.

/**
 * Lightweight DB counters for smoke/diag.
 * ``static_analysis_findings_rows`` = canonical persistence; ``v_web_app_findings_rows`` = Web read-model.
 *
 * @return array<string,mixed>
 */
function app_diagnostics(): array
{
    $versionRow = db_one(SQL_DIAG_DB_VERSION) ?? [];
    $countRow = db_one(SQL_DIAG_COUNTS) ?? [];

    return [
        'db_ok' => true,
        'version' => $versionRow['version'] ?? '?',
        'static_runs' => (int)($countRow['static_runs'] ?? 0),
        'static_analysis_findings_rows' => (int)($countRow['static_analysis_findings_rows'] ?? 0),
        'v_web_app_findings_rows' => (int)($countRow['v_web_app_findings_rows'] ?? 0),
        'audit_snapshots' => (int)($countRow['audit_snapshots'] ?? 0),
        'audit_packages' => (int)($countRow['audit_packages'] ?? 0),
        'static_packages' => (int)($countRow['static_packages'] ?? 0),
        'app_catalog' => (int)($countRow['app_catalog'] ?? 0),
        'dynamic_runs' => (int)($countRow['dynamic_runs'] ?? 0),
        'dynamic_packages' => (int)($countRow['dynamic_packages'] ?? 0),
        'dynamic_feature_rows' => (int)($countRow['dynamic_feature_rows'] ?? 0),
        'analysis_cohorts' => (int)($countRow['analysis_cohorts'] ?? 0),
        'runtime_regime_rows' => (int)($countRow['runtime_regime_rows'] ?? 0),
    ];
}
