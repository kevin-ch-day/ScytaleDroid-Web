<?php
// database/db_lib/db_queries/dynamic_run.php — Dynamic run detail query constants.

const SQL_DYNAMIC_RUN_DETAIL = <<<SQL
SELECT
  dynamic_run_id,
  package_name,
  app_label,
  scenario_id,
  started_at_utc,
  ended_at_utc,
  duration_seconds,
  sampling_duration_seconds,
  operator_run_profile AS run_profile,
  operator_interaction_level AS interaction_level,
  evidence_path,
  pcap_valid,
  pcap_bytes,
  status,
  tier,
  technical_validity_state,
  quota_state,
  cohort_eligibility_state,
  feature_state,
  static_link_state,
  packet_count,
  bytes_per_sec,
  packets_per_sec,
  tls_ratio,
  quic_ratio,
  unique_dns_qname_count,
  unique_sni_count,
  low_signal
FROM v_web_runtime_run_detail
WHERE dynamic_run_id = :dynamic_run_id
LIMIT 1
SQL;

const SQL_DYNAMIC_RUN_DOMAIN_CONTEXT = <<<SQL
SELECT
  obs.package_name,
  obs.observed_domain,
  obs.root_domain,
  obs.observation_rows,
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
    SUM(COALESCE(ddo.indicator_count, 0)) AS total_indicator_hits,
    GROUP_CONCAT(DISTINCT ddo.indicator_type ORDER BY ddo.indicator_type SEPARATOR ', ') AS indicator_types_csv,
    GROUP_CONCAT(DISTINCT ddo.indicator_source ORDER BY ddo.indicator_source SEPARATOR ', ') AS indicator_sources_csv,
    GROUP_CONCAT(DISTINCT ddo.owner_class ORDER BY ddo.owner_class SEPARATOR ', ') AS owner_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.role_class ORDER BY ddo.role_class SEPARATOR ', ') AS role_classes_csv,
    GROUP_CONCAT(DISTINCT ddo.confidence ORDER BY ddo.confidence SEPARATOR ', ') AS confidence_csv,
    GROUP_CONCAT(DISTINCT ddo.classification_basis ORDER BY ddo.classification_basis SEPARATOR ', ') AS classification_basis_csv,
    MAX(ddo.is_first_party) AS is_first_party
  FROM dynamic_domain_observations ddo
  WHERE ddo.dynamic_run_id = :dynamic_run_domain_context_id
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
  obs.total_indicator_hits,
  obs.indicator_types_csv,
  obs.indicator_sources_csv,
  obs.owner_classes_csv,
  obs.role_classes_csv,
  obs.confidence_csv,
  obs.classification_basis_csv
ORDER BY obs.total_indicator_hits DESC, obs.observed_domain ASC
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
  ON CONVERT(m.cohort_id USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(acr.cohort_id USING utf8mb4) COLLATE utf8mb4_general_ci
 AND CONVERT(m.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(acr.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
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
  ON CONVERT(rr.cohort_id USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(acr.cohort_id USING utf8mb4) COLLATE utf8mb4_general_ci
 AND CONVERT(rr.package_name USING utf8mb4) COLLATE utf8mb4_general_ci =
     CONVERT(acr.package_name USING utf8mb4) COLLATE utf8mb4_general_ci
WHERE acr.dynamic_run_id = :regime_run_id
ORDER BY rr.created_at_utc DESC, rr.cohort_id ASC
SQL;
