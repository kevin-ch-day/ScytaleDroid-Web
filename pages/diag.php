<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/db_lib/db_func.php';
header('Content-Type: text/plain; charset=utf-8');

function diag_allowed(): bool
{
    $flag = getenv('SCYTALEDROID_WEB_ENABLE_DIAG');
    if ($flag !== false && in_array(strtolower((string)$flag), ['1', 'true', 'yes'], true)) {
        return true;
    }

    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    return in_array($remote, ['127.0.0.1', '::1'], true);
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
    echo "runs: {$diag['runs']}\n";
    echo "static_runs: {$diag['static_runs']}\n";
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
    echo "DB ERROR\n" . $e->getMessage() . "\n";
}
