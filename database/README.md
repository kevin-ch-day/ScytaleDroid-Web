# Database Guide

This application is read-only and expects a populated ScytaleDroid schema. Use the notes below to prepare and validate the database before exposing the UI.

## Required Tables / Views

Static exposure and app-directory views:
- `v_web_app_directory`
- `permission_audit_apps`
- `permission_audit_snapshots`
- `android_app_categories`
- `android_app_profiles`
- `apps`
- `app_versions`
- `static_findings_summary`
- `static_findings`
- `static_permission_matrix`
- `static_string_summary`
- `static_string_selected_samples`

Runtime deviation views:
- `v_web_runtime_run_index`
- `v_web_runtime_run_detail`
- `dynamic_sessions`
- `dynamic_network_features`
- `dynamic_network_indicators`
- `dynamic_session_issues`
- `analysis_cohorts`
- `analysis_ml_app_phase_model_metrics`
- `analysis_risk_regime_summary`

Evidence/artifact views:
- `v_current_artifact_registry`
- `v_artifact_registry_integrity`

## Recommended Indexes

| Table | Index |
| --- | --- |
| `permission_audit_apps` | `(package_name, snapshot_id)` |
| `permission_audit_snapshots` | `(snapshot_id)` and `(snapshot_key)` |
| `apps` | `(package_name)` |
| `android_app_categories` | `(category_id)` |
| `static_findings_summary` | `(package_name, session_stamp)` |
| `static_analysis_runs` | `(session_stamp, app_version_id)` |
| `dynamic_sessions` | `(package_name, started_at_utc)` and `(dynamic_run_id)` |
| `dynamic_network_features` | `(dynamic_run_id)` |
| `dynamic_session_issues` | `(dynamic_run_id)` |
| `analysis_risk_regime_summary` | `(package_name, created_at_utc)` |

Adjust index names to match your organisation’s conventions.

## Connection Expectations

- Database user must have **SELECT** on the tables/views listed above.
- No write access is required.
- Character set should be `utf8mb4`.

### Environment overrides

The preferred deployment model is environment-based configuration. For local development, copy
`database/db_core/db_config.example.php` to `database/db_core/db_config.php`; the local file is ignored
by Git and should never contain production credentials.

You can override any setting at runtime with environment variables before Apache/PHP start:

| Variable | Purpose |
| --- | --- |
| `SCYTALEDROID_DB_HOST` | Hostname for the MySQL/MariaDB server. |
| `SCYTALEDROID_DB_PORT` | TCP port when not using a socket. |
| `SCYTALEDROID_DB_SOCKET` | Path to a Unix socket (skips host/port). |
| `SCYTALEDROID_DB_NAME` | Database/schema name. |
| `SCYTALEDROID_DB_USER` | Database user. |
| `SCYTALEDROID_DB_PASS` | Database password. |
| `SCYTALEDROID_DB_CHARSET` | Optional charset (defaults to `utf8mb4`). |
| `SCYTALEDROID_DB_DSN` | Full PDO DSN, if you need complete manual control. |

Only set the values you need—anything unset falls back to the constants in `db_config.php`.

## Sanity Checks

The preferred read contract is the `v_web_*` view set created by the CLI database
bootstrap. These views keep app-directory and runtime-deviation reconstruction in
the database instead of duplicating it in PHP.

```sql
-- Verify the web app-directory contract
SELECT COUNT(*) FROM v_web_app_directory;

-- Ensure app rows include latest static/audit state
SELECT package_name, app_label, grade, high, med, low, source_state
FROM v_web_app_directory
LIMIT 5;

-- Confirm static exposure summaries exist
SELECT package_name, session_stamp, high, med, low
FROM static_findings_summary
ORDER BY updated_at DESC
LIMIT 5;

-- Confirm runtime deviation runs exist
SELECT package_name, status, tier, started_at_utc, dynamic_run_id, feature_state, static_link_state
FROM v_web_runtime_run_index
ORDER BY started_at_utc DESC
LIMIT 5;

-- Confirm runtime deviation feature rows exist
SELECT package_name, run_profile, interaction_level, packet_count, bytes_per_sec
FROM dynamic_network_features
ORDER BY updated_at DESC
LIMIT 5;

-- Confirm cross-analysis regime rows exist
SELECT package_name, static_grade, dynamic_grade_if, final_regime_if
FROM analysis_risk_regime_summary
ORDER BY created_at_utc DESC
LIMIT 5;

-- Confirm artifact registry link health
SELECT link_state, COUNT(*)
FROM v_artifact_registry_integrity
GROUP BY link_state;
```

If any query returns zero rows, the UI will show empty states. Populate the data pipeline before rolling out ScytaleDroid-Web.
