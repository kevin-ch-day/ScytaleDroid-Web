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

function _positive_limit(int $limit, int $max = 250): int
{
    return max(1, min($limit, $max));
}

/**
 * Apps Directory — paginated, with optional filters.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function apps_directory_paged(?string $category, ?string $q, int $page, int $size): array
{
    [$where, $params] = _apps_dir_where([
        'category' => $category,
        'q' => $q,
    ]);

    return db_paged(
        SQL_APPS_DIR_BASE,
        SQL_APPS_DIR_COUNT,
        $where,
        SQL_APPS_DIR_ORDER,
        $params,
        $page,
        $size
    );
}

/**
 * Runtime Deviation dashboard summary.
 *
 * @return array<string,mixed>
 */
function runtime_deviation_overview(): array
{
    return db_one(SQL_RUNTIME_OVERVIEW) ?? [];
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
            'pkg_audit' => $packageName,
            'pkg_audit_match' => $packageName,
            'pkg_static' => $packageName,
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
            'pkg_audits' => $packageName,
            'pkg_sessions' => $packageName,
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
