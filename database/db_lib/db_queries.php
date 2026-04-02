<?php
// database/db_lib/db_queries.php

// Normalize string comparisons to utf8mb4_general_ci to avoid collation mismatches
// across legacy and newer ScytaleDroid tables.

const SQL_APPS_DIR_BASE = <<<SQL
SELECT
  pa.package_name,
  COALESCE(NULLIF(a.display_name, ''), pa.app_label, pa.package_name) AS app_label,
  COALESCE(cat.category_name, 'Uncategorized') AS category,
  COALESCE(ap.display_name, a.profile_key, 'Unclassified') AS profile_label,
  pa.grade,
  pa.score_capped,
  pas.created_at AS last_scanned,
  SUBSTRING_INDEX(pas.snapshot_key, ':', -1) AS session_stamp,
  COALESCE(sfs.high, 0) AS high,
  COALESCE(sfs.med, 0) AS med,
  COALESCE(sfs.low, 0) AS low,
  COALESCE(sfs.info, 0) AS info
FROM permission_audit_apps pa
JOIN permission_audit_snapshots pas
  ON pas.snapshot_id = pa.snapshot_id
JOIN (
  SELECT pa2.package_name,
         MAX(pas2.created_at) AS max_created
  FROM permission_audit_apps pa2
  JOIN permission_audit_snapshots pas2
    ON pas2.snapshot_id = pa2.snapshot_id
  GROUP BY pa2.package_name
) latest
  ON latest.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
 AND latest.max_created = pas.created_at
LEFT JOIN apps a
  ON a.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
LEFT JOIN android_app_categories cat
  ON cat.category_id = a.category_id
LEFT JOIN android_app_profiles ap
  ON ap.profile_key = a.profile_key
LEFT JOIN static_findings_summary sfs
  ON sfs.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
 AND sfs.session_stamp COLLATE utf8mb4_general_ci = SUBSTRING_INDEX(pas.snapshot_key, ':', -1) COLLATE utf8mb4_general_ci
SQL;

const SQL_APPS_DIR_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM permission_audit_apps pa
JOIN permission_audit_snapshots pas
  ON pas.snapshot_id = pa.snapshot_id
JOIN (
  SELECT pa2.package_name,
         MAX(pas2.created_at) AS max_created
  FROM permission_audit_apps pa2
  JOIN permission_audit_snapshots pas2
    ON pas2.snapshot_id = pa2.snapshot_id
  GROUP BY pa2.package_name
) latest
  ON latest.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
 AND latest.max_created = pas.created_at
LEFT JOIN apps a
  ON a.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
LEFT JOIN android_app_categories cat
  ON cat.category_id = a.category_id
LEFT JOIN android_app_profiles ap
  ON ap.profile_key = a.profile_key
SQL;

const SQL_APPS_DIR_ORDER = "ORDER BY COALESCE(pa.score_capped, 0) DESC, pa.package_name";

const SQL_APP_OVERVIEW = <<<SQL
SELECT
  pkg.package_name,
  COALESCE(NULLIF(a.display_name, ''), latest_audit.app_label, pkg.package_name) AS app_label,
  COALESCE(cat.category_name, 'Uncategorized') AS category,
  COALESCE(ap.display_name, a.profile_key, 'Unclassified') AS profile_label,
  latest_audit.grade,
  latest_audit.score_capped,
  latest_audit.dangerous_count,
  latest_audit.signature_count,
  latest_audit.vendor_count,
  latest_audit.snapshot_key,
  latest_audit.last_scanned,
  latest_audit.session_stamp AS latest_audit_session,
  latest_static.session_stamp AS latest_static_session,
  COALESCE(latest_static.high, 0) AS high,
  COALESCE(latest_static.med, 0) AS med,
  COALESCE(latest_static.low, 0) AS low,
  COALESCE(latest_static.info, 0) AS info,
  latest_static.details AS details_json,
  latest_strings.endpoints,
  latest_strings.http_cleartext,
  latest_strings.api_keys,
  latest_strings.analytics_ids,
  latest_strings.cloud_refs,
  latest_strings.ipc,
  latest_strings.uris,
  latest_strings.flags,
  latest_strings.certs,
  latest_strings.high_entropy
FROM (
  SELECT :pkg_lookup AS package_name
) pkg
LEFT JOIN apps a
  ON a.package_name COLLATE utf8mb4_general_ci = pkg.package_name COLLATE utf8mb4_general_ci
LEFT JOIN android_app_categories cat
  ON cat.category_id = a.category_id
LEFT JOIN android_app_profiles ap
  ON ap.profile_key = a.profile_key
LEFT JOIN (
  SELECT
    pa.package_name,
    pa.app_label,
    pa.grade,
    pa.score_capped,
    pa.dangerous_count,
    pa.signature_count,
    pa.vendor_count,
    pas.snapshot_key,
    pas.created_at AS last_scanned,
    SUBSTRING_INDEX(pas.snapshot_key, ':', -1) AS session_stamp
  FROM permission_audit_apps pa
  JOIN permission_audit_snapshots pas
    ON pas.snapshot_id = pa.snapshot_id
  JOIN (
    SELECT pa2.package_name, MAX(pas2.created_at) AS max_created
    FROM permission_audit_apps pa2
    JOIN permission_audit_snapshots pas2
      ON pas2.snapshot_id = pa2.snapshot_id
    WHERE pa2.package_name = :pkg_audit
    GROUP BY pa2.package_name
  ) latest
    ON latest.package_name COLLATE utf8mb4_general_ci = pa.package_name COLLATE utf8mb4_general_ci
   AND latest.max_created = pas.created_at
  WHERE pa.package_name = :pkg_audit_match
) latest_audit
  ON latest_audit.package_name COLLATE utf8mb4_general_ci = pkg.package_name COLLATE utf8mb4_general_ci
LEFT JOIN (
  SELECT s1.*
  FROM static_findings_summary s1
  JOIN (
    SELECT package_name, MAX(created_at) AS max_created
    FROM static_findings_summary
    WHERE package_name = :pkg_static
    GROUP BY package_name
  ) latest
    ON latest.package_name COLLATE utf8mb4_general_ci = s1.package_name COLLATE utf8mb4_general_ci
   AND latest.max_created = s1.created_at
) latest_static
  ON latest_static.package_name COLLATE utf8mb4_general_ci = pkg.package_name COLLATE utf8mb4_general_ci
LEFT JOIN static_string_summary latest_strings
  ON latest_strings.package_name COLLATE utf8mb4_general_ci = latest_static.package_name COLLATE utf8mb4_general_ci
 AND latest_strings.session_stamp COLLATE utf8mb4_general_ci = latest_static.session_stamp COLLATE utf8mb4_general_ci
SQL;

const SQL_APP_SESSIONS = <<<SQL
SELECT
  sfs.session_stamp,
  sfs.created_at,
  COALESCE(sar.status, 'UNKNOWN') AS run_status,
  sar.profile,
  sar.findings_total,
  sar.non_canonical_reasons,
  sfs.high,
  sfs.med,
  sfs.low,
  sfs.info,
  COALESCE(sss.high_entropy, 0) AS high_entropy,
  COALESCE(sss.endpoints, 0) AS endpoints,
  audits.grade,
  audits.score_capped,
  audits.audit_created_at
FROM static_findings_summary sfs
LEFT JOIN static_string_summary sss
  ON sss.package_name COLLATE utf8mb4_general_ci = sfs.package_name COLLATE utf8mb4_general_ci
 AND sss.session_stamp COLLATE utf8mb4_general_ci = sfs.session_stamp COLLATE utf8mb4_general_ci
LEFT JOIN (
  SELECT
    a.package_name,
    sar.session_stamp,
    MAX(sar.id) AS latest_run_id
  FROM static_analysis_runs sar
  JOIN app_versions av
    ON av.id = sar.app_version_id
  JOIN apps a
    ON a.id = av.app_id
  WHERE a.package_name = :pkg_runs
  GROUP BY a.package_name, sar.session_stamp
) latest_runs
  ON latest_runs.package_name COLLATE utf8mb4_general_ci = sfs.package_name COLLATE utf8mb4_general_ci
 AND latest_runs.session_stamp COLLATE utf8mb4_general_ci = sfs.session_stamp COLLATE utf8mb4_general_ci
LEFT JOIN static_analysis_runs sar
  ON sar.id = latest_runs.latest_run_id
LEFT JOIN (
  SELECT
    pa.package_name,
    SUBSTRING_INDEX(pas.snapshot_key, ':', -1) AS session_stamp,
    pa.grade,
    pa.score_capped,
    pas.created_at AS audit_created_at
  FROM permission_audit_apps pa
  JOIN permission_audit_snapshots pas
    ON pas.snapshot_id = pa.snapshot_id
  WHERE pa.package_name = :pkg_audits
) audits
  ON audits.package_name COLLATE utf8mb4_general_ci = sfs.package_name COLLATE utf8mb4_general_ci
 AND audits.session_stamp COLLATE utf8mb4_general_ci = sfs.session_stamp COLLATE utf8mb4_general_ci
WHERE sfs.package_name = :pkg_sessions
ORDER BY sfs.created_at DESC
SQL;

const SQL_APP_FINDINGS_SUMMARY = <<<SQL
SELECT
  package_name,
  session_stamp,
  scope_label,
  high,
  med,
  low,
  info,
  details,
  created_at
FROM static_findings_summary
WHERE package_name = :pkg_summary
  AND session_stamp = :session_summary
LIMIT 1
SQL;

const SQL_APP_FINDINGS_LIST = <<<SQL
SELECT
  sf.severity,
  sf.title,
  sf.evidence,
  sf.fix,
  sf.created_at
FROM static_findings sf
JOIN static_findings_summary sfs
  ON sfs.id = sf.summary_id
WHERE sfs.package_name = :pkg_findings
  AND sfs.session_stamp = :session_findings
ORDER BY
  CASE LOWER(sf.severity)
    WHEN 'critical' THEN 1
    WHEN 'high' THEN 2
    WHEN 'medium' THEN 3
    WHEN 'low' THEN 4
    ELSE 5
  END,
  sf.title ASC
SQL;

const SQL_APP_STRINGS_SUMMARY = <<<SQL
SELECT
  sss.*,
  sfs.details AS findings_details
FROM static_string_summary sss
LEFT JOIN static_findings_summary sfs
  ON sfs.package_name COLLATE utf8mb4_general_ci = sss.package_name COLLATE utf8mb4_general_ci
 AND sfs.session_stamp COLLATE utf8mb4_general_ci = sss.session_stamp COLLATE utf8mb4_general_ci
WHERE sss.package_name = :pkg_strings_summary
  AND sss.session_stamp = :session_strings_summary
LIMIT 1
SQL;

const SQL_APP_STRING_SAMPLES = <<<SQL
SELECT
  sss.bucket,
  sss.value_masked,
  sss.src,
  sss.tag,
  sss.source_type,
  sss.finding_type,
  sss.provider,
  sss.risk_tag,
  sss.confidence,
  sss.root_domain,
  sss.resource_name,
  sss.scheme,
  sss.rank
FROM static_string_selected_samples sss
JOIN static_string_summary summary
  ON summary.id = sss.summary_id
WHERE summary.package_name = :pkg_string_samples
  AND summary.session_stamp = :session_string_samples
ORDER BY sss.bucket ASC, sss.rank ASC, sss.id ASC
SQL;

const SQL_APP_PERMISSIONS = <<<SQL
SELECT
  spm.permission_name,
  spm.source,
  spm.protection,
  spm.severity,
  spm.is_runtime_dangerous,
  spm.is_signature,
  spm.is_privileged,
  spm.is_special_access,
  spm.is_custom
FROM static_permission_matrix spm
JOIN static_analysis_runs sar
  ON sar.id = spm.run_id
JOIN app_versions av
  ON av.id = sar.app_version_id
JOIN apps a
  ON a.id = av.app_id
WHERE a.package_name = :pkg_permissions
  AND sar.session_stamp = :session_permissions
ORDER BY spm.severity DESC, spm.permission_name ASC
SQL;
