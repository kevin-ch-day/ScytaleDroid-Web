<?php
// database/db_lib/db_queries/findings.php — Findings explorer query constants.

const SQL_FINDINGS_EXPLORER_BASE = <<<SQL
SELECT
  latest.package_name,
  latest.app_label,
  latest.profile_key,
  latest.profile_label,
  latest.publisher_key,
  latest.session_stamp,
  latest.version_name,
  latest.severity,
  latest.severity_raw,
  latest.category,
  latest.detector,
  latest.masvs_area,
  latest.title,
  latest.evidence,
  latest.fix,
  NULL AS cvss_score
FROM v_web_app_findings latest
SQL;
const SQL_FINDINGS_EXPLORER_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_GROUP_TITLE_BASE = <<<SQL
SELECT
  latest.title AS group_value,
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps,
  SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
  SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
  SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
  SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
  SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows,
  MAX(latest.severity) AS dominant_severity,
  latest.category AS category,
  latest.masvs_area AS masvs_area
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_GROUP_DETECTOR_BASE = <<<SQL
SELECT
  latest.detector AS group_value,
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps,
  SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
  SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
  SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
  SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
  SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows,
  MAX(latest.severity) AS dominant_severity,
  latest.category AS category,
  latest.masvs_area AS masvs_area
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_GROUP_APP_BASE = <<<SQL
SELECT
  app_label AS group_value,
  package_name,
  COUNT(*) AS finding_rows,
  SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
  SUM(CASE WHEN severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
  SUM(CASE WHEN severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
  SUM(CASE WHEN severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
  SUM(CASE WHEN severity = 'info' THEN 1 ELSE 0 END) AS info_rows,
  MAX(severity) AS dominant_severity
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_GROUP_MASVS_BASE = <<<SQL
SELECT
  latest.masvs_area AS group_value,
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps,
  SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
  SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
  SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
  SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
  SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_SOURCE_SUMMARY = <<<SQL
SELECT
  COUNT(*) AS finding_rows,
  COUNT(DISTINCT latest.package_name) AS affected_apps,
  COUNT(DISTINCT latest.session_stamp) AS session_count,
  COALESCE((
    SELECT latest2.session_stamp
    FROM v_web_app_findings latest2
    %s
    GROUP BY latest2.session_stamp
    ORDER BY COUNT(*) DESC, latest2.session_stamp DESC
    LIMIT 1
  ), '') AS primary_session_stamp
FROM v_web_app_findings latest
SQL;

const SQL_FINDINGS_EXPLORER_ORDER = <<<SQL
ORDER BY
  CASE severity
    WHEN 'critical' THEN 1
    WHEN 'high' THEN 2
    WHEN 'medium' THEN 3
    WHEN 'low' THEN 4
    ELSE 5
  END,
  app_label ASC,
  title ASC
SQL;

const SQL_FINDINGS_GROUP_TITLE_ORDER = <<<SQL
ORDER BY affected_apps DESC, finding_rows DESC, group_value ASC
SQL;

const SQL_FINDINGS_GROUP_DETECTOR_ORDER = <<<SQL
ORDER BY finding_rows DESC, affected_apps DESC, group_value ASC
SQL;

const SQL_FINDINGS_GROUP_APP_ORDER = <<<SQL
ORDER BY high_rows DESC, finding_rows DESC, group_value ASC
SQL;

const SQL_FINDINGS_GROUP_MASVS_ORDER = <<<SQL
ORDER BY high_rows DESC, finding_rows DESC, group_value ASC
SQL;

const SQL_FINDINGS_CATEGORIES = <<<SQL
SELECT DISTINCT category
FROM v_web_app_findings
WHERE COALESCE(category, '') <> ''
ORDER BY category ASC
SQL;

const SQL_FINDINGS_MASVS_AREAS = <<<SQL
SELECT DISTINCT masvs_area
FROM v_web_app_findings
WHERE COALESCE(masvs_area, '') <> ''
ORDER BY masvs_area ASC
SQL;

const SQL_FINDINGS_DETECTORS = <<<SQL
SELECT DISTINCT detector
FROM v_web_app_findings
WHERE COALESCE(detector, '') <> ''
ORDER BY detector ASC
SQL;
