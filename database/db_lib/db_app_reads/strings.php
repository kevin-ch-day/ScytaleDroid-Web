<?php
// database/db_lib/db_app_reads/strings.php — per-app string summary and sample helpers.

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
