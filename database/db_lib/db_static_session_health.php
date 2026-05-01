<?php
// database/db_lib/db_static_session_health.php — v_web_static_session_health + run health helpers.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';
function static_session_health(
    int $limit = 20,
    ?string $sessionStamp = null,
    ?string $sessionType = null,
    bool $includeHidden = false
): array
{
    $limit = _positive_limit($limit, 50);
    $where = [];
    $params = [];

    if (!$includeHidden) {
        $where[] = 'session_hidden_by_default = 0';
    }
    if ($sessionStamp !== null) {
        $where[] = 'session_stamp = :rh_session';
        $params['rh_session'] = $sessionStamp;
    }
    if ($sessionType !== null) {
        $where[] = 'session_type_key = :rh_session_type';
        $params['rh_session_type'] = strtolower($sessionType);
    }

    $sql = SQL_STATIC_SESSION_HEALTH_BASE;
    if ($where) {
        $sql .= "\nWHERE " . implode("\n  AND ", $where);
    }
    $sql .= "\nORDER BY created_at DESC";
    $sql .= "\nLIMIT {$limit}";
    return db_all($sql, $params);
}

/**
 * @return array<string,mixed>
 */
function static_session_quality(): array
{
    return db_one(SQL_STATIC_SESSION_QUALITY) ?? [];
}

/**
 * @return array<string,mixed>
 */
function static_session_filter_options(int $limit = 60, bool $includeHidden = true): array
{
    $rows = static_session_health($limit, null, null, $includeHidden);
    $sessions = [];
    $types = [];
    foreach ($rows as $row) {
        $stamp = trim((string)($row['session_stamp'] ?? ''));
        $typeKey = strtolower(trim((string)($row['session_type_key'] ?? '')));
        $typeLabel = trim((string)($row['session_type_label'] ?? ''));
        if ($stamp !== '') {
            $sessions[$stamp] = $stamp;
        }
        if ($typeKey !== '') {
            $types[$typeKey] = $typeLabel !== '' ? $typeLabel : ucwords(str_replace('_', ' ', $typeKey));
        }
    }
    ksort($types);
    return [
        'sessions' => array_values($sessions),
        'types' => $types,
    ];
}
