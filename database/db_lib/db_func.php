<?php
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_utils.php';

/** Apps Directory – paginated, with optional filters */
function apps_directory_paged(?string $category, ?string $q, int $page, int $size): array
{
  // Build WHERE from optional filters
  [$where, $params] = sql_filters(
    ['category' => $category, 'q' => $q],
    [
      'category' => ['cat.category = :category'],
      'q'        => ['(a.package_name LIKE :q OR COALESCE(ad.app_label,a.app_label) LIKE :q)', fn($v) => "%$v%"]
    ]
  );
  return db_paged(SQL_APPS_DIR_BASE, SQL_APPS_DIR_COUNT, $where, SQL_APPS_DIR_ORDER, $params, $page, $size);
}

/** Optional: simple list (no pagination) using the same templates */
function apps_directory_list(?string $category, ?string $q, int $limit = 100, int $offset = 0): array
{
  [$where, $params] = sql_filters(
    ['category' => $category, 'q' => $q],
    [
      'category' => ['cat.category = :category'],
      'q'        => ['(a.package_name LIKE :q OR COALESCE(ad.app_label,a.app_label) LIKE :q)', fn($v) => "%$v%"]
    ]
  );
  $sql = SQL_APPS_DIR_BASE . " $where " . SQL_APPS_DIR_ORDER . " LIMIT :limit OFFSET :offset";
  return db_all($sql, array_merge($params, ['limit' => $limit, 'offset' => $offset]));
}
