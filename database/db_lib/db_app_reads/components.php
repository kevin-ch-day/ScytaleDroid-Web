<?php
// database/db_lib/db_app_reads/components.php — per-app component and report summary helpers.

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
    return web_cache_remember("app_component_summary_v1_" . sha1($packageName . '|' . $sessionStamp), 120, static function () use ($packageName, $sessionStamp): ?array {
        return db_one(
            SQL_APP_COMPONENT_SUMMARY,
            [
                'pkg_component_summary' => $packageName,
                'session_component_summary' => $sessionStamp,
            ]
        );
    });
}

/**
 * @return array<string,mixed>|null
 */
function app_report_summary(string $packageName, string $sessionStamp): ?array
{
    return web_cache_remember("app_report_summary_v1_" . sha1($packageName . '|' . $sessionStamp), 120, static function () use ($packageName, $sessionStamp): ?array {
        return db_one(
            SQL_APP_REPORT_SUMMARY,
            [
                'pkg_report_summary' => $packageName,
                'session_report_summary' => $sessionStamp,
            ]
        );
    });
}
