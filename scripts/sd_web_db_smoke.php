#!/usr/bin/env php
<?php
/**
 * One-shot DB read-model smoke checks for ScytaleDroid-Web workers.
 * Exit 0 on success; non-zero on first failure (connectivity, missing view, etc.).
 *
 * Usage: php scripts/sd_web_db_smoke.php
 * Set SCYTALEDROID_WEB_SMOKE_FULL=1 for heavyweight cohort/view probes.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/database/db_lib/db_func.php';

$firstPackage = static function (): ?string {
    $rows = apps_directory_probe(5);
    if (!is_array($rows) || $rows === []) {
        return null;
    }
    $pkg = $rows[0]['package_name'] ?? null;
    return is_string($pkg) && $pkg !== '' ? $pkg : null;
};

$firstPackageWithSession = static function (): array {
    $rows = apps_directory_probe(25);
    if (!is_array($rows)) {
        return [null, null];
    }
    foreach ($rows as $row) {
        $pkg = $row['package_name'] ?? null;
        if (!is_string($pkg) || $pkg === '') {
            continue;
        }
        $sessions = app_sessions($pkg, 1);
        if (!is_array($sessions) || $sessions === []) {
            continue;
        }
        $stamp = $sessions[0]['session_stamp'] ?? null;
        if (is_string($stamp) && $stamp !== '') {
            return [$pkg, $stamp];
        }
    }
    return [null, null];
};

$firstPackageWithDynamicRun = static function (): array {
    $rows = apps_directory_probe(50);
    if (!is_array($rows)) {
        return [null, null];
    }
    foreach ($rows as $row) {
        $pkg = $row['package_name'] ?? null;
        if (!is_string($pkg) || $pkg === '') {
            continue;
        }
        $runs = app_dynamic_runs($pkg, 1);
        if (!is_array($runs) || $runs === []) {
            continue;
        }
        $runId = $runs[0]['dynamic_run_id'] ?? null;
        if (is_string($runId) && $runId !== '') {
            return [$pkg, $runId];
        }
    }
    return [null, null];
};

$checks = [
    'db_ping' => static fn(): bool => db_ping(),
    'fleet_dashboard_overview' => static fn() => fleet_dashboard_overview(),
    'findings_categories' => static fn() => findings_categories(),
    'permission_intel_session_options' => static fn() => permission_intel_session_options(),
    'static_session_quality' => static fn() => static_session_quality(),
    'component_exposure_overview' => static fn() => component_exposure_overview(),
    'apps_directory_probe' => static fn() => apps_directory_probe(1),
    'app_overview' => static function () use ($firstPackage) {
        $pkg = $firstPackage();
        return $pkg === null ? true : app_overview($pkg);
    },
    'app_sessions' => static function () use ($firstPackage) {
        $pkg = $firstPackage();
        return $pkg === null ? true : app_sessions($pkg, 1);
    },
    'app_diagnostics' => static fn() => app_diagnostics(),
];

// This view is intentionally comprehensive and can be expensive on a large
// historical corpus. Keep the default deployment smoke bounded; operators can
// opt into these additional read-model checks during maintenance.
if (in_array(strtolower((string)(getenv('SCYTALEDROID_WEB_SMOKE_FULL') ?: '')), ['1', 'true', 'yes'], true)) {
    $checks['app_findings_summary'] = static function () use ($firstPackageWithSession) {
        [$pkg, $stamp] = $firstPackageWithSession();
        return ($pkg === null || $stamp === null) ? true : app_findings_summary($pkg, $stamp);
    };
    $checks['app_dynamic_summary'] = static function () use ($firstPackageWithDynamicRun) {
        [$pkg, $_runId] = $firstPackageWithDynamicRun();
        return $pkg === null ? true : app_dynamic_summary($pkg);
    };
    $checks['app_dynamic_runs'] = static function () use ($firstPackageWithDynamicRun) {
        [$pkg, $_runId] = $firstPackageWithDynamicRun();
        return $pkg === null ? true : app_dynamic_runs($pkg, 1);
    };
    $checks['dynamic_run_detail'] = static function () use ($firstPackageWithDynamicRun) {
        [$_pkg, $runId] = $firstPackageWithDynamicRun();
        return $runId === null ? true : dynamic_run_detail($runId);
    };
    $checks['static_dynamic_summary_probe'] = static fn() => static_dynamic_summary_probe();
    $checks['dynamic_collection_queue_overview'] = static fn() => dynamic_collection_queue_overview();
    $checks['dynamic_collection_queue_probe'] = static fn() => dynamic_collection_queue_probe(1);
    $checks['dynamic_collection_queue_recommended'] = static fn() => dynamic_collection_queue_recommended_captures(3);
}

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
