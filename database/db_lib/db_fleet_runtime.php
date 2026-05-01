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
    return web_cache_remember('runtime_deviation_overview_v1', 60, static function (): array {
        return db_one(SQL_RUNTIME_OVERVIEW) ?? [];
    });
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

    return db_paged(
        SQL_RUNTIME_RUNS_BASE,
        SQL_RUNTIME_RUNS_COUNT,
        $where,
        SQL_RUNTIME_RUNS_ORDER,
        $params,
        $page,
        $size
    );
}
