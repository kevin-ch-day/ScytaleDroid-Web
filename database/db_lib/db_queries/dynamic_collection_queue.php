<?php
// database/db_lib/db_queries/dynamic_collection_queue.php — Dynamic collection queue read model.

const SQL_DCQ_BASE = <<<SQL
SELECT
  q.package_name,
  q.app_label,
  q.cohort_key,
  q.sort_order,
  q.baseline_required,
  q.interactive_required,
  q.baseline_quota_counted,
  q.baseline_extra_valid,
  q.baseline_low_signal_retained,
  q.baseline_quiescent_fg_valid,
  q.baseline_total_valid,
  q.interactive_quota_counted,
  q.interactive_raw_valid,
  q.interactive_extra_valid,
  q.interactive_low_signal_retained,
  q.interactive_total_valid,
  q.all_build_baseline_quota_counted,
  q.all_build_interactive_quota_counted,
  q.current_build_baseline_quota_counted,
  q.current_build_interactive_quota_counted,
  q.current_build_baseline_quiescent_fg_valid,
  q.current_build_interactive_raw_valid,
  q.need_baseline,
  q.need_interactive,
  q.quota_satisfied,
  q.dynamic_run_count,
  q.invalid_run_count,
  q.legacy_unknown_runs AS unevaluated_historical_runs,
  q.retained_prior_build_valid_runs,
  q.retained_prior_build_quiescent_fg_valid,
  q.retained_prior_build_count,
  q.retained_prior_build_pcap_count,
  q.has_static_data,
  q.latest_static_run_id,
  q.latest_version_name,
  q.latest_version_code,
  q.latest_dynamic_run_id,
  q.data_scope,
  q.quota_gap_label,
  q.collection_status,
  q.need_label,
  q.qa_label,
  q.next_action_hint,
  q.baseline_quota_label,
  q.interactive_quota_label,
  q.interactive_display_label
FROM v_web_dynamic_app_queue_v1 q
SQL;

const SQL_DCQ_COUNT = <<<SQL
SELECT COUNT(*) AS c
FROM v_web_dynamic_app_queue_v1 q
SQL;

const SQL_DCQ_ORDER = <<<SQL
ORDER BY
  CASE q.collection_status
    WHEN 'needs_static' THEN 0
    WHEN 'baseline' THEN 1
    WHEN 'interactive' THEN 2
    WHEN 'prior_build_only' THEN 3
    WHEN 'review' THEN 4
    WHEN 'complete' THEN 5
    ELSE 6
  END,
  (q.need_baseline + q.need_interactive) DESC,
  q.sort_order ASC,
  q.app_label ASC
SQL;

const SQL_DCQ_OVERVIEW = <<<SQL
SELECT
  COUNT(*) AS cohort_apps,
  SUM(CASE WHEN collection_status = 'complete' THEN 1 ELSE 0 END) AS complete_apps,
  SUM(CASE WHEN collection_status = 'baseline' THEN 1 ELSE 0 END) AS baseline_gap_apps,
  SUM(CASE WHEN collection_status = 'interactive' THEN 1 ELSE 0 END) AS interactive_gap_apps,
  SUM(CASE WHEN collection_status = 'needs_static' THEN 1 ELSE 0 END) AS needs_static_apps,
  SUM(CASE WHEN collection_status = 'review' THEN 1 ELSE 0 END) AS review_apps,
  SUM(CASE WHEN collection_status = 'prior_build_only' THEN 1 ELSE 0 END) AS prior_build_only_apps,
  SUM(need_baseline) AS total_need_baseline,
  SUM(need_interactive) AS total_need_interactive,
  SUM(baseline_quota_counted) AS total_baseline_quota_counted,
  SUM(interactive_quota_counted) AS total_interactive_quota_counted,
  SUM(baseline_quiescent_fg_valid) AS total_quiescent_fg_runs,
  SUM(interactive_raw_valid) AS total_interactive_raw_runs,
  SUM(retained_prior_build_valid_runs) AS total_retained_prior_build_runs,
  SUM(retained_prior_build_quiescent_fg_valid) AS total_retained_prior_build_qfg_runs,
  SUM(baseline_extra_valid + baseline_low_signal_retained + interactive_extra_valid + interactive_low_signal_retained) AS total_other_supplemental_runs,
  SUM(dynamic_run_count) AS total_dynamic_runs,
  SUM(invalid_run_count) AS total_invalid_runs_all_builds,
  SUM(current_build_invalid_run_count) AS total_invalid_runs,
  SUM(legacy_unknown_runs) AS total_unevaluated_historical_runs
FROM v_web_dynamic_app_queue_v1
SQL;

const SQL_DCQ_PROBE = <<<SQL
SELECT
  package_name,
  app_label,
  collection_status,
  quota_gap_label,
  baseline_quota_label,
  interactive_display_label,
  baseline_quiescent_fg_valid,
  interactive_raw_valid,
  retained_prior_build_valid_runs,
  retained_prior_build_quiescent_fg_valid,
  retained_prior_build_count,
  retained_prior_build_pcap_count
FROM v_web_dynamic_app_queue_v1
ORDER BY sort_order ASC
SQL;

const SQL_DCQ_RECOMMENDED_CAPTURES = <<<SQL
SELECT
  package_name,
  app_label,
  collection_status,
  quota_gap_label,
  qa_label,
  need_baseline,
  need_interactive,
  baseline_quota_counted,
  baseline_quiescent_fg_valid,
  interactive_raw_valid,
  retained_prior_build_valid_runs,
  retained_prior_build_quiescent_fg_valid,
  retained_prior_build_count
FROM v_web_dynamic_app_queue_v1
WHERE need_baseline > 0 OR need_interactive > 0
ORDER BY need_interactive DESC, need_baseline DESC, sort_order ASC, app_label ASC
SQL;
