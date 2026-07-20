<?php
// database/db_lib/db_queries/dynamic_runtime.php — Fleet runtime and dynamic overview query constants.

const SQL_RUNTIME_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS dynamic_runs,
  COUNT(DISTINCT package_name) AS dynamic_packages,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(status, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'success' THEN 1 ELSE 0 END) AS successful_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(status, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'degraded' THEN 1 ELSE 0 END) AS degraded_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(status, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'failed' THEN 1 ELSE 0 END) AS failed_runs,
  SUM(CASE WHEN CONVERT(COALESCE(quota_state, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'QUOTA_VALID' THEN 1 ELSE 0 END) AS quota_valid_runs,
  SUM(CASE WHEN CONVERT(COALESCE(quota_state, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'SUPPLEMENTAL_VALID' THEN 1 ELSE 0 END) AS supplemental_valid_runs,
  SUM(CASE WHEN valid_dataset_run = 1 AND LOWER(CONVERT(COALESCE(run_profile, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'baseline_idle' AND COALESCE(baseline_not_idle, 0) = 0 AND countable = 1 THEN 1 ELSE 0 END) AS strict_idle_runs,
  SUM(CASE WHEN valid_dataset_run = 1 AND LOWER(CONVERT(COALESCE(run_profile, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'baseline_idle' AND COALESCE(baseline_not_idle, 0) = 1 AND countable = 0 AND COALESCE(low_signal, 0) = 0 THEN 1 ELSE 0 END) AS quiescent_fg_runs,
  SUM(CASE WHEN valid_dataset_run = 1 AND (LOWER(CONVERT(COALESCE(run_profile, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci LIKE 'interaction_%' OR LOWER(CONVERT(COALESCE(run_profile, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci LIKE '%interactive%') THEN 1 ELSE 0 END) AS interactive_raw_runs,
  SUM(CASE WHEN CONVERT(COALESCE(technical_validity_state, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'TECH_INVALID' THEN 1 ELSE 0 END) AS invalid_or_skipped_runs,
  SUM(CASE WHEN CONVERT(COALESCE(technical_validity_state, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'TECH_LEGACY_UNKNOWN' THEN 1 ELSE 0 END) AS unevaluated_historical_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(static_link_state, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'static_linked' THEN 1 ELSE 0 END) AS static_linked_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(static_link_state, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'missing_static_run_id' THEN 1 ELSE 0 END) AS missing_static_link_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(feature_state, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'features_available' THEN 1 ELSE 0 END) AS features_available_runs,
  SUM(CASE WHEN LOWER(CONVERT(COALESCE(feature_state, '') USING utf8mb4)) COLLATE utf8mb4_unicode_ci = 'missing_features' THEN 1 ELSE 0 END) AS missing_feature_runs,
  (SELECT COUNT(*) FROM dynamic_network_features) AS feature_rows,
  (SELECT COUNT(*) FROM dynamic_network_indicators) AS indicator_rows,
  (SELECT COUNT(*) FROM dynamic_domain_observations) AS domain_observation_rows,
  (SELECT COUNT(DISTINCT observed_domain) FROM dynamic_domain_observations) AS observed_domains,
  (SELECT COUNT(DISTINCT root_domain) FROM dynamic_domain_observations) AS root_domains,
  (SELECT COUNT(*) FROM dynamic_service_catalog WHERE is_active = 1) AS service_catalog_rows,
  (SELECT COUNT(*) FROM dynamic_signal_catalog WHERE is_active = 1) AS signal_catalog_rows,
  (SELECT COUNT(*) FROM dynamic_session_issues) AS issue_rows,
  (SELECT COUNT(*) FROM analysis_cohorts) AS cohorts,
  (SELECT COUNT(*) FROM analysis_ml_app_phase_model_metrics) AS model_metric_rows,
  (SELECT COUNT(*) FROM analysis_risk_regime_summary) AS risk_regime_rows
FROM v_web_runtime_run_index
SQL;
const SQL_RUNTIME_RUNS_BASE = <<<SQL
SELECT
  rri.dynamic_run_id,
  rri.package_name,
  rri.app_label,
  rri.status,
  rri.tier,
  rri.run_profile,
  rri.interaction_level,
  rri.started_at_utc,
  rri.duration_seconds,
  rri.grade,
  rri.technical_validity_state,
  rri.quota_state,
  rri.cohort_eligibility_state,
  rri.pcap_bytes,
  rri.network_signal_quality,
  rri.packet_count,
  rri.bytes_per_sec,
  rri.packets_per_sec,
  rri.low_signal,
  rri.issue_count,
  rri.static_grade,
  rri.dynamic_grade_if,
  rri.dynamic_score_if,
  rri.final_regime_if,
  rri.feature_state,
  rri.static_link_state,
  COALESCE(ctx.domain_observation_rows, 0) AS domain_observation_rows,
  COALESCE(ctx.distinct_observed_domains, 0) AS distinct_observed_domains,
  COALESCE(ctx.distinct_root_domains, 0) AS distinct_root_domains,
  COALESCE(ctx.first_party_domain_rows, 0) AS first_party_domain_rows,
  COALESCE(ctx.third_party_domain_rows, 0) AS third_party_domain_rows,
  COALESCE(ctx.matched_service_count, 0) AS matched_service_count,
  COALESCE(ctx.matched_signal_count, 0) AS matched_signal_count
FROM v_web_runtime_run_index rri
LEFT JOIN (
  SELECT
    obs.dynamic_run_id,
    COUNT(DISTINCT obs.observation_id) AS domain_observation_rows,
    COUNT(DISTINCT obs.observed_domain) AS distinct_observed_domains,
    COUNT(DISTINCT obs.root_domain) AS distinct_root_domains,
    COUNT(DISTINCT CASE WHEN LOWER(TRIM(COALESCE(obs.owner_class, ''))) = 'first_party' THEN obs.observation_id ELSE NULL END) AS first_party_domain_rows,
    COUNT(DISTINCT CASE WHEN LOWER(TRIM(COALESCE(obs.owner_class, ''))) = 'third_party' THEN obs.observation_id ELSE NULL END) AS third_party_domain_rows,
    COUNT(DISTINCT svc.service_id) AS matched_service_count,
    COUNT(DISTINCT sig.signal_id) AS matched_signal_count
  FROM dynamic_domain_observations obs
  LEFT JOIN dynamic_service_domain_map sdm
    ON sdm.is_active = 1
   AND (
        LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
        OR CONVERT(sdm.package_name_scope USING utf8mb4) COLLATE utf8mb4_general_ci =
           CONVERT(obs.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
   )
   AND (
        (
          UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
          AND LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
        )
        OR
        (
          UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
          AND (
            LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
            OR LOWER(TRIM(COALESCE(obs.observed_domain, ''))) LIKE
               CONCAT('%.', LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))))
          )
        )
   )
  LEFT JOIN dynamic_service_catalog svc
    ON svc.service_id = sdm.service_id
   AND svc.is_active = 1
  LEFT JOIN dynamic_service_signal_map ssm
    ON ssm.service_id = svc.service_id
   AND ssm.is_active = 1
  LEFT JOIN dynamic_signal_catalog sig
    ON sig.signal_id = ssm.signal_id
   AND sig.is_active = 1
  GROUP BY obs.dynamic_run_id
) ctx
  ON ctx.dynamic_run_id = rri.dynamic_run_id
SQL;

const SQL_RUNTIME_RUNS_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_runtime_run_index rri
SQL;

const SQL_RUNTIME_RUNS_ORDER = "ORDER BY rri.started_at_utc DESC, rri.dynamic_run_id DESC";

const SQL_RUNTIME_TOP_SERVICES = <<<SQL
SELECT
  svc.service_key,
  svc.display_name,
  svc.owner_name,
  svc.owner_class,
  svc.service_category,
  svc.primary_use_case,
  COUNT(DISTINCT obs.package_name) AS package_count,
  COUNT(DISTINCT obs.dynamic_run_id) AS observed_run_count,
  COUNT(DISTINCT LOWER(TRIM(obs.observed_domain))) AS distinct_domains,
  COUNT(DISTINCT LOWER(TRIM(obs.root_domain))) AS distinct_root_domains,
  COUNT(DISTINCT obs.observation_id) AS observation_rows,
  SUM(COALESCE(obs.indicator_count, 0)) AS total_indicator_hits,
  COUNT(DISTINCT sig.signal_id) AS matched_signal_count,
  GROUP_CONCAT(DISTINCT sig.display_name ORDER BY sig.display_name SEPARATOR ', ') AS signal_names_csv,
  GROUP_CONCAT(DISTINCT sig.focus_area ORDER BY sig.focus_area SEPARATOR ', ') AS signal_focus_areas_csv,
  GROUP_CONCAT(DISTINCT sig.severity_hint ORDER BY sig.severity_hint SEPARATOR ', ') AS signal_severity_hints_csv
FROM dynamic_domain_observations obs
JOIN dynamic_service_domain_map sdm
  ON sdm.is_active = 1
 AND (
      LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
      OR CONVERT(sdm.package_name_scope USING utf8mb4) COLLATE utf8mb4_general_ci =
         CONVERT(obs.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
 )
 AND (
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
        AND LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
      )
      OR
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
        AND (
          LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
          OR LOWER(TRIM(COALESCE(obs.observed_domain, ''))) LIKE
             CONCAT('%.', LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))))
        )
      )
 )
JOIN dynamic_service_catalog svc
  ON svc.service_id = sdm.service_id
 AND svc.is_active = 1
LEFT JOIN dynamic_service_signal_map ssm
  ON ssm.service_id = svc.service_id
 AND ssm.is_active = 1
LEFT JOIN dynamic_signal_catalog sig
  ON sig.signal_id = ssm.signal_id
 AND sig.is_active = 1
GROUP BY
  svc.service_key,
  svc.display_name,
  svc.owner_name,
  svc.owner_class,
  svc.service_category,
  svc.primary_use_case
ORDER BY total_indicator_hits DESC, distinct_domains DESC, svc.service_key ASC
SQL;

const SQL_RUNTIME_TOP_SIGNALS = <<<SQL
SELECT
  sig.signal_key,
  sig.display_name,
  sig.signal_family,
  sig.focus_area,
  sig.severity_hint,
  COUNT(DISTINCT obs.package_name) AS package_count,
  COUNT(DISTINCT obs.dynamic_run_id) AS observed_run_count,
  COUNT(DISTINCT svc.service_id) AS matched_service_count,
  COUNT(DISTINCT LOWER(TRIM(obs.observed_domain))) AS distinct_domains,
  COUNT(DISTINCT LOWER(TRIM(obs.root_domain))) AS distinct_root_domains,
  COUNT(DISTINCT obs.observation_id) AS observation_rows,
  SUM(COALESCE(obs.indicator_count, 0)) AS total_indicator_hits,
  GROUP_CONCAT(DISTINCT svc.display_name ORDER BY svc.display_name SEPARATOR ', ') AS service_names_csv,
  GROUP_CONCAT(DISTINCT svc.service_category ORDER BY svc.service_category SEPARATOR ', ') AS service_categories_csv
FROM dynamic_domain_observations obs
JOIN dynamic_service_domain_map sdm
  ON sdm.is_active = 1
 AND (
      LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
      OR CONVERT(sdm.package_name_scope USING utf8mb4) COLLATE utf8mb4_general_ci =
         CONVERT(obs.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
 )
 AND (
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
        AND LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
      )
      OR
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
        AND (
          LOWER(TRIM(COALESCE(obs.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
          OR LOWER(TRIM(COALESCE(obs.observed_domain, ''))) LIKE
             CONCAT('%.', LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))))
        )
      )
 )
JOIN dynamic_service_catalog svc
  ON svc.service_id = sdm.service_id
 AND svc.is_active = 1
JOIN dynamic_service_signal_map ssm
  ON ssm.service_id = svc.service_id
 AND ssm.is_active = 1
JOIN dynamic_signal_catalog sig
  ON sig.signal_id = ssm.signal_id
 AND sig.is_active = 1
GROUP BY
  sig.signal_key,
  sig.display_name,
  sig.signal_family,
  sig.focus_area,
  sig.severity_hint
ORDER BY total_indicator_hits DESC, distinct_domains DESC, sig.signal_key ASC
SQL;

const SQL_RUNTIME_TOP_DOMAINS = <<<SQL
SELECT
  obs.observed_domain,
  obs.root_domain,
  obs.package_count,
  obs.observed_run_count,
  obs.observation_rows,
  obs.total_indicator_hits,
  obs.indicator_types_csv,
  obs.owner_classes_csv,
  obs.role_classes_csv,
  COUNT(DISTINCT svc.service_id) AS matched_service_count,
  GROUP_CONCAT(DISTINCT svc.display_name ORDER BY svc.display_name SEPARATOR ', ') AS service_names_csv,
  GROUP_CONCAT(DISTINCT svc.service_category ORDER BY svc.service_category SEPARATOR ', ') AS service_categories_csv,
  COUNT(DISTINCT sig.signal_id) AS matched_signal_count,
  GROUP_CONCAT(DISTINCT sig.display_name ORDER BY sig.display_name SEPARATOR ', ') AS signal_names_csv,
  GROUP_CONCAT(DISTINCT sig.focus_area ORDER BY sig.focus_area SEPARATOR ', ') AS signal_focus_areas_csv,
  GROUP_CONCAT(DISTINCT sig.severity_hint ORDER BY sig.severity_hint SEPARATOR ', ') AS signal_severity_hints_csv
FROM (
  SELECT
    LOWER(TRIM(ddo.observed_domain)) AS observed_domain,
    LOWER(TRIM(ddo.root_domain)) AS root_domain,
    COUNT(DISTINCT ddo.package_name) AS package_count,
    COUNT(DISTINCT ddo.dynamic_run_id) AS observed_run_count,
    COUNT(DISTINCT ddo.observation_id) AS observation_rows,
    SUM(COALESCE(ddo.indicator_count, 0)) AS total_indicator_hits,
    GROUP_CONCAT(DISTINCT ddo.indicator_type ORDER BY ddo.indicator_type SEPARATOR ', ') AS indicator_types_csv,
    GROUP_CONCAT(DISTINCT ddo.owner_class ORDER BY ddo.owner_class SEPARATOR ', ') AS owner_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.role_class ORDER BY ddo.role_class SEPARATOR ', ') AS role_classes_csv
  FROM dynamic_domain_observations ddo
  GROUP BY LOWER(TRIM(ddo.observed_domain)), LOWER(TRIM(ddo.root_domain))
) obs
LEFT JOIN dynamic_service_domain_map sdm
  ON sdm.is_active = 1
 AND LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
 AND (
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
        AND LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))) = obs.observed_domain
      )
      OR
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
        AND (
          LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))) = obs.observed_domain
          OR obs.observed_domain LIKE CONCAT('%.', LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))))
        )
      )
 )
LEFT JOIN dynamic_service_catalog svc
  ON svc.service_id = sdm.service_id
 AND svc.is_active = 1
LEFT JOIN dynamic_service_signal_map ssm
  ON ssm.service_id = svc.service_id
 AND ssm.is_active = 1
LEFT JOIN dynamic_signal_catalog sig
  ON sig.signal_id = ssm.signal_id
 AND sig.is_active = 1
GROUP BY
  obs.observed_domain,
  obs.root_domain,
  obs.package_count,
  obs.observed_run_count,
  obs.observation_rows,
  obs.total_indicator_hits,
  obs.indicator_types_csv,
  obs.owner_classes_csv,
  obs.role_classes_csv
ORDER BY obs.total_indicator_hits DESC, obs.package_count DESC, obs.observed_domain ASC
SQL;
