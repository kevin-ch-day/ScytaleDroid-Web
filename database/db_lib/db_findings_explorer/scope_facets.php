<?php
// database/db_lib/db_findings_explorer/scope_facets.php — Findings explorer scope and facet helpers.

function _findings_scope_clause(?string $appScope, string $alias = 'latest'): string
{
    return match ((string)$appScope) {
        'user_apps' => "COALESCE({$alias}.profile_key, '') <> 'SYSTEM_CORE'",
        'system_oem_apps' => "COALESCE({$alias}.profile_key, '') = 'SYSTEM_CORE'",
        'google_apps' => "COALESCE({$alias}.publisher_key, '') = 'GOOGLE'",
        default => '',
    };
}

/**
 * @return array<int,string>
 */
function findings_categories(): array
{
    return web_cache_remember('findings_categories_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['category'] ?? ''),
            db_all(_findings_explorer_sql(SQL_FINDINGS_CATEGORIES))
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_masvs_areas(): array
{
    return web_cache_remember('findings_masvs_areas_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['masvs_area'] ?? ''),
            db_all(_findings_explorer_sql(SQL_FINDINGS_MASVS_AREAS))
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_sessions(): array
{
    return web_cache_remember('findings_sessions_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['session_stamp'] ?? ''),
            db_all(_findings_explorer_sql("SELECT DISTINCT session_stamp FROM v_web_app_findings WHERE COALESCE(session_stamp, '') <> '' ORDER BY session_stamp DESC"))
        ));
    });
}

/**
 * @return array<int,string>
 */
function findings_detectors(): array
{
    return web_cache_remember('findings_detectors_v1', 300, static function (): array {
        return array_values(array_map(
            static fn(array $row): string => (string)($row['detector'] ?? ''),
            db_all(_findings_explorer_sql(SQL_FINDINGS_DETECTORS))
        ));
    });
}
