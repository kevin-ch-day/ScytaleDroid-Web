#!/usr/bin/env php
<?php
/**
 * One-shot DB read-model smoke checks for ScytaleDroid-Web workers.
 * Exit 0 on success; non-zero on first failure (connectivity, missing view, etc.).
 *
 * Usage: php scripts/sd_web_db_smoke.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/database/db_lib/db_func.php';

$checks = [
    'db_ping' => static fn(): bool => db_ping(),
    'fleet_dashboard_overview' => static fn() => fleet_dashboard_overview(),
    'findings_categories' => static fn() => findings_categories(),
    'permission_intel_session_options' => static fn() => permission_intel_session_options(),
    'static_session_quality' => static fn() => static_session_quality(),
    'component_exposure_overview' => static fn() => component_exposure_overview(),
    'apps_directory_probe' => static fn() => apps_directory_probe(1),
    'app_diagnostics' => static fn() => app_diagnostics(),
];

$fail = 0;
foreach ($checks as $label => $fn) {
    try {
        $fn();
        fwrite(STDOUT, "OK  {$label}\n");
    } catch (Throwable $e) {
        $fail = 1;
        fwrite(STDERR, "FAIL {$label}: {$e->getMessage()}\n");
        break;
    }
}

exit($fail);
