<?php
// database/db_lib/db_component_exposure.php — exported provider / component explorer.
require_once __DIR__ . '/db_queries.php';
require_once __DIR__ . '/db_filters.php';
function component_exposure_paged(?string $exported, ?string $guard, ?string $q, int $page, int $size): array
{
    [$whereExtra, $params] = _component_exposure_where([
        'exported' => $exported,
        'guard' => $guard,
        'q' => $q,
    ]);

    $where = $whereExtra === '' ? '' : (' AND ' . preg_replace('/^WHERE\s+/i', '', $whereExtra));

    return db_paged(
        SQL_COMPONENT_EXPOSURE_BASE,
        SQL_COMPONENT_EXPOSURE_COUNT,
        $where,
        SQL_COMPONENT_EXPOSURE_ORDER,
        $params,
        $page,
        $size,
        60,
        'component_exposure_' . sha1(json_encode([$where, $params], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $where)
    );
}

/**
 * @return array<string,mixed>
 */
function component_exposure_overview(): array
{
    return db_one(SQL_COMPONENT_EXPOSURE_OVERVIEW) ?? [];
}
