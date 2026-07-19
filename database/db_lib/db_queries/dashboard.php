<?php
// database/db_lib/db_queries/dashboard.php — Dashboard overview query constants.

const SQL_DASHBOARD_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS tracked_apps,
  SUM(CASE WHEN source_state <> 'catalog_only' THEN 1 ELSE 0 END) AS analyzed_apps,
  SUM(CASE WHEN source_state = 'catalog_only' THEN 1 ELSE 0 END) AS catalog_only_apps,
  SUM(COALESCE(high, 0)) AS high_total,
  SUM(COALESCE(med, 0)) AS med_total,
  SUM(COALESCE(low, 0)) AS low_total,
  SUM(COALESCE(info, 0)) AS info_total,
  COUNT(DISTINCT CASE
    WHEN source_state <> 'catalog_only' THEN session_stamp
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
  SUM(CASE WHEN source_state <> 'catalog_only' THEN 1 ELSE 0 END) AS analyzed_apps
FROM v_web_app_directory
GROUP BY category
ORDER BY app_count DESC, category ASC
SQL;

const SQL_DASHBOARD_RECURRING_FINDINGS = <<<SQL
SELECT
  title,
  category,
  masvs_area,
  severity,
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT package_name) AS affected_apps
FROM v_web_app_findings
GROUP BY
  title,
  category,
  masvs_area,
  severity
ORDER BY affected_apps DESC, finding_rows DESC, title ASC
SQL;
