<?php
// database/db_lib/db_dynamic.php — per-package dynamic summary and run drilldown.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

/**
 * Dynamic summary for an app package.
 *
 * @return array<string,mixed>
 */
function app_dynamic_summary(string $packageName): array
{
    return db_one(
        SQL_APP_DYNAMIC_SUMMARY,
        ['pkg_dynamic_summary' => $packageName]
    ) ?? [];
}

/**
 * Dynamic runs for an app package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_dynamic_runs(string $packageName, int $limit = 50): array
{
    $limit = _positive_limit($limit, 200);
    $sql = SQL_APP_DYNAMIC_RUNS . " LIMIT $limit";
    return db_all(
        $sql,
        ['pkg_dynamic_runs' => $packageName]
    );
}

/**
 * @return array<string,mixed>|null
 */
function dynamic_run_detail(string $dynamicRunId): ?array
{
    return db_one(
        SQL_DYNAMIC_RUN_DETAIL,
        ['dynamic_run_id' => $dynamicRunId]
    );
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_indicators(string $dynamicRunId, int $limit = 120): array
{
    $limit = _positive_limit($limit, 300);
    $sql = SQL_DYNAMIC_RUN_INDICATORS . " LIMIT $limit";
    return db_all($sql, ['indicator_run_id' => $dynamicRunId]);
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_issues(string $dynamicRunId, int $limit = 80): array
{
    $limit = _positive_limit($limit, 200);
    $sql = SQL_DYNAMIC_RUN_ISSUES . " LIMIT $limit";
    return db_all($sql, ['issue_run_id' => $dynamicRunId]);
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_cohorts(string $dynamicRunId, int $limit = 40): array
{
    $limit = _positive_limit($limit, 100);
    $sql = SQL_DYNAMIC_RUN_COHORTS . " LIMIT $limit";
    return db_all($sql, ['cohort_run_id' => $dynamicRunId]);
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_model_metrics(string $dynamicRunId, int $limit = 80): array
{
    $limit = _positive_limit($limit, 200);
    $sql = SQL_DYNAMIC_RUN_MODEL_METRICS . " LIMIT $limit";
    return db_all($sql, ['model_run_id' => $dynamicRunId]);
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_risk_regimes(string $dynamicRunId, int $limit = 40): array
{
    $limit = _positive_limit($limit, 100);
    $sql = SQL_DYNAMIC_RUN_RISK_REGIMES . " LIMIT $limit";
    return db_all($sql, ['regime_run_id' => $dynamicRunId]);
}
