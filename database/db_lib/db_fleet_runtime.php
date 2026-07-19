<?php
// database/db_lib/db_fleet_runtime.php — dashboard metrics + runtime run list.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

/**
 * Fleet dashboard overview metrics.
 *
 * @return array<string,mixed>
 */
function fleet_dashboard_overview(): array
{
    return web_cache_remember('fleet_dashboard_overview_v1', 60, static function (): array {
        return db_one(SQL_DASHBOARD_OVERVIEW) ?? [];
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function fleet_category_summary(int $limit = 8): array
{
    $limit = _positive_limit($limit, 30);
    return web_cache_remember("fleet_category_summary_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_DASHBOARD_CATEGORY_SUMMARY . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function fleet_recurring_findings(int $limit = 10): array
{
    $limit = _positive_limit($limit, 50);
    return web_cache_remember("fleet_recurring_findings_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_DASHBOARD_RECURRING_FINDINGS . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Runtime Deviation dashboard summary.
 *
 * @return array<string,mixed>
 */
function runtime_deviation_overview(): array
{
    return web_cache_remember('runtime_deviation_overview_v5', 60, static function (): array {
        return db_one(SQL_RUNTIME_OVERVIEW) ?? [];
    });
}

/**
 * Fleet-level dynamic service context by observed network volume.
 *
 * @return array<int,array<string,mixed>>
 */
function runtime_top_services(int $limit = 10): array
{
    $limit = _positive_limit($limit, 40);
    return web_cache_remember("runtime_top_services_v1_{$limit}", 60, static function () use ($limit): array {
        $sql = SQL_RUNTIME_TOP_SERVICES . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Fleet-level dynamic signal context by observed network volume.
 *
 * @return array<int,array<string,mixed>>
 */
function runtime_top_signals(int $limit = 10): array
{
    $limit = _positive_limit($limit, 40);
    return web_cache_remember("runtime_top_signals_v1_{$limit}", 60, static function () use ($limit): array {
        $sql = SQL_RUNTIME_TOP_SIGNALS . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Fleet-level observed domains by network indicator volume.
 *
 * @return array<int,array<string,mixed>>
 */
function runtime_top_domains(int $limit = 10): array
{
    $limit = _positive_limit($limit, 40);
    return web_cache_remember("runtime_top_domains_v1_{$limit}", 60, static function () use ($limit): array {
        $sql = SQL_RUNTIME_TOP_DOMAINS . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Lightweight probe for the normalized static/dynamic package summary view.
 *
 * @return array<string,mixed>
 */
function static_dynamic_summary_probe(): array
{
    return db_one(SQL_STATIC_DYNAMIC_SUMMARY_PROBE) ?? [];
}

/**
 * Runtime Deviation — paginated dynamic run list.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function runtime_deviation_runs_paged(
    ?string $status,
    ?string $tier,
    ?string $q,
    int $page,
    int $size
): array {
    [$where, $params] = _runtime_runs_where([
        'status' => $status,
        'tier' => $tier,
        'q' => $q,
    ]);

    $cacheSeed = json_encode([$where, $params, $page, $size], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where;
    return web_cache_remember('runtime_runs_paged_v1_' . sha1($cacheSeed), 60, static function () use ($where, $params, $page, $size, $cacheSeed): array {
        return db_paged(
            SQL_RUNTIME_RUNS_BASE,
            SQL_RUNTIME_RUNS_COUNT,
            $where,
            SQL_RUNTIME_RUNS_ORDER,
            $params,
            $page,
            $size,
            60,
            'runtime_runs_' . sha1($cacheSeed)
        );
    });
}
