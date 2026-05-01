<?php
// database/db_lib/db_permission_intel.php — fleet permission intelligence SQL.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

function permission_intel_overview(): array
{
    return permission_intel_overview_for_session(null);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_session_options(): array
{
    return web_cache_remember('permission_intel_session_options_v3', 120, static function (): array {
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
    $cacheKey = 'permission_intel_overview_v3_' . sha1(json_encode($surface) ?: 'surface');
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
    $cacheKey = 'permission_intel_source_meta_v3_' . sha1(json_encode($surface) ?: 'surface');
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
  AND UPPER(COALESCE(run_status, '')) = 'COMPLETED'
  AND session_usability IN ('usable_complete', 'partial_rows')
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
  AND UPPER(COALESCE(run_status, '')) = 'COMPLETED'
  AND session_usability IN ('usable_complete', 'partial_rows')
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
    $cacheKey = "permission_intel_top_dangerous_v3_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
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
    $cacheKey = "permission_intel_source_breakdown_v3_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
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
    $cacheKey = "permission_intel_custom_breakdown_v3_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
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
    $cacheKey = "permission_intel_protection_breakdown_v3_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
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
    $cacheKey = "permission_intel_sensitive_combos_v3_{$limit}_" . sha1(json_encode($surface) ?: 'surface');
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
