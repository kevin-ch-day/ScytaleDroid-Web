<?php
// database/db_lib/db_app_reads/overview_sessions.php — app overview and session index helpers.

function app_overview(string $packageName): ?array
{
    return web_cache_remember("app_overview_v2_" . sha1($packageName), 120, static function () use ($packageName): ?array {
        return db_one(
            SQL_APP_OVERVIEW,
            [
                'pkg_lookup' => $packageName,
            ]
        );
    });
}

/**
 * Return recent static sessions for a package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_sessions(string $packageName, int $limit = 24): array
{
    $limit = _positive_limit($limit, 100);
    return web_cache_remember("app_sessions_v2_" . sha1($packageName . '|' . $limit), 120, static function () use ($packageName, $limit): array {
        $sql = SQL_APP_SESSIONS . " LIMIT $limit";
        return db_all(
            $sql,
            [
                'pkg_runs' => $packageName,
            ]
        );
    });
}
