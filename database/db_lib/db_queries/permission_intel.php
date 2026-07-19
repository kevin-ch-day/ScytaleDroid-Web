<?php
// database/db_lib/db_queries/permission_intel.php — Permission intelligence query constants.

const SQL_PERMISSION_INTEL_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT package_name) AS apps_with_permissions,
  COUNT(DISTINCT permission_name) AS distinct_permissions,
  SUM(CASE WHEN is_runtime_dangerous = 1 THEN 1 ELSE 0 END) AS dangerous_rows,
  SUM(CASE WHEN is_signature = 1 THEN 1 ELSE 0 END) AS signature_rows,
  SUM(CASE WHEN is_privileged = 1 THEN 1 ELSE 0 END) AS privileged_rows,
  SUM(CASE WHEN is_custom = 1 THEN 1 ELSE 0 END) AS custom_rows
FROM v_web_permission_intel_current
SQL;

const SQL_PERMISSION_INTEL_SESSION_OPTIONS = <<<SQL
SELECT sar.session_stamp
FROM static_analysis_runs sar
JOIN static_permission_matrix spm
  ON spm.run_id = sar.id
LEFT JOIN static_analysis_sessions sas
  ON sas.static_session_id = sar.static_session_id
WHERE COALESCE(sar.session_stamp, '') <> ''
  AND UPPER(COALESCE(sar.status, '')) = 'COMPLETED'
  AND COALESCE(sas.web_visibility_default, 'public') <> 'hidden'
  AND LOWER(sar.session_stamp) NOT LIKE '%smoke%'
  AND LOWER(sar.session_stamp) NOT LIKE '%debug%'
  AND LOWER(sar.session_stamp) NOT LIKE '%qa%'
GROUP BY sar.session_stamp
HAVING COUNT(*) > 0
ORDER BY sar.session_stamp DESC
LIMIT 200
SQL;

const SQL_PERMISSION_INTEL_TOP_DANGEROUS = <<<SQL
SELECT
  permission_name,
  COUNT(DISTINCT package_name) AS app_count,
  MAX(source) AS source,
  MAX(protection) AS protection
FROM v_web_permission_intel_current
WHERE is_runtime_dangerous = 1
GROUP BY permission_name
ORDER BY app_count DESC, permission_name ASC
SQL;

const SQL_PERMISSION_INTEL_SOURCE_BREAKDOWN = <<<SQL
SELECT
  source,
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT permission_name) AS distinct_permissions,
  COUNT(DISTINCT package_name) AS app_count
FROM v_web_permission_intel_current
GROUP BY source
ORDER BY permission_rows DESC, source ASC
SQL;

const SQL_PERMISSION_INTEL_PROTECTION_BREAKDOWN = <<<SQL
SELECT
  protection,
  COUNT(*) AS permission_rows,
  COUNT(DISTINCT permission_name) AS distinct_permissions
FROM v_web_permission_intel_current
GROUP BY protection
ORDER BY permission_rows DESC, protection ASC
SQL;

const SQL_PERMISSION_INTEL_SENSITIVE_COMBOS = <<<SQL
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
  FROM v_web_permission_intel_current
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
SQL;
