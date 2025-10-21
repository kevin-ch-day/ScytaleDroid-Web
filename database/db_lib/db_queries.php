<?php
// Apps directory — base select (no WHERE/LIMIT)
const SQL_APPS_DIR_BASE = <<<SQL
WITH latest AS (
  SELECT a.package_name, MAX(s.created_at) AS max_created
  FROM permission_audit_apps a
  JOIN permission_audit_snapshots s ON s.snapshot_id = a.snapshot_id
  GROUP BY a.package_name
)
SELECT a.package_name,
       COALESCE(ad.app_label, a.app_label) AS app_label,
       COALESCE(cat.category, 'Uncategorized') AS category,
       a.grade, a.score_capped,
       s.created_at AS last_scanned,
       SUBSTRING_INDEX(s.snapshot_key, ':', -1) AS session_stamp,
       sfs.high, sfs.med, sfs.low, sfs.info
FROM latest L
JOIN permission_audit_apps a
  ON a.package_name = L.package_name
JOIN permission_audit_snapshots s
  ON s.snapshot_id = a.snapshot_id AND s.created_at = L.max_created
LEFT JOIN android_app_categories cat
  ON cat.package_name = a.package_name
LEFT JOIN android_app_definitions ad
  ON ad.package_name = a.package_name
LEFT JOIN static_findings_summary sfs
  ON sfs.package_name = a.package_name
 AND sfs.session_stamp = SUBSTRING_INDEX(s.snapshot_key, ':', -1)
SQL;

const SQL_APPS_DIR_COUNT = <<<SQL
WITH latest AS (
  SELECT a.package_name, MAX(s.created_at) AS max_created
  FROM permission_audit_apps a
  JOIN permission_audit_snapshots s ON s.snapshot_id = a.snapshot_id
  GROUP BY a.package_name
)
SELECT COUNT(*) AS c
FROM latest L
JOIN permission_audit_apps a
  ON a.package_name = L.package_name
JOIN permission_audit_snapshots s
  ON s.snapshot_id = a.snapshot_id AND s.created_at = L.max_created
LEFT JOIN android_app_categories cat
  ON cat.package_name = a.package_name
LEFT JOIN android_app_definitions ad
  ON ad.package_name = a.package_name
SQL;

// Deterministic order for pagination
const SQL_APPS_DIR_ORDER = "ORDER BY a.score_capped DESC, a.package_name";
