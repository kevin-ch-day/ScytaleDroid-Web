<?php
// database/db_lib/db_findings_explorer/group_detail.php — Findings explorer group detail functions.

function findings_group_detail_summary(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $finalWhere = 'WHERE ' . implode(' AND ', $clauses);
    $sql = "
        SELECT
          COUNT(*) AS finding_rows,
          COUNT(DISTINCT latest.package_name) AS affected_apps,
          COUNT(DISTINCT latest.session_stamp) AS session_count,
          SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
          SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
          SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
          SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
          SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows,
          MIN(latest.category) AS category,
          MIN(latest.masvs_area) AS masvs_area,
          MIN(latest.detector) AS detector,
          SUM(CASE WHEN COALESCE(latest.profile_key, '') = 'SYSTEM_CORE' THEN 1 ELSE 0 END) AS system_rows,
          SUM(CASE WHEN COALESCE(latest.publisher_key, '') = 'GOOGLE' THEN 1 ELSE 0 END) AS google_rows
        FROM " . _findings_explorer_surface_table() . " latest
        {$finalWhere}
    ";
    return db_one($sql, $params) ?? [];
}

/**
 * @return array<int,array<string,mixed>>
 */
function findings_group_detail_apps(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic,
    int $limit = 25
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $limit = _positive_limit($limit, 100);
    $sql = "
        SELECT
          latest.package_name,
          latest.app_label,
          latest.profile_label,
          latest.publisher_key,
          latest.session_stamp,
          COUNT(*) AS finding_rows,
          SUM(CASE WHEN latest.severity = 'critical' THEN 1 ELSE 0 END) AS critical_rows,
          SUM(CASE WHEN latest.severity = 'high' THEN 1 ELSE 0 END) AS high_rows,
          SUM(CASE WHEN latest.severity = 'medium' THEN 1 ELSE 0 END) AS medium_rows,
          SUM(CASE WHEN latest.severity = 'low' THEN 1 ELSE 0 END) AS low_rows,
          SUM(CASE WHEN latest.severity = 'info' THEN 1 ELSE 0 END) AS info_rows
        FROM " . _findings_explorer_surface_table() . " latest
        WHERE " . implode(' AND ', $clauses) . "
        GROUP BY latest.package_name, latest.app_label, latest.profile_label, latest.publisher_key, latest.session_stamp
        ORDER BY high_rows DESC, medium_rows DESC, finding_rows DESC, latest.app_label ASC
        LIMIT {$limit}
    ";
    return db_all($sql, $params);
}

/**
 * @return array<int,array<string,mixed>>
 */
function findings_group_detail_examples(
    string $groupBy,
    string $groupValue,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    bool $includeSynthetic,
    int $limit = 12
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => null,
    ]);
    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $field = match ($groupBy) {
        'detector' => 'latest.detector',
        'masvs_area' => 'latest.masvs_area',
        'app' => 'latest.app_label',
        default => 'latest.title',
    };
    $clauses[] = "COALESCE({$field}, '') = :group_value";
    $params['group_value'] = $groupValue;
    $limit = _positive_limit($limit, 50);
    $sql = _findings_explorer_sql(SQL_FINDINGS_EXPLORER_BASE)
        . ' WHERE ' . implode(' AND ', $clauses)
        . ' ' . SQL_FINDINGS_EXPLORER_ORDER
        . " LIMIT {$limit}";
    return db_all($sql, $params);
}
