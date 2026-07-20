<?php
// database/db_lib/db_permission_intel/surface.php — permission intelligence SQL surface selector.

/**
 * @return array{from_sql:string,where_sql:string,params:array<string,mixed>,mode:string}
 */
function _permission_intel_surface_meta(?string $sessionStamp): array
{
    $normalized = trim((string)($sessionStamp ?? ''));
    if ($normalized === '') {
        $tempTable = _permission_intel_temp_surface(null);
        if ($tempTable !== null) {
            return [
                'from_sql' => $tempTable,
                'where_sql' => 'WHERE 1=1',
                'params' => [],
                'mode' => 'preferred_temp',
            ];
        }

        return [
            'from_sql' => 'v_web_permission_intel_current',
            'where_sql' => 'WHERE 1=1',
            'params' => [],
            'mode' => 'preferred',
        ];
    }

    $tempTable = _permission_intel_temp_surface($normalized);
    if ($tempTable !== null) {
        return [
            'from_sql' => $tempTable,
            'where_sql' => 'WHERE 1=1',
            'params' => [],
            'mode' => 'session_temp',
        ];
    }

    return [
        'from_sql' => 'v_web_app_permissions',
        'where_sql' => 'WHERE session_stamp = :permission_session_stamp',
        'params' => ['permission_session_stamp' => $normalized],
        'mode' => 'session',
    ];
}

function _permission_intel_surface_cache_key(?string $sessionStamp): string
{
    $normalized = trim((string)($sessionStamp ?? ''));
    return $normalized === '' ? 'preferred' : ('session_' . sha1($normalized));
}

function _permission_intel_temp_surface(?string $sessionStamp): ?string
{
    static $created = [];

    if (!web_temp_tables_enabled()) {
        return null;
    }

    $normalized = trim((string)($sessionStamp ?? ''));
    $key = $normalized === '' ? 'current' : sha1($normalized);
    $tableName = 'tmp_scytale_perm_intel_' . substr($key, 0, 16);
    if (isset($created[$tableName])) {
        return $tableName;
    }

    try {
        db_exec("DROP TEMPORARY TABLE IF EXISTS {$tableName}");
        if ($normalized === '') {
            db_exec("
                CREATE TEMPORARY TABLE {$tableName} AS
                SELECT
                  package_name,
                  static_run_id,
                  session_stamp,
                  session_type_key,
                  session_type_label,
                  session_usability,
                  session_hidden_by_default,
                  permission_name,
                  source,
                  source_family,
                  custom_family,
                  protection,
                  severity,
                  is_runtime_dangerous,
                  is_signature,
                  is_privileged,
                  is_special_access,
                  is_custom
                FROM v_web_permission_intel_current
            ");
        } else {
            db_exec(
                "
                CREATE TEMPORARY TABLE {$tableName} AS
                SELECT
                  package_name,
                  static_run_id,
                  session_stamp,
                  NULL AS session_type_key,
                  NULL AS session_type_label,
                  NULL AS session_usability,
                  0 AS session_hidden_by_default,
                  permission_name,
                  source,
                  source_family,
                  custom_family,
                  protection,
                  severity,
                  is_runtime_dangerous,
                  is_signature,
                  is_privileged,
                  is_special_access,
                  is_custom
                FROM v_web_app_permissions
                WHERE session_stamp = :session_stamp
                ",
                ['session_stamp' => $normalized]
            );
        }
        db_exec("ALTER TABLE {$tableName} ADD INDEX idx_perm_package (package_name)");
        db_exec("ALTER TABLE {$tableName} ADD INDEX idx_perm_name (permission_name)");
        db_exec("ALTER TABLE {$tableName} ADD INDEX idx_perm_flags (is_runtime_dangerous, is_custom, protection)");
        $created[$tableName] = true;
        return $tableName;
    } catch (Throwable $e) {
        error_log('[ScytaleDroid-Web] permission intel temp surface unavailable: ' . $e->getMessage());
        return null;
    }
}
