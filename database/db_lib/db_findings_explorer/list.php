<?php
// database/db_lib/db_findings_explorer/list.php — Findings explorer list paging functions.

function findings_explorer_paged(?string $severity, ?string $category, ?string $masvsArea, ?string $q, int $page, int $size): array
{
    [$where, $params] = _findings_explorer_where([
        'severity' => $severity,
        'category' => $category,
        'masvs_area' => $masvsArea,
        'detector' => null,
        'q' => $q,
    ]);

    $cacheSeed = json_encode([$where, $params, $page, $size], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where;
    return web_cache_remember('findings_explorer_page_v2_' . sha1($cacheSeed), 60, static function () use ($where, $params, $page, $size, $cacheSeed): array {
        return db_paged(
            _findings_explorer_sql(SQL_FINDINGS_EXPLORER_BASE),
            _findings_explorer_sql(SQL_FINDINGS_EXPLORER_COUNT),
            $where,
            SQL_FINDINGS_EXPLORER_ORDER,
            $params,
            $page,
            $size,
            60,
            'findings_explorer_' . sha1($cacheSeed)
        );
    });
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

    $cacheSeed = json_encode([$finalWhere, $params, $includeSynthetic, $page, $size], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $finalWhere;
    return web_cache_remember('findings_explorer_page_v3_' . sha1($cacheSeed), 60, static function () use ($finalWhere, $params, $page, $size, $includeSynthetic, $cacheSeed): array {
        return db_paged(
            _findings_explorer_sql(SQL_FINDINGS_EXPLORER_BASE),
            _findings_explorer_sql(SQL_FINDINGS_EXPLORER_COUNT),
            $finalWhere,
            SQL_FINDINGS_EXPLORER_ORDER,
            $params,
            $page,
            $size,
            60,
            'findings_explorer_v2_' . sha1($cacheSeed . '|' . ($includeSynthetic ? '1' : '0'))
        );
    });
}
