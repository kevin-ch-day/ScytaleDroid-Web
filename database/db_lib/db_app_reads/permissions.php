<?php
// database/db_lib/db_app_reads/permissions.php — per-app permission helpers.

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
    return web_cache_remember("app_permission_summary_v1_" . sha1($packageName . '|' . $sessionStamp), 120, static function () use ($packageName, $sessionStamp): ?array {
        return db_one(
            SQL_APP_PERMISSION_SUMMARY,
            [
                'pkg_permission_summary' => $packageName,
                'session_permission_summary' => $sessionStamp,
            ]
        );
    });
}
