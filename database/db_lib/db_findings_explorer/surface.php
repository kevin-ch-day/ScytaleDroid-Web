<?php
// database/db_lib/db_findings_explorer/surface.php — request-local findings explorer SQL surface.

function _findings_explorer_surface_table(): string
{
    static $tableName = null;
    static $available = null;

    if (!web_temp_tables_enabled()) {
        return 'v_web_app_findings';
    }

    if ($available === false) {
        return 'v_web_app_findings';
    }
    if (is_string($tableName)) {
        return $tableName;
    }

    $candidate = 'tmp_scytale_findings_surface';
    try {
        db_exec("DROP TEMPORARY TABLE IF EXISTS {$candidate}");
        db_exec("
            CREATE TEMPORARY TABLE {$candidate} AS
            SELECT
              package_name,
              app_label,
              profile_key,
              profile_label,
              publisher_key,
              static_run_id,
              session_stamp,
              session_label,
              version_name,
              version_code,
              finding_id,
              severity,
              severity_raw,
              title,
              category,
              masvs_area,
              detector,
              evidence,
              fix,
              created_at
            FROM v_web_app_findings
        ");
        db_exec("ALTER TABLE {$candidate} ADD INDEX idx_findings_session (session_stamp)");
        db_exec("ALTER TABLE {$candidate} ADD INDEX idx_findings_package (package_name)");
        db_exec("ALTER TABLE {$candidate} ADD INDEX idx_findings_severity (severity)");
        db_exec("ALTER TABLE {$candidate} ADD INDEX idx_findings_category (category)");
        db_exec("ALTER TABLE {$candidate} ADD INDEX idx_findings_detector (detector)");
        $tableName = $candidate;
        $available = true;
        return $candidate;
    } catch (Throwable $e) {
        error_log('[ScytaleDroid-Web] findings temp surface unavailable: ' . $e->getMessage());
        $available = false;
        return 'v_web_app_findings';
    }
}

function _findings_explorer_sql(string $sql): string
{
    return str_replace('v_web_app_findings', _findings_explorer_surface_table(), $sql);
}
