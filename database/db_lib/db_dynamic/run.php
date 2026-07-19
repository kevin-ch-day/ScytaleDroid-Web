<?php
// database/db_lib/db_dynamic/run.php — dynamic run drilldown helpers.

/**
 * @return array<string,mixed>|null
 */
function dynamic_run_detail(string $dynamicRunId): ?array
{
    return web_cache_remember("dynamic_run_detail_v1_" . sha1($dynamicRunId), 60, static function () use ($dynamicRunId): ?array {
        return db_one(
            SQL_DYNAMIC_RUN_DETAIL,
            ['dynamic_run_id' => $dynamicRunId]
        );
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_domain_context(string $dynamicRunId, int $limit = 120): array
{
    $limit = _positive_limit($limit, 300);
    return web_cache_remember("dynamic_run_domain_context_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_DOMAIN_CONTEXT . " LIMIT $limit";
        return db_all($sql, ['dynamic_run_domain_context_id' => $dynamicRunId]);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_indicators(string $dynamicRunId, int $limit = 120): array
{
    $limit = _positive_limit($limit, 300);
    return web_cache_remember("dynamic_run_indicators_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_INDICATORS . " LIMIT $limit";
        return db_all($sql, ['indicator_run_id' => $dynamicRunId]);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_issues(string $dynamicRunId, int $limit = 80): array
{
    $limit = _positive_limit($limit, 200);
    return web_cache_remember("dynamic_run_issues_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_ISSUES . " LIMIT $limit";
        return db_all($sql, ['issue_run_id' => $dynamicRunId]);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_cohorts(string $dynamicRunId, int $limit = 40): array
{
    $limit = _positive_limit($limit, 100);
    return web_cache_remember("dynamic_run_cohorts_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_COHORTS . " LIMIT $limit";
        return db_all($sql, ['cohort_run_id' => $dynamicRunId]);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_model_metrics(string $dynamicRunId, int $limit = 80): array
{
    $limit = _positive_limit($limit, 200);
    return web_cache_remember("dynamic_run_model_metrics_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_MODEL_METRICS . " LIMIT $limit";
        return db_all($sql, ['model_run_id' => $dynamicRunId]);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function dynamic_run_risk_regimes(string $dynamicRunId, int $limit = 40): array
{
    $limit = _positive_limit($limit, 100);
    return web_cache_remember("dynamic_run_risk_regimes_v1_" . sha1($dynamicRunId . '|' . $limit), 60, static function () use ($dynamicRunId, $limit): array {
        $sql = SQL_DYNAMIC_RUN_RISK_REGIMES . " LIMIT $limit";
        return db_all($sql, ['regime_run_id' => $dynamicRunId]);
    });
}
