<?php
// database/db_lib/db_func.php
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_utils.php';

const _APPS_DIR_FILTERS = [
    'category' => ['category = :category'],
    'q' => [
        '('
        . 'CONVERT(package_name USING utf8mb4) COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(app_label USING utf8mb4) '
        . 'COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

const _RUNTIME_RUN_FILTERS = [
    'q' => [
        '('
        . 'CONVERT(package_name USING utf8mb4) COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(app_label USING utf8mb4) '
        . 'COLLATE utf8mb4_general_ci '
        . 'LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR dynamic_run_id LIKE :q_run'
        . ')',
        'like',
    ],
    'status' => ['LOWER(status) = LOWER(:status)'],
    'tier' => ['LOWER(tier) = LOWER(:tier)'],
];

const _FINDINGS_EXPLORER_FILTERS = [
    'severity' => ['LOWER(COALESCE(latest.severity, \'\')) = LOWER(:severity)'],
    'category' => ['COALESCE(latest.category, \'Uncategorized\') = :category'],
    'masvs_area' => ['COALESCE(latest.masvs_area, \'Unmapped\') = :masvs_area'],
    'detector' => ['COALESCE(latest.detector, \'unknown\') = :detector'],
    'session_stamp' => ['COALESCE(latest.session_stamp, \'\') = :session_stamp'],
    'q' => [
        '('
        . 'CONVERT(latest.package_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(latest.app_label USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(latest.title USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_title AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

const _COMPONENT_EXPOSURE_FILTERS = [
    'exported' => ['fp.exported = :exported'],
    'guard' => ['LOWER(COALESCE(fp.effective_guard, \'\')) = LOWER(:guard)'],
    'q' => [
        '('
        . 'CONVERT(dir.package_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_pkg AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(dir.app_label USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_label AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(fp.provider_name USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_provider AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci '
        . 'OR CONVERT(fp.authority USING utf8mb4) COLLATE utf8mb4_general_ci LIKE CAST(:q_authority AS CHAR CHARACTER SET utf8mb4) COLLATE utf8mb4_general_ci'
        . ')',
        'like',
    ],
];

/**
 * Internal: resolve transform names used in filter maps.
 */
function _xf(string $name): callable
{
    switch ($name) {
        case 'like':
            return static function ($v) {
                return '%' . $v . '%';
            };
        default:
            return static function ($v) {
                return $v;
            };
    }
}

/**
 * Build WHERE/params using the shared filter map.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _apps_dir_where(array $filters): array
{
    $map = [];
    foreach (_APPS_DIR_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for runtime deviation run filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _runtime_runs_where(array $filters): array
{
    $map = [];
    foreach (_RUNTIME_RUN_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for latest-findings explorer filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _findings_explorer_where(array $filters): array
{
    $map = [];
    foreach (_FINDINGS_EXPLORER_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Build WHERE/params for component exposure filters.
 *
 * @param array<string,mixed> $filters
 * @return array{0:string,1:array<string,mixed>}
 */
function _component_exposure_where(array $filters): array
{
    $map = [];
    foreach (_COMPONENT_EXPOSURE_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf = $tpl[1] ?? null;
        if (is_string($xf)) {
            $xf = _xf($xf);
        }
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

function _positive_limit(int $limit, int $max = 250): int
{
    return max(1, min($limit, $max));
}

/**
 * Apps Directory — paginated, with optional filters.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function apps_directory_paged(?string $category, ?string $q, bool $includeCatalogOnly, int $page, int $size): array
{
    [$where, $params] = _apps_dir_where([
        'category' => $category,
        'q' => $q,
    ]);

    if (!$includeCatalogOnly) {
        $extra = "source_state <> 'catalog_only'";
        $where = $where === '' ? ('WHERE ' . $extra) : ($where . ' AND ' . $extra);
    }

    return db_paged(
        SQL_APPS_DIR_BASE,
        SQL_APPS_DIR_COUNT,
        $where,
        SQL_APPS_DIR_ORDER,
        $params,
        $page,
        $size,
        60,
        'apps_directory_' . sha1(json_encode([$where, $params, $includeCatalogOnly], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where)
    );
}

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

/**
 * Apps Directory — simple list (no total count pagination).
 *
 * @return array<int,array<string,mixed>>
 */
function apps_directory_list(?string $category, ?string $q, int $limit = 100, int $offset = 0): array
{
    [$where, $params] = _apps_dir_where([
        'category' => $category,
        'q' => $q,
    ]);

    $limit = _positive_limit($limit, 500);
    $offset = max(0, (int)$offset);

    $sql = SQL_APPS_DIR_BASE
        . ' ' . $where . ' '
        . SQL_APPS_DIR_ORDER
        . " LIMIT $limit OFFSET $offset";

    return db_all($sql, $params);
}

/**
 * Lightweight direct probe used when paginated composition returns no rows.
 *
 * @return array<int,array<string,mixed>>
 */
function apps_directory_probe(int $limit = 10): array
{
    $limit = _positive_limit($limit, 50);
    $sql = SQL_APPS_DIR_BASE . ' ' . SQL_APPS_DIR_ORDER . " LIMIT $limit";
    return db_all($sql);
}

/**
 * Latest findings explorer across current web-facing static surfaces.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function findings_explorer_paged(?string $severity, ?string $category, ?string $masvsArea, ?string $q, int $page, int $size): array
{
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => null,
        'q' => $q,
    ]);

    return db_paged(
        SQL_FINDINGS_EXPLORER_BASE,
        SQL_FINDINGS_EXPLORER_COUNT,
        $where,
        SQL_FINDINGS_EXPLORER_ORDER,
        $params,
        $page,
        $size,
        60,
        'findings_explorer_' . sha1(json_encode([$where, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where)
    );
}

/**
 * Latest findings explorer with detector filter and optional synthetic rollups.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function findings_explorer_paged_v2(
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic,
    int $page,
    int $size
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

    return db_paged(
        SQL_FINDINGS_EXPLORER_BASE,
        SQL_FINDINGS_EXPLORER_COUNT,
        $finalWhere,
        SQL_FINDINGS_EXPLORER_ORDER,
        $params,
        $page,
        $size,
        60,
        'findings_explorer_v2_' . sha1(json_encode([$finalWhere, $params, $includeSynthetic], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $finalWhere)
    );
}

/**
 * @return string
 */
function _findings_scope_clause(?string $appScope, string $alias = 'latest'): string
{
    return match ((string)$appScope) {
        'user_apps' => "COALESCE({$alias}.profile_key, '') <> 'SYSTEM_CORE'",
        'system_oem_apps' => "COALESCE({$alias}.profile_key, '') = 'SYSTEM_CORE'",
        'google_apps' => "COALESCE({$alias}.publisher_key, '') = 'GOOGLE'",
        default => '',
    };
}

/**
 * @return array<int,string>
 */
function findings_categories(): array
{
    return web_cache_remember('findings_categories_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['category'] ?? ''),
            db_all(SQL_FINDINGS_CATEGORIES)
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_masvs_areas(): array
{
    return web_cache_remember('findings_masvs_areas_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['masvs_area'] ?? ''),
            db_all(SQL_FINDINGS_MASVS_AREAS)
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_sessions(): array
{
    return web_cache_remember('findings_sessions_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['session_stamp'] ?? ''),
            db_all("SELECT DISTINCT session_stamp FROM v_web_app_findings WHERE COALESCE(session_stamp, '') <> '' ORDER BY session_stamp DESC")
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_detectors(): array
{
    return web_cache_remember('findings_detectors_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['detector'] ?? ''),
            db_all(SQL_FINDINGS_DETECTORS)
        ));
    });
}

/**
 * @return array<string,mixed>
 */
function permission_intel_overview(): array
{
    return permission_intel_overview_for_session(null);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_session_options(): array
{
    return web_cache_remember('permission_intel_session_options_v1', 120, static function (): array {
        return db_all(SQL_PERMISSION_INTEL_SESSION_OPTIONS);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_top_dangerous(int $limit = 15): array
{
    return permission_intel_top_dangerous_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_source_breakdown(int $limit = 12): array
{
    return permission_intel_source_breakdown_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_protection_breakdown(int $limit = 12): array
{
    return permission_intel_protection_breakdown_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_sensitive_combos(int $limit = 10): array
{
    return permission_intel_sensitive_combos_for_session(null, $limit);
}

/**
 * @return array<string,mixed>
 */
function permission_intel_overview_for_session(?string $sessionStamp): array
{
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = 'permission_intel_overview_v2_' . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($surface): array {
        $sql = <<<SQL
SELECT
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT package_name) AS apps_with_permissions,
  COUNT(DISTINCT permission_name) AS distinct_permissions,
  COUNT(DISTINCT session_stamp) AS session_count,
  MAX(session_stamp) AS latest_session_stamp,
  SUM(CASE WHEN is_runtime_dangerous = 1 THEN 1 ELSE 0 END) AS dangerous_rows,
  SUM(CASE WHEN is_signature = 1 THEN 1 ELSE 0 END) AS signature_rows,
  SUM(CASE WHEN is_privileged = 1 THEN 1 ELSE 0 END) AS privileged_rows,
  SUM(CASE WHEN is_custom = 1 THEN 1 ELSE 0 END) AS custom_rows,
  COUNT(DISTINCT CASE WHEN COALESCE(custom_family, '') = 'app_defined_internal' THEN permission_name ELSE NULL END) AS app_defined_distinct,
  COUNT(DISTINCT CASE WHEN COALESCE(custom_family, '') = 'vendor_oem' THEN permission_name ELSE NULL END) AS vendor_custom_distinct,
  COUNT(DISTINCT CASE WHEN COALESCE(custom_family, '') = 'google_platform_adjacent' THEN permission_name ELSE NULL END) AS google_custom_distinct,
  COUNT(DISTINCT CASE WHEN COALESCE(custom_family, '') = 'unknown_custom' THEN permission_name ELSE NULL END) AS unknown_custom_distinct
FROM {$surface['from_sql']}
{$surface['where_sql']}
SQL;
        return db_one($sql, $surface['params']) ?? [];
    });
}

/**
 * @return array<string,mixed>
 */
function permission_intel_source_meta_for_session(?string $sessionStamp): array
{
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = 'permission_intel_source_meta_v1_' . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($surface, $sessionStamp): array {
        if ($sessionStamp !== null && trim($sessionStamp) !== '') {
            $sql = <<<SQL
SELECT
  MAX(session_stamp) AS session_stamp,
  MAX(run_status) AS run_status,
  MAX(session_usability) AS session_usability,
  MAX(created_at) AS latest_created_at,
  COUNT(DISTINCT package_name) AS apps_with_rows
FROM v_web_app_sessions
WHERE session_stamp = :session_meta
  AND session_hidden_by_default = 0
  AND session_usability = 'usable_complete'
SQL;
            return db_one($sql, ['session_meta' => $sessionStamp]) ?? [];
        }

        $sql = <<<SQL
SELECT
  COUNT(DISTINCT session_stamp) AS session_count,
  MAX(session_stamp) AS latest_session_stamp,
  MAX(created_at) AS latest_created_at,
  COUNT(DISTINCT package_name) AS apps_with_rows
FROM v_web_app_sessions
WHERE session_hidden_by_default = 0
  AND session_usability = 'usable_complete'
  AND COALESCE(permission_rows, 0) > 0
SQL;
        return db_one($sql) ?? [];
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_top_dangerous_for_session(?string $sessionStamp, int $limit = 15): array
{
    $limit = _positive_limit($limit, 50);
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = "permission_intel_top_dangerous_v2_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $surface): array {
        $sql = <<<SQL
SELECT
  permission_name,
  COUNT(DISTINCT package_name) AS app_count,
  MAX(source_family) AS source_family,
  MAX(source) AS source,
  MAX(protection) AS protection
FROM {$surface['from_sql']}
{$surface['where_sql']}
  AND is_runtime_dangerous = 1
GROUP BY permission_name
ORDER BY app_count DESC, permission_name ASC
LIMIT $limit
SQL;
        return db_all($sql, $surface['params']);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_source_breakdown_for_session(?string $sessionStamp, int $limit = 12): array
{
    $limit = _positive_limit($limit, 30);
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = "permission_intel_source_breakdown_v2_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $surface): array {
        $sql = <<<SQL
SELECT
  source_family AS source,
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT permission_name) AS distinct_permissions,
  COUNT(DISTINCT package_name) AS app_count
FROM {$surface['from_sql']}
{$surface['where_sql']}
GROUP BY source_family
ORDER BY permission_rows DESC, source_family ASC
LIMIT $limit
SQL;
        return db_all($sql, $surface['params']);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_custom_breakdown_for_session(?string $sessionStamp, int $limit = 12): array
{
    $limit = _positive_limit($limit, 30);
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = "permission_intel_custom_breakdown_v1_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $surface): array {
        $sql = <<<SQL
SELECT
  COALESCE(custom_family, 'not_custom') AS custom_family,
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT permission_name) AS distinct_permissions,
  COUNT(DISTINCT package_name) AS app_count
FROM {$surface['from_sql']}
{$surface['where_sql']}
  AND is_custom = 1
GROUP BY COALESCE(custom_family, 'not_custom')
ORDER BY permission_rows DESC, custom_family ASC
LIMIT $limit
SQL;
        return db_all($sql, $surface['params']);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_protection_breakdown_for_session(?string $sessionStamp, int $limit = 12): array
{
    $limit = _positive_limit($limit, 30);
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = "permission_intel_protection_breakdown_v2_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $surface): array {
        $sql = <<<SQL
SELECT
  protection,
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT permission_name) AS distinct_permissions
FROM {$surface['from_sql']}
{$surface['where_sql']}
GROUP BY protection
ORDER BY permission_rows DESC, protection ASC
LIMIT $limit
SQL;
        return db_all($sql, $surface['params']);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_sensitive_combos_for_session(?string $sessionStamp, int $limit = 10): array
{
    $limit = _positive_limit($limit, 20);
    $surface = _permission_intel_surface_meta($sessionStamp);
    $cacheKey = "permission_intel_sensitive_combos_v2_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $surface): array {
        $sql = <<<SQL
SELECT
  CASE
    WHEN has_location = 1 AND has_contacts = 1 THEN 'Location + Contacts'
    WHEN has_location = 1 AND has_microphone = 1 THEN 'Location + Microphone'
    WHEN has_camera = 1 AND has_microphone = 1 THEN 'Camera + Microphone'
    WHEN has_contacts = 1 AND has_accounts = 1 THEN 'Contacts + Accounts'
    WHEN has_ad_id = 1 AND has_location = 1 THEN 'Advertising ID + Location'
    WHEN has_background_location = 1 AND has_media = 1 THEN 'Background Location + Media'
    ELSE NULL
  END AS combo_label,
  COUNT(*) AS app_count
FROM (
  SELECT
    package_name,
    MAX(CASE WHEN permission_name IN ('android.permission.ACCESS_FINE_LOCATION', 'android.permission.ACCESS_COARSE_LOCATION') THEN 1 ELSE 0 END) AS has_location,
    MAX(CASE WHEN permission_name = 'android.permission.ACCESS_BACKGROUND_LOCATION' THEN 1 ELSE 0 END) AS has_background_location,
    MAX(CASE WHEN permission_name = 'android.permission.RECORD_AUDIO' THEN 1 ELSE 0 END) AS has_microphone,
    MAX(CASE WHEN permission_name = 'android.permission.CAMERA' THEN 1 ELSE 0 END) AS has_camera,
    MAX(CASE WHEN permission_name = 'android.permission.READ_CONTACTS' THEN 1 ELSE 0 END) AS has_contacts,
    MAX(CASE WHEN permission_name = 'android.permission.GET_ACCOUNTS' THEN 1 ELSE 0 END) AS has_accounts,
    MAX(CASE WHEN permission_name = 'com.google.android.gms.permission.AD_ID' THEN 1 ELSE 0 END) AS has_ad_id,
    MAX(CASE WHEN permission_name IN ('android.permission.READ_MEDIA_IMAGES', 'android.permission.READ_MEDIA_VIDEO', 'android.permission.READ_EXTERNAL_STORAGE', 'android.permission.WRITE_EXTERNAL_STORAGE') THEN 1 ELSE 0 END) AS has_media
  FROM {$surface['from_sql']}
  {$surface['where_sql']}
  GROUP BY package_name
) combos
WHERE (
  (has_location = 1 AND has_contacts = 1)
  OR (has_location = 1 AND has_microphone = 1)
  OR (has_camera = 1 AND has_microphone = 1)
  OR (has_contacts = 1 AND has_accounts = 1)
  OR (has_ad_id = 1 AND has_location = 1)
  OR (has_background_location = 1 AND has_media = 1)
)
GROUP BY combo_label
ORDER BY app_count DESC, combo_label ASC
LIMIT $limit
SQL;
        return db_all($sql, $surface['params']);
    });
}

/**
 * @return array{from_sql:string,where_sql:string,params:array<string,mixed>,mode:string}
 */
function _permission_intel_surface_meta(?string $sessionStamp): array
{
    $normalized = trim((string)($sessionStamp ?? ''));
    if ($normalized === '') {
        return [
            'from_sql' => 'v_web_permission_intel_current',
            'where_sql' => 'WHERE 1=1',
            'params' => [],
            'mode' => 'preferred',
        ];
    }

    return [
        'from_sql' => 'v_web_app_permissions',
        'where_sql' => 'WHERE session_stamp = :permission_session_stamp',
        'params' => ['permission_session_stamp' => $normalized],
        'mode' => 'session',
    ];
}

/**
 * Group findings for fleet pattern discovery.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function findings_explorer_grouped(
    string $groupBy,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic,
    int $page,
    int $size
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

    $map = [
        'title' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_TITLE_BASE,
            'group' => 'GROUP BY latest.title, latest.category, latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_TITLE_ORDER,
        ],
        'detector' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_DETECTOR_BASE,
            'group' => 'GROUP BY latest.detector, latest.category, latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_DETECTOR_ORDER,
        ],
        'app' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_APP_BASE,
            'group' => 'GROUP BY latest.app_label, latest.package_name',
            'order' => SQL_FINDINGS_GROUP_APP_ORDER,
        ],
        'masvs_area' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_MASVS_BASE,
            'group' => 'GROUP BY latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_MASVS_ORDER,
        ],
    ];
    $spec = $map[$groupBy] ?? $map['title'];
    $inner = $spec['base'] . ' ' . $finalWhere . ' ' . $spec['group'];
    $baseSql = 'SELECT * FROM (' . $inner . ') grouped';
    $countSql = 'SELECT COUNT(*) AS c FROM (' . $inner . ') grouped';

    return db_paged(
        $baseSql,
        $countSql,
        '',
        $spec['order'],
        $params,
        $page,
        $size,
        60,
        'findings_grouped_' . sha1(json_encode([$groupBy, $inner, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $groupBy)
    );
}

/**
 * @return array<string,mixed>
 */
function findings_explorer_source_summary(
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
    $subWhere = $finalWhere === '' ? '' : str_replace('latest.', 'latest2.', $finalWhere);
    $sql = sprintf(SQL_FINDINGS_EXPLORER_SOURCE_SUMMARY, $subWhere);
    return db_one($sql, $params) ?? [];
}

/**
 * @return array<string,mixed>
 */
function findings_group_detail_summary(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $finalWhere = 'WHERE ' . implode(' AND ', $clauses);
    $sql = "
        SELECT
          COUNT(*) AS finding_rows,
          COUNT(DISTINCT latest.package_name) AS affected_apps,
          COUNT(DISTINCT latest.session_stamp) AS session_count,
          SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
          SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
          SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
          SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
          SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows,
          MIN(latest.category) AS category,
          MIN(latest.masvs_area) AS masvs_area,
          MIN(latest.detector) AS detector,
          SUM(CASE WHEN COALESCE(latest.profile_key, '') = 'SYSTEM_CORE' THEN 1 ELSE 0 END) AS system_rows,
          SUM(CASE WHEN COALESCE(latest.publisher_key, '') = 'GOOGLE' THEN 1 ELSE 0 END) AS google_rows
        FROM v_web_app_findings latest
        {$finalWhere}
    ";
    return db_one($sql, $params) ?? [];
}

/**
 * @return array<int,array<string,mixed>>
 */
function findings_group_detail_apps(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic,
    int $limit = 25
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $limit = _positive_limit($limit, 100);
    $sql = "
        SELECT
          latest.package_name,
          latest.app_label,
          latest.profile_label,
          latest.publisher_key,
          latest.session_stamp,
          COUNT(*) AS finding_rows,
          SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
          SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
          SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
          SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
          SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows
        FROM v_web_app_findings latest
        WHERE " . implode(' AND ', $clauses) . "
        GROUP BY latest.package_name, latest.app_label, latest.profile_label, latest.publisher_key, latest.session_stamp
        ORDER BY high_rows DESC, medium_rows DESC, finding_rows DESC, latest.app_label ASC
        LIMIT {$limit}
    ";
    return db_all($sql, $params);
}

/**
 * @return array<int,array<string,mixed>>
 */
function findings_group_detail_examples(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic,
    int $limit = 12
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $limit = _positive_limit($limit, 50);
    $sql = SQL_FINDINGS_EXPLORER_BASE
        . ' WHERE ' . implode(' AND ', $clauses)
        . ' ' . SQL_FINDINGS_EXPLORER_ORDER
        . " LIMIT {$limit}";
    return db_all($sql, $params);
}

/**
 * @return array<int,array<string,mixed>>
 */
function static_session_health(
    int $limit = 20,
    ?string $sessionStamp = null,
    ?string $sessionType = null,
    bool $includeHidden = false
): array
{
    $limit = _positive_limit($limit, 50);
    $where = [];
    $params = [];

    if (!$includeHidden) {
        $where[] = 'session_hidden_by_default = 0';
    }
    if ($sessionStamp !== null) {
        $where[] = 'session_stamp = :rh_session';
        $params['rh_session'] = $sessionStamp;
    }
    if ($sessionType !== null) {
        $where[] = 'session_type_key = :rh_session_type';
        $params['rh_session_type'] = strtolower($sessionType);
    }

    $sql = SQL_STATIC_SESSION_HEALTH_BASE;
    if ($where) {
        $sql .= "\nWHERE " . implode("\n  AND ", $where);
    }
    $sql .= "\nORDER BY created_at DESC";
    $sql .= "\nLIMIT {$limit}";
    return db_all($sql, $params);
}

/**
 * @return array<string,mixed>
 */
function static_session_quality(): array
{
    return db_one(SQL_STATIC_SESSION_QUALITY) ?? [];
}

/**
 * @return array<string,mixed>
 */
function static_session_filter_options(int $limit = 60, bool $includeHidden = true): array
{
    $rows = static_session_health($limit, null, null, $includeHidden);
    $sessions = [];
    $types = [];
    foreach ($rows as $row) {
        $stamp = trim((string)($row['session_stamp'] ?? ''));
        $typeKey = strtolower(trim((string)($row['session_type_key'] ?? '')));
        $typeLabel = trim((string)($row['session_type_label'] ?? ''));
        if ($stamp !== '') {
            $sessions[$stamp] = $stamp;
        }
        if ($typeKey !== '') {
            $types[$typeKey] = $typeLabel !== '' ? $typeLabel : ucwords(str_replace('_', ' ', $typeKey));
        }
    }
    ksort($types);
    return [
        'sessions' => array_values($sessions),
        'types' => $types,
    ];
}

/**
 * Fleet component exposure explorer.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function component_exposure_paged(?string $exported, ?string $guard, ?string $q, int $page, int $size): array
{
    [$whereExtra, $params] = _component_exposure_where([
        'exported' => $exported,
        'guard' => $guard,
        'q' => $q,
    ]);

    $where = $whereExtra === '' ? '' : (' AND ' . preg_replace('/^WHERE\s+/i', '', $whereExtra));

    return db_paged(
        SQL_COMPONENT_EXPOSURE_BASE,
        SQL_COMPONENT_EXPOSURE_COUNT,
        $where,
        SQL_COMPONENT_EXPOSURE_ORDER,
        $params,
        $page,
        $size,
        60,
        'component_exposure_' . sha1(json_encode([$where, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where)
    );
}

/**
 * @return array<string,mixed>
 */
function component_exposure_overview(): array
{
    return db_one(SQL_COMPONENT_EXPOSURE_OVERVIEW) ?? [];
}

/**
 * Return app-level overview data merged from audit/static tables.
 *
 * @return array<string,mixed>|null
 */
function app_overview(string $packageName): ?array
{
    return db_one(
        SQL_APP_OVERVIEW,
        [
            'pkg_lookup' => $packageName,
        ]
    );
}

/**
 * Return recent static sessions for a package.
 *
 * @return array<int,array<string,mixed>>
 */
function app_sessions(string $packageName, int $limit = 24): array
{
    $limit = _positive_limit($limit, 100);
    $sql = SQL_APP_SESSIONS . " LIMIT $limit";
    return db_all(
        $sql,
        [
            'pkg_runs' => $packageName,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_findings_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_FINDINGS_SUMMARY,
        [
            'pkg_summary' => $packageName,
            'session_summary' => $sessionStamp,
        ]
    );
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
    return db_one(
        SQL_APP_PERMISSION_SUMMARY,
        [
            'pkg_permission_summary' => $packageName,
            'session_permission_summary' => $sessionStamp,
        ]
    );
}

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
    return db_one(
        SQL_APP_COMPONENT_SUMMARY,
        [
            'pkg_component_summary' => $packageName,
            'session_component_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>|null
 */
function app_report_summary(string $packageName, string $sessionStamp): ?array
{
    return db_one(
        SQL_APP_REPORT_SUMMARY,
        [
            'pkg_report_summary' => $packageName,
            'session_report_summary' => $sessionStamp,
        ]
    );
}

/**
 * @return array<string,mixed>
 */
function app_diagnostics(): array
{
    $versionRow = db_one(SQL_DIAG_DB_VERSION) ?? [];
    $countRow = db_one(SQL_DIAG_COUNTS) ?? [];

    return [
        'db_ok' => true,
        'version' => $versionRow['version'] ?? '?',
        'runs' => (int)($countRow['runs'] ?? 0),
        'static_runs' => (int)($countRow['static_runs'] ?? 0),
        'audit_snapshots' => (int)($countRow['audit_snapshots'] ?? 0),
        'audit_packages' => (int)($countRow['audit_packages'] ?? 0),
        'static_packages' => (int)($countRow['static_packages'] ?? 0),
        'app_catalog' => (int)($countRow['app_catalog'] ?? 0),
        'dynamic_runs' => (int)($countRow['dynamic_runs'] ?? 0),
        'dynamic_packages' => (int)($countRow['dynamic_packages'] ?? 0),
        'dynamic_feature_rows' => (int)($countRow['dynamic_feature_rows'] ?? 0),
        'analysis_cohorts' => (int)($countRow['analysis_cohorts'] ?? 0),
        'runtime_regime_rows' => (int)($countRow['runtime_regime_rows'] ?? 0),
    ];
}
