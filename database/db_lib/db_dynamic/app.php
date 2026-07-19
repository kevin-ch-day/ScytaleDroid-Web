<?php
// database/db_lib/db_dynamic/app.php — per-package dynamic summary helpers.

/**
 * Dynamic summary for an app package.
 *
 * @return array<string,mixed>
 */
function app_dynamic_summary(string $packageName): array
{
    return web_cache_remember("app_dynamic_summary_v1_" . sha1($packageName), 60, static function () use ($packageName): array {
        return db_one(
            SQL_APP_DYNAMIC_SUMMARY,
            ['pkg_dynamic_summary' => $packageName]
        ) ?? [];
    });
}

/**
 * Dynamic runs for an app package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_dynamic_runs(string $packageName, int $limit = 50): array
{
    $limit = _positive_limit($limit, 200);
    return web_cache_remember("app_dynamic_runs_v1_" . sha1($packageName . '|' . $limit), 60, static function () use ($packageName, $limit): array {
        $sql = SQL_APP_DYNAMIC_RUNS . " LIMIT $limit";
        return db_all(
            $sql,
            ['pkg_dynamic_runs' => $packageName]
        );
    });
}

/**
 * Dynamic domain/network context rows for an app package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_dynamic_domain_context(string $packageName, int $limit = 120): array
{
    $limit = _positive_limit($limit, 300);
    return web_cache_remember("app_dynamic_domain_context_v1_" . sha1($packageName . '|' . $limit), 60, static function () use ($packageName, $limit): array {
        $sql = SQL_APP_DYNAMIC_DOMAIN_CONTEXT . " LIMIT $limit";
        return db_all(
            $sql,
            ['pkg_dynamic_domain_context' => $packageName]
        );
    });
}

/**
 * Dynamic service and signal summary for an app package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_dynamic_service_summary(string $packageName, int $limit = 80): array
{
    $limit = _positive_limit($limit, 200);
    return web_cache_remember("app_dynamic_service_summary_v1_" . sha1($packageName . '|' . $limit), 60, static function () use ($packageName, $limit): array {
        $sql = SQL_APP_DYNAMIC_SERVICE_SUMMARY . " LIMIT $limit";
        return db_all(
            $sql,
            ['pkg_dynamic_service_summary' => $packageName]
        );
    });
}

/**
 * Dynamic signal summary for an app package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_dynamic_signal_summary(string $packageName, int $limit = 60): array
{
    $limit = _positive_limit($limit, 150);
    return web_cache_remember("app_dynamic_signal_summary_v1_" . sha1($packageName . '|' . $limit), 60, static function () use ($packageName, $limit): array {
        $sql = SQL_APP_DYNAMIC_SIGNAL_SUMMARY . " LIMIT $limit";
        return db_all(
            $sql,
            ['pkg_dynamic_signal_summary' => $packageName]
        );
    });
}
