<?php
// database/db_lib/db_apps_directory.php — v_web_app_directory list + pagination.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

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
    $offset = max(0, (int) $offset);

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
