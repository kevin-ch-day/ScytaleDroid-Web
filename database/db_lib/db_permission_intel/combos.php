<?php
// database/db_lib/db_permission_intel/combos.php — permission intelligence sensitive combination queries.

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_sensitive_combos_for_session(?string $sessionStamp, int $limit = 10): array
{
    $limit = _positive_limit($limit, 20);
    $cacheKey = "permission_intel_sensitive_combos_v4_{$limit}_" . _permission_intel_surface_cache_key($sessionStamp);
    return web_cache_remember($cacheKey, 120, static function () use ($limit, $sessionStamp): array {
        $surface = _permission_intel_surface_meta($sessionStamp);
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
