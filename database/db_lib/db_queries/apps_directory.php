<?php
// database/db_lib/db_queries/apps_directory.php — Apps directory and category query constants.

const SQL_APPS_DIR_BASE = <<<SQL
SELECT
  dir.package_name,
  dir.app_label,
  dir.category,
  dir.profile_label,
  dir.grade,
  dir.score_capped,
  dir.last_scanned,
  dir.session_stamp,
  dir.high,
  dir.med,
  dir.low,
  dir.info,
  dir.source_state,
  COALESCE(dyn.dynamic_runs, 0) AS dynamic_runs,
  COALESCE(dyn.successful_runs, 0) AS dynamic_successful_runs,
  COALESCE(dyn.degraded_runs, 0) AS dynamic_degraded_runs,
  COALESCE(dyn.failed_runs, 0) AS dynamic_failed_runs,
  COALESCE(dyn.quota_valid_runs, 0) AS dynamic_quota_valid_runs,
  COALESCE(dyn.supplemental_valid_runs, 0) AS dynamic_supplemental_valid_runs,
  COALESCE(dyn.features_available_runs, 0) AS dynamic_features_available_runs,
  COALESCE(dyn.static_linked_runs, 0) AS dynamic_static_linked_runs,
  dyn.latest_dynamic_started_at,
  dyn.latest_dynamic_run_id,
  COALESCE(ctx.distinct_observed_domains, 0) AS dynamic_observed_domains,
  COALESCE(ctx.distinct_root_domains, 0) AS dynamic_root_domains
FROM v_web_app_directory dir
LEFT JOIN (
  SELECT
    package_name,
    COUNT(*) AS dynamic_runs,
    SUM(CASE WHEN LOWER(status) = 'success' THEN 1 ELSE 0 END) AS successful_runs,
    SUM(CASE WHEN LOWER(status) = 'degraded' THEN 1 ELSE 0 END) AS degraded_runs,
    SUM(CASE WHEN LOWER(status) = 'failed' THEN 1 ELSE 0 END) AS failed_runs,
    SUM(CASE WHEN quota_state = 'QUOTA_VALID' THEN 1 ELSE 0 END) AS quota_valid_runs,
    SUM(CASE WHEN quota_state = 'SUPPLEMENTAL_VALID' THEN 1 ELSE 0 END) AS supplemental_valid_runs,
    SUM(CASE WHEN LOWER(COALESCE(feature_state, '')) = 'features_available' THEN 1 ELSE 0 END) AS features_available_runs,
    SUM(CASE WHEN LOWER(COALESCE(static_link_state, '')) = 'static_linked' THEN 1 ELSE 0 END) AS static_linked_runs,
    MAX(started_at_utc) AS latest_dynamic_started_at,
    SUBSTRING_INDEX(
      GROUP_CONCAT(dynamic_run_id ORDER BY started_at_utc DESC, dynamic_run_id DESC SEPARATOR ','),
      ',',
      1
    ) AS latest_dynamic_run_id
  FROM v_web_runtime_run_index
  GROUP BY package_name
) dyn
  ON CONVERT(dyn.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(dir.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
LEFT JOIN (
  SELECT
    obs.package_name,
    COUNT(DISTINCT obs.observed_domain) AS distinct_observed_domains,
    COUNT(DISTINCT obs.root_domain) AS distinct_root_domains
  FROM dynamic_domain_observations obs
  GROUP BY obs.package_name
) ctx
  ON CONVERT(ctx.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(dir.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
SQL;

const SQL_APPS_DIR_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_app_directory dir
SQL;

const SQL_APPS_DIR_CATEGORIES = <<<SQL
SELECT DISTINCT category
FROM v_web_app_directory
WHERE COALESCE(TRIM(category), '') <> ''
ORDER BY category ASC
SQL;

const SQL_APPS_DIR_ORDER = "ORDER BY COALESCE(dir.score_capped, 0) DESC, dir.package_name";
