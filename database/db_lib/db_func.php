<?php
// database/db_lib/db_func.php
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_utils.php';

const _APPS_DIR_FILTERS = [
    'category' => ['cat.category_name = :category'],
    'q' => [
        '(pa.package_name LIKE :q OR COALESCE(NULLIF(a.display_name, \'\'), pa.app_label, pa.package_name) LIKE :q)',
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
