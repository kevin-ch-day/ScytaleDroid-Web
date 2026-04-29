<?php
// database/db_lib/db_queries.php

// Normalize string comparisons to utf8mb4_unicode_ci for current package-name joins
// while older compatibility tables are still being phased out.

const SQL_APPS_DIR_BASE = <<<SQL
SELECT
  package_name,
  app_label,
  category,
  profile_label,
  grade,
  score_capped,
  last_scanned,
  session_stamp,
  high,
  med,
  low,
  info,
  source_state
FROM v_web_app_directory
SQL;

const SQL_APPS_DIR_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_app_directory
SQL;

const SQL_APPS_DIR_ORDER = "ORDER BY COALESCE(score_capped, 0) DESC, package_name";

const SQL_DASHBOARD_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS tracked_apps,
  SUM(CASE WHEN source_state = 'static+permission_audit' THEN 1 ELSE 0 END) AS analyzed_apps,
  SUM(CASE WHEN source_state = 'catalog' THEN 1 ELSE 0 END) AS catalog_only_apps,
  SUM(COALESCE(high, 0)) AS high_total,
  SUM(COALESCE(med, 0)) AS med_total,
  SUM(COALESCE(low, 0)) AS low_total,
  COUNT(DISTINCT CASE
    WHEN source_state IN ('static', 'static+permission_audit') THEN session_stamp
    ELSE NULL
  END) AS static_sessions
FROM v_web_app_directory
SQL;

const SQL_DASHBOARD_CATEGORY_SUMMARY = <<<SQL
SELECT
  category,
  COUNT(*) AS app_count,
  ROUND(AVG(COALESCE(score_capped, 0)), 2) AS avg_score,
  SUM(COALESCE(high, 0)) AS high_total,
  SUM(CASE WHEN source_state = 'static+permission_audit' THEN 1 ELSE 0 END) AS analyzed_apps
FROM v_web_app_directory
GROUP BY category
ORDER BY app_count DESC, category ASC
SQL;

const SQL_DASHBOARD_RECURRING_FINDINGS = <<<SQL
SELECT
  f.title,
  COALESCE(f.category, 'Uncategorized') AS category,
  COALESCE(f.masvs_area, 'Unmapped') AS masvs_area,
  LOWER(COALESCE(f.severity, 'info')) AS severity,
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps
FROM vw_static_finding_surfaces_latest latest
JOIN static_analysis_findings f
  ON f.run_id = latest.static_run_id
GROUP BY
  f.title,
  COALESCE(f.category, 'Uncategorized'),
  COALESCE(f.masvs_area, 'Unmapped'),
  LOWER(COALESCE(f.severity, 'info'))
ORDER BY affected_apps DESC, finding_rows DESC, f.title ASC
SQL;

const SQL_APP_OVERVIEW = <<<SQL
SELECT
  dir.package_name,
  dir.app_label,
  dir.category,
  dir.profile_label,
  dir.grade,
  dir.score_capped,
  risk.permission_audit_dangerous_count AS dangerous_count,
  risk.permission_audit_signature_count AS signature_count,
  risk.permission_audit_vendor_count AS vendor_count,
  risk.permission_audit_snapshot_key AS snapshot_key,
  dir.last_scanned,
  CASE
    WHEN dir.source_state IN ('permission_audit', 'static+permission_audit') THEN dir.session_stamp
    ELSE NULL
  END AS latest_audit_session,
  CASE
    WHEN dir.source_state IN ('static', 'static+permission_audit') THEN dir.session_stamp
    ELSE NULL
  END AS latest_static_session,
  COALESCE(findings.canonical_high, 0) AS high,
  COALESCE(findings.canonical_med, 0) AS med,
  COALESCE(findings.canonical_low, 0) AS low,
  COALESCE(findings.canonical_info, 0) AS info,
  dir.source_state,
  summary.details AS details_json,
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
FROM v_web_app_directory dir
LEFT JOIN apps a
  ON a.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
LEFT JOIN android_app_categories cat
  ON cat.category_id = a.category_id
LEFT JOIN android_app_profiles ap
  ON ap.profile_key = a.profile_key
LEFT JOIN vw_static_finding_surfaces_latest findings
  ON findings.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND findings.session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
LEFT JOIN vw_static_risk_surfaces_latest risk
  ON risk.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND risk.permission_audit_session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
LEFT JOIN static_findings_summary summary
  ON summary.id = findings.summary_row_id
LEFT JOIN static_string_summary latest_strings
  ON latest_strings.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND latest_strings.session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
WHERE dir.package_name = :pkg_lookup
SQL;

const SQL_APP_SESSIONS = <<<SQL
SELECT
  sar.id AS static_run_id,
  sar.session_stamp,
  sar.created_at,
  COALESCE(sar.status, 'UNKNOWN') AS run_status,
  sar.profile,
  COALESCE(cf.findings_total, 0) AS findings_total,
  sar.non_canonical_reasons,
  COALESCE(cf.high, 0) AS high,
  COALESCE(cf.med, 0) AS med,
  COALESCE(cf.low, 0) AS low,
  COALESCE(cf.info, 0) AS info,
  COALESCE(pm.permission_rows, 0) AS permission_rows,
  COALESCE(sss.high_entropy, 0) AS high_entropy,
  COALESCE(sss.endpoints, 0) AS endpoints,
  COALESCE(sss.string_rows, 0) AS string_rows,
  audits.grade,
  audits.score_capped,
  audits.audit_created_at,
  COALESCE(audits.audit_rows, 0) AS audit_rows,
  COALESCE(audits.dangerous_count, 0) AS dangerous_count,
  COALESCE(audits.signature_count, 0) AS signature_count,
  COALESCE(audits.vendor_count, 0) AS vendor_count,
  COALESCE(links.link_rows, 0) AS link_rows,
  CASE
    WHEN UPPER(COALESCE(sar.status, '')) IN ('FAILED', 'ABORTED') THEN 'failed'
    WHEN UPPER(COALESCE(sar.status, '')) IN ('STARTED', 'RUNNING', 'SCANNED', 'PERSISTING')
      AND COALESCE(cf.findings_total, 0) = 0
      AND COALESCE(pm.permission_rows, 0) = 0
      AND COALESCE(sss.string_rows, 0) = 0
      AND COALESCE(audits.audit_rows, 0) = 0
      THEN 'in_progress_no_rows'
    WHEN UPPER(COALESCE(sar.status, '')) = 'COMPLETED'
      AND COALESCE(cf.findings_total, 0) > 0
      AND COALESCE(pm.permission_rows, 0) > 0
      AND COALESCE(sss.string_rows, 0) > 0
      THEN 'usable_complete'
    WHEN UPPER(COALESCE(sar.status, '')) = 'COMPLETED' THEN 'partial_rows'
    ELSE 'partial_rows'
  END AS session_usability,
  CASE
    WHEN UPPER(COALESCE(sar.status, '')) = 'COMPLETED'
      AND COALESCE(cf.findings_total, 0) > 0
      AND COALESCE(pm.permission_rows, 0) > 0
      AND COALESCE(sss.string_rows, 0) > 0
      THEN 1
    ELSE 0
  END AS is_usable_complete
FROM static_analysis_runs sar
JOIN app_versions av
  ON av.id = sar.app_version_id
JOIN apps a
  ON a.id = av.app_id
LEFT JOIN (
  SELECT
    run_id,
    COUNT(*) AS findings_total,
    SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'high' THEN 1 ELSE 0 END) AS high,
    SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'medium' THEN 1 ELSE 0 END) AS med,
    SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'low' THEN 1 ELSE 0 END) AS low,
    SUM(CASE WHEN LOWER(COALESCE(severity, '')) = 'info' THEN 1 ELSE 0 END) AS info
  FROM static_analysis_findings
  GROUP BY run_id
) cf
  ON cf.run_id = sar.id
LEFT JOIN (
  SELECT
    run_id,
    COUNT(*) AS permission_rows
  FROM static_permission_matrix
  GROUP BY run_id
) pm
  ON pm.run_id = sar.id
LEFT JOIN (
  SELECT
    package_name,
    session_stamp,
    COUNT(*) AS string_rows,
    MAX(high_entropy) AS high_entropy,
    MAX(endpoints) AS endpoints
  FROM static_string_summary
  GROUP BY package_name, session_stamp
) sss
  ON sss.package_name COLLATE utf8mb4_unicode_ci = a.package_name COLLATE utf8mb4_unicode_ci
 AND sss.session_stamp COLLATE utf8mb4_unicode_ci = sar.session_stamp COLLATE utf8mb4_unicode_ci
LEFT JOIN (
  SELECT
    pa.static_run_id,
    COUNT(*) AS audit_rows,
    MAX(pa.grade) AS grade,
    MAX(pa.score_capped) AS score_capped,
    MAX(pa.dangerous_count) AS dangerous_count,
    MAX(pa.signature_count) AS signature_count,
    MAX(pa.vendor_count) AS vendor_count,
    MAX(pas.created_at) AS audit_created_at
  FROM permission_audit_apps pa
  JOIN permission_audit_snapshots pas ON pas.snapshot_id = pa.snapshot_id
  GROUP BY pa.static_run_id
) audits
  ON audits.static_run_id = sar.id
LEFT JOIN (
  SELECT
    static_run_id,
    COUNT(*) AS link_rows
  FROM static_session_run_links
  GROUP BY static_run_id
) links
  ON links.static_run_id = sar.id
WHERE a.package_name = :pkg_runs
ORDER BY sar.created_at DESC
SQL;

const SQL_APP_FINDINGS_SUMMARY = <<<SQL
SELECT
  a.package_name,
  sar.session_stamp,
  sar.scope_label,
  SUM(CASE WHEN LOWER(COALESCE(f.severity, '')) = 'high' THEN 1 ELSE 0 END) AS high,
  SUM(CASE WHEN LOWER(COALESCE(f.severity, '')) = 'medium' THEN 1 ELSE 0 END) AS med,
  SUM(CASE WHEN LOWER(COALESCE(f.severity, '')) = 'low' THEN 1 ELSE 0 END) AS low,
  SUM(CASE WHEN LOWER(COALESCE(f.severity, '')) = 'info' THEN 1 ELSE 0 END) AS info,
  sfs.details,
  sar.created_at
FROM static_analysis_runs sar
JOIN app_versions av ON av.id = sar.app_version_id
JOIN apps a ON a.id = av.app_id
LEFT JOIN static_analysis_findings f ON f.run_id = sar.id
LEFT JOIN static_findings_summary sfs
  ON sfs.static_run_id = sar.id
WHERE a.package_name = :pkg_summary
  AND sar.session_stamp = :session_summary
GROUP BY a.package_name, sar.session_stamp, sar.scope_label, sfs.details, sar.created_at
LIMIT 1
SQL;

const SQL_APP_FINDINGS_LIST = <<<SQL
SELECT
  f.severity,
  f.title,
  f.evidence,
  f.fix,
  f.created_at
FROM static_analysis_findings f
JOIN static_analysis_runs sar
  ON sar.id = f.run_id
JOIN app_versions av
  ON av.id = sar.app_version_id
JOIN apps a
  ON a.id = av.app_id
WHERE a.package_name = :pkg_findings
  AND sar.session_stamp = :session_findings
ORDER BY
  CASE LOWER(f.severity)
    WHEN 'critical' THEN 1
    WHEN 'high' THEN 2
    WHEN 'medium' THEN 3
    WHEN 'low' THEN 4
    ELSE 5
  END,
  f.title ASC
SQL;

const SQL_APP_STRINGS_SUMMARY = <<<SQL
SELECT
  sss.*,
  sfs.details AS findings_details
FROM static_string_summary sss
LEFT JOIN static_findings_summary sfs
  ON sfs.package_name COLLATE utf8mb4_unicode_ci = sss.package_name COLLATE utf8mb4_unicode_ci
 AND sfs.session_stamp COLLATE utf8mb4_unicode_ci = sss.session_stamp COLLATE utf8mb4_unicode_ci
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

const SQL_APP_FILEPROVIDERS = <<<SQL
SELECT
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
FROM static_fileproviders
WHERE package_name = :pkg_fileproviders
  AND session_stamp = :session_fileproviders
ORDER BY exported DESC, COALESCE(risk, '') DESC, provider_name ASC
SQL;

const SQL_APP_PROVIDER_ACL = <<<SQL
SELECT
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
FROM static_provider_acl
WHERE package_name = :pkg_provider_acl
  AND session_stamp = :session_provider_acl
ORDER BY exported DESC, provider_name ASC, path ASC
SQL;

const SQL_FINDINGS_EXPLORER_BASE = <<<SQL
SELECT
  latest.package_name,
  latest.app_label,
  latest.session_stamp,
  latest.version_name,
  LOWER(COALESCE(f.severity, 'info')) AS severity,
  COALESCE(f.category, 'Uncategorized') AS category,
  COALESCE(f.detector, 'unknown') AS detector,
  COALESCE(f.masvs_area, 'Unmapped') AS masvs_area,
  f.title,
  f.evidence,
  f.fix,
  f.cvss_score
FROM vw_static_finding_surfaces_latest latest
JOIN static_analysis_findings f
  ON f.run_id = latest.static_run_id
SQL;

const SQL_FINDINGS_EXPLORER_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM vw_static_finding_surfaces_latest latest
JOIN static_analysis_findings f
  ON f.run_id = latest.static_run_id
SQL;

const SQL_FINDINGS_EXPLORER_ORDER = <<<SQL
ORDER BY
  CASE LOWER(COALESCE(f.severity, ''))
    WHEN 'critical' THEN 1
    WHEN 'high' THEN 2
    WHEN 'medium' THEN 3
    WHEN 'low' THEN 4
    ELSE 5
  END,
  latest.app_label ASC,
  f.title ASC
SQL;

const SQL_FINDINGS_CATEGORIES = <<<SQL
SELECT DISTINCT COALESCE(f.category, 'Uncategorized') AS category
FROM vw_static_finding_surfaces_latest latest
JOIN static_analysis_findings f
  ON f.run_id = latest.static_run_id
WHERE COALESCE(f.category, '') <> ''
ORDER BY category ASC
SQL;

const SQL_FINDINGS_MASVS_AREAS = <<<SQL
SELECT DISTINCT COALESCE(f.masvs_area, 'Unmapped') AS masvs_area
FROM vw_static_finding_surfaces_latest latest
JOIN static_analysis_findings f
  ON f.run_id = latest.static_run_id
WHERE COALESCE(f.masvs_area, '') <> ''
ORDER BY masvs_area ASC
SQL;

const SQL_COMPONENT_EXPOSURE_BASE = <<<SQL
SELECT
  dir.package_name,
  dir.app_label,
  dir.category,
  dir.session_stamp,
  fp.provider_name,
  fp.component_name,
  fp.authority,
  fp.exported,
  fp.effective_guard,
  fp.risk,
  fp.read_permission,
  fp.write_permission,
  fp.base_permission,
  fp.created_at
FROM v_web_app_directory dir
JOIN static_fileproviders fp
  ON fp.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND fp.session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
WHERE dir.source_state IN ('static', 'static+permission_audit')
SQL;

const SQL_COMPONENT_EXPOSURE_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_app_directory dir
JOIN static_fileproviders fp
  ON fp.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND fp.session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
WHERE dir.source_state IN ('static', 'static+permission_audit')
SQL;

const SQL_COMPONENT_EXPOSURE_ORDER = <<<SQL
ORDER BY fp.exported DESC, dir.app_label ASC, fp.provider_name ASC
SQL;

const SQL_COMPONENT_EXPOSURE_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS provider_rows,
  SUM(CASE WHEN fp.exported = 1 THEN 1 ELSE 0 END) AS exported_rows,
  SUM(CASE WHEN fp.exported = 1 AND LOWER(COALESCE(fp.effective_guard, '')) IN ('', 'none', 'weak') THEN 1 ELSE 0 END) AS weak_guard_rows,
  COUNT(DISTINCT dir.package_name) AS affected_apps
FROM v_web_app_directory dir
JOIN static_fileproviders fp
  ON fp.package_name COLLATE utf8mb4_unicode_ci = dir.package_name COLLATE utf8mb4_unicode_ci
 AND fp.session_stamp COLLATE utf8mb4_unicode_ci = dir.session_stamp COLLATE utf8mb4_unicode_ci
WHERE dir.source_state IN ('static', 'static+permission_audit')
SQL;

const SQL_STATIC_SESSION_HEALTH = <<<SQL
SELECT
  sar.session_stamp,
  MAX(sar.created_at) AS created_at,
  MAX(COALESCE(sar.status, 'UNKNOWN')) AS status,
  COUNT(*) AS app_runs,
  SUM(CASE WHEN COALESCE(f.c, 0) > 0 THEN 1 ELSE 0 END) AS findings_ready,
  SUM(CASE WHEN COALESCE(pm.c, 0) > 0 THEN 1 ELSE 0 END) AS permissions_ready,
  SUM(CASE WHEN COALESCE(ss.c, 0) > 0 THEN 1 ELSE 0 END) AS strings_ready,
  SUM(CASE WHEN COALESCE(pa.c, 0) > 0 THEN 1 ELSE 0 END) AS audit_ready,
  SUM(CASE WHEN COALESCE(links.c, 0) > 0 THEN 1 ELSE 0 END) AS link_ready
FROM static_analysis_runs sar
JOIN app_versions av
  ON av.id = sar.app_version_id
JOIN apps a
  ON a.id = av.app_id
LEFT JOIN (
  SELECT run_id, COUNT(*) AS c
  FROM static_analysis_findings
  GROUP BY run_id
) f
  ON f.run_id = sar.id
LEFT JOIN (
  SELECT run_id, COUNT(*) AS c
  FROM static_permission_matrix
  GROUP BY run_id
) pm
  ON pm.run_id = sar.id
LEFT JOIN (
  SELECT package_name, session_stamp, COUNT(*) AS c
  FROM static_string_summary
  GROUP BY package_name, session_stamp
) ss
  ON ss.package_name COLLATE utf8mb4_unicode_ci = a.package_name COLLATE utf8mb4_unicode_ci
 AND ss.session_stamp COLLATE utf8mb4_unicode_ci = sar.session_stamp COLLATE utf8mb4_unicode_ci
LEFT JOIN (
  SELECT static_run_id, COUNT(*) AS c
  FROM permission_audit_apps
  GROUP BY static_run_id
) pa
  ON pa.static_run_id = sar.id
LEFT JOIN (
  SELECT static_run_id, COUNT(*) AS c
  FROM static_session_run_links
  GROUP BY static_run_id
) links
  ON links.static_run_id = sar.id
GROUP BY sar.session_stamp
ORDER BY created_at DESC
SQL;

const SQL_STATIC_SESSION_QUALITY = <<<SQL
SELECT
  COUNT(*) AS total_static_runs,
  SUM(CASE WHEN UPPER(COALESCE(status, '')) = 'COMPLETED' THEN 1 ELSE 0 END) AS completed_runs,
  SUM(CASE WHEN UPPER(COALESCE(status, '')) IN ('STARTED', 'RUNNING', 'SCANNED', 'PERSISTING') THEN 1 ELSE 0 END) AS in_progress_runs,
  SUM(CASE WHEN UPPER(COALESCE(status, '')) IN ('FAILED', 'ABORTED') THEN 1 ELSE 0 END) AS failed_runs,
  COUNT(DISTINCT session_stamp) AS session_count
FROM static_analysis_runs
SQL;

const SQL_DIAG_DB_VERSION = <<<SQL
SELECT VERSION() AS version
SQL;

const SQL_DIAG_COUNTS = <<<SQL
SELECT
  (SELECT COUNT(*) FROM runs) AS runs,
  (SELECT COUNT(*) FROM static_analysis_runs) AS static_runs,
  (SELECT COUNT(*) FROM permission_audit_snapshots) AS audit_snapshots,
  (SELECT COUNT(DISTINCT package_name) FROM vw_static_risk_surfaces_latest) AS audit_packages,
  (SELECT COUNT(DISTINCT package_name) FROM vw_static_finding_surfaces_latest) AS static_packages,
  (SELECT COUNT(*) FROM apps) AS app_catalog,
  (SELECT COUNT(*) FROM dynamic_sessions) AS dynamic_runs,
  (SELECT COUNT(DISTINCT package_name) FROM dynamic_sessions) AS dynamic_packages,
  (SELECT COUNT(*) FROM dynamic_network_features) AS dynamic_feature_rows,
  (SELECT COUNT(*) FROM analysis_cohorts) AS analysis_cohorts,
  (SELECT COUNT(*) FROM analysis_risk_regime_summary) AS runtime_regime_rows
SQL;

const SQL_RUNTIME_OVERVIEW = <<<SQL
SELECT
  (SELECT COUNT(*) FROM dynamic_sessions) AS dynamic_runs,
  (SELECT COUNT(DISTINCT package_name) FROM dynamic_sessions) AS dynamic_packages,
  (SELECT COUNT(*) FROM dynamic_sessions WHERE LOWER(status) = 'success') AS successful_runs,
  (SELECT COUNT(*) FROM dynamic_sessions WHERE LOWER(status) = 'degraded') AS degraded_runs,
  (SELECT COUNT(*) FROM dynamic_sessions WHERE LOWER(status) = 'failed') AS failed_runs,
  (SELECT COUNT(*) FROM dynamic_network_features) AS feature_rows,
  (SELECT COUNT(*) FROM dynamic_network_indicators) AS indicator_rows,
  (SELECT COUNT(*) FROM dynamic_session_issues) AS issue_rows,
  (SELECT COUNT(*) FROM analysis_cohorts) AS cohorts,
  (SELECT COUNT(*) FROM analysis_ml_app_phase_model_metrics) AS model_metric_rows,
  (SELECT COUNT(*) FROM analysis_risk_regime_summary) AS risk_regime_rows
SQL;

const SQL_RUNTIME_RUNS_BASE = <<<SQL
SELECT
  dynamic_run_id,
  package_name,
  app_label,
  status,
  tier,
  run_profile,
  interaction_level,
  started_at_utc,
  duration_seconds,
  grade,
  countable,
  valid_dataset_run,
  invalid_reason_code,
  pcap_bytes,
  network_signal_quality,
  packet_count,
  bytes_per_sec,
  packets_per_sec,
  low_signal,
  issue_count,
  static_grade,
  dynamic_grade_if,
  dynamic_score_if,
  final_regime_if,
  feature_state,
  static_link_state
FROM v_web_runtime_run_index
SQL;

const SQL_RUNTIME_RUNS_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_runtime_run_index
SQL;

const SQL_RUNTIME_RUNS_ORDER = "ORDER BY started_at_utc DESC, dynamic_run_id DESC";

const SQL_APP_DYNAMIC_SUMMARY = <<<SQL
SELECT
  COUNT(*) AS dynamic_runs,
  SUM(CASE WHEN LOWER(status) = 'success' THEN 1 ELSE 0 END) AS successful_runs,
  SUM(CASE WHEN LOWER(status) = 'degraded' THEN 1 ELSE 0 END) AS degraded_runs,
  SUM(CASE WHEN LOWER(status) = 'failed' THEN 1 ELSE 0 END) AS failed_runs,
  MAX(started_at_utc) AS latest_started_at,
  SUM(CASE WHEN pcap_valid = 1 THEN 1 ELSE 0 END) AS valid_pcaps,
  SUM(CASE WHEN countable = 1 THEN 1 ELSE 0 END) AS countable_runs,
  COUNT(DISTINCT tier) AS tier_count
FROM dynamic_sessions
WHERE package_name = :pkg_dynamic_summary
SQL;

const SQL_APP_DYNAMIC_RUNS = <<<SQL
SELECT
  ds.dynamic_run_id,
  ds.package_name,
  ds.status,
  ds.tier,
  COALESCE(ds.operator_run_profile, nf.run_profile, ds.profile_key, 'unknown') AS run_profile,
  COALESCE(ds.operator_interaction_level, nf.interaction_level, 'unknown') AS interaction_level,
  ds.started_at_utc,
  ds.ended_at_utc,
  ds.duration_seconds,
  ds.grade,
  ds.network_signal_quality,
  ds.pcap_valid,
  ds.pcap_bytes,
  ds.countable,
  ds.valid_dataset_run,
  ds.invalid_reason_code,
  nf.packet_count,
  nf.bytes_per_sec,
  nf.packets_per_sec,
  nf.low_signal,
  COALESCE(issues.issue_count, 0) AS issue_count
FROM dynamic_sessions ds
LEFT JOIN dynamic_network_features nf
  ON nf.dynamic_run_id = ds.dynamic_run_id
LEFT JOIN (
  SELECT dynamic_run_id, COUNT(*) AS issue_count
  FROM dynamic_session_issues
  GROUP BY dynamic_run_id
) issues
  ON issues.dynamic_run_id = ds.dynamic_run_id
WHERE ds.package_name = :pkg_dynamic_runs
ORDER BY ds.started_at_utc DESC, ds.dynamic_run_id DESC
SQL;

const SQL_DYNAMIC_RUN_DETAIL = <<<SQL
SELECT *
FROM v_web_runtime_run_detail
WHERE dynamic_run_id = :dynamic_run_id
LIMIT 1
SQL;

const SQL_DYNAMIC_RUN_INDICATORS = <<<SQL
SELECT
  indicator_type,
  indicator_value,
  indicator_count,
  indicator_source,
  meta_json,
  created_at
FROM dynamic_network_indicators
WHERE dynamic_run_id = :indicator_run_id
ORDER BY indicator_type ASC, indicator_count DESC, indicator_value ASC
SQL;

const SQL_DYNAMIC_RUN_ISSUES = <<<SQL
SELECT
  issue_code,
  details_json,
  created_at
FROM dynamic_session_issues
WHERE dynamic_run_id = :issue_run_id
ORDER BY created_at ASC, id ASC
SQL;

const SQL_DYNAMIC_RUN_COHORTS = <<<SQL
SELECT
  cohort_id,
  run_role,
  included,
  exclude_reason,
  evidence_pack_sha256,
  pcap_sha256,
  created_at_utc
FROM analysis_cohort_runs
WHERE dynamic_run_id = :cohort_run_id
ORDER BY created_at_utc DESC, cohort_id ASC
SQL;

const SQL_DYNAMIC_RUN_MODEL_METRICS = <<<SQL
SELECT
  m.cohort_id,
  m.phase,
  m.model_key,
  m.windows_total,
  m.windows_flagged,
  m.flagged_pct,
  m.training_mode,
  m.ml_schema_version,
  m.created_at_utc
FROM analysis_cohort_runs acr
JOIN analysis_ml_app_phase_model_metrics m
  ON m.cohort_id COLLATE utf8mb4_general_ci = acr.cohort_id COLLATE utf8mb4_general_ci
 AND m.package_name COLLATE utf8mb4_general_ci = acr.package_name COLLATE utf8mb4_general_ci
WHERE acr.dynamic_run_id = :model_run_id
ORDER BY m.created_at_utc DESC, m.phase ASC, m.model_key ASC
SQL;

const SQL_DYNAMIC_RUN_RISK_REGIMES = <<<SQL
SELECT
  rr.cohort_id,
  rr.static_score,
  rr.static_grade,
  rr.dynamic_score_if,
  rr.dynamic_grade_if,
  rr.final_regime_if,
  rr.notes_json,
  rr.created_at_utc
FROM analysis_cohort_runs acr
JOIN analysis_risk_regime_summary rr
  ON rr.cohort_id COLLATE utf8mb4_general_ci = acr.cohort_id COLLATE utf8mb4_general_ci
 AND rr.package_name COLLATE utf8mb4_general_ci = acr.package_name COLLATE utf8mb4_general_ci
WHERE acr.dynamic_run_id = :regime_run_id
ORDER BY rr.created_at_utc DESC, rr.cohort_id ASC
SQL;
