<?php
// database/db_lib/db_queries/static_health_diag.php — Static session health and diagnostics query constants.

const SQL_STATIC_SESSION_HEALTH_BASE = <<<SQL
SELECT
  session_stamp,
  created_at,
  status,
  session_type_key,
  session_type_label,
  session_hidden_by_default,
  app_runs,
  findings_ready,
  permissions_ready,
  strings_ready,
  audit_ready,
  link_ready,
  session_usability,
  is_usable_complete
FROM v_web_static_session_health
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
