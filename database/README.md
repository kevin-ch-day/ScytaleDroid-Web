# Database Guide

This application is read-only and expects a populated ScytaleDroid schema. Use the notes below to prepare and validate the database before exposing the UI.

## Required Tables / Views

- `permission_audit_apps`
- `permission_audit_snapshots`
- `android_app_categories`
- `android_app_definitions`
- `static_findings_summary`
- `static_permission_risk` (for future permissions matrix)
- `android_detected_permissions` / `android_vendor_permissions`

## Recommended Indexes

| Table | Index |
| --- | --- |
| `permission_audit_apps` | `(package_name, snapshot_id)` |
| `permission_audit_snapshots` | `(snapshot_id)` and `(snapshot_key)` |
| `android_app_categories` | `(package_name)` |
| `android_app_definitions` | `(package_name)` |
| `static_findings_summary` | `(package_name, session_stamp)` |

Adjust index names to match your organisation’s conventions.

## Connection Expectations

- Database user must have **SELECT** on the tables/views listed above.
- No write access is required.
- Character set should be `utf8mb4`.

## Sanity Checks

The directory queries assume `permission_audit_snapshots.snapshot_key` uses the pattern
`perm-audit:app:<session_stamp>` so that the suffix matches `static_findings_summary.session_stamp`. If
your pipeline emits a different key format, adjust `SQL_APPS_DIR_BASE` and `SQL_APPS_DIR_COUNT`
accordingly.

```sql
-- Verify snapshot counts
SELECT COUNT(*) FROM permission_audit_snapshots;

-- Ensure apps are linked to snapshots
SELECT a.package_name, s.snapshot_key
FROM permission_audit_apps a
JOIN permission_audit_snapshots s ON s.snapshot_id = a.snapshot_id
LIMIT 5;

-- Confirm findings summary exists
SELECT package_name, session_stamp, high, med, low
FROM static_findings_summary
ORDER BY updated_at DESC
LIMIT 5;
```

If any query returns zero rows, the UI will show empty states. Populate the data pipeline before rolling out ScytaleDroid-Web.
