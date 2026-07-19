<?php
// database/db_lib/db_queries/app_detail.php — App detail, sessions, and report query constants.

const SQL_APP_OVERVIEW = <<<SQL
SELECT
  dir.package_name,
  dir.app_label,
  dir.category,
  dir.profile_label,
  dir.grade,
  dir.score_capped,
  0 AS dangerous_count,
  0 AS signature_count,
  0 AS vendor_count,
  NULL AS snapshot_key,
  dir.last_scanned,
  CASE
    WHEN dir.source_state IN ('permission_audit_only', 'static_findings+risk+permission_audit') THEN dir.session_stamp
    ELSE NULL
  END AS latest_audit_session,
  CASE
    WHEN dir.source_state IN ('static_findings', 'static_findings+risk', 'static_findings+risk+permission_audit') THEN dir.session_stamp
    ELSE NULL
  END AS latest_static_session,
  COALESCE(dir.high, 0) AS high,
  COALESCE(dir.med, 0) AS med,
  COALESCE(dir.low, 0) AS low,
  COALESCE(dir.info, 0) AS info,
  dir.source_state,
  NULL AS details_json,
  0 AS endpoints,
  0 AS http_cleartext,
  0 AS api_keys,
  0 AS analytics_ids,
  0 AS cloud_refs,
  0 AS ipc,
  0 AS uris,
  0 AS flags,
  0 AS certs,
  0 AS high_entropy
FROM v_web_app_directory dir
WHERE dir.package_name = :pkg_lookup
SQL;

const SQL_APP_SESSIONS = <<<SQL
SELECT
  ranked.*,
  ROW_NUMBER() OVER (
    PARTITION BY ranked.package_name
    ORDER BY
      CASE ranked.session_usability
        WHEN 'usable_complete' THEN 1
        WHEN 'partial_rows' THEN 2
        WHEN 'in_progress_no_rows' THEN 3
        WHEN 'failed' THEN 4
        ELSE 5
      END,
      ranked.created_at DESC,
      ranked.static_run_id DESC
  ) AS session_preference_rank,
  ROW_NUMBER() OVER (
    PARTITION BY ranked.package_name
    ORDER BY ranked.created_at DESC, ranked.static_run_id DESC
  ) AS session_recency_rank
FROM (
  SELECT
    metrics.*,
    CASE
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%static-batch%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%static-batch%'
        THEN 'qa'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%smoke%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%smoke%'
        THEN 'smoke'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%rerun%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%rerun%'
        THEN 'rerun'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%fast%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%fast%'
        THEN 'fast'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%single%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%single%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%one-app%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%one-app%'
        THEN 'single_app'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%all-full%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%all-full%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%rda-full%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%rda-full%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%full%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%full%'
        THEN 'full'
      ELSE 'session'
    END AS session_type_key,
    CASE
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%static-batch%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%static-batch%'
        THEN 'QA / Debug'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%smoke%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%smoke%'
        THEN 'Smoke'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%rerun%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%rerun%'
        THEN 'Rerun'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%fast%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%fast%'
        THEN 'Fast'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%single%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%single%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%one-app%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%one-app%'
        THEN 'Single App'
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%all-full%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%all-full%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%rda-full%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%rda-full%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%full%'
        THEN 'Full'
      ELSE 'Session'
    END AS session_type_label,
    CASE
      WHEN LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%qa%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%headless%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%stability%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%debug%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%static-batch%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%static-batch%'
        OR LOWER(COALESCE(metrics.session_stamp, '')) LIKE '%smoke%'
        OR LOWER(COALESCE(metrics.profile, '')) LIKE '%smoke%'
        THEN 1
      ELSE 0
    END AS session_hidden_by_default,
    CASE
      WHEN UPPER(COALESCE(metrics.run_status, '')) IN ('FAILED', 'ABORTED') THEN 'failed'
      WHEN UPPER(COALESCE(metrics.run_status, '')) IN ('STARTED', 'RUNNING', 'SCANNED', 'PERSISTING')
        AND metrics.findings_total = 0
        AND metrics.permission_rows = 0
        AND metrics.string_rows = 0
        AND metrics.audit_rows = 0
        THEN 'in_progress_no_rows'
      WHEN UPPER(COALESCE(metrics.run_status, '')) = 'COMPLETED'
        AND metrics.findings_total > 0
        AND metrics.permission_rows > 0
        AND metrics.string_rows > 0
        THEN 'usable_complete'
      WHEN UPPER(COALESCE(metrics.run_status, '')) = 'COMPLETED' THEN 'partial_rows'
      ELSE 'partial_rows'
    END AS session_usability,
    CASE
      WHEN UPPER(COALESCE(metrics.run_status, '')) = 'COMPLETED'
        AND metrics.findings_total > 0
        AND metrics.permission_rows > 0
        AND metrics.string_rows > 0
        THEN 1
      ELSE 0
    END AS is_usable_complete
  FROM (
    SELECT
      base.package_name,
      base.static_run_id,
      base.session_stamp,
      base.created_at,
      base.run_status,
      base.profile,
      base.non_canonical_reasons,
      base.findings_runtime_total,
      base.findings_capped_total,
      base.findings_capped_by_detector_json,
      (SELECT COUNT(*) FROM static_analysis_findings f WHERE f.run_id = base.static_run_id) AS findings_total,
      (SELECT COUNT(*) FROM static_analysis_findings f WHERE f.run_id = base.static_run_id AND LOWER(COALESCE(f.severity, '')) = 'high') AS high,
      (SELECT COUNT(*) FROM static_analysis_findings f WHERE f.run_id = base.static_run_id AND LOWER(COALESCE(f.severity, '')) = 'medium') AS med,
      (SELECT COUNT(*) FROM static_analysis_findings f WHERE f.run_id = base.static_run_id AND LOWER(COALESCE(f.severity, '')) = 'low') AS low,
      (SELECT COUNT(*) FROM static_analysis_findings f WHERE f.run_id = base.static_run_id AND LOWER(COALESCE(f.severity, '')) = 'info') AS info,
      (SELECT COUNT(*) FROM static_permission_matrix pm WHERE pm.run_id = base.static_run_id) AS permission_rows,
      COALESCE((SELECT MAX(ss.high_entropy) FROM static_string_summary ss WHERE ss.package_name = base.package_name AND ss.session_stamp = base.session_stamp), 0) AS high_entropy,
      COALESCE((SELECT MAX(ss.endpoints) FROM static_string_summary ss WHERE ss.package_name = base.package_name AND ss.session_stamp = base.session_stamp), 0) AS endpoints,
      COALESCE((SELECT COUNT(*) FROM static_string_summary ss WHERE ss.package_name = base.package_name AND ss.session_stamp = base.session_stamp), 0) AS string_rows,
      (SELECT MAX(pa.grade) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id) AS grade,
      (SELECT MAX(pa.score_capped) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id) AS score_capped,
      (SELECT MAX(pas.created_at) FROM permission_audit_apps pa JOIN permission_audit_snapshots pas ON pas.snapshot_id = pa.snapshot_id WHERE pa.static_run_id = base.static_run_id) AS audit_created_at,
      COALESCE((SELECT COUNT(*) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id), 0) AS audit_rows,
      COALESCE((SELECT MAX(pa.dangerous_count) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id), 0) AS dangerous_count,
      COALESCE((SELECT MAX(pa.signature_count) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id), 0) AS signature_count,
      COALESCE((SELECT MAX(pa.vendor_count) FROM permission_audit_apps pa WHERE pa.static_run_id = base.static_run_id), 0) AS vendor_count,
      COALESCE((SELECT COUNT(*) FROM static_session_run_links l WHERE l.static_run_id = base.static_run_id), 0) AS link_rows
    FROM (
      SELECT
        a.package_name,
        sar.id AS static_run_id,
        sar.session_stamp,
        sar.created_at,
        COALESCE(sar.status, 'UNKNOWN') AS run_status,
        sar.profile,
        sar.non_canonical_reasons,
        sar.findings_runtime_total,
        sar.findings_capped_total,
        sar.findings_capped_by_detector_json
      FROM static_analysis_runs sar
      JOIN app_versions av ON av.id = sar.app_version_id
      JOIN apps a ON a.id = av.app_id
      WHERE a.package_name = :pkg_runs
    ) base
  ) metrics
) ranked
ORDER BY session_recency_rank ASC, created_at DESC
SQL;

const SQL_APP_FINDINGS_SUMMARY = <<<SQL
SELECT
  latest.package_name,
  latest.session_stamp,
  MAX(sar.scope_label) AS scope_label,
  SUM(CASE WHEN LOWER(COALESCE(latest.severity, '')) = 'high' THEN 1 ELSE 0 END) AS high,
  SUM(CASE WHEN LOWER(COALESCE(latest.severity, '')) = 'medium' THEN 1 ELSE 0 END) AS med,
  SUM(CASE WHEN LOWER(COALESCE(latest.severity, '')) = 'low' THEN 1 ELSE 0 END) AS low,
  SUM(CASE WHEN LOWER(COALESCE(latest.severity, '')) = 'info' THEN 1 ELSE 0 END) AS info,
  MAX(sar.findings_runtime_total) AS findings_runtime_total,
  MAX(sar.findings_capped_total) AS findings_capped_total,
  MAX(sar.findings_capped_by_detector_json) AS findings_capped_by_detector_json,
  MAX(sfs.details) AS details,
  MAX(sar.created_at) AS created_at
FROM v_web_app_findings latest
INNER JOIN static_analysis_runs sar ON sar.id = latest.static_run_id
LEFT JOIN static_findings_summary sfs
  ON sfs.static_run_id = sar.id
WHERE latest.package_name = :pkg_summary
  AND latest.session_stamp = :session_summary
GROUP BY latest.package_name, latest.session_stamp
LIMIT 1
SQL;

const SQL_APP_FINDINGS_LIST = <<<SQL
SELECT
  latest.severity,
  latest.severity_raw,
  latest.title,
  latest.evidence,
  latest.fix,
  latest.created_at
FROM v_web_app_findings latest
WHERE latest.package_name = :pkg_findings
  AND latest.session_stamp = :session_findings
ORDER BY
  CASE LOWER(COALESCE(latest.severity, ''))
    WHEN 'critical' THEN 1
    WHEN 'high' THEN 2
    WHEN 'medium' THEN 3
    WHEN 'low' THEN 4
    ELSE 5
  END,
  latest.title ASC
SQL;

const SQL_APP_STRINGS_SUMMARY = <<<SQL
SELECT
  summary_id,
  package_name,
  session_stamp,
  endpoints,
  http_cleartext,
  api_keys,
  analytics_ids,
  cloud_refs,
  ipc,
  uris,
  flags,
  certs,
  high_entropy,
  findings_details
FROM v_web_app_string_summary
WHERE package_name = :pkg_strings_summary
  AND session_stamp = :session_strings_summary
LIMIT 1
SQL;

const SQL_APP_STRING_SAMPLES = <<<SQL
SELECT
  bucket,
  value_masked,
  src,
  tag,
  source_type,
  finding_type,
  provider,
  risk_tag,
  confidence,
  root_domain,
  resource_name,
  scheme,
  rank
FROM v_web_app_string_samples
WHERE package_name = :pkg_string_samples
  AND session_stamp = :session_string_samples
ORDER BY bucket ASC, rank ASC, sample_id ASC
SQL;

const SQL_APP_PERMISSIONS = <<<SQL
SELECT
  permission_name,
  source,
  protection,
  severity,
  is_runtime_dangerous,
  is_signature,
  is_privileged,
  is_special_access,
  is_custom
FROM v_web_app_permissions
WHERE package_name = :pkg_permissions
  AND session_stamp = :session_permissions
ORDER BY severity DESC, permission_name ASC
SQL;

const SQL_APP_PERMISSION_SUMMARY = <<<SQL
SELECT
  package_name,
  static_run_id,
  session_stamp,
  permission_rows,
  dangerous_count,
  signature_count,
  privileged_count,
  special_access_count,
  custom_count,
  max_weight
FROM v_web_app_permission_summary
WHERE package_name = :pkg_permission_summary
  AND session_stamp = :session_permission_summary
LIMIT 1
SQL;

const SQL_APP_FILEPROVIDERS = <<<SQL
SELECT
  package_name,
  session_stamp,
  provider_name,
  component_name,
  authority,
  exported,
  effective_guard,
  risk,
  read_permission,
  write_permission,
  base_permission,
  created_at
FROM v_web_app_components
WHERE package_name = :pkg_fileproviders
  AND session_stamp = :session_fileproviders
ORDER BY exported DESC, COALESCE(risk, '') DESC, provider_name ASC
SQL;

const SQL_APP_PROVIDER_ACL = <<<SQL
SELECT
  package_name,
  session_stamp,
  provider_name,
  authority,
  path,
  path_type,
  exported,
  read_guard,
  write_guard,
  read_perm,
  write_perm,
  base_perm,
  created_at
FROM v_web_app_component_acl
WHERE package_name = :pkg_provider_acl
  AND session_stamp = :session_provider_acl
ORDER BY exported DESC, provider_name ASC, path ASC
SQL;

const SQL_APP_COMPONENT_SUMMARY = <<<SQL
SELECT
  package_name,
  session_stamp,
  providers,
  exported_providers,
  weak_provider_guards,
  acl_rows
FROM v_web_app_component_summary
WHERE package_name = :pkg_component_summary
  AND session_stamp = :session_component_summary
LIMIT 1
SQL;

const SQL_APP_REPORT_SUMMARY = <<<SQL
SELECT
  package_name,
  static_run_id,
  session_stamp,
  created_at,
  run_status,
  profile,
  session_type_key,
  session_type_label,
  session_hidden_by_default,
  session_usability,
  is_usable_complete,
  non_canonical_reasons,
  grade,
  score_capped,
  audit_created_at,
  audit_rows,
  link_rows,
  findings_total,
  high,
  med,
  low,
  info,
  permission_rows,
  dangerous_count,
  signature_count,
  privileged_count,
  special_access_count,
  custom_count,
  string_rows,
  endpoints,
  http_cleartext,
  api_keys,
  analytics_ids,
  cloud_refs,
  ipc,
  uris,
  flags,
  certs,
  high_entropy,
  details_json,
  providers,
  exported_providers,
  weak_provider_guards,
  acl_rows
FROM v_web_app_report_summary
WHERE package_name = :pkg_report_summary
  AND session_stamp = :session_report_summary
LIMIT 1
SQL;
