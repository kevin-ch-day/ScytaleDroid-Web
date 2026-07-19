<?php
// database/db_lib/db_app_reads/findings.php — per-app static finding helpers.

/**
 * @return array<string,mixed>|null
 */
function app_findings_summary(string $packageName, string $sessionStamp): ?array
{
    return web_cache_remember("app_findings_summary_v3_" . sha1($packageName . '|' . $sessionStamp), 120, static function () use ($packageName, $sessionStamp): ?array {
        return db_one(
            SQL_APP_FINDINGS_SUMMARY,
            [
                'pkg_summary' => $packageName,
                'session_summary' => $sessionStamp,
            ]
        );
    });
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
