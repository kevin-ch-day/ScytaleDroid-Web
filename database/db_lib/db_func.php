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
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
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
    return web_cache_remember('permission_intel_overview_v1', 120, static function (): array {
        return db_one(SQL_PERMISSION_INTEL_OVERVIEW) ?? [];
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_top_dangerous(int $limit = 15): array
{
    $limit = _positive_limit($limit, 50);
    return web_cache_remember("permission_intel_top_dangerous_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_PERMISSION_INTEL_TOP_DANGEROUS . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_source_breakdown(int $limit = 12): array
{
    $limit = _positive_limit($limit, 30);
    return web_cache_remember("permission_intel_source_breakdown_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_PERMISSION_INTEL_SOURCE_BREAKDOWN . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_protection_breakdown(int $limit = 12): array
{
    $limit = _positive_limit($limit, 30);
    return web_cache_remember("permission_intel_protection_breakdown_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_PERMISSION_INTEL_PROTECTION_BREAKDOWN . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_sensitive_combos(int $limit = 10): array
{
    $limit = _positive_limit($limit, 20);
    return web_cache_remember("permission_intel_sensitive_combos_v1_{$limit}", 120, static function () use ($limit): array {
        $sql = SQL_PERMISSION_INTEL_SENSITIVE_COMBOS . " LIMIT $limit";
        return db_all($sql);
    });
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
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
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
 * @return array<int,array<string,mixed>>
 */
function static_session_health(int $limit = 20): array
{
    $limit = _positive_limit($limit, 50);
    $sql = SQL_STATIC_SESSION_HEALTH . " LIMIT $limit";
    return db_all($sql);
}

/**
 * @return array<string,mixed>
 */
function static_session_quality(): array
{
    return db_one(SQL_STATIC_SESSION_QUALITY) ?? [];
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
