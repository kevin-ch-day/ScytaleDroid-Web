<?php
// database/db_lib/db_app_reads.php — per-package/session read helpers + diagnostics.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';
function app_overview(string $packageName): ?array
{
    return db_one(
        SQL_APP_OVERVIEW,
        [
            'pkg_lookup' => $packageName,
        ]
    );
}

/**
 * Return recent static sessions for a package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_sessions(string $packageName, int $limit = 24): array
{
    $limit = _positive_limit($limit, 100);
    $sql = SQL_APP_SESSIONS . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_runs' => $packageName,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_findings_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_FINDINGS_SUMMARY,
        [
            'pkg_summary' => $packageName,
            'session_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function app_findings_list(string $packageName, string $sessionStamp, int $limit = 200): array
{
    $limit = _positive_limit($limit, 500);
    $sql = SQL_APP_FINDINGS_LIST . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_findings' => $packageName,
            'session_findings' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_strings_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_STRINGS_SUMMARY,
        [
            'pkg_strings_summary' => $packageName,
            'session_strings_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function app_string_samples(string $packageName, string $sessionStamp, int $limit = 120): array
{
    $limit = _positive_limit($limit, 300);
    $sql = SQL_APP_STRING_SAMPLES . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_string_samples' => $packageName,
            'session_string_samples' => $sessionStamp,
        ]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function app_permissions(string $packageName, string $sessionStamp, int $limit = 250): array
{
    $limit = _positive_limit($limit, 500);
    $sql = SQL_APP_PERMISSIONS . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_permissions' => $packageName,
            'session_permissions' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_permission_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_PERMISSION_SUMMARY,
        [
            'pkg_permission_summary' => $packageName,
            'session_permission_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function app_fileproviders(string $packageName, string $sessionStamp, int $limit = 100): array
{
    $limit = _positive_limit($limit, 250);
    $sql = SQL_APP_FILEPROVIDERS . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_fileproviders' => $packageName,
            'session_fileproviders' => $sessionStamp,
        ]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function app_provider_acl(string $packageName, string $sessionStamp, int $limit = 150): array
{
    $limit = _positive_limit($limit, 300);
    $sql = SQL_APP_PROVIDER_ACL . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_provider_acl' => $packageName,
            'session_provider_acl' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_component_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_COMPONENT_SUMMARY,
        [
            'pkg_component_summary' => $packageName,
            'session_component_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_report_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_REPORT_SUMMARY,
        [
            'pkg_report_summary' => $packageName,
            'session_report_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>
 */
function app_diagnostics(): array
{
    $versionRow = db_one(SQL_DIAG_DB_VERSION) ?? [];
    $countRow = db_one(SQL_DIAG_COUNTS) ?? [];

    return [
        'db_ok' => true,
        'version' => $versionRow['version'] ?? '?',
        'runs' => (int)($countRow['runs'] ?? 0),
        'static_runs' => (int)($countRow['static_runs'] ?? 0),
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
