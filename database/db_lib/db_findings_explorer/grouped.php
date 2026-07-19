<?php
// database/db_lib/db_findings_explorer/grouped.php — Findings explorer grouped summary functions.

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
    $cacheSeed = json_encode([$groupBy, $finalWhere, $params, $page, $size], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $groupBy;
    return web_cache_remember('findings_grouped_page_v2_' . sha1($cacheSeed), 60, static function () use ($spec, $finalWhere, $params, $page, $size, $groupBy, $cacheSeed): array {
        $inner = _findings_explorer_sql($spec['base']) . ' ' . $finalWhere . ' ' . $spec['group'];
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
            'findings_grouped_' . sha1($cacheSeed)
        );
    });
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
    $cacheSeed = json_encode([$subWhere, $params, $includeSynthetic], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $subWhere;
    return web_cache_remember('findings_source_summary_v2_' . sha1($cacheSeed), 60, static function () use ($subWhere, $params): array {
        $sql = _findings_explorer_sql(sprintf(SQL_FINDINGS_EXPLORER_SOURCE_SUMMARY, $subWhere));
        return db_one($sql, $params) ?? [];
    });
}
