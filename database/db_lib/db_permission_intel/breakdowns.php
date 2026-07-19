<?php
// database/db_lib/db_permission_intel/breakdowns.php — permission intelligence aggregate breakdown queries.

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_top_dangerous_for_session(?string $sessionStamp, int $limit = 15): array
{
    $limit = _positive_limit($limit, 50);
    $cacheKey = "permission_intel_top_dangerous_v4_{$limit}_" . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
    $cacheKey = "permission_intel_source_breakdown_v4_{$limit}_" . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
    $cacheKey = "permission_intel_custom_breakdown_v4_{$limit}_" . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
    $cacheKey = "permission_intel_protection_breakdown_v4_{$limit}_" . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
