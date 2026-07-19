<?php
// database/db_lib/db_permission_intel/entrypoints.php — fleet permission intelligence wrapper functions.

function permission_intel_overview(): array
{
    return permission_intel_overview_for_session(null);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_session_options(): array
{
    return web_cache_remember('permission_intel_session_options_v3', 120, static function (): array {
        return db_all(SQL_PERMISSION_INTEL_SESSION_OPTIONS);
    });
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_top_dangerous(int $limit = 15): array
{
    return permission_intel_top_dangerous_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_source_breakdown(int $limit = 12): array
{
    return permission_intel_source_breakdown_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_protection_breakdown(int $limit = 12): array
{
    return permission_intel_protection_breakdown_for_session(null, $limit);
}

/**
 * @return array<int,array<string,mixed>>
 */
function permission_intel_sensitive_combos(int $limit = 10): array
{
    return permission_intel_sensitive_combos_for_session(null, $limit);
}
