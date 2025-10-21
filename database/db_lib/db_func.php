<?php
// database/db_lib/db_func.php
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_utils.php';

/**
 * Shared filter map for the apps directory.
 * - 'q' uses a LIKE transform to support wildcards/partial matches.
 */
const _APPS_DIR_FILTERS = [
    'category' => ['cat.category = :category'],
    'q'        => ['(a.package_name LIKE :q OR COALESCE(ad.app_label, a.app_label) LIKE :q)', 'like'],
];

/**
 * Internal: apply transform names used in filter maps.
 *
 * @param string $name
 * @return callable
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
    // Resolve named transforms into callables
    $map = [];
    foreach (_APPS_DIR_FILTERS as $k => $tpl) {
        $sql = $tpl[0] ?? '';
        $xf  = $tpl[1] ?? null;
        if (is_string($xf)) $xf = _xf($xf);
        $map[$k] = [$sql, $xf];
    }
    return sql_filters($filters, $map);
}

/**
 * Apps Directory — paginated, with optional filters.
 *
 * @param string|null $category
 * @param string|null $q
 * @param int $page
 * @param int $size
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function apps_directory_paged(?string $category, ?string $q, int $page, int $size): array
{
    [$where, $params] = _apps_dir_where(['category' => $category, 'q' => $q]);

    // db_paged() handles LIMIT/OFFSET safely (inlined ints), keeps params bound.
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
 * NOTE: LIMIT/OFFSET are inlined (validated ints) for MySQL native prepares.
 *
 * @param string|null $category
 * @param string|null $q
 * @param int $limit
 * @param int $offset
 * @return array<int,array<string,mixed>>
 */
function apps_directory_list(?string $category, ?string $q, int $limit = 100, int $offset = 0): array
{
    [$where, $params] = _apps_dir_where(['category' => $category, 'q' => $q]);

    $limit  = max(1, (int)$limit);
    $offset = max(0, (int)$offset);

    $sql = SQL_APPS_DIR_BASE
        . ' ' . $where . ' '
        . SQL_APPS_DIR_ORDER
        . " LIMIT $limit OFFSET $offset";

    return db_all($sql, $params);
}
