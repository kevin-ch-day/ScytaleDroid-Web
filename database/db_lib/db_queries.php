<?php
// Apps directory — base select (portable; no CTE/WITH)
const SQL_APPS_DIR_BASE = <<<SQL
SELECT
  a.package_name,
  COALESCE(ad.app_label, a.app_label)        AS app_label,
  COALESCE(cat.category, 'Uncategorized')    AS category,
  a.grade,
  a.score_capped,
  s.created_at                                AS last_scanned,
  SUBSTRING_INDEX(s.snapshot_key, ':', -1)    AS session_stamp,
  sfs.high, sfs.med, sfs.low, sfs.info
FROM permission_audit_apps a
JOIN permission_audit_snapshots s
  ON s.snapshot_id = a.snapshot_id
JOIN (
  SELECT a2.package_name, MAX(s2.created_at) AS max_created
  FROM permission_audit_apps a2
  JOIN permission_audit_snapshots s2
    ON s2.snapshot_id = a2.snapshot_id
  GROUP BY a2.package_name
) L
  ON L.package_name = a.package_name
 AND s.created_at   = L.max_created
LEFT JOIN android_app_categories  cat ON cat.package_name = a.package_name
LEFT JOIN android_app_definitions ad  ON ad.package_name  = a.package_name
LEFT JOIN static_findings_summary sfs
  ON sfs.package_name  = a.package_name
 AND sfs.session_stamp = SUBSTRING_INDEX(s.snapshot_key, ':', -1)
SQL;

const SQL_APPS_DIR_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM permission_audit_apps a
JOIN permission_audit_snapshots s
  ON s.snapshot_id = a.snapshot_id
JOIN (
  SELECT a2.package_name, MAX(s2.created_at) AS max_created
  FROM permission_audit_apps a2
  JOIN permission_audit_snapshots s2
    ON s2.snapshot_id = a2.snapshot_id
  GROUP BY a2.package_name
) L
  ON L.package_name = a.package_name
 AND s.created_at   = L.max_created
LEFT JOIN android_app_categories  cat ON cat.package_name = a.package_name
LEFT JOIN android_app_definitions ad  ON ad.package_name  = a.package_name
SQL;

// Deterministic order for pagination (handle NULL scores)
const SQL_APPS_DIR_ORDER = "ORDER BY COALESCE(a.score_capped, 0) DESC, a.package_name";
