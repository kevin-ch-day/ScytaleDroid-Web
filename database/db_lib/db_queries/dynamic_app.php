<?php
// database/db_lib/db_queries/dynamic_app.php — Per-app dynamic query constants.

const SQL_APP_DYNAMIC_SUMMARY = <<<SQL
SELECT
  COUNT(*) AS dynamic_runs,
  SUM(CASE WHEN LOWER(status) = 'success' THEN 1 ELSE 0 END) AS successful_runs,
  SUM(CASE WHEN LOWER(status) = 'degraded' THEN 1 ELSE 0 END) AS degraded_runs,
  SUM(CASE WHEN LOWER(status) = 'failed' THEN 1 ELSE 0 END) AS failed_runs,
  MAX(started_at_utc) AS latest_started_at,
  SUM(CASE WHEN pcap_valid = 1 THEN 1 ELSE 0 END) AS valid_pcaps,
  SUM(CASE WHEN quota_state = 'QUOTA_VALID' THEN 1 ELSE 0 END) AS quota_valid_runs,
  SUM(CASE WHEN quota_state = 'SUPPLEMENTAL_VALID' THEN 1 ELSE 0 END) AS supplemental_valid_runs,
  SUM(CASE WHEN technical_validity_state = 'TECH_INVALID' THEN 1 ELSE 0 END) AS invalid_or_skipped_runs,
  SUM(CASE WHEN technical_validity_state = 'TECH_LEGACY_UNKNOWN' THEN 1 ELSE 0 END) AS legacy_or_unevaluated_runs,
  SUM(CASE WHEN LOWER(COALESCE(static_link_state, '')) = 'static_linked' THEN 1 ELSE 0 END) AS static_linked_runs,
  SUM(CASE WHEN LOWER(COALESCE(static_link_state, '')) = 'missing_static_run_id' THEN 1 ELSE 0 END) AS missing_static_link_runs,
  SUM(CASE WHEN LOWER(COALESCE(feature_state, '')) = 'features_available' THEN 1 ELSE 0 END) AS features_available_runs,
  SUM(CASE WHEN LOWER(COALESCE(feature_state, '')) = 'missing_features' THEN 1 ELSE 0 END) AS missing_feature_runs,
  COUNT(DISTINCT tier) AS tier_count
FROM v_web_runtime_run_index
WHERE package_name = :pkg_dynamic_summary
SQL;
const SQL_APP_DYNAMIC_RUNS = <<<SQL
SELECT
  dynamic_run_id,
  package_name,
  status,
  tier,
  run_profile,
  interaction_level,
  started_at_utc,
  ended_at_utc,
  duration_seconds,
  grade,
  network_signal_quality,
  pcap_valid,
  pcap_bytes,
  technical_validity_state,
  quota_state,
  cohort_eligibility_state,
  packet_count,
  bytes_per_sec,
  packets_per_sec,
  low_signal,
  issue_count,
  feature_state,
  static_link_state
FROM v_web_runtime_run_index
WHERE package_name = :pkg_dynamic_runs
ORDER BY started_at_utc DESC, dynamic_run_id DESC
SQL;

const SQL_APP_DYNAMIC_DOMAIN_CONTEXT = <<<SQL
SELECT
  obs.package_name,
  obs.observed_domain,
  obs.root_domain,
  obs.observation_rows,
  obs.observed_run_count,
  obs.total_indicator_hits,
  obs.indicator_types_csv,
  obs.indicator_sources_csv,
  obs.owner_classes_csv,
  obs.role_classes_csv,
  obs.confidence_csv,
  obs.classification_basis_csv,
  MAX(obs.is_first_party) AS is_first_party,
  COUNT(DISTINCT svc.service_id) AS matched_service_count,
  GROUP_CONCAT(DISTINCT svc.service_key ORDER BY svc.service_key SEPARATOR ', ') AS service_keys_csv,
  GROUP_CONCAT(DISTINCT svc.display_name ORDER BY svc.display_name SEPARATOR ', ') AS service_names_csv,
  GROUP_CONCAT(DISTINCT svc.owner_name ORDER BY svc.owner_name SEPARATOR ', ') AS service_owner_names_csv,
  GROUP_CONCAT(DISTINCT svc.service_category ORDER BY svc.service_category SEPARATOR ', ') AS service_categories_csv,
  GROUP_CONCAT(DISTINCT svc.primary_use_case ORDER BY svc.primary_use_case SEPARATOR ', ') AS service_use_cases_csv,
  COUNT(DISTINCT sig.signal_id) AS matched_signal_count,
  GROUP_CONCAT(DISTINCT sig.signal_key ORDER BY sig.signal_key SEPARATOR ', ') AS signal_keys_csv,
  GROUP_CONCAT(DISTINCT sig.display_name ORDER BY sig.display_name SEPARATOR ', ') AS signal_names_csv,
  GROUP_CONCAT(DISTINCT sig.focus_area ORDER BY sig.focus_area SEPARATOR ', ') AS signal_focus_areas_csv,
  GROUP_CONCAT(DISTINCT sig.severity_hint ORDER BY sig.severity_hint SEPARATOR ', ') AS signal_severity_hints_csv
FROM (
  SELECT
    ddo.package_name,
    LOWER(TRIM(ddo.observed_domain)) AS observed_domain,
    LOWER(TRIM(ddo.root_domain)) AS root_domain,
    COUNT(DISTINCT ddo.observation_id) AS observation_rows,
    COUNT(DISTINCT ddo.dynamic_run_id) AS observed_run_count,
    SUM(COALESCE(ddo.indicator_count, 0)) AS total_indicator_hits,
    GROUP_CONCAT(DISTINCT ddo.indicator_type ORDER BY ddo.indicator_type SEPARATOR ', ') AS indicator_types_csv,
    GROUP_CONCAT(DISTINCT ddo.indicator_source ORDER BY ddo.indicator_source SEPARATOR ', ') AS indicator_sources_csv,
    GROUP_CONCAT(DISTINCT ddo.owner_class ORDER BY ddo.owner_class SEPARATOR ', ') AS owner_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.role_class ORDER BY ddo.role_class SEPARATOR ', ') AS role_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.confidence ORDER BY ddo.confidence SEPARATOR ', ') AS confidence_csv,
    GROUP_CONCAT(DISTINCT ddo.classification_basis ORDER BY ddo.classification_basis SEPARATOR ', ') AS classification_basis_csv,
    MAX(ddo.is_first_party) AS is_first_party
  FROM dynamic_domain_observations ddo
  WHERE CONVERT(ddo.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
        CONVERT(:pkg_dynamic_domain_context USING utf8mb4) COLLATE utf8mb4_general_ci
  GROUP BY ddo.package_name, LOWER(TRIM(ddo.observed_domain)), LOWER(TRIM(ddo.root_domain))
) obs
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
  obs.package_name,
  obs.observed_domain,
  obs.root_domain,
  obs.observation_rows,
  obs.observed_run_count,
  obs.total_indicator_hits,
  obs.indicator_types_csv,
  obs.indicator_sources_csv,
  obs.owner_classes_csv,
  obs.role_classes_csv,
  obs.confidence_csv,
  obs.classification_basis_csv
ORDER BY obs.total_indicator_hits DESC, obs.observed_run_count DESC, obs.observed_domain ASC
SQL;

const SQL_APP_DYNAMIC_SERVICE_SUMMARY = <<<SQL
SELECT
  svc_obs.service_key,
  svc_obs.display_name,
  svc_obs.owner_name,
  svc_obs.owner_class,
  svc_obs.service_category,
  svc_obs.primary_use_case,
  svc_obs.confidence,
  svc_obs.distinct_domains,
  svc_obs.distinct_root_domains,
  svc_obs.observed_run_count,
  svc_obs.observation_rows,
  svc_obs.total_indicator_hits,
  svc_obs.owner_classes_csv,
  svc_obs.role_classes_csv,
  COUNT(DISTINCT sig.signal_id) AS matched_signal_count,
  GROUP_CONCAT(DISTINCT sig.signal_key ORDER BY sig.signal_key SEPARATOR ', ') AS signal_keys_csv,
  GROUP_CONCAT(DISTINCT sig.display_name ORDER BY sig.display_name SEPARATOR ', ') AS signal_names_csv,
  GROUP_CONCAT(DISTINCT sig.focus_area ORDER BY sig.focus_area SEPARATOR ', ') AS signal_focus_areas_csv,
  GROUP_CONCAT(DISTINCT sig.severity_hint ORDER BY sig.severity_hint SEPARATOR ', ') AS signal_severity_hints_csv
FROM (
  SELECT
    svc.service_id,
    svc.service_key,
    svc.display_name,
    svc.owner_name,
    svc.owner_class,
    svc.service_category,
    svc.primary_use_case,
    svc.confidence,
    COUNT(DISTINCT LOWER(TRIM(ddo.observed_domain))) AS distinct_domains,
    COUNT(DISTINCT LOWER(TRIM(ddo.root_domain))) AS distinct_root_domains,
    COUNT(DISTINCT ddo.dynamic_run_id) AS observed_run_count,
    COUNT(DISTINCT ddo.observation_id) AS observation_rows,
    SUM(COALESCE(ddo.indicator_count, 0)) AS total_indicator_hits,
    GROUP_CONCAT(DISTINCT ddo.owner_class ORDER BY ddo.owner_class SEPARATOR ', ') AS owner_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.role_class ORDER BY ddo.role_class SEPARATOR ', ') AS role_classes_csv
  FROM dynamic_domain_observations ddo
  JOIN dynamic_service_domain_map sdm
    ON sdm.is_active = 1
   AND (
        LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
        OR CONVERT(sdm.package_name_scope USING utf8mb4) COLLATE utf8mb4_general_ci =
           CONVERT(ddo.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
   )
   AND (
        (
          UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
          AND LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
        )
        OR
        (
          UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
          AND (
            LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
            OR LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) LIKE
               CONCAT('%.', LOWER(TRIM(COALESCE(sdm.domain_pattern, ''))))
          )
        )
   )
  JOIN dynamic_service_catalog svc
    ON svc.service_id = sdm.service_id
   AND svc.is_active = 1
  WHERE CONVERT(ddo.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
        CONVERT(:pkg_dynamic_service_summary USING utf8mb4) COLLATE utf8mb4_general_ci
  GROUP BY
    svc.service_id,
    svc.service_key,
    svc.display_name,
    svc.owner_name,
    svc.owner_class,
    svc.service_category,
    svc.primary_use_case,
    svc.confidence
) svc_obs
LEFT JOIN dynamic_service_signal_map ssm
  ON ssm.service_id = svc_obs.service_id
 AND ssm.is_active = 1
LEFT JOIN dynamic_signal_catalog sig
  ON sig.signal_id = ssm.signal_id
 AND sig.is_active = 1
GROUP BY
  svc_obs.service_key,
  svc_obs.display_name,
  svc_obs.owner_name,
  svc_obs.owner_class,
  svc_obs.service_category,
  svc_obs.primary_use_case,
  svc_obs.confidence,
  svc_obs.distinct_domains,
  svc_obs.distinct_root_domains,
  svc_obs.observed_run_count,
  svc_obs.observation_rows,
  svc_obs.total_indicator_hits,
  svc_obs.owner_classes_csv,
  svc_obs.role_classes_csv
ORDER BY svc_obs.total_indicator_hits DESC, svc_obs.distinct_domains DESC, svc_obs.service_key ASC
SQL;

const SQL_APP_DYNAMIC_SIGNAL_SUMMARY = <<<SQL
SELECT
  sig.signal_key,
  sig.display_name,
  sig.signal_family,
  sig.focus_area,
  sig.severity_hint,
  COUNT(DISTINCT svc.service_id) AS matched_service_count,
  COUNT(DISTINCT LOWER(TRIM(ddo.observed_domain))) AS distinct_domains,
  COUNT(DISTINCT LOWER(TRIM(ddo.root_domain))) AS distinct_root_domains,
  COUNT(DISTINCT ddo.dynamic_run_id) AS observed_run_count,
  COUNT(DISTINCT ddo.observation_id) AS observation_rows,
  SUM(COALESCE(ddo.indicator_count, 0)) AS total_indicator_hits,
  GROUP_CONCAT(DISTINCT svc.display_name ORDER BY svc.display_name SEPARATOR ', ') AS service_names_csv,
  GROUP_CONCAT(DISTINCT svc.service_category ORDER BY svc.service_category SEPARATOR ', ') AS service_categories_csv
FROM dynamic_domain_observations ddo
JOIN dynamic_service_domain_map sdm
  ON sdm.is_active = 1
 AND (
      LOWER(TRIM(COALESCE(sdm.package_name_scope, ''))) = ''
      OR CONVERT(sdm.package_name_scope USING utf8mb4) COLLATE utf8mb4_general_ci =
         CONVERT(ddo.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
 )
 AND (
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'EXACT'
        AND LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
      )
      OR
      (
        UPPER(TRIM(COALESCE(sdm.match_type, ''))) = 'SUFFIX'
        AND (
          LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) = LOWER(TRIM(COALESCE(sdm.domain_pattern, '')))
          OR LOWER(TRIM(COALESCE(ddo.observed_domain, ''))) LIKE
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
WHERE CONVERT(ddo.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
      CONVERT(:pkg_dynamic_signal_summary USING utf8mb4) COLLATE utf8mb4_general_ci
GROUP BY
  sig.signal_key,
  sig.display_name,
  sig.signal_family,
  sig.focus_area,
  sig.severity_hint
ORDER BY total_indicator_hits DESC, distinct_domains DESC, sig.signal_key ASC
SQL;
