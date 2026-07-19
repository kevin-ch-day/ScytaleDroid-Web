<?php
// database/db_lib/db_permission_intel/session_meta.php — permission intelligence overview and session metadata.

/**
 * @return array<string,mixed>
 */
function permission_intel_overview_for_session(?string $sessionStamp): array
{
    $cacheKey = 'permission_intel_overview_v4_' . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
    $cacheKey = 'permission_intel_source_meta_v5_' . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
        $sql = <<<SQL
SELECT
  COUNT(DISTINCT session_stamp) AS session_count,
  MAX(session_stamp) AS latest_session_stamp,
  MAX(session_stamp) AS session_stamp,
  'COMPLETED' AS run_status,
  COALESCE(MAX(session_usability), 'selected_permission_surface') AS session_usability,
  NULL AS latest_created_at,
  COUNT(DISTINCT package_name) AS apps_with_rows
FROM {$surface['from_sql']}
{$surface['where_sql']}
SQL;
        return db_one($sql, $surface['params']) ?? [];
    });
}
