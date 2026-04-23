<?php
// database/db_lib/db_queries.php

// Normalize string comparisons to utf8mb4_general_ci to avoid collation mismatches
// across legacy and newer ScytaleDroid tables.

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

const SQL_DIAG_DB_VERSION = <<<SQL
SELECT VERSION() AS version
SQL;

const SQL_DIAG_COUNTS = <<<SQL
SELECT
  (SELECT COUNT(*) FROM runs) AS runs,
  (SELECT COUNT(*) FROM static_analysis_runs) AS static_runs,
  (SELECT COUNT(*) FROM permission_audit_snapshots) AS audit_snapshots,
  (SELECT COUNT(DISTINCT package_name) FROM permission_audit_apps) AS audit_packages,
  (SELECT COUNT(DISTINCT package_name) FROM static_findings_summary) AS static_packages,
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
