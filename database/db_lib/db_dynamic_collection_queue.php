<?php
// database/db_lib/db_dynamic_collection_queue.php — Dynamic collection queue queries.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';

/**
 * Fleet-level dynamic collection queue overview metrics.
 *
 * @return array<string,mixed>
 */
function dynamic_collection_queue_overview(): array
{
    return web_cache_remember('dynamic_collection_queue_overview_v1', 60, static function (): array {
        return db_one(SQL_DCQ_OVERVIEW) ?? [];
    });
}

/**
 * Lightweight probe for the collection queue view.
 *
 * @return array<int,array<string,mixed>>
 */
function dynamic_collection_queue_probe(int $limit = 5): array
{
    $limit = _positive_limit($limit, 25);
    return web_cache_remember("dynamic_collection_queue_probe_v1_{$limit}", 60, static function () use ($limit): array {
        $sql = SQL_DCQ_PROBE . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Top quota-gap apps ordered like the CLI capture plan (interactive gaps first).
 *
 * @return array<int,array<string,mixed>>
 */
function dynamic_collection_queue_recommended_captures(int $limit = 5): array
{
    $limit = _positive_limit($limit, 10);
    return web_cache_remember("dynamic_collection_queue_recommended_v1_{$limit}", 60, static function () use ($limit): array {
        $sql = SQL_DCQ_RECOMMENDED_CAPTURES . " LIMIT $limit";
        return db_all($sql);
    });
}

/**
 * Dynamic collection queue — paginated cohort app list.
 *
 * @return array{rows:array<int,array<string,mixed>>,total:int,page:int,size:int}
 */
function dynamic_collection_queue_paged(
    ?string $collectionStatus,
    ?string $cohortKey,
    ?string $q,
    int $page,
    int $size
): array {
    [$where, $params] = _dynamic_collection_queue_where([
        'collection_status' => $collectionStatus,
        'cohort_key' => $cohortKey,
        'q' => $q,
    ]);

    $cacheSeed = json_encode([$where, $params, $page, $size], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where;
    return web_cache_remember('dynamic_collection_queue_paged_v1_' . sha1($cacheSeed), 60, static function () use ($where, $params, $page, $size, $cacheSeed): array {
        return db_paged(
            SQL_DCQ_BASE,
            SQL_DCQ_COUNT,
            $where,
            SQL_DCQ_ORDER,
            $params,
            $page,
            $size,
            60,
            'dynamic_collection_queue_' . sha1($cacheSeed)
        );
    });
}
