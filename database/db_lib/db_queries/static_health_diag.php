<?php
// database/db_lib/db_queries/static_health_diag.php — Static session health and diagnostics query constants.

const SQL_STATIC_SESSION_HEALTH_BASE = <<<SQL
SELECT *
FROM (
SELECT
  session_stamp,
  COALESCE(last_ended_at, first_created_at) AS created_at,
  session_status AS status,
  NULL AS session_type_key,
  NULL AS session_type_label,
  CASE WHEN LOWER(COALESCE(web_visibility_default, 'public')) = 'public' THEN 0 ELSE 1 END AS session_hidden_by_default,
  total_run_count AS app_runs,
  total_findings_rows AS findings_ready,
  total_permission_matrix_rows AS permissions_ready,
  total_string_summary_rows AS strings_ready,
  total_permission_risk_rows AS audit_ready,
  session_link_rows AS link_ready,
  CASE LOWER(COALESCE(usability_class, ''))
    WHEN 'ready' THEN 'usable_complete'
    WHEN 'partial' THEN 'partial_rows'
    WHEN 'in_progress' THEN 'in_progress_no_rows'
    WHEN 'failed' THEN 'failed'
    ELSE COALESCE(usability_class, 'unknown')
  END AS session_usability,
  CASE WHEN COALESCE(web_default_eligible, 0) = 1 THEN 1 ELSE 0 END AS is_usable_complete
FROM v_web_static_session_index_v2
) AS session_index
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
  (SELECT COUNT(*) FROM static_analysis_runs) AS static_runs,
  (SELECT COUNT(*) FROM static_analysis_findings) AS static_analysis_findings_rows,
  (SELECT COUNT(*) FROM v_web_app_findings) AS v_web_app_findings_rows,
  (SELECT COUNT(*) FROM permission_audit_snapshots) AS audit_snapshots,
  (SELECT COUNT(DISTINCT package_name) FROM vw_static_risk_surfaces_latest) AS audit_packages,
  (SELECT COUNT(DISTINCT package_name) FROM vw_static_finding_surfaces_latest) AS static_packages,
  (SELECT COUNT(*) FROM apps) AS app_catalog,
  (SELECT COUNT(*) FROM v_web_runtime_run_index) AS dynamic_runs,
  (SELECT COUNT(DISTINCT package_name) FROM v_web_runtime_run_index) AS dynamic_packages,
  (SELECT COUNT(*) FROM dynamic_network_features) AS dynamic_feature_rows,
  (SELECT COUNT(*) FROM analysis_cohorts) AS analysis_cohorts,
  (SELECT COUNT(*) FROM analysis_risk_regime_summary) AS runtime_regime_rows
SQL;

const SQL_STATIC_DYNAMIC_SUMMARY_PROBE = <<<SQL
SELECT
  package_name,
  app_label,
  latest_static_run_id,
  latest_dynamic_run_id,
  latest_feature_dynamic_run_id,
  summary_state
FROM v_web_static_dynamic_app_summary
ORDER BY package_name ASC
LIMIT 1
SQL;
