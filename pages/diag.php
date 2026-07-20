<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, private');

function diag_allowed(): bool
{
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (in_array($remote, ['127.0.0.1', '::1'], true)) {
        return true;
    }

    $enabled = strtolower((string)(getenv('SCYTALEDROID_WEB_ENABLE_DIAG') ?: ''));
    $expectedToken = getenv('SCYTALEDROID_WEB_DIAG_TOKEN');
    $providedToken = $_SERVER['HTTP_X_SCYTALEDROID_DIAG_TOKEN'] ?? '';
    return in_array($enabled, ['1', 'true', 'yes'], true)
        && is_string($expectedToken)
        && $expectedToken !== ''
        && is_string($providedToken)
        && hash_equals($expectedToken, $providedToken);
}

if (!diag_allowed()) {
    http_response_code(404);
    echo "Not found\n";
    exit;
}

try {
    $diag = app_diagnostics();
    echo "DB OK\n";
    echo "Version: {$diag['version']}\n";
    echo "historical_runtime_rows: not queried by this diagnostic endpoint\n";
    echo "static_runs: {$diag['static_runs']}\n";
    echo "static_analysis_findings_rows: {$diag['static_analysis_findings_rows']}\n";
    echo "v_web_app_findings_rows: {$diag['v_web_app_findings_rows']}\n";
    echo "audit_snapshots: {$diag['audit_snapshots']}\n";
    echo "audit_packages: {$diag['audit_packages']}\n";
    echo "static_packages: {$diag['static_packages']}\n";
    echo "app_catalog: {$diag['app_catalog']}\n";
    echo "dynamic_runs: {$diag['dynamic_runs']}\n";
    echo "dynamic_packages: {$diag['dynamic_packages']}\n";
    echo "dynamic_feature_rows: {$diag['dynamic_feature_rows']}\n";
    echo "analysis_cohorts: {$diag['analysis_cohorts']}\n";
    echo "runtime_regime_rows: {$diag['runtime_regime_rows']}\n";
} catch (Throwable $e) {
    error_log('[ScytaleDroid-Web] diagnostic query failed: ' . $e->getMessage());
    http_response_code(503);
    echo "DB ERROR\n";
}
