<?php
// database/db_lib/db_findings_explorer.php — findings explorer, grouping, and drilldown queries.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

function findings_explorer_paged(?string $severity, ?string $category, ?string $masvsArea, ?string $q, int $page, int $size): array
{
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => null,
        'q' => $q,
    ]);

    return db_paged(
        SQL_FINDINGS_EXPLORER_BASE,
        SQL_FINDINGS_EXPLORER_COUNT,
        $where,
        SQL_FINDINGS_EXPLORER_ORDER,
        $params,
        $page,
        $size,
        60,
        'findings_explorer_' . sha1(json_encode([$where, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where)
    );
}

/**
 * Latest findings explorer with detector filter and optional synthetic rollups.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function findings_explorer_paged_v2(
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic,
    int $page,
    int $size
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
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
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

    return db_paged(
        SQL_FINDINGS_EXPLORER_BASE,
        SQL_FINDINGS_EXPLORER_COUNT,
        $finalWhere,
        SQL_FINDINGS_EXPLORER_ORDER,
        $params,
        $page,
        $size,
        60,
        'findings_explorer_v2_' . sha1(json_encode([$finalWhere, $params, $includeSynthetic], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $finalWhere)
    );
}

/**
 * @return string
 */
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
            db_all(SQL_FINDINGS_CATEGORIES)
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
            db_all(SQL_FINDINGS_MASVS_AREAS)
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
            db_all("SELECT DISTINCT session_stamp FROM v_web_app_findings WHERE COALESCE(session_stamp, '') <> '' ORDER BY session_stamp DESC")
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
            db_all(SQL_FINDINGS_DETECTORS)
        ));
    });
}

/**
 * Group findings for fleet pattern discovery.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function findings_explorer_grouped(
    string $groupBy,
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic,
    int $page,
    int $size
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
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
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';

    $map = [
        'title' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_TITLE_BASE,
            'group' => 'GROUP BY latest.title, latest.category, latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_TITLE_ORDER,
        ],
        'detector' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_DETECTOR_BASE,
            'group' => 'GROUP BY latest.detector, latest.category, latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_DETECTOR_ORDER,
        ],
        'app' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_APP_BASE,
            'group' => 'GROUP BY latest.app_label, latest.package_name',
            'order' => SQL_FINDINGS_GROUP_APP_ORDER,
        ],
        'masvs_area' => [
            'base' => SQL_FINDINGS_EXPLORER_GROUP_MASVS_BASE,
            'group' => 'GROUP BY latest.masvs_area',
            'order' => SQL_FINDINGS_GROUP_MASVS_ORDER,
        ],
    ];
    $spec = $map[$groupBy] ?? $map['title'];
    $inner = $spec['base'] . ' ' . $finalWhere . ' ' . $spec['group'];
    $baseSql = 'SELECT * FROM (' . $inner . ') grouped';
    $countSql = 'SELECT COUNT(*) AS c FROM (' . $inner . ') grouped';

    return db_paged(
        $baseSql,
        $countSql,
        '',
        $spec['order'],
        $params,
        $page,
        $size,
        60,
        'findings_grouped_' . sha1(json_encode([$groupBy, $inner, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $groupBy)
    );
}

/**
 * @return array<string,mixed>
 */
function findings_explorer_source_summary(
    ?string $severity,
    ?string $category,
    ?string $masvsArea,
    ?string $detector,
    ?string $sessionStamp,
    ?string $appScope,
    ?string $q,
    bool $includeSynthetic
): array {
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => $detector,
        'session_stamp' => $sessionStamp,
        'q' => $q,
    ]);

    $clauses = [];
    if ($where !== '') {
        $clauses[] = preg_replace('/^WHERE\\s+/i', '', $where);
    }
    $scopeClause = _findings_scope_clause($appScope, 'latest');
    if ($scopeClause !== '') {
        $clauses[] = $scopeClause;
    }
    if (!$includeSynthetic) {
        $clauses[] = "NOT (COALESCE(latest.detector, '') = 'correlation_engine' OR COALESCE(latest.title, '') LIKE 'Composite risk — %')";
    }
    $finalWhere = $clauses ? ('WHERE ' . implode(' AND ', $clauses)) : '';
    $subWhere = $finalWhere === '' ? '' : str_replace('latest.', 'latest2.', $finalWhere);
    $sql = sprintf(SQL_FINDINGS_EXPLORER_SOURCE_SUMMARY, $subWhere);
    return db_one($sql, $params) ?? [];
}

/**
 * @return array<string,mixed>
 */
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
        FROM v_web_app_findings latest
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
        FROM v_web_app_findings latest
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
    $sql = SQL_FINDINGS_EXPLORER_BASE
        . ' WHERE ' . implode(' AND ', $clauses)
        . ' ' . SQL_FINDINGS_EXPLORER_ORDER
        . " LIMIT {$limit}";
    return db_all($sql, $params);
}
